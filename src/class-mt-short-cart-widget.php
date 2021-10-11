<?php
/**
 * My Tickets widgets.
 *
 * @category Widgets
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Register short cart widget.
 */
function mt_register_widgets() {
	register_widget( 'Mt_Short_Cart_Widget' );
}
add_action( 'widgets_init', 'mt_register_widgets' );

/**
 * My Tickets Short Cart widget class
 *
 * @category  Widgets
 * @package   My Tickets
 * @author    Joe Dolson
 * @copyright 2015
 * @license   GPLv2 or later
 * @version   1.0
 */
class Mt_Short_Cart_Widget extends WP_Widget {
	/**
	 * Mt_Short_Cart_Widget constructor.
	 */
	function __construct() {
		parent::__construct( false, $name = __( 'My Tickets: Quick Cart', 'my-tickets' ), array( 'customize_selective_refresh' => true ) );
	}

	/**
	 * Display the widget.
	 *
	 * @param array $args Widget arguments from theme.
	 * @param array $instance Widget settings.
	 */
	function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];

		$the_title    = apply_filters( 'widget_title', $instance['title'] );
		$widget_title = empty( $the_title ) ? '' : $the_title;
		$widget_title = ( '' !== $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$cart         = my_tickets_short_cart();
		echo wp_kses_post( $before_widget . $widget_title . $cart . $after_widget );
	}

	/**
	 * Form to configure widget.
	 *
	 * @param array $instance Settings.
	 *
	 * @return void
	 */
	function form( $instance ) {
		$title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'my-tickets' ); ?>:</label><br />
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $title; ?>"/>
		</p>
		<?php
	}

	/**
	 * Update widget.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Old settings.
	 *
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance          = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );

		return $instance;
	}
}
