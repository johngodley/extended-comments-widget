<?php
/*
Plugin Name: Extended Comments Widget
Plugin URI: http://urbangiraffe.com/plugins/extended-comments-widget/
Description: A widget that shows a section of comment text along with the author name. Can exclude certain authors from the list
Author: John Godley
Author URI: http://urbangiraffe.com
Version: 0.1.1
*/

class Extended_Comments_Widget extends WP_Widget {
	function Extended_Comments_Widget() {
		$widget_ops  = array( 'classname' => 'widget_extended_comments', 'description' => __( 'Show extended latest comments', 'extended-comments' ) );
		$control_ops = array( 'width' => 300, 'height' => 300 );

		$this->WP_Widget( 'extended-comments', __( 'Extended Comments', 'extended-comments' ), $widget_ops, $control_ops );

		if ( is_active_widget( false, false, $this->id_base ) )
			add_action( 'wp_head', array( &$this, 'style' ) );
	}

	function style() {
?>
	<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;} .recentcomments blockquote { padding: 0;}</style>
<?php
	}

	/**
	 * Display the widget
	 *
	 * @param string $args Widget arguments
	 * @param string $instance Widget instance
	 * @return void
	 **/
	function widget( $args, $instance ) {
		extract( $args );

		$instance = wp_parse_args( (array)$instance, array( 'title' => __( 'Comments', 'extended-comments' ), 'ignore' => '', 'number' => 5 ) );
		$title    = apply_filters( 'widget_title', $instance['title'] );
		$ignore   = stripslashes( $instance['ignore'] );

		echo $before_widget;

		if ( $title )
			echo $before_title . stripslashes( $title ) . $after_title;

		$this->recent_comments( $instance['number'], $ignore );

		// After
		echo $after_widget;
	}

	function recent_comments( $number, $ignore, $words = 12 )	{
		global $wpdb;

		if ( $ignore != '' )
			$ignore = "AND user_id NOT IN($ignore)";

		$comments = $wpdb->get_results( $wpdb->prepare( "SELECT comment_author, comment_ID, comment_post_ID, comment_content FROM $wpdb->comments WHERE comment_approved = '1' AND comment_type='' $ignore ORDER BY comment_date_gmt DESC LIMIT %d", $number ) );

		if ( $comments ) {
			echo '<ul class="recentcomments">';

			foreach ( $comments AS $comment ) {
?>
	<li>
		<blockquote><?php echo $this->limit_words( $comment->comment_content, $words );?>
			&mdash;
			<cite>
				<a href="<?php echo get_permalink( $comment->comment_post_ID ) ?>#comment-<?php echo $comment->comment_ID ?>"><?php echo esc_html( $comment->comment_author ) ?></a>
			</cite>
		</blockquote>
	</li>
<?php
			}

			echo '</ul>';
		}
	}

	function limit_words( $text, $limit = 10, $characters = 100 ) {
		$parts = explode( ' ', wp_filter_nohtml_kses( $text ) );

		if ( count( $parts ) > $limit )
			$text = implode( ' ', array_splice( $parts, 0, $limit ) ).'&hellip;';

		return wp_html_excerpt( stripslashes( $text ), $characters );
	}

	/**
	 * Display config interface
	 *
	 * @param string $instance Widget instance
	 * @return void
	 **/
	function form( $instance ) {
		$instance = wp_parse_args( (array)$instance, array( 'title' => __( 'Comments' ), 'ignore' => '', 'number' => 5 ) );

		$title  = stripslashes( $instance['title'] );
		$ignore = stripslashes( $instance['ignore'] );
		$number = intval( $instance['number'] );

		?>
<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'extended-comments' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>
<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of comments:', 'extended-comments' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" /></label></p>
<p><label for="<?php echo $this->get_field_id( 'ignore' ); ?>"><?php _e( 'Ignore users:', 'extended-comments' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'ignore' ); ?>" name="<?php echo $this->get_field_name( 'ignore' ); ?>" type="text" value="<?php echo esc_attr( $ignore ); ?>" /></label></p>
		<?php
	}

	/**
	 * Save widget data
	 *
	 * @param string $new_instance
	 * @param string $old_instance
	 * @return void
	 **/
	function update( $new_instance, $old_instance ) {
		$instance     = $old_instance;
		$new_instance = wp_parse_args( (array)$new_instance, array( 'title' => __( 'Comments' ), 'ignore' => '', 'number' => 5 ) );

		$instance['title']  = wp_filter_nohtml_kses( $new_instance['title'] );
		$instance['ignore'] = implode( ',', array_filter( array_map( 'intval', explode( ',', $new_instance['ignore'] ) ) ) );
		$instance['number'] = intval( $new_instance['number'] );
		return $instance;
	}
}

function extended_comments_widget_init() {
	register_widget( 'Extended_Comments_Widget' );
}

add_action( 'widgets_init', 'extended_comments_widget_init' );
