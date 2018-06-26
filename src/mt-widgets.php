<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
} // Exit if accessed directly

function mt_register_widgets() {
    register_widget( 'mt_short_cart_widget' );
}
add_action( 'widgets_init', 'mt_register_widgets' );

class mt_short_cart_widget extends WP_Widget {
	function __construct() {
		parent::__construct( false,$name=__( 'My Tickets: Quick Cart','my-tickets' ), array( 'customize_selective_refresh' => true ) );
	}

	function widget($args, $instance) {
        $before_widget = $args['before_widget'];
        $after_widget  = $args['after_widget'];
        $before_title  = $args['before_title'];
        $after_title   = $args['after_title'];
        
		$the_title = apply_filters( 'widget_title', $instance['title'] );
		$widget_title = empty( $the_title ) ? '' : $the_title;
		$widget_title = ( $widget_title != '' ) ? $before_title . $widget_title . $after_title : '';
		$cart = my_tickets_short_cart();
		echo $before_widget;
		echo $widget_title;
		echo $cart;
		echo $after_widget;
	}

	function form($instance) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title']) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title','my-tickets'); ?>:</label><br />
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>"/>
		</p>
		<?php
	}

	function update($new_instance,$old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}
}