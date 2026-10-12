<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core class. A controller extends it, lists its shortcodes in tags() and has one method
 * per shortcode; the core registers the shortcodes and renders the views of the views/ folder:
 * views/admin/ is what the admin shows, views/frontend/ what the site shows.
 *
 * A controller can also have lists of links, like the announcements: it names them in lists() and
 * the core gives each one its page in the admin, saves it and shows it with its shortcode.
 */
abstract class TrigrexamBaseClass {

    // Hook of the admin page of a list => key of the list.
    private $list_pages = array();

    // The lists and their pages are loaded, so a page that shows many lists does not query for each one.
    private $lists_loaded = false;

    // Shortcode tag of a list => the function the plugin gave WordPress for it, to see later if it still has it.
    private $list_shortcodes = array();

    // What is wrong with the shortcodes of the lists, like two lists with the same one. The admin shows it.
    private $list_problems = array();

    /**
     * The shortcodes of the controller that have a method of their own. A controller without them leaves it out.
     *
     * @return array Shortcode tag => method of the class.
     */
    public function tags(){
        return array();
    }

    /**
     * The lists of links of the controller. Each one is built in the admin under TrigrExam, the way a menu
     * is built under Appearance > Menus: pages or custom links are added, given a title, dragged into order
     * and saved. Its shortcode shows it with its view, a file of views/frontend/menu/, which gets $links:
     * each one a title and a url.
     *
     * @return array Key of the list => title (of its admin page), shortcode (tag), view (file name, without ".php").
     */
    public function lists(){
        return array();
    }

    public function register(){
        foreach($this->tags() as $tag => $method){
            add_shortcode($tag, array($this, $method));
        }
        if($this->lists()){
            $this->register_lists();
        }
    }

    /**
     * @param string $view View name, without ".php".
     * @param array  $data Variables for the view.
     * @return string HTML.
     */
    protected function render($view, $data = array()){
        return self::load('views/'.$view.'.php', $data);
    }

    // A view that is not there shows nothing, where including it would be a warning on the page.
    private static function load($__file, $__data){
        if(!is_file(trigrexam_plugin_dir . $__file)){
            return '';
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        include trigrexam_plugin_dir . $__file;
        return ob_get_clean();
    }

    // One of the settings of the plugin, which the settings page saves, or '' when it is not saved.
    protected static function setting($name){
        $settings = get_option('trigrexam_settings');
        return is_array($settings) && isset($settings[$name]) && is_string($settings[$name]) ? $settings[$name] : '';
    }

    // Connects the shortcodes and the admin pages of the lists to WordPress.
    private function register_lists(){
        foreach($this->lists() as $key => $list){
            $this->register_list_shortcode($key, $list);
            add_action('admin_post_'.$this->list_save_action($key), function() use ($key){
                $this->save_list($key);
            });
        }
        add_action('admin_notices', array($this, 'list_notices'));
        // After the "TrigrExam" menu itself is added, which is at 11.
        add_action('admin_menu', array($this, 'add_list_pages'), 12);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_list_scripts'));
        // After WordPress has printed jQuery and the sortable script, which is at 10.
        add_action('admin_print_footer_scripts', array($this, 'print_list_script'), 20);
    }

    // WordPress has one function for a shortcode: a second one with the same tag takes the place of the first
    // without a word. So of two lists with the same shortcode only the first gets it, and the admin says so,
    // as it does when the shortcode was already the one of something else.
    private function register_list_shortcode($key, $list){
        $tag = $list['shortcode'];
        foreach($this->lists() as $other_key => $other){
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

    // The problems with the shortcodes of the lists, on the admin pages of the plugin.
    public function list_notices(){
        $screen = get_current_screen();
        if(!$screen || strpos($screen->id, 'trigrexam') === false || !current_user_can('manage_options')){
            return;
        }
        global $shortcode_tags;
        $problems = $this->list_problems;
        foreach($this->list_shortcodes as $tag => $function){
            // Something that came after the plugin, like the theme, has put its own function in its place.
            if(!isset($shortcode_tags[$tag]) || $shortcode_tags[$tag] !== $function){
                $problems[] = sprintf('The shortcode [%s] is taken over by the theme or by another plugin, so it does not show the list of this plugin.', $tag);
            }
        }
        foreach($problems as $problem){
            echo '<div class="notice notice-warning"><p><strong>TrigrExam:</strong> '.esc_html($problem).'</p></div>';
        }
    }

    // The items a list has until it is saved for the first time: title, page_id, url.
    protected function default_list_items($key){
        return array();
    }

    private function list_option($key){
        return 'trigrexam_'.$key;
    }

    private function list_page($key){
        return 'trigrexam-'.$key;
    }

    private function list_save_action($key){
        return 'trigrexam_save_'.$key;
    }

    // The saved items of a list: title, page_id, url. Anything else in the option, like a row changed
    // by hand in the database, is left out, so every item has the three fields the rest of the code reads.
    protected function list_items($key){
        $saved = get_option($this->list_option($key));
        if(!is_array($saved)){
            return $this->default_list_items($key);
        }
        $items = array();
        foreach($saved as $item){
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

    // Loads all lists of the controller and the pages their items link to in a few queries, once per request.
    // Without it every list is a query and every item of a page is another one.
    private function load_lists(){
        if($this->lists_loaded){
            return;
        }
        $this->lists_loaded = true;
        // Since WordPress 6.4. An older one reads the lists one by one, which also works.
        if(function_exists('wp_prime_option_caches')){
            wp_prime_option_caches(array_map(array($this, 'list_option'), array_keys($this->lists())));
        }

        $ids = array();
        foreach(array_keys($this->lists()) as $key){
            $ids = array_merge($ids, wp_list_pluck($this->list_items($key), 'page_id'));
        }
        // The address of a page under another page needs that page too, so the parents are loaded the same way.
        while($ids = array_unique(array_filter($ids))){
            _prime_post_caches($ids, false, false);
            $ids = array_diff(array_map('wp_get_post_parent_id', $ids), $ids);
        }
    }

    // The view of several lists that are shown together.
    const LISTS_VIEW = 'lists-view';

    /**
     * [announcement] is one list in its own view. With the shortcodes of other lists after it, like
     * [iitjam_announcement neet_announcement], the lists are together in views/frontend/menu/lists-view.php,
     * or in another view of that folder: [iitjam_announcement neet_announcement view="home-view"].
     * Every view gets $lists, and $links: the links of all the lists in one.
     */
    private function list_shortcode($key, $atts){
        $atts = is_array($atts) ? $atts : array();
        $keys = array($key);
        foreach($atts as $name => $value){
            // The other lists are the words without a name. A comma between them is left out.
            $other = is_int($name) ? $this->list_key(trim($value, ', ')) : null;
            if($other !== null && !in_array($other, $keys, true)){
                $keys[] = $other;
            }
        }

        $lists = array();
        $links = array();
        foreach($keys as $list_key){
            $lists[$list_key] = array(
                'title' => $this->lists()[$list_key]['title'],
                'links' => $this->list_links($list_key),
            );
            $links = array_merge($links, $lists[$list_key]['links']);
        }
        // A view gets both, so any view shows one list as well as several: $links for a view of links
        // like menu-view, $lists for a view of lists like lists-view.
        $data = array('lists' => $lists, 'links' => $links);
        $view = count($keys) === 1 ? $this->lists()[$key]['view'] : (isset($atts['view']) ? $atts['view'] : self::LISTS_VIEW);
        return $this->render($this->list_view($view), $data);
    }

    // The view of the site with this name: a file of views/frontend/menu/. A name that is not one of its
    // files, which can come from the content of a page, gives the view of several lists.
    private function list_view($name){
        // A name with a folder in front of it, like "menu/menu-view", is the file of that name.
        $name = basename((string) $name);
        if(!preg_match('/^[a-z0-9-]+$/', $name) || !is_file(trigrexam_plugin_dir.'views/frontend/menu/'.$name.'.php')){
            $name = self::LISTS_VIEW;
        }
        return 'frontend/menu/'.$name;
    }

    // The key of the list with this shortcode or this key, or null.
    private function list_key($name){
        foreach($this->lists() as $key => $list){
            if($name === $list['shortcode'] || $name === $key){
                return $key;
            }
        }
        return null;
    }

    // The links of a list as a view shows them: each one a title and a url.
    protected function list_links($key){
        $this->load_lists();
        $links = array();
        foreach($this->list_items($key) as $item){
            $url = $this->list_item_url($item);
            // null is a page that is deleted or not published any more.
            if($url !== null){
                $links[] = array('title' => $item['title'], 'url' => $url);
            }
        }
        return $links;
    }

    // The positions put the pages below "Enquiries" and above "Settings".
    public function add_list_pages(){
        $position = 1;
        foreach($this->lists() as $key => $list){
            $hook = add_submenu_page('trigrexam', $list['title'], $list['title'], 'manage_options', $this->list_page($key), function() use ($key){
                $this->list_admin_page($key);
            }, $position++);
            $this->list_pages[$hook] = $key;
        }
    }

    public function enqueue_list_scripts($hook){
        if(isset($this->list_pages[$hook])){
            wp_enqueue_script('jquery-ui-sortable');
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
        $list = $this->lists()[$key];
        // Before the rows, as it loads all pages at once and the rows of pages then need no query of their own.
        $pages = get_pages(array('sort_column' => 'post_title'));
        $rows = '';
        foreach($this->list_items($key) as $number => $item){
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
            'missing' => $item['page_id'] && $this->list_item_url($item) === null,
        ));
    }

    // The posted list. The items are saved in the order they are posted, which is the order on the screen.
    private function save_list($key){
        if(!current_user_can('manage_options')){
            wp_die('You are not allowed to change this list.');
        }
        check_admin_referer($this->list_save_action($key));

        $posted = isset($_POST['items']) && is_array($_POST['items']) ? wp_unslash($_POST['items']) : array();
        $items = array();
        foreach($posted as $row){
            $item = $this->clean_list_item($row);
            if($item !== null){
                $items[] = $item;
            }
        }
        update_option($this->list_option($key), $items, false);

        wp_safe_redirect(add_query_arg(array('page' => $this->list_page($key), 'updated' => 1), admin_url('admin.php')));
        exit;
    }

    // One posted item, cleaned: title, page_id, url. An item of a page has no url, a custom link has no page_id.
    // Returns null for an item without a title.
    private function clean_list_item($row){
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
    private function list_item_url($item){
        if(!$item['page_id']){
            return $item['url'];
        }
        if(get_post_status($item['page_id']) !== 'publish'){
            return null;
        }
        return get_permalink($item['page_id']);
    }
}
