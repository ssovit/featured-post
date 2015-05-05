<?php
/*************************************************************************
Plugin Name: Featured Post
Plugin URI: http://ssovit.com/featured-post-wordpress-plugin/
Description: Featured Post Plugin For Wordpress.
Version: 3.2.1
Author: Sovit Tamrakar
Author URI: http://wppress.net
**************************************************************************
Copyright (C) 2010 Sovit Tamrakar(http://ssovit.com)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

class Featured_Post
{
    var $db = NULL;
    public $post_types = array();
    function __construct() {
        add_action('init', array(&$this,
            'init'
        ));
        add_action('admin_init', array(&$this,
            'admin_init'
        ));
        add_action('wp_ajax_toggle-featured-post', array(&$this,
            'admin_ajax'
        ));
        add_action( 'plugins_loaded', array(&$this,
            'load_featured_textdomain'
        ));
    }
    function init() {
        add_filter('query_vars', array(&$this,
            'query_vars'
        ));
        add_action('pre_get_posts', array(&$this,
            'pre_get_posts'
        ));
    }
    function admin_init() {
        /* Set the new post to 'featured=no' when it's created */ 
        add_action('new_to_publish', array(&$this,
            'set_not_featured'
        ), 1, 2);
        add_action('draft_to_publish', array(&$this,
            'set_not_featured'
        ), 1, 2);
        add_action('pending_to_publish', array(&$this,
            'set_not_featured'
        ), 1, 2);

        add_filter('current_screen', array(&$this,
            'my_current_screen'
        ));
        add_action('admin_head-edit.php', array(&$this,
            'admin_head'
        ));
        add_filter('pre_get_posts', array(&$this,
            'admin_pre_get_posts'
        ) , 1);
        $this->post_types = get_post_types(array(
            '_builtin' => false,
        ) , 'names', 'or');
        $this->post_types['post'] = 'post';
        $this->post_types['page'] = 'page';
        ksort($this->post_types);
        foreach ($this->post_types as $key => $val) {
            add_filter('manage_edit-' . $key . '_columns', array(&$this,
                'manage_posts_columns'
            ));
            add_action('manage_' . $key . '_posts_custom_column', array(&$this,
                'manage_posts_custom_column'
            ) , 10, 2);
        }
        add_action( 'post_submitbox_misc_actions', array(&$this,
            'edit_screen_featured_ui'
        ));
        add_action( 'save_post', array(&$this,
            'edit_screen_featured_save'
        ));
    }
    function add_views_link($views) {
        $post_type = ((isset($_GET['post_type']) && $_GET['post_type'] != "") ? $_GET['post_type'] : 'post');
        $count = $this->total_featured($post_type);
        $class =  (isset($_GET['post_status']) && $_GET['post_status'] == 'featured') ? "current" : '';
        $views['featured'] = "<a class=\"" . $class . "\" id=\"featured-post-filter\" href=\"edit.php?&post_status=featured&post_type={$post_type}\">" . __( 'Featured', 'featured-post' ) . "<span class=\"count\">({$count})</span></a>";
        return $views;
    }
    function total_featured($post_type = "post") {
        $rowQ = new WP_Query(array(
            'post_type' => $post_type,
            'meta_query' => array(
                array(
                    'key' => '_is_featured',
                    'value' => 'yes'
                )
            ) ,
            'posts_per_page' => 1
        ));
        wp_reset_postdata();
        wp_reset_query();
        $rows = $rowQ->found_posts;
        unset($rowQ);
        return $rows;
    }
    function my_current_screen($screen) {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return $screen;
        }
        $this->post_types = get_post_types(array(
            '_builtin' => false,
        ) , 'names', 'or');
        $this->post_types['post'] = 'post';
        $this->post_types['page'] = 'page';
        ksort($this->post_types);
        foreach ($this->post_types as $key => $val) {
            add_filter('views_edit-' . $key, array(&$this,
                'add_views_link'
            ));
        }
        return $screen;
    }
    function manage_posts_columns($columns) {
        global $current_user;
        get_currentuserinfo();
        if (current_user_can('edit_posts', $user_id)) {
            $columns['featured'] = __('Featured', 'featured-post' );
        }
        return $columns;
    }
    function manage_posts_custom_column($column_name, $post_id) {
        //echo "here";
        if ($column_name == 'featured') {
            $is_featured = get_post_meta($post_id, '_is_featured', true);
            $class = "dashicons";
            $text = "";
            if ($is_featured == "yes") {
                $class.= " dashicons-star-filled";
                $text = "";
            } else {
                $class.= " dashicons-star-empty";
            }
            echo "<a href=\"#!featured-toggle\" class=\"featured-post-toggle {$class}\" data-post-id=\"{$post_id}\">$text</a>";
        }
    }
    function admin_head() {
        echo '<script type="text/javascript">
        jQuery(document).ready(function($){
            $(\'.featured-post-toggle\').on("click",function(e){
                e.preventDefault();
                var _el=$(this);
                var post_id=$(this).attr(\'data-post-id\');
                var data={action:\'toggle-featured-post\',post_id:post_id};
                $.ajax({url:ajaxurl,data:data,type:\'post\',
                    dataType:\'json\',
                    success:function(data){
                    _el.removeClass(\'dashicons-star-filled\').removeClass(\'dashicons-star-empty\');
                    $("#featured-post-filter span.count").text("("+data.total_featured+")");
                    if(data.new_status=="yes"){
                        _el.addClass(\'dashicons-star-filled\');
                    }else{
                        _el.addClass(\'dashicons-star-empty\');
                    }
                    }
                });
            });
        });
        </script>';
    }
    function admin_ajax() {
        header('Content-Type: application/json');
        $post_id = $_POST['post_id'];
        $is_featured = get_post_meta($post_id, '_is_featured', true);
        $newStatus = $is_featured == 'yes' ? 'no' : 'yes';
        delete_post_meta($post_id, '_is_featured');
        add_post_meta($post_id, '_is_featured', $newStatus);
        echo json_encode(array(
            'ID' => $post_id,
            'new_status' => $newStatus,
            'total_featured' => $this->total_featured(get_post_type($post_id))
        ));
        die();
    }  
    /**
     * set_not_featured()
     *
     * Sets the value of 'featured' to 'no' right after the post creation
     */
    function set_not_featured($post_id) {      
        add_post_meta($post_id, '_is_featured', 'no');
    }
    function admin_pre_get_posts($query) {
        global $wp_query;
        if (is_admin() && isset($_GET['post_status']) && $_GET['post_status'] == 'featured') {
            $query->set('meta_key', '_is_featured');
            $query->set('meta_value', 'yes');
        }
        return $query;
    }
    function query_vars($public_query_vars) {
        $public_query_vars[] = 'featured';
        return $public_query_vars;
    }
    function pre_get_posts($query) {
        if (!is_admin()) {
            if ($query->get('featured') == 'yes') {
                $query->set('meta_key', '_is_featured');
                $query->set('meta_value', 'yes');
            }
        }
        return $query;
    }

    function edit_screen_featured_ui() {
    // global $typenow;
    if ( is_admin() ) { //Post types could be defined here ( $typenow == 'post' )
        echo '<div class="misc-pub-section"><span style="color:#999; margin: -2px 2px 0 -1px;" class="dashicons dashicons-star-filled"></span>' . "\n";
        echo '<label for="featured" title="' . esc_attr__( 'If checked, this is marked as featured.', 'featured-post' ) . '">' . "\n";
        echo __( 'Featured?', 'featured-post' ) . ' <input id="featured"" type="checkbox" value="yes" ' . checked( get_post_meta( get_the_ID(), '_is_featured', true ), 'yes', false ) . ' name="featured" /></label></div>' . "\n";
    }
    }
    function edit_screen_featured_save( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['featured'] ) ) {
            update_post_meta( $post_id, '_is_featured', esc_attr( $_POST['featured'] ) );
        }
    }
    function load_featured_textdomain() {
        load_plugin_textdomain( 'featured-post', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
    }
}
class Featured_Post_Widget extends WP_Widget
{
    private $post_types = array();
    function __construct() {
        parent::WP_Widget(false, $name = __( 'Featured Post', 'featured-post' ) );
    }
    function form($instance) {
        $title = esc_attr($instance['title']);
        $type = esc_attr($instance['post_type']);
        $num = (int)esc_attr($instance['num']);
        $this->post_types = get_post_types(array(
            '_builtin' => false,
        ) , 'names', 'or');
        $this->post_types['post'] = 'post';
        $this->post_types['page'] = 'page';
        ksort($this->post_types);
        echo "<p>";
        echo "<label for=\"" . $this->get_field_id('title') . "\">";
        echo _e('Title') .':';
        echo "</label>";
        echo "<input class=\"widefat\" id=\"" . $this->get_field_id('title') . "\" name=\"" . $this->get_field_name('title') . "\" type=\"text\" value=\"" . $title . "\" />";
        echo "</p>";
        echo "<p>";
        echo "<label for=\"" . $this->get_field_id('post_type') . "\">";
        echo _e('Post Type', 'featured-post') . ':';
        echo "</label>";
        echo "<select name = \"" . $this->get_field_name('post_type') . "\" id=\"" . $this->get_field_id('title') . "\" >";
        foreach ($this->post_types as $key => $post_type) {
            echo '<option value="' . $key . '"' . ($key == $type ? " selected" : "") . '>' . $key . "</option>";
        }
        echo "</select>";
        echo "</p>";
        echo "<p>";
        echo "<label for=\"" . $this->get_field_id('num') . "\">";
        echo _e('Number To show:', 'featured-post');
        echo "</label>";
        echo "<input id = \"" . $this->get_field_id('num') . "\" class = \"widefat\" name = \"" . $this->get_field_name('num') . "\" type=\"text\" value =\"" . $num . "\" / >";
        echo "</p>";
    }
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['num'] = (int)strip_tags($new_instance['num']);
        $instance['post_type'] = strip_tags($new_instance['post_type']);
        if ($instance['num'] < 1) {
            $instance['num'] = 10;
        }
        return $instance;
    }
    function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo "<ul class=\"widget-list featured-post-widget featured-post\">";
        wp_reset_query();
        global $wp_query;
        $old_query = $wp_query;
        $FeaturedPost_query = new WP_Query(array(
            'post_type' => $instance['post_type'],
            'showposts' => $instance['num'],
            'featured' => 'yes',
            'paged' => 1
        ));
        while ($FeaturedPost_query->have_posts()) {
            $FeaturedPost_query->the_post();
            echo "<li><a href=\"" . get_permalink() . "\">";
            echo get_the_title();
            echo "</a>";
            echo "</li>";
        }
        wp_reset_query();
        $wp_query = $old_query;
        echo "</ul>";
        echo $after_widget;
        // outputs the content of the widget
    }
}
$Featured_Post = new Featured_Post();

add_action('widgets_init', create_function('', 'return register_widget("Featured_Post_Widget");') , 100);