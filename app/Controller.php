<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core of the two controllers of the plugin, which connect it to WordPress: app/Admin.php is what the
 * admin uses and app/Frontend.php what the site uses. It has what both of them need: the views and the styles.
 * The data comes from app/Model.php and the HTML is in the views of views/: views/admin/ is what the admin
 * shows, views/frontend/ what the site shows.
 */
abstract class TrigrexamController {

    // Connects the controller to WordPress: its hooks and its shortcodes.
    abstract public function register();

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

    // The styles of the plugin, assets/trigrexam.css: the site has them on every page, the admin on the pages
    // of the plugin. The time the file was changed is its version, so a browser does not keep an old copy.
    public function enqueue_style(){
        $file = 'assets/trigrexam.css';
        wp_enqueue_style('trigrexam', plugins_url($file, trigrexam_plugin_dir.'trigrexam.php'), array(), (string) @filemtime(trigrexam_plugin_dir.$file));
    }
}
