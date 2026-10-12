<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The controller of the lists of links, like the announcements. A list is one line in lists() and a view
 * in views/frontend/menu/; the core class does the rest: its page under TrigrExam in the admin, saving it and its shortcode.
 * The views: "menu-view" is one scrolling line of links, "lists-view" a heading with the links under it.
 * Several lists show together when their shortcodes are in one: [iitjam_announcement neet_announcement].
 */
class TrigrexamMenu extends TrigrexamBaseClass {

    // Key of the list => title of its admin page, tag of its shortcode, view of views/frontend/menu/ that shows it.
    public function lists(){
        return array(
            'announcements' => array('title' => 'Announcements', 'shortcode' => 'announcement', 'view' => 'menu-view'),
            'iitjam_announcements' => array('title' => 'IIT-JAM Announcements', 'shortcode' => 'iitjam_announcement', 'view' => 'menu-view'),
            'neet_announcements' => array('title' => 'NEET Announcements', 'shortcode' => 'neet_announcement', 'view' => 'menu-view'),
            'download' => array('title' => 'Download', 'shortcode' => 'download', 'view' => 'menu-view'),
            'syllabus' => array('title' => 'Syllabus', 'shortcode' => 'syllabus', 'view' => 'menu-view'),
            'others' => array('title' => 'Others', 'shortcode' => 'others', 'view' => 'lists-view'),
        );
    }

    // Until the announcements are saved as a list they are the ones of the old post type, so the site shows what it did.
    protected function default_list_items($key){
        $items = array();
        return $items;
    }
}
