<?php
/*************************************************************************

Plugin Name: Featured Post
Plugin URI: http://ssovit.com/featured-post-wordpress-plugin/
Description: Featured Post Plugin For Wordpress.
Version: 2.0.1
Author: Sovit Tamrakar
Author URI: http://ssovit.com

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
class SovitFeaturedPost
{
	var $db = NULL;
	var $ignore_post_type = array( 'page', 'attachment', 'revision', 'nav_menu_item' );
	function __construct( )
	{
	}
	function init( )
	{
		add_filter( 'query_vars', array(
			 &$this,
			'query_vars' 
		) );
		add_action( 'pre_get_posts', array(
			 &$this,
			'pre_get_posts' 
		) );
	}
	function admin_init( )
	{
		add_filter( 'current_screen', array(
			 &$this,
			'my_current_screen' 
		) );
		add_filter( 'manage_posts_columns', array(
			 &$this,
			'manage_posts_columns' 
		) );
		add_filter( 'manage_pages_columns', array(
			 &$this,
			'manage_posts_columns' 
		) );
		add_filter( 'manage_posts_custom_column', array(
			 &$this,
			'manage_posts_custom_column' 
		), 10, 2 );
		add_filter( 'manage_pages_custom_column', array(
			 &$this,
			'manage_posts_custom_column' 
		), 10, 2 );
		add_action( 'admin_head-edit.php', array(
			 &$this,
			'admin_head' 
		) );
		add_filter( 'pre_get_posts', array(
			 &$this,
			'admin_pre_get_posts' 
		), 1 );
	}
	function add_views_link( $views )
	{
		$post_type           = ( ( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] != "" ) ? $_GET[ 'post_type' ] : 'post' );
		$count               = $this->total_featured( $post_type );
		$class               = $_GET[ 'post_status' ] == 'featured' ? "current" : '';
		$views[ 'featured' ] = "<a class=\"$class\" href=\"edit.php?&post_status=featured&post_type={$post_type}\">Featured <span class=\"count\">({$count})</span></a>";
		return $views;
	}
	function total_featured( $post_type = "post" )
	{
		$rowQ = new WP_Query( array(
			 'post_type' => $post_type,
			'meta_query' => array(
				 array(
					 'key' => '_post_is_featured',
					'value' => 'yes' 
				) 
			),
			'posts_per_page' => 1 
		) );
		wp_reset_postdata();
		wp_reset_query();
		$rows = $rowQ->found_posts;
		unset( $rowQ );
		return $rows;
	}
	function my_current_screen( $screen )
	{
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return $screen;
		}
		$post_types = get_post_types( '', 'names' );
		foreach ( $post_types as $post_type ) {
			if ( !in_array( $post_type, $this->ignore_post_type ) ) {
				add_filter( 'views_edit-' . $post_type, array(
					 &$this,
					'add_views_link' 
				) );
			}
		}
		return $screen;
	}
	function manage_posts_columns( $columns )
	{
		global $current_user;
		get_currentuserinfo();
		if ( $current_user->allcaps[ 'level_7' ] ) {
			$columns[ 'featured' ] = __( 'Featured' );
		}
		return $columns;
	}
	function manage_posts_custom_column( $column_name, $post_id )
	{
		//echo "here";
		if ( $column_name == 'featured' ) {
			$is_featured = get_post_meta( $post_id, 'featured', true );
			$class       = "featured-post-toggle";
			$text        = "NO";
			if ( $is_featured == "yes" ) {
				$class .= " featured-post-on";
				$text = "YES";
			} else {
				$class .= " featured-post-off";
			}
			echo "<a href=\"#!featured-toggle\" class=\"{$class}\" data-post-id=\"{$post_id}\">$text</a>";
		}
	}
	function admin_head( )
	{
		echo '<style>.featured-post-toggle{width:16px; height:16px; display:inline-block; overflow:hidden; text-indent:-1000px;}';
		echo '.featured-post-off, .featured-post-on:hover{ background:url(http://cdn3.iconfinder.com/data/icons/woothemesiconset/16/star_off.png);}';
		echo '.featured-post-on, .featured-post-off:hover{ background:url(http://cdn3.iconfinder.com/data/icons/woothemesiconset/16/star.png); }';
		echo '</style>';
		echo '<script type="text/javascript">jQuery(document).ready(function($){$(\'.featured-post-toggle\').click(function(e){e.preventDefault();var post_id=$(this).attr(\'data-post-id\');var data={action:\'toggle-featured-post\',post_id:post_id};$.post(ajaxurl,data,function(data){document.location=document.location;})});});</script>';
	}
	function admin_ajax( )
	{
		$post_id     = $_POST[ 'post_id' ];
		$is_featured = get_post_meta( $post_id, 'featured', true );
		$newStatus   = $is_featured == 'yes' ? 'no' : 'yes';
		delete_post_meta( $post_id, 'featured' );
		add_post_meta( $post_id, 'featured', $newStatus );
		echo ":P";
		die( );
	}
	function admin_pre_get_posts( $query )
	{
		global $wp_query;
		if ( is_admin() && $_GET[ 'post_status' ] == 'featured' ) {
			$query->set( 'meta_key', 'featured' );
			$query->set( 'meta_value', 'yes' );
		}
		return $query;
	}
	function query_vars( $public_query_vars )
	{
		$public_query_vars[ ] = 'featured';
		return $public_query_vars;
	}
	function pre_get_posts( $query )
	{
		if ( !is_admin() ) {
			if ( $query->get( 'featured' ) == 'yes' ) {
				$query->set( 'meta_key', 'featured' );
				$query->set( 'meta_value', 'yes' );
			}
		}
		return $query;
	}
}
$SovitFeaturedPost = new SovitFeaturedPost();
add_action( 'init', array(
	 $SovitFeaturedPost,
	'init' 
) );
add_action( 'admin_init', array(
	 $SovitFeaturedPost,
	'admin_init' 
) );
add_action( 'wp_ajax_toggle-featured-post', array(
	 $SovitFeaturedPost,
	'admin_ajax' 
) );
class Featured_Post_Widget extends WP_Widget
{
	function __construct( )
	{
		parent::WP_Widget( false, $name = 'Featured Post' );
	}
	function form( $instance )
	{
		global $SovitFeaturedPost;
		$title      = esc_attr( $instance[ 'title' ] );
		$type       = esc_attr( $instance[ 'post_type' ] );
		$num        = (int) esc_attr( $instance[ 'num' ] );
		$post_types = get_post_types( array(
			 'publicly_queryable' => true 
		), 'objects' );
?>

<p>
	<label for="<?php
		echo $this->get_field_id( 'title' );
?>">
		<?php
		_e( 'Title:' );
?>
	</label>
	<input class="widefat" id="<?php
		echo $this->get_field_id( 'title' );
?>" name="<?php
		echo $this->get_field_name( 'title' );
?>" type="text" value="<?php
		echo $title;
?>" />
</p>
<p>
	<label for="<?php
		echo $this->get_field_id( 'post_type' );
?>">
		<?php
		_e( 'Post Type:' );
?>
	</label>
	<select name="<?php
		echo $this->get_field_name( 'post_type' );
?>"  id="<?php
		echo $this->get_field_id( 'title' );
?>" >
		<?php
		foreach ( $post_types as $key => $post_type ) {
?>
		<?php
			if ( !in_array( $key, $SovitFeaturedPost->ignore_post_type ) ) {
?>
		<option value="<?php
				echo $key;
?>"<?php
				echo ( $key == $type ? " selected" : "" );
?>><?php
				echo $post_type->labels->name;
?></option>
		<?php
			}
?>
		<?php
		}
?>
	</select>
</p>
<p>
	<label for="<?php
		echo $this->get_field_id( 'num' );
?>">
		<?php
		_e( 'Number To show:' );
?>
	</label>
	<input id="<?php
		echo $this->get_field_id( 'num' );
?>" class="widefat" name="<?php
		echo $this->get_field_name( 'num' );
?>" type="text" value="<?php
		echo $num;
?>" />
</p>
<?php
	}
	function update( $new_instance, $old_instance )
	{
		$instance                = $old_instance;
		$instance[ 'title' ]     = strip_tags( $new_instance[ 'title' ] );
		$instance[ 'num' ]       = (int) strip_tags( $new_instance[ 'num' ] );
		$instance[ 'post_type' ] = strip_tags( $new_instance[ 'post_type' ] );
		if ( $instance[ 'num' ] < 1 ) {
			$instance[ 'num' ] = 10;
		}
		return $instance;
	}
	function widget( $args, $instance )
	{
		extract( $args );
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
?>
<?php
		echo $before_widget;
?>
<?php
		if ( $title )
			echo $before_title . $title . $after_title;
?>
<ul class="widget-list featured-post-widget featured-post">
	<?php
		wp_reset_query();
		query_posts( 'post_type=' . $instance[ 'post_type' ] . '&showposts=' . $instance[ 'num' ] . '&featured=yes' );
		while ( have_posts() ):
			the_post();
?>
	<li><a href="<?php
			the_permalink();
?>">
		<?php
			the_title();
?>
		</a></li>
	<?php
		endwhile;
		wp_reset_query(); // set it not to reset the query when in featured posts page of edit.php in wp-admin
?>
</ul>
<?php
		echo $after_widget;
?>
<?php
		// outputs the content of the widget
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("Featured_Post_Widget");' ) );

