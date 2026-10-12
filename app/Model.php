<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The model of the plugin: its data. The settings, the lists of links and the enquiries are read and saved here,
 * and an enquiry is mailed and sent as an SMS here. It has no HTML and no hooks: app/Controller.php connects it
 * to WordPress and the views of views/ show what it gives.
 */
class TrigrexamModel {

    // Used until an address is saved on the settings page.
    const ENQUIRY_EMAIL = 'info@careerendeavour.com';
    const ENQUIRY_MAX_LENGTH = 200;
    // The phone column of the table holds 30 characters.
    const ENQUIRY_MAX_PHONE_LENGTH = 30;
    const ENQUIRY_MAX_MESSAGE_LENGTH = 5000;
    // The rate limit: the site takes this many enquiries in this many minutes, from all visitors together. It
    // keeps a bot that fills in the form again and again from flooding the mailbox and the SMS account.
    const ENQUIRY_LIMIT = 100;
    const ENQUIRY_LIMIT_MINUTES = 15;
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

    // The lists and their pages are loaded, so a page that shows many lists does not query for each one.
    private static $lists_loaded = false;


    // ---------- The settings ----------

    // One of the settings of the plugin, which the settings page saves, or '' when it is not saved.
    public static function setting($name){
        $settings = get_option('trigrexam_settings');
        return is_array($settings) && isset($settings[$name]) && is_string($settings[$name]) ? $settings[$name] : '';
    }

    // The address the enquiry forms mail to.
    public static function enquiry_email(){
        $email = self::setting('enquiry_email');
        return is_email($email) ? $email : self::ENQUIRY_EMAIL;
    }

    // The mobile number for the SMS about an enquiry, or '' when none is saved.
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


    // ---------- The lists of links ----------

    /**
     * The lists of links, like the announcements. Each one is built in the admin under TrigrExam, the way a menu
     * is built under Appearance > Menus: pages or custom links are added, given a title, dragged into order
     * and saved. Its shortcode shows it with its view, a file of views/frontend/menu/: "marquee-view" is one
     * scrolling line of links, "lists-view" a heading with the links under it, and "list-view", "table-view",
     * "card-view" and "toggle-view" are what their names say. A shortcode can name another view than the one
     * here: [announcement view="table"]. A new list is one line here.
     *
     * The dashboard shows each list as a card under its category, a key of categories(), with its subtitle
     * under its title: one line that says where the list is used on the site.
     *
     * @return array Key of the list => title (of its admin page), subtitle, category, shortcode (tag),
     *               view (file name, without ".php").
     */
    public static function lists(){
        $data = array();

        // The header.
        $data['announcements'] = array('title' => 'Announcements', 'subtitle' => 'Scrolling line in the header of every page', 'category' => 'header', 'shortcode' => 'announcement', 'view' => 'marquee-view');

        // The home page: its blocks of links, each under its heading.
        $data['csir_net_gate_coaching'] = array('title' => 'CSIR-NET & GATE Coaching', 'subtitle' => 'Coaching block on the home page', 'category' => 'home', 'shortcode' => 'csir_net_gate_coaching', 'view' => 'lists-view');
        $data['iit_jam_cuet_pg_coaching'] = array('title' => 'IIT-JAM & CUET-PG Coaching', 'subtitle' => 'Coaching block on the home page', 'category' => 'home', 'shortcode' => 'iit_jam_cuet_pg_coaching', 'view' => 'lists-view');
        $data['pgt_online_classes'] = array('title' => 'PGT Online Classes', 'subtitle' => 'Online classes block on the home page', 'category' => 'home', 'shortcode' => 'pgt_online_classes', 'view' => 'lists-view');
        $data['csir_net_gate_study_material'] = array('title' => 'CSIR-NET/GATE Study Material', 'subtitle' => 'Study material block on the home page', 'category' => 'home', 'shortcode' => 'csir_net_gate_study_material', 'view' => 'lists-view');
        $data['csir_net_gate_test_series'] = array('title' => 'CSIR-NET/GATE Test Series', 'subtitle' => 'Test series block on the home page', 'category' => 'home', 'shortcode' => 'csir_net_gate_test_series', 'view' => 'lists-view');
        $data['iit_jam_study_material'] = array('title' => 'IIT-JAM Study Material', 'subtitle' => 'Study material block on the home page', 'category' => 'home', 'shortcode' => 'iit_jam_study_material', 'view' => 'lists-view');
        $data['iit_jam_test_series'] = array('title' => 'IIT-JAM Test Series', 'subtitle' => 'Test series block on the home page', 'category' => 'home', 'shortcode' => 'iit_jam_test_series', 'view' => 'lists-view');
        $data['syllabus'] = array('title' => 'Syllabus', 'subtitle' => 'Syllabus block on the home page', 'category' => 'home', 'shortcode' => 'syllabus', 'view' => 'lists-view');
        $data['question_paper'] = array('title' => 'Question Paper', 'subtitle' => 'Question paper block on the home page', 'category' => 'home', 'shortcode' => 'question_paper', 'view' => 'lists-view');
        $data['reference_book'] = array('title' => 'Reference Book', 'subtitle' => 'Reference book block on the home page', 'category' => 'home', 'shortcode' => 'reference_book', 'view' => 'lists-view');
        $data['quick_links'] = array('title' => 'Quick Links', 'subtitle' => 'Quick links block on the home page', 'category' => 'home', 'shortcode' => 'quick_links', 'view' => 'lists-view');
        $data['results'] = array('title' => 'Results', 'subtitle' => 'Results block on the home page', 'category' => 'home', 'shortcode' => 'results', 'view' => 'lists-view');

        // The footer: its three columns of links.
        $data['about'] = array('title' => 'About us', 'subtitle' => 'About us column of the footer', 'category' => 'footer', 'shortcode' => 'about', 'view' => 'lists-view');
        $data['download'] = array('title' => 'Downloads', 'subtitle' => 'Downloads column of the footer', 'category' => 'footer', 'shortcode' => 'download', 'view' => 'lists-view');
        $data['others'] = array('title' => 'Others', 'subtitle' => 'Others column of the footer', 'category' => 'footer', 'shortcode' => 'others', 'view' => 'lists-view');

        // Other pages.
        $data['iitjam_announcements'] = array('title' => 'IIT-JAM Announcements', 'subtitle' => 'Announcements on the IIT-JAM pages', 'category' => 'pages', 'shortcode' => 'iitjam_announcement', 'view' => 'marquee-view');
        $data['neet_announcements'] = array('title' => 'NEET Announcements', 'subtitle' => 'Announcements on the NEET pages', 'category' => 'pages', 'shortcode' => 'neet_announcement', 'view' => 'marquee-view');

        return $data;
    }

    /**
     * The categories of the lists, in the order the dashboard shows them: the part of the site a list is used in.
     *
     * @return array Key of the category => its title.
     */
    public static function categories(){
        return array(
            'header' => 'Header',
            'home' => 'Home Page',
            'footer' => 'Footer',
            'pages' => 'Pages',
            'settings' => 'Settings',
        );
    }

    // The key of the list with this shortcode or this key, or null.
    public static function list_key($name){
        foreach(self::lists() as $key => $list){
            if($name === $list['shortcode'] || $name === $key){
                return $key;
            }
        }
        return null;
    }

    public static function list_option($key){
        return 'trigrexam_'.$key;
    }

    // The saved items of a list: title, page_id, url. Anything else in the option, like a row changed
    // by hand in the database, is left out, so every item has the three fields the rest of the code reads.
    public static function list_items($key){
        $saved = get_option(self::list_option($key));
        $items = array();
        foreach(is_array($saved) ? $saved : array() as $item){
            if(is_array($item) && isset($item['title']) && is_scalar($item['title'])){
                $items[] = array(
                    'title' => (string) $item['title'],
                    'page_id' => isset($item['page_id']) ? absint($item['page_id']) : 0,
                    'url' => isset($item['url']) && is_string($item['url']) ? $item['url'] : '',
                );
            }
        }
        return $items;
    }

    // Saves the posted items of a list, cleaned, in the order they are posted, which is the order on the screen.
    public static function save_list($key, $posted){
        $items = array();
        foreach($posted as $row){
            $item = self::clean_list_item($row);
            if($item !== null){
                $items[] = $item;
            }
        }
        update_option(self::list_option($key), $items, false);
    }

    // One posted item, cleaned: title, page_id, url. An item of a page has no url, a custom link has no page_id.
    // Returns null for an item without a title.
    private static function clean_list_item($row){
        if(!is_array($row)){
            return null;
        }
        $title = isset($row['title']) && is_string($row['title']) ? sanitize_text_field($row['title']) : '';
        $page_id = isset($row['page_id']) ? absint($row['page_id']) : 0;
        $url = isset($row['url']) && is_string($row['url']) ? esc_url_raw(trim($row['url'])) : '';
        if($title === '' && $page_id){
            $title = get_the_title($page_id);
        }
        if($title === ''){
            return null;
        }
        return array('title' => $title, 'page_id' => $page_id, 'url' => $page_id ? '' : $url);
    }

    // Where an item links to: the address of its page, or its custom link, which may be ''.
    // Returns null when its page is deleted or not published.
    public static function list_item_url($item){
        if(!$item['page_id']){
            return $item['url'];
        }
        if(get_post_status($item['page_id']) !== 'publish'){
            return null;
        }
        return get_permalink($item['page_id']);
    }

    // The links of a list as a view shows them: each one a title and a url.
    public static function list_links($key){
        self::load_lists();
        $links = array();
        foreach(self::list_items($key) as $item){
            $url = self::list_item_url($item);
            // null is a page that is deleted or not published any more.
            if($url !== null){
                $links[] = array('title' => $item['title'], 'url' => $url);
            }
        }
        return $links;
    }

    // Loads all lists and the pages their items link to in a few queries, once per request.
    // Without it every list is a query and every item of a page is another one.
    private static function load_lists(){
        if(self::$lists_loaded){
            return;
        }
        self::$lists_loaded = true;
        $keys = array_keys(self::lists());
        // Since WordPress 6.4. An older one reads the lists one by one, which also works.
        if(function_exists('wp_prime_option_caches')){
            wp_prime_option_caches(array_map(array(__CLASS__, 'list_option'), $keys));
        }

        $ids = array();
        foreach($keys as $key){
            $ids = array_merge($ids, wp_list_pluck(self::list_items($key), 'page_id'));
        }
        // The address of a page under another page needs that page too, so the parents are loaded the same way.
        while($ids = array_unique(array_filter($ids))){
            _prime_post_caches($ids, false, false);
            $ids = array_diff(array_map('wp_get_post_parent_id', $ids), $ids);
        }
    }


    // ---------- The SEO options of a page ----------

    // The post meta that holds them. It is the one of the Career Endeavour theme, which had these options
    // before the plugin, so what was typed with the theme is still there with another theme.
    const SEO_META = '_page_seo';

    // The post types with the SEO options, of the ones the site has.
    public static function seo_post_types(){
        return array_values(array_filter(array('page', 'post', 'portfolio'), 'post_type_exists'));
    }

    // The theme has the SEO options itself: the Career Endeavour theme, which shows the box and prints the tags.
    // The plugin then leaves both to it, or a page would have two boxes and two descriptions.
    public static function theme_has_seo(){
        return !empty($GLOBALS['page_seo_meta']);
    }

    // An SEO plugin prints the description of a page, so this plugin does not print another one.
    public static function seo_plugin_active(){
        return defined('WPSEO_VERSION') || defined('AIOSEO_VERSION') || defined('AIOSEOP_VERSION') || defined('RANK_MATH_VERSION') || defined('SEOPRESS_VERSION');
    }

    // The SEO options of a page: description and keywords, each '' when it is not saved.
    public static function seo($post_id){
        $meta = get_post_meta($post_id, self::SEO_META, true);
        $saved = is_array($meta) && isset($meta['webnus_seo_options'][0]) && is_array($meta['webnus_seo_options'][0]) ? $meta['webnus_seo_options'][0] : array();
        $seo = array();
        foreach(array('description' => 'seo_desc', 'keywords' => 'seo_keyword') as $name => $field){
            $seo[$name] = isset($saved[$field]) && is_string($saved[$field]) ? $saved[$field] : '';
        }
        return $seo;
    }

    // Saves the SEO options of a page the way the theme saved them. A field that is empty is left out, and
    // a page with both empty has no meta at all.
    public static function save_seo($post_id, $description, $keywords){
        $fields = array_filter(array(
            'seo_desc' => trim(sanitize_textarea_field($description)),
            'seo_keyword' => trim(sanitize_textarea_field($keywords)),
        ), 'strlen');
        if(!$fields){
            delete_post_meta($post_id, self::SEO_META);
            return;
        }
        // WordPress takes the slashes off what it is given, so they are put on first.
        update_post_meta($post_id, self::SEO_META, wp_slash(array('webnus_seo_options' => array($fields))));
    }


    // ---------- The enquiries ----------

    private static function table(){
        global $wpdb;
        return $wpdb->prefix.self::TABLE;
    }

    // Makes the table of the enquiries on a site that does not have it yet. A table that is there is left as it is.
    // It runs in the admin and before an enquiry is saved, so a site where it cannot be made does not try on every page.
    public static function create_table(){
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

    // The enquiry fields out of the posted data, cleaned.
    public static function enquiry_values($posted){
        $values = array();
        foreach(array('name', 'phone', 'email', 'subject', 'message') as $field){
            $value = isset($posted['enquiry_'.$field]) && is_string($posted['enquiry_'.$field]) ? $posted['enquiry_'.$field] : '';
            $values[$field] = $field === 'message' ? sanitize_textarea_field($value) : sanitize_text_field($value);
        }
        return $values;
    }

    // What is wrong with an enquiry: field => message. Empty when it can be sent.
    public static function enquiry_errors($values){
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
        return $errors;
    }

    // The rate limit is reached: the table has ENQUIRY_LIMIT enquiries of the last ENQUIRY_LIMIT_MINUTES minutes,
    // so the next one is refused.
    // An enquiry that is deleted in the admin does not count any more. A site without the table has no limit yet.
    public static function enquiry_limit_reached(){
        global $wpdb;
        // The rows have the time of the site, so the minutes are counted back from that time.
        $since = date('Y-m-d H:i:s', strtotime(current_time('mysql')) - self::ENQUIRY_LIMIT_MINUTES * MINUTE_IN_SECONDS);
        $errors = $wpdb->suppress_errors();
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".self::table()." WHERE created_at > %s", $since));
        $wpdb->suppress_errors($errors);
        return $count >= self::ENQUIRY_LIMIT;
    }

    // Mails and saves a checked enquiry of the 'popup' or 'page' form and sends the SMS about it. A saved enquiry
    // is not lost when the email fails, so it only returns false, for an error to the visitor, when both fail.
    public static function send_enquiry($form, $values){
        $mailed = self::mail_enquiry($form, $values);
        $saved = self::save_enquiry($values);
        self::sms_enquiry($values);
        return $mailed || $saved;
    }

    // Saves an enquiry as a row of the table. Its status is 'new' until it is opened in the admin.
    private static function save_enquiry($values){
        global $wpdb;
        self::create_table();
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

    // The row of one enquiry, or null.
    public static function enquiry($id){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM ".self::table()." WHERE id = %d", $id));
    }

    // An enquiry that is opened is read.
    public static function read_enquiry($id){
        global $wpdb;
        $wpdb->update(self::table(), array('status' => 'read'), array('id' => $id));
    }

    // The rows of the enquiries, newest first.
    public static function enquiries($limit, $offset){
        global $wpdb;
        return (array) $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM ".self::table()." ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    // The number of enquiries, or of the ones not opened yet.
    public static function count_enquiries($new_only = false){
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM ".self::table().($new_only ? " WHERE status = 'new'" : ''));
    }

    public static function delete_enquiry($id){
        global $wpdb;
        $wpdb->delete(self::table(), array('id' => $id), array('%d'));
    }

    // "Site name <wordpress@domain>". The domain is the one of the site; a site without a real domain,
    // like a local copy, gives an address mail servers refuse, so the domain of the enquiry address is used then.
    private static function mail_from(){
        $host = preg_replace('/^www\./', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
        $address = 'wordpress@'.$host;
        if(!is_email($address)){
            $address = 'wordpress@'.substr(strrchr(self::enquiry_email(), '@'), 1);
        }
        return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES).' <'.$address.'>';
    }

    private static function mail_enquiry($form, $values){
        $site = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $body = "From: {$values['name']} <{$values['email']}>\n"
            . "Subject: {$values['subject']}\n"
            . "mobile: {$values['phone']}\n"
            . "Email id:{$values['email']}\n"
            . "Message Body:\n"
            . "{$values['message']}\n\n"
            . "-- \n"
            . "This e-mail was sent from the enquiry form on {$site} (".home_url().")";
        $headers = array('From: '.self::mail_from(), 'Reply-To: '.$values['email']);
        return wp_mail(self::enquiry_email(), $form === 'page' ? 'Contact Form' : 'Quick Enquiry', $body, $headers);
    }

    /**
     * The SMS about an enquiry, to the mobile number of the settings. Nothing is sent until the number, the
     * auth key and the template are all saved there. The template gets the fields of the enquiry as its
     * variables: ##name##, ##phone##, ##email## and ##subject##. An SMS that fails does not fail the enquiry.
     */
    private static function sms_enquiry($values){
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
}
