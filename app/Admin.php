<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The controller of the admin: the "TrigrExam" menu with its dashboard, enquiries, lists of links and settings
 * pages, and what those pages save and delete. Its views are in views/admin/. WordPress loads it in the admin
 * only, so a page of the site does not read it. What the site uses is in app/Frontend.php.
 */
class TrigrexamAdmin extends TrigrexamController {

    // The admin pages. The dashboard has the slug of the menu itself.
    const DASHBOARD_PAGE = 'trigrexam';
    const ENQUIRIES_PAGE = 'trigrexam-enquiries';
    const SETTINGS_PAGE = 'trigrexam-settings';
    // The action that deletes an enquiry, and how many enquiries are on a page.
    const DELETE_ACTION = 'trigrexam_delete_enquiry';
    const PER_PAGE = 20;

    // The controller of the site, which knows what is wrong with the shortcodes of the lists.
    private $frontend;

    // Hook of the admin page of a list => key of the list.
    private $list_pages = array();

    public function __construct(TrigrexamFrontend $frontend){
        $this->frontend = $frontend;
    }

    public function register(){
        // The menu and its pages.
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'hide_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        // After WordPress has printed jQuery and the sortable script, which is at 10.
        add_action('admin_print_footer_scripts', array($this, 'print_list_script'), 20);
        add_action('admin_notices', array($this, 'list_notices'));
        add_filter('plugin_action_links_'.plugin_basename(trigrexam_plugin_dir.'trigrexam.php'), array($this, 'settings_link'));

        // What the pages save and delete.
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array('TrigrexamModel', 'create_table'));
        add_action('admin_post_'.self::DELETE_ACTION, array($this, 'delete_enquiry'));
        // The SEO options of a page, on the screen that edits it.
        add_action('add_meta_boxes', array($this, 'add_seo_box'));
        add_action('save_post', array($this, 'save_seo'));
        foreach(array_keys(TrigrexamModel::lists()) as $key){
            add_action('admin_post_'.$this->list_save_action($key), function() use ($key){
                $this->save_list($key);
            });
        }
    }

    private static function page_url($page){
        return add_query_arg('page', $page, admin_url('admin.php'));
    }


    // ---------- The menu and the dashboard ----------

    // The "TrigrExam" menu of the admin and its pages, in the order of the dashboard. A click on the menu
    // opens its first page, which is the dashboard.
    public function add_admin_menu(){
        // 59.5 puts it just above "Appearance", which is at 60. It is a string, as an older WordPress makes
        // a number of it 59, which is the line above "Appearance", and the menu would take its place.
        add_menu_page('TrigrExam', 'TrigrExam', 'manage_options', self::DASHBOARD_PAGE, array($this, 'dashboard_page'), 'dashicons-welcome-learn-more', '59.5');
        add_submenu_page(self::DASHBOARD_PAGE, 'TrigrExam', 'Dashboard', 'manage_options', self::DASHBOARD_PAGE);
        add_submenu_page(self::DASHBOARD_PAGE, 'Enquiries', 'Enquiries', 'manage_options', self::ENQUIRIES_PAGE, array($this, 'enquiries_page'));
        foreach(TrigrexamModel::lists() as $key => $list){
            $hook = add_submenu_page(self::DASHBOARD_PAGE, $list['title'], $list['title'], 'manage_options', $this->list_page($key), function() use ($key){
                $this->list_admin_page($key);
            });
            $this->list_pages[$hook] = $key;
        }
        add_submenu_page(self::DASHBOARD_PAGE, 'TrigrExam Settings', 'Settings', 'manage_options', self::SETTINGS_PAGE, array($this, 'settings_page'));
    }

    // The admin menu shows "TrigrExam" only, without its pages under it: the cards of the dashboard open them.
    // The pages stay in the menu of WordPress, which is what lets them open and keeps "TrigrExam" marked on them,
    // so they are hidden with a style. It is on every page of the admin, where the styles of the plugin are not.
    public function hide_submenu(){
        echo '<style id="trigrexam-hide-submenu">#adminmenu #toplevel_page_trigrexam .wp-submenu{display:none}</style>'."\n";
    }

    // The dashboard: the pages of the plugin as cards, in sections. "General" has the enquiries and the
    // settings; then every category of the model has the lists of that category. A new list gets its card
    // without a change here, and one without a category of the model is under "Other".
    public function dashboard_page(){
        if(!current_user_can('manage_options')){
            return;
        }
        $sections = array('general' => array('title' => 'General', 'cards' => array(
            $this->card('Enquiries', 'Messages sent with the enquiry forms', self::ENQUIRIES_PAGE, 'dashicons-email-alt'),
            $this->card('Settings', 'Enquiry email, SMS, custom CSS and JavaScript', self::SETTINGS_PAGE, 'dashicons-admin-settings'),
        )));
        foreach(TrigrexamModel::categories() + array('' => 'Other') as $category => $title){
            $sections[$category] = array('title' => $title, 'cards' => array());
        }
        foreach(TrigrexamModel::lists() as $key => $list){
            $category = isset($list['category'], $sections[$list['category']]) ? $list['category'] : '';
            $sections[$category]['cards'][] = $this->card($list['title'], isset($list['subtitle']) ? $list['subtitle'] : '', $this->list_page($key), 'dashicons-list-view');
        }
        // A category without a list is left out.
        foreach($sections as $category => $section){
            if(!$section['cards']){
                unset($sections[$category]);
            }
        }
        echo $this->render('admin/dashboard', array('sections' => $sections));
    }

    // A card of the dashboard: the title of a page, a line about it, the address of the page and its dashicon.
    private function card($title, $subtitle, $page, $icon){
        return array('title' => $title, 'subtitle' => $subtitle, 'url' => self::page_url($page), 'icon' => $icon);
    }

    // On the pages of the plugin only, which all have "trigrexam" in their hook: its styles, and on the page
    // of a list the script that drags its items.
    public function enqueue_admin_assets($hook){
        if(strpos($hook, 'trigrexam') !== false){
            $this->enqueue_style();
        }
        if(isset($this->list_pages[$hook])){
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    // The "Settings" link in the row of the plugin on the Plugins page.
    public function settings_link($links){
        array_unshift($links, '<a href="'.esc_url(self::page_url(self::SETTINGS_PAGE)).'">Settings</a>');
        return $links;
    }


    // ---------- The settings ----------

    public function register_settings(){
        register_setting('trigrexam', 'trigrexam_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    public function settings_page(){
        if(!current_user_can('manage_options')){
            return;
        }
        echo $this->render('admin/settings', array(
            'enquiry_email' => TrigrexamModel::enquiry_email(),
            'enquiry_mobile' => TrigrexamModel::enquiry_mobile(),
            'msg91_authkey' => TrigrexamModel::sms_authkey(),
            'msg91_template' => TrigrexamModel::sms_template(),
            'sms_status' => TrigrexamModel::sms_status(),
            'custom_css' => TrigrexamModel::setting('custom_css'),
            'custom_js' => TrigrexamModel::setting('custom_js'),
            'can_edit_code' => current_user_can('unfiltered_html'),
        ));
    }

    // Keeps the saved address or number when the new one is not a valid one. All but the email can be left empty.
    // The auth key and the template id of MSG91 are letters and digits; anything else in them is left out.
    public function sanitize_settings($input){
        $input = is_array($input) ? $input : array();
        $email = isset($input['enquiry_email']) && is_string($input['enquiry_email']) ? sanitize_email($input['enquiry_email']) : '';
        if(!is_email($email)){
            $this->settings_error('enquiry_email', 'The enquiry email is not a valid email address.');
            $email = TrigrexamModel::enquiry_email();
        }
        // Spaces, dashes and brackets are left out: "+91 98765-43210" is saved as "+919876543210".
        $mobile = isset($input['enquiry_mobile']) && is_string($input['enquiry_mobile']) ? preg_replace('/[\s().-]+/', '', $input['enquiry_mobile']) : '';
        if($mobile !== '' && !preg_match('/^\+?[0-9]{10,15}$/', $mobile)){
            $this->settings_error('enquiry_mobile', 'The enquiry mobile is not a valid mobile number: 10 to 15 digits, with or without a + in front.');
            $mobile = TrigrexamModel::enquiry_mobile();
        }
        return array(
            'enquiry_email' => $email,
            'enquiry_mobile' => $mobile,
            'msg91_authkey' => $this->letters_and_digits($input, 'msg91_authkey'),
            'msg91_template' => $this->letters_and_digits($input, 'msg91_template'),
            'custom_css' => $this->custom_code($input, 'custom_css', 'style'),
            'custom_js' => $this->custom_code($input, 'custom_js', 'script'),
        );
    }

    // An error for the settings page, once: the first time the settings are saved on a site WordPress
    // cleans them twice, and the error would be there twice.
    private function settings_error($code, $message){
        foreach(get_settings_errors('trigrexam_settings') as $error){
            if($error['code'] === $code){
                return;
            }
        }
        add_settings_error('trigrexam_settings', $code, $message);
    }

    /**
     * The custom CSS or JavaScript as it is saved. The <style> or <script> tags around it are left out, as the
     * plugin puts its own around it, and so is a closing tag inside it, which would end it in the middle.
     * Only a user who may put any HTML in the site can change it; for another one the saved code is kept.
     */
    private function custom_code($input, $name, $tag){
        if(!current_user_can('unfiltered_html') || !isset($input[$name]) || !is_string($input[$name])){
            return TrigrexamModel::setting($name);
        }
        return trim(preg_replace('#</?'.$tag.'\b[^>]*>?#i', '', $input[$name]));
    }

    private function letters_and_digits($input, $name){
        return isset($input[$name]) && is_string($input[$name]) ? substr(preg_replace('/[^A-Za-z0-9]/', '', $input[$name]), 0, 100) : '';
    }


    // ---------- The lists of links ----------

    private function list_page($key){
        return 'trigrexam-'.$key;
    }

    private function list_save_action($key){
        return 'trigrexam_save_'.$key;
    }

    // The problems with the shortcodes of the lists, on the pages of the plugin.
    public function list_notices(){
        $screen = get_current_screen();
        if(!$screen || strpos($screen->id, 'trigrexam') === false || !current_user_can('manage_options')){
            return;
        }
        foreach($this->frontend->list_problems() as $problem){
            echo '<div class="notice notice-warning"><p><strong>TrigrExam:</strong> '.esc_html($problem).'</p></div>';
        }
    }

    public function print_list_script(){
        $screen = get_current_screen();
        if($screen && isset($this->list_pages[$screen->id])){
            echo $this->render('admin/menu/menu-script');
        }
    }

    private function list_admin_page($key){
        if(!current_user_can('manage_options')){
            return;
        }
        $lists = TrigrexamModel::lists();
        $list = $lists[$key];
        // Before the rows, as it loads all pages at once and the rows of pages then need no query of their own.
        $pages = get_pages(array('sort_column' => 'post_title'));
        $rows = '';
        foreach(TrigrexamModel::list_items($key) as $number => $item){
            $rows .= $this->list_item_row('item-'.$number, $item);
        }
        echo $this->render('admin/menu/menu-list', array(
            'title' => $list['title'],
            'shortcode' => $list['shortcode'],
            'rows' => $rows,
            // The row the script copies for a new item; it puts a real key in the place of __KEY__.
            'template' => $this->list_item_row('__KEY__', array('title' => '', 'page_id' => 0, 'url' => '')),
            'pages' => $pages,
            'action' => admin_url('admin-post.php'),
            'save_action' => $this->list_save_action($key),
            'saved' => isset($_GET['updated']),
        ));
    }

    private function list_item_row($row_key, $item){
        $page = $item['page_id'] ? get_post($item['page_id']) : null;
        return $this->render('admin/menu/menu-item', array(
            'key' => $row_key,
            'item' => $item,
            'page_title' => $page ? $page->post_title : '',
            'page_url' => $page ? get_permalink($page) : '',
            'missing' => $item['page_id'] && TrigrexamModel::list_item_url($item) === null,
        ));
    }

    // The posted list.
    private function save_list($key){
        if(!current_user_can('manage_options')){
            wp_die('You are not allowed to change this list.');
        }
        check_admin_referer($this->list_save_action($key));

        TrigrexamModel::save_list($key, isset($_POST['items']) && is_array($_POST['items']) ? wp_unslash($_POST['items']) : array());

        wp_safe_redirect(add_query_arg('updated', 1, self::page_url($this->list_page($key))));
        exit;
    }


    // ---------- The SEO options of a page ----------

    const SEO_NONCE = 'trigrexam_seo_nonce';
    const SEO_ACTION = 'trigrexam_save_seo';

    // The "SEO Options" box on the screen that edits a page or a post: its description and its keywords.
    // The Career Endeavour theme has the same box, so with that theme this one is left out.
    public function add_seo_box(){
        if(TrigrexamModel::theme_has_seo()){
            return;
        }
        foreach(TrigrexamModel::seo_post_types() as $post_type){
            add_meta_box('trigrexam_seo', 'SEO Options', array($this, 'seo_box'), $post_type, 'normal', 'high');
        }
    }

    public function seo_box($post){
        echo $this->render('admin/seo-box', TrigrexamModel::seo($post->ID) + array(
            'nonce' => wp_nonce_field(self::SEO_ACTION, self::SEO_NONCE, false, false),
        ));
    }

    // Saves the box when the page is saved. A save that does not come from the screen with the box, like an
    // autosave or a quick edit, has no nonce of it and changes nothing.
    public function save_seo($post_id){
        if(TrigrexamModel::theme_has_seo() || !isset($_POST[self::SEO_NONCE]) || !is_string($_POST[self::SEO_NONCE]) || !wp_verify_nonce($_POST[self::SEO_NONCE], self::SEO_ACTION)){
            return;
        }
        if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)){
            return;
        }
        if(!in_array(get_post_type($post_id), TrigrexamModel::seo_post_types(), true)){
            return;
        }
        $posted = isset($_POST['trigrexam_seo']) && is_array($_POST['trigrexam_seo']) ? wp_unslash($_POST['trigrexam_seo']) : array();
        TrigrexamModel::save_seo(
            $post_id,
            isset($posted['description']) && is_string($posted['description']) ? $posted['description'] : '',
            isset($posted['keywords']) && is_string($posted['keywords']) ? $posted['keywords'] : ''
        );
    }


    // ---------- The enquiries ----------

    // The list of the enquiries, newest first, or one enquiry when its id is in the address.
    public function enquiries_page(){
        if(!current_user_can('manage_options')){
            return;
        }
        $page_url = self::page_url(self::ENQUIRIES_PAGE);

        $id = isset($_GET['enquiry']) ? absint($_GET['enquiry']) : 0;
        $enquiry = $id ? TrigrexamModel::enquiry($id) : null;
        if($enquiry){
            if($enquiry->status === 'new'){
                TrigrexamModel::read_enquiry($id);
                $enquiry->status = 'read';
            }
            echo $this->render('admin/enquiry-details', array(
                'enquiry' => $this->enquiry_row($enquiry, $page_url),
                'back_url' => $page_url,
            ));
            return;
        }

        $total = TrigrexamModel::count_enquiries();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $current = min($pages, max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1));
        $enquiries = array();
        foreach(TrigrexamModel::enquiries(self::PER_PAGE, ($current - 1) * self::PER_PAGE) as $row){
            $enquiries[] = $this->enquiry_row($row, $page_url);
        }
        echo $this->render('admin/enquiry-list', array(
            'enquiries' => $enquiries,
            'total' => $total,
            'unread' => TrigrexamModel::count_enquiries(true),
            'pagination' => paginate_links(array(
                'base' => add_query_arg('paged', '%#%', $page_url),
                'format' => '',
                'current' => $current,
                'total' => $pages,
            )),
            'deleted' => isset($_GET['deleted']),
        ));
    }

    // A row of the table as the views show it: its fields, its date and the addresses that open and delete it.
    private function enquiry_row($row, $page_url){
        return array(
            'id' => (int) $row->id,
            'name' => $row->name,
            'email' => $row->email,
            'phone' => (string) $row->phone,
            'subject' => (string) $row->subject,
            'message' => $row->message,
            'new' => $row->status === 'new',
            'date' => mysql2date(get_option('date_format').' '.get_option('time_format'), $row->created_at),
            'view_url' => add_query_arg('enquiry', (int) $row->id, $page_url),
            'delete_url' => wp_nonce_url(
                add_query_arg(array('action' => self::DELETE_ACTION, 'id' => (int) $row->id), admin_url('admin-post.php')),
                self::DELETE_ACTION.'_'.(int) $row->id
            ),
        );
    }

    public function delete_enquiry(){
        if(!current_user_can('manage_options')){
            wp_die('You are not allowed to delete enquiries.');
        }
        $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        check_admin_referer(self::DELETE_ACTION.'_'.$id);

        TrigrexamModel::delete_enquiry($id);

        wp_safe_redirect(add_query_arg('deleted', 1, self::page_url(self::ENQUIRIES_PAGE)));
        exit;
    }
}
