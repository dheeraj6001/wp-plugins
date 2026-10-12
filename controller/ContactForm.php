<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The controller of the contact forms: the "Quick Enquiry" popup and the form inside a page.
 * It shows the forms, checks what a visitor posts, saves it, mails it and sends an SMS about it with MSG91.
 * The enquiries are saved in their own table and are in the admin under TrigrExam > Enquiries.
 */
class TrigrexamContactForm extends TrigrexamBaseClass {

    // Used until an address is saved on the settings page.
    const ENQUIRY_EMAIL = 'info@careerendeavour.com';
    const ENQUIRY_MAX_LENGTH = 200;
    // The phone column of the table holds 30 characters.
    const ENQUIRY_MAX_PHONE_LENGTH = 30;
    const ENQUIRY_MAX_MESSAGE_LENGTH = 5000;
    const ENQUIRY_SENT = 'Thank you for your message. It has been sent.';
    // The table of the enquiries, without the prefix of the site, and the option that says it is made.
    const TABLE = 'enquiries';
    const TABLE_OPTION = 'trigrexam_enquiries_table';
    const TABLE_VERSION = '1';
    // MSG91 sends an SMS from a template ("flow") that is made and approved in its panel.
    const SMS_URL = 'https://control.msg91.com/api/v5/flow';
    // A value in an SMS template may be 30 characters long.
    const SMS_MAX_VALUE_LENGTH = 30;
    // What the last SMS did: sent or not, the answer of MSG91 and the time. The settings page shows it.
    const SMS_STATUS_OPTION = 'trigrexam_sms_status';
    // The admin page of the enquiries and the action that deletes one.
    const PAGE = 'trigrexam-enquiries';
    const DELETE_ACTION = 'trigrexam_delete_enquiry';
    const PER_PAGE = 20;
    const ENQUIRY_FAILED = 'There was an error trying to send your message. Please try again later.';

    // The enquiry of this request, if one was posted and could not be sent: form, values, errors, message.
    private $enquiry = null;

    // Shortcode tag => method of this class.
    public function tags(){
        return array(
            'contact_form' => 'contact_form',
            'contact_us' => 'contact_us',
        );
    }

    // Connects the posted forms and the shortcodes to WordPress.
    public function register(){
        add_action('init', array($this, 'handle_enquiry'));
        add_action('wp_ajax_trigrexam_enquiry', array($this, 'ajax_enquiry'));
        add_action('wp_ajax_nopriv_trigrexam_enquiry', array($this, 'ajax_enquiry'));
        add_action('wp_enqueue_scripts', array($this, 'register_script'));
        add_action('admin_init', array($this, 'create_table'));
        // After the "TrigrExam" menu itself is added, which is at 11.
        add_action('admin_menu', array($this, 'add_admin_page'), 12);
        add_action('admin_post_'.self::DELETE_ACTION, array($this, 'delete_enquiry'));
        parent::register();
    }

    // The address the enquiry forms mail to. The settings page of the plugin shows and saves it.
    public static function enquiry_email(){
        $settings = get_option('trigrexam_settings');
        $email = is_array($settings) && isset($settings['enquiry_email']) ? $settings['enquiry_email'] : '';
        return is_email($email) ? $email : self::ENQUIRY_EMAIL;
    }

    // The mobile number for the SMS about an enquiry, or '' when none is saved. The settings page shows and saves it.
    public static function enquiry_mobile(){
        return self::setting('enquiry_mobile');
    }

    // The auth key of the MSG91 account and the id of its SMS template, or '' when they are not saved.
    public static function sms_authkey(){
        return self::setting('msg91_authkey');
    }

    public static function sms_template(){
        return self::setting('msg91_template');
    }

    // What the last SMS did: sent (bool), message, time. Or null when no SMS was tried yet.
    public static function sms_status(){
        $status = get_option(self::SMS_STATUS_OPTION);
        return is_array($status) && isset($status['sent'], $status['message'], $status['time']) ? $status : null;
    }

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
            'values' => $this->enquiry_values(array()),
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

    // Checks the posted enquiry and mails it. Returns what the form shows next: form, values, errors, message, sent.
    private function process_enquiry(){
        $form = isset($_POST['trigrexam_enquiry']) && $_POST['trigrexam_enquiry'] === 'page' ? 'page' : 'popup';
        $values = $this->enquiry_values(wp_unslash($_POST));
        $errors = array();
        foreach(array('name', 'phone', 'email') as $field){
            if($values[$field] === ''){
                $errors[$field] = 'The field is required.';
            }
        }
        if(!isset($errors['email']) && !is_email($values['email'])){
            $errors['email'] = 'The e-mail address entered is invalid.';
        }
        $limits = array('message' => self::ENQUIRY_MAX_MESSAGE_LENGTH, 'phone' => self::ENQUIRY_MAX_PHONE_LENGTH);
        foreach($values as $field => $value){
            $max = isset($limits[$field]) ? $limits[$field] : self::ENQUIRY_MAX_LENGTH;
            if(!isset($errors[$field]) && mb_strlen($value) > $max){
                $errors[$field] = 'The field is too long.';
            }
        }

        $result = array('form' => $form, 'values' => $values, 'errors' => $errors, 'sent' => false);
        if($errors){
            $result['message'] = 'One or more fields have an error. Please check and try again.';
            return $result;
        }

        // A saved enquiry is not lost when the email fails, so the visitor is only told of an error when both fail.
        $mailed = $this->mail_enquiry($form, $values);
        $saved = $this->save_enquiry($values);
        $this->sms_enquiry($values);
        $result['sent'] = $mailed || $saved;
        $result['message'] = $result['sent'] ? self::ENQUIRY_SENT : self::ENQUIRY_FAILED;
        return $result;
    }

    private static function table(){
        global $wpdb;
        return $wpdb->prefix.self::TABLE;
    }

    // Makes the table of the enquiries on a site that does not have it yet. A table that is there is left as it is.
    // It runs in the admin and before an enquiry is saved, so a site where it cannot be made does not try on every page.
    public function create_table(){
        if(get_option(self::TABLE_OPTION) === self::TABLE_VERSION){
            return;
        }
        global $wpdb;
        $made = $wpdb->query("CREATE TABLE IF NOT EXISTS ".self::table()." (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(30),
            subject VARCHAR(255),
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ".$wpdb->get_charset_collate());
        if($made !== false){
            update_option(self::TABLE_OPTION, self::TABLE_VERSION);
        }
    }

    // Saves an enquiry as a row of the table. Its status is 'new' until it is opened in the admin.
    private function save_enquiry($values){
        global $wpdb;
        $this->create_table();
        return (bool) $wpdb->insert(self::table(), array(
            'name' => $values['name'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'subject' => $values['subject'],
            'message' => $values['message'],
            'status' => 'new',
            // The time of the site, not the one of the database server.
            'created_at' => current_time('mysql'),
        ));
    }

    // Position 1 puts "Enquiries" in the "TrigrExam" menu just below "Dashboard", which has to stay first:
    // a click on the menu opens its first page.
    public function add_admin_page(){
        add_submenu_page('trigrexam', 'Enquiries', 'Enquiries', 'manage_options', self::PAGE, array($this, 'admin_page'), 1);
    }

    // The list of the enquiries, newest first, or one enquiry when its id is in the address.
    public function admin_page(){
        if(!current_user_can('manage_options')){
            return;
        }
        global $wpdb;
        $table = self::table();
        $page_url = add_query_arg('page', self::PAGE, admin_url('admin.php'));

        $id = isset($_GET['enquiry']) ? absint($_GET['enquiry']) : 0;
        $enquiry = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id)) : null;
        if($enquiry){
            // An enquiry that is opened is read.
            if($enquiry->status === 'new'){
                $wpdb->update($table, array('status' => 'read'), array('id' => $id));
                $enquiry->status = 'read';
            }
            echo $this->render('admin/enquiry-details', array(
                'enquiry' => $this->enquiry_row($enquiry, $page_url),
                'back_url' => $page_url,
            ));
            return;
        }

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $current = min($pages, max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1));
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
            self::PER_PAGE,
            ($current - 1) * self::PER_PAGE
        ));
        $enquiries = array();
        foreach((array) $rows as $row){
            $enquiries[] = $this->enquiry_row($row, $page_url);
        }
        echo $this->render('admin/enquiry-list', array(
            'enquiries' => $enquiries,
            'total' => $total,
            'unread' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'new'"),
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

        global $wpdb;
        $wpdb->delete(self::table(), array('id' => $id), array('%d'));

        wp_safe_redirect(add_query_arg(array('page' => self::PAGE, 'deleted' => 1), admin_url('admin.php')));
        exit;
    }

    // The enquiry fields out of the posted data, cleaned.
    private function enquiry_values($posted){
        $values = array();
        foreach(array('name', 'phone', 'email', 'subject', 'message') as $field){
            $value = isset($posted['enquiry_'.$field]) && is_string($posted['enquiry_'.$field]) ? $posted['enquiry_'.$field] : '';
            $values[$field] = $field === 'message' ? sanitize_textarea_field($value) : sanitize_text_field($value);
        }
        return $values;
    }

    // "Site name <wordpress@domain>". The domain is the one of the site; a site without a real domain,
    // like a local copy, gives an address mail servers refuse, so the domain of the enquiry address is used then.
    private function mail_from(){
        $host = preg_replace('/^www\./', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
        $address = 'wordpress@'.$host;
        if(!is_email($address)){
            $address = 'wordpress@'.substr(strrchr(self::enquiry_email(), '@'), 1);
        }
        return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES).' <'.$address.'>';
    }

    /**
     * The SMS about an enquiry, to the mobile number of the settings. Nothing is sent until the number, the
     * auth key and the template are all saved there. The template gets the fields of the enquiry as its
     * variables: ##name##, ##phone##, ##email## and ##subject##. An SMS that fails does not fail the enquiry.
     */
    private function sms_enquiry($values){
        $mobile = self::enquiry_mobile();
        $authkey = self::sms_authkey();
        $template = self::sms_template();
        if($mobile === '' || $authkey === '' || $template === ''){
            return;
        }
        // MSG91 wants the number with its country code and no "+". A number of 10 digits is one of India.
        $mobile = ltrim($mobile, '+');
        if(strlen($mobile) === 10){
            $mobile = '91'.$mobile;
        }
        $recipient = array('mobiles' => $mobile);
        foreach(array('name', 'phone', 'email', 'subject') as $field){
            $recipient[$field] = mb_substr($values[$field], 0, self::SMS_MAX_VALUE_LENGTH);
        }

        $response = wp_remote_post(self::SMS_URL, array(
            // The visitor waits for the answer of the form, so not for long.
            'timeout' => 5,
            'headers' => array('authkey' => $authkey, 'Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body' => wp_json_encode(array('template_id' => $template, 'short_url' => '0', 'recipients' => array($recipient))),
        ));
        if(is_wp_error($response)){
            $sent = false;
            $message = $response->get_error_message();
        }else{
            $answer = json_decode(wp_remote_retrieve_body($response), true);
            $sent = wp_remote_retrieve_response_code($response) === 200 && is_array($answer) && isset($answer['type']) && $answer['type'] === 'success';
            $message = is_array($answer) && isset($answer['message']) && is_string($answer['message']) ? $answer['message'] : 'HTTP '.wp_remote_retrieve_response_code($response);
        }
        update_option(self::SMS_STATUS_OPTION, array(
            'sent' => $sent,
            'message' => sanitize_text_field(mb_substr($message, 0, 200)),
            'time' => current_time('mysql'),
        ), false);
    }

    private function mail_enquiry($form, $values){
        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $body = "From: {$values['name']} <{$values['email']}>\n"
            . "Subject: {$values['subject']}\n"
            . "mobile: {$values['phone']}\n"
            . "Email id:{$values['email']}\n"
            . "Message Body:\n"
            . "{$values['message']}\n\n"
            . "-- \n"
            . "This e-mail was sent from the enquiry form on {$site} (".home_url().")";
        $headers = array('From: '.$this->mail_from(), 'Reply-To: '.$values['email']);
        return wp_mail(self::enquiry_email(), $form === 'page' ? 'Contact Form' : 'Quick Enquiry', $body, $headers);
    }
}
