<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The controller of the site: what a visitor sees and posts. The shortcodes of the lists of links and of the
 * two enquiry forms, the styles and the script, the custom CSS and JavaScript of the settings, and the posted
 * enquiry forms. Its views are in views/frontend/. What the admin uses is in app/Admin.php.
 */
class TrigrexamFrontend extends TrigrexamController {

    const ENQUIRY_SENT = 'Thank you for your message. It has been sent.';
    const ENQUIRY_FAILED = 'There was an error trying to send your message. Please try again later.';
    const ENQUIRY_LIMITED = 'Too many enquiries have been sent in the last few minutes. Please try again later.';
    // The view of several lists that are shown together.
    const LISTS_VIEW = 'lists-view';

    // Shortcode tag of a list => the function the plugin gave WordPress for it, to see later if it still has it.
    private $list_shortcodes = array();

    // What is wrong with the shortcodes of the lists, like two lists with the same one. The admin shows it.
    private $list_problems = array();

    // The enquiry of this request, if one was posted and could not be sent: form, values, errors, message.
    private $enquiry = null;

    public function register(){
        // Late, so the styles of the plugin come after the ones of the theme and are not overruled by them.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_style'), 100);
        add_action('wp_enqueue_scripts', array($this, 'register_script'));
        // Late, so the custom CSS comes after the styles of the theme and the custom JavaScript after its scripts.
        add_action('wp_head', array($this, 'print_custom_css'), 100);
        add_action('wp_footer', array($this, 'print_custom_js'), 100);
        // Early, so the description and the keywords of the page are at the top of its head.
        add_action('wp_head', array($this, 'print_seo'), 1);

        // The posted enquiry forms. The script posts them to admin-ajax.php, which is how WordPress takes
        // such a post of a visitor too.
        add_action('init', array($this, 'handle_enquiry'));
        add_action('wp_ajax_trigrexam_enquiry', array($this, 'ajax_enquiry'));
        add_action('wp_ajax_nopriv_trigrexam_enquiry', array($this, 'ajax_enquiry'));

        // The shortcodes: one of each list, and the two enquiry forms.
        foreach(TrigrexamModel::lists() as $key => $list){
            $this->register_list_shortcode($key, $list);
        }
        add_shortcode('contact_form', array($this, 'contact_form'));
        add_shortcode('contact_us', array($this, 'contact_us'));
    }


    // ---------- The SEO options of a page ----------

    /**
     * The description and the keywords of the page, as meta tags in its head: what is saved in its "SEO Options"
     * box. A page without a description gets the one the Career Endeavour theme gave it: the title of a post,
     * or the name and the tagline of the site. Nothing is printed when that theme or an SEO plugin prints them.
     */
    public function print_seo(){
        if(TrigrexamModel::theme_has_seo() || TrigrexamModel::seo_plugin_active()){
            return;
        }
        $seo = is_singular() ? TrigrexamModel::seo(get_queried_object_id()) : array('description' => '', 'keywords' => '');
        if($seo['description'] === ''){
            $seo['description'] = is_single() ? single_post_title('', false) : get_bloginfo('name').' - '.get_bloginfo('description');
        }
        echo $this->render('frontend/seo-meta', $seo);
    }


    // ---------- The custom CSS and JavaScript of the settings ----------

    // In the head of every page of the site.
    public function print_custom_css(){
        $css = TrigrexamModel::setting('custom_css');
        if($css !== ''){
            echo $this->render('frontend/custom-css', array('css' => $css));
        }
    }

    // At the end of every page of the site.
    public function print_custom_js(){
        $js = TrigrexamModel::setting('custom_js');
        if($js !== ''){
            echo $this->render('frontend/custom-js', array('js' => $js));
        }
    }


    // ---------- The shortcodes of the lists of links ----------

    // WordPress has one function for a shortcode: a second one with the same tag takes the place of the first
    // without a word. So of two lists with the same shortcode only the first gets it, and the admin says so,
    // as it does when the shortcode was already the one of something else.
    private function register_list_shortcode($key, $list){
        $tag = $list['shortcode'];
        foreach(TrigrexamModel::lists() as $other_key => $other){
            if($other_key === $key){
                break;
            }
            if($other['shortcode'] === $tag){
                $this->list_problems[] = sprintf('The lists "%s" and "%s" have the same shortcode [%s]. It shows "%s" only; give "%s" a shortcode of its own.', $other['title'], $list['title'], $tag, $other['title'], $list['title']);
                return;
            }
        }
        if(shortcode_exists($tag)){
            $this->list_problems[] = sprintf('The shortcode [%s] of the list "%s" was already used by another plugin or by another part of this one. It now shows the list.', $tag, $list['title']);
        }
        $this->list_shortcodes[$tag] = function($atts) use ($key){
            return $this->list_shortcode($key, $atts);
        };
        add_shortcode($tag, $this->list_shortcodes[$tag]);
    }

    // The problems with the shortcodes of the lists, for the admin to show on the pages of the plugin.
    public function list_problems(){
        global $shortcode_tags;
        $problems = $this->list_problems;
        foreach($this->list_shortcodes as $tag => $function){
            // Something that came after the plugin, like the theme, has put its own function in its place.
            if(!isset($shortcode_tags[$tag]) || $shortcode_tags[$tag] !== $function){
                $problems[] = sprintf('The shortcode [%s] is taken over by the theme or by another plugin, so it does not show the list of this plugin.', $tag);
            }
        }
        return $problems;
    }

    /**
     * [announcement] is one list in its own view. With the shortcodes of other lists after it, like
     * [iitjam_announcement neet_announcement], the lists are together in views/frontend/menu/lists-view.php,
     * or in another view of that folder: [iitjam_announcement neet_announcement view="card"].
     * The views are the components the site shows a list as: "marquee" (one scrolling line), "lists" (a heading
     * with the links under it), "list", "table", "card" and "toggle". A new file in that folder is a new one.
     * Every view gets $lists, and $links: the links of all the lists in one.
     */
    private function list_shortcode($key, $atts){
        $atts = is_array($atts) ? $atts : array();
        $all = TrigrexamModel::lists();
        $keys = array($key);
        foreach($atts as $name => $value){
            // The other lists are the words without a name. A comma between them is left out.
            $other = is_int($name) ? TrigrexamModel::list_key(trim($value, ', ')) : null;
            if($other !== null && !in_array($other, $keys, true)){
                $keys[] = $other;
            }
        }

        $lists = array();
        $links = array();
        foreach($keys as $list_key){
            $lists[$list_key] = array(
                'title' => $all[$list_key]['title'],
                'links' => TrigrexamModel::list_links($list_key),
            );
            $links = array_merge($links, $lists[$list_key]['links']);
        }
        // A view gets both, so any view shows one list as well as several: $links for a view of links
        // like marquee-view, $lists for a view of lists like lists-view.
        $data = array('lists' => $lists, 'links' => $links);
        // One list has the view of lists() and several lists the view of lists, until the shortcode names
        // another one: [announcement view="table"].
        $default = count($keys) === 1 ? $all[$key]['view'] : self::LISTS_VIEW;
        return $this->render($this->list_view(isset($atts['view']) ? $atts['view'] : $default, $default), $data);
    }

    // The view of the site with this name: a file of views/frontend/menu/, named in full, like "table-view",
    // or without its end, like "table". A name that is not one of its files, which can come from the content
    // of a page, gives the default view.
    private function list_view($name, $default){
        // A name with a folder in front of it, like "menu/table-view", is the file of that name.
        $name = basename((string) $name);
        if(preg_match('/^[a-z0-9-]+$/', $name)){
            foreach(array($name, $name.'-view') as $file){
                if(is_file(trigrexam_plugin_dir.'views/frontend/menu/'.$file.'.php')){
                    return 'frontend/menu/'.$file;
                }
            }
        }
        return 'frontend/menu/'.$default;
    }


    // ---------- The enquiry forms ----------

    // The "Quick Enquiry" button with its popup and form.
    public function contact_form(){
        $this->enqueue_script();
        $data = $this->enquiry_data('popup');
        return $this->render('frontend/contact-form', array(
            'form' => $this->render('frontend/contact-fields', $data + array('heading' => 'Quick Enquiry', 'anchor' => '')),
            'open' => $data['message'] !== '',
        ));
    }

    // The same form inside the content of a page, without the button and the popup.
    public function contact_us(){
        $this->enqueue_script();
        return $this->render('frontend/contact-fields', $this->enquiry_data('page') + array('heading' => '', 'anchor' => 'trigrexam-contact'));
    }

    // What the 'popup' or 'page' form shows: the posted values and errors if it is the form that was posted, else an empty form.
    private function enquiry_data($form){
        if($this->enquiry !== null && $this->enquiry['form'] === $form){
            return $this->enquiry;
        }
        $sent = isset($_GET['enquiry']) && $_GET['enquiry'] === $form;
        return array(
            'form' => $form,
            'values' => TrigrexamModel::enquiry_values(array()),
            'errors' => array(),
            'message' => $sent ? self::ENQUIRY_SENT : '',
            'sent' => $sent,
        );
    }

    // The script of the forms, assets/trigrexam.js. It is known to WordPress on every page of the site,
    // with the address the forms are posted to, and loaded in the footer of a page that shows a form.
    public function register_script(){
        $file = 'assets/trigrexam.js';
        wp_register_script('trigrexam', plugins_url($file, trigrexam_plugin_dir.'trigrexam.php'), array(), (string) @filemtime(trigrexam_plugin_dir.$file), true);
        wp_localize_script('trigrexam', 'trigrexamEnquiry', array(
            'url' => admin_url('admin-ajax.php'),
            'failure' => self::ENQUIRY_FAILED,
        ));
    }

    // A form is on the page, so its footer gets the script. A form that is shown before WordPress
    // knows the script, like one in the head of a theme, makes it known first.
    private function enqueue_script(){
        if(!wp_script_is('trigrexam', 'registered')){
            $this->register_script();
        }
        wp_enqueue_script('trigrexam');
    }

    // A form sent by the script: the answer is JSON and the page is not reloaded.
    public function ajax_enquiry(){
        // Visitors do not see this field; only bots fill it in. They get the same answer as a sent enquiry.
        if(!empty($_POST['enquiry_website'])){
            wp_send_json(array('sent' => true, 'message' => self::ENQUIRY_SENT, 'errors' => new stdClass()));
        }
        $result = $this->process_enquiry();
        wp_send_json(array(
            'sent' => $result['sent'],
            'message' => $result['message'],
            'errors' => (object) $result['errors'],
        ));
    }

    // A form posted without JavaScript. On success the visitor is redirected, so a reload does not send it twice.
    public function handle_enquiry(){
        if(!isset($_POST['trigrexam_enquiry']) || wp_doing_ajax()){
            return;
        }
        if(!empty($_POST['enquiry_website'])){
            return;
        }

        $result = $this->process_enquiry();
        if($result['sent']){
            wp_safe_redirect(add_query_arg('enquiry', $result['form']).($result['form'] === 'page' ? '#trigrexam-contact' : ''));
            exit;
        }
        $this->enquiry = $result;
    }

    // Checks the posted enquiry and sends it. Returns what the form shows next: form, values, errors, message, sent.
    private function process_enquiry(){
        $form = isset($_POST['trigrexam_enquiry']) && $_POST['trigrexam_enquiry'] === 'page' ? 'page' : 'popup';
        $values = TrigrexamModel::enquiry_values(wp_unslash($_POST));
        $errors = TrigrexamModel::enquiry_errors($values);

        $result = array('form' => $form, 'values' => $values, 'errors' => $errors, 'sent' => false);
        if($errors){
            $result['message'] = 'One or more fields have an error. Please check and try again.';
            return $result;
        }
        // The site takes so many enquiries in a quarter of an hour and no more: one over it is not mailed, saved
        // or sent as an SMS.
        if(TrigrexamModel::enquiry_limit_reached()){
            $result['message'] = self::ENQUIRY_LIMITED;
            return $result;
        }
        $result['sent'] = TrigrexamModel::send_enquiry($form, $values);
        $result['message'] = $result['sent'] ? self::ENQUIRY_SENT : self::ENQUIRY_FAILED;
        return $result;
    }
}
