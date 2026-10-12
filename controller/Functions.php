<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The controller of the plugin itself: the "TrigrExam" menu of the admin and its settings page,
 * and the custom CSS and JavaScript of the settings, which it puts in the pages of the site.
 * The enquiries and the lists of links are in their own controllers and add their pages to this menu.
 */
class TrigrexamFunctions extends TrigrexamBaseClass {

    // Connects the menu and the settings to WordPress.
    public function register(){
        add_action('admin_init', array($this, 'register_settings'));
        // The enquiries and the lists add their pages to the menu after it, at 12, above "Settings".
        add_action('admin_menu', array($this, 'add_admin_menu'), 11);
        add_filter('plugin_action_links_'.plugin_basename(trigrexam_plugin_dir.'trigrexam.php'), array($this, 'settings_link'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_style'));
        // Late, so the styles of the plugin come after the ones of the theme and are not overruled by them.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_style'), 100);
        // Late, so the custom CSS comes after the styles of the theme and the custom JavaScript after its scripts.
        add_action('wp_head', array($this, 'print_custom_css'), 100);
        add_action('wp_footer', array($this, 'print_custom_js'), 100);
        parent::register();
    }

    public function register_settings(){
        register_setting('trigrexam', 'trigrexam_settings', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    // The "TrigrExam" menu of the admin, with "Settings" in it.
    public function add_admin_menu(){
        // 59.5 puts it just above "Appearance", which is at 60.
        add_menu_page('TrigrExam', 'TrigrExam', 'manage_options', 'trigrexam', array($this, 'settings_page'), 'dashicons-welcome-learn-more', 59.5);
        add_submenu_page('trigrexam', 'TrigrExam Settings', 'Settings', 'manage_options', 'trigrexam');
    }

    // The styles of the plugin, in the admin on its own pages only, which all have "trigrexam" in their hook.
    public function enqueue_admin_style($hook){
        if(strpos($hook, 'trigrexam') !== false){
            $this->enqueue_style();
        }
    }

    // The styles of the plugin, on every page of the site, as a shortcode can be anywhere in it. The time the
    // file was changed is its version, so a browser does not keep an old copy.
    public function enqueue_style(){
        $file = 'assets/trigrexam.css';
        wp_enqueue_style('trigrexam', plugins_url($file, trigrexam_plugin_dir.'trigrexam.php'), array(), (string) @filemtime(trigrexam_plugin_dir.$file));
    }

    // The "Settings" link in the row of the plugin on the Plugins page.
    public function settings_link($links){
        array_unshift($links, '<a href="'.esc_url(admin_url('admin.php?page=trigrexam')).'">Settings</a>');
        return $links;
    }

    public function settings_page(){
        if(!current_user_can('manage_options')){
            return;
        }
        echo $this->render('admin/settings', array(
            'enquiry_email' => TrigrexamContactForm::enquiry_email(),
            'enquiry_mobile' => TrigrexamContactForm::enquiry_mobile(),
            'msg91_authkey' => TrigrexamContactForm::sms_authkey(),
            'msg91_template' => TrigrexamContactForm::sms_template(),
            'sms_status' => TrigrexamContactForm::sms_status(),
            'custom_css' => self::setting('custom_css'),
            'custom_js' => self::setting('custom_js'),
            'can_edit_code' => current_user_can('unfiltered_html'),
        ));
    }

    // The custom CSS of the settings, in the head of every page of the site. Not in the admin.
    public function print_custom_css(){
        $css = self::setting('custom_css');
        if($css !== ''){
            echo $this->render('frontend/custom-css', array('css' => $css));
        }
    }

    // The custom JavaScript of the settings, at the end of every page of the site. Not in the admin.
    public function print_custom_js(){
        $js = self::setting('custom_js');
        if($js !== ''){
            echo $this->render('frontend/custom-js', array('js' => $js));
        }
    }

    // Keeps the saved address or number when the new one is not a valid one. All but the email can be left empty.
    // The auth key and the template id of MSG91 are letters and digits; anything else in them is left out.
    public function sanitize_settings($input){
        $input = is_array($input) ? $input : array();
        $email = isset($input['enquiry_email']) && is_string($input['enquiry_email']) ? sanitize_email($input['enquiry_email']) : '';
        if(!is_email($email)){
            add_settings_error('trigrexam_settings', 'enquiry_email', 'The enquiry email is not a valid email address.');
            $email = TrigrexamContactForm::enquiry_email();
        }
        // Spaces, dashes and brackets are left out: "+91 98765-43210" is saved as "+919876543210".
        $mobile = isset($input['enquiry_mobile']) && is_string($input['enquiry_mobile']) ? preg_replace('/[\s().-]+/', '', $input['enquiry_mobile']) : '';
        if($mobile !== '' && !preg_match('/^\+?[0-9]{10,15}$/', $mobile)){
            add_settings_error('trigrexam_settings', 'enquiry_mobile', 'The enquiry mobile is not a valid mobile number: 10 to 15 digits, with or without a + in front.');
            $mobile = TrigrexamContactForm::enquiry_mobile();
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

    /**
     * The custom CSS or JavaScript as it is saved. The <style> or <script> tags around it are left out, as the
     * plugin puts its own around it, and so is a closing tag inside it, which would end it in the middle.
     * Only a user who may put any HTML in the site can change it; for another one the saved code is kept.
     */
    private function custom_code($input, $name, $tag){
        if(!current_user_can('unfiltered_html') || !isset($input[$name]) || !is_string($input[$name])){
            return self::setting($name);
        }
        return trim(preg_replace('#</?'.$tag.'\b[^>]*>?#i', '', $input[$name]));
    }

    private function letters_and_digits($input, $name){
        return isset($input[$name]) && is_string($input[$name]) ? substr(preg_replace('/[^A-Za-z0-9]/', '', $input[$name]), 0, 100) : '';
    }
}
