<?php
/**
 * Add-to-cart purchase path tests.
 *
 * @package My Tickets
 */

/**
 * Verify add-to-cart behavior for guest and authenticated users.
 */
class Tests_My_Tickets_Add_To_Cart_Paths extends WP_UnitTestCase {
	/**
	 * Event ID used by tests.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Guest cart IDs created during tests.
	 *
	 * @var array
	 */
	private $guest_cart_ids = array();

	/**
	 * User IDs created during tests.
	 *
	 * @var array
	 */
	private $user_ids = array();

	/**
	 * Create a reusable event fixture.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->event_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Add To Cart Event',
			)
		);

		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 10 ) );
	}

	/**
	 * Clean up session state between tests.
	 */
	public function tear_down(): void {
		foreach ( $this->guest_cart_ids as $guest_id ) {
			mt_delete_data( 'cart', $guest_id );
			mt_delete_transient( 'mt_' . $guest_id . '_expiration' );
		}

		foreach ( $this->user_ids as $user_id ) {
			delete_user_meta( $user_id, '_mt_user_cart' );
			delete_user_meta( $user_id, '_mt_user_init_expiration' );
		}

		unset( $_COOKIE['mt_unique_id'] );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Set and track a deterministic guest cart identifier.
	 *
	 * @return string
	 */
	private function set_guest_cart_id() {
		$guest_id                = 'guest_' . wp_generate_password( 12, false, false );
		$_COOKIE['mt_unique_id'] = $guest_id;
		$this->guest_cart_ids[]  = $guest_id;

		return $guest_id;
	}

	/**
	 * Build request payload used by first-time add-to-cart updates.
	 *
	 * @param int $count Number of tickets.
	 *
	 * @return array
	 */
	private function get_initial_add_to_cart_payload( $count = 1 ) {
		return array(
			'mt_event_id' => $this->event_id,
			'mt_tickets'  => array(
				'standard' => $count,
			),
		);
	}

	/**
	 * Build payload used when cart already exists.
	 *
	 * @param int $count Number of tickets.
	 *
	 * @return array
	 */
	private function get_update_payload( $count = 1 ) {
		return array(
			$this->event_id => array(
				'standard' => array(
					'count' => $count,
				),
			),
		);
	}

	/**
	 * Build basic registration data.
	 *
	 * @param int $tickets Number of tickets available.
	 *
	 * @return array
	 */
	private function get_registration( $tickets = 1 ) {
		return array(
			'counting_method' => 'discrete',
			'total'           => 'inherit',
			'prices'          => array(
				'standard' => array(
					'label'   => 'Standard',
					'price'   => 25,
					'tickets' => $tickets,
					'sold'    => 0,
					'close'   => '',
				),
			),
		);
	}

	/**
	 * Override wp_die handler during AJAX testing.
	 *
	 * @return callable
	 */
	public function filter_wp_die_handler() {
		return array( $this, 'throw_wp_die_exception' );
	}

	/**
	 * Throw an exception instead of terminating process in wp_die.
	 *
	 * @throws Exception Raised to stop AJAX execution safely in tests.
	 *
	 * @return void
	 */
	public function throw_wp_die_exception() {
		throw new Exception( 'mt_ajax_wp_die' );
	}

	/**
	 * Dispatch add-to-cart through AJAX action and return decoded JSON payload.
	 *
	 * @param int  $tickets Number of tickets to request.
	 * @param bool $logged_in Whether to call authenticated AJAX action.
	 *
	 * @throws Exception Raised if AJAX action fails unexpectedly.
	 *
	 * @return array
	 */
	private function dispatch_ajax_add_to_cart( $tickets = 1, $logged_in = false ) {
		$original_request = $_REQUEST;
		$original_post    = $_POST;

		$nonce = wp_create_nonce( 'mt-cart-nonce' );
		$data  = http_build_query(
			array(
				'mt_event_id' => $this->event_id,
				'mt_tickets'  => array(
					'standard' => $tickets,
				),
			)
		);

		$_REQUEST = array(
			'action'   => 'mt_ajax_handler',
			'function' => 'add_to_cart',
			'data'     => $data,
			'security' => $nonce,
		);
		$_POST    = $_REQUEST;

		add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );

		ob_start();
		try {
			do_action( $logged_in ? 'wp_ajax_mt_ajax_handler' : 'wp_ajax_nopriv_mt_ajax_handler' );
		} catch ( Exception $e ) {
			if ( 'mt_ajax_wp_die' !== $e->getMessage() ) {
				remove_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );
				$_REQUEST = $original_request;
				$_POST    = $original_post;
				ob_end_clean();
				throw $e;
			}
		}
		$response = ob_get_clean();

		remove_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );
		$_REQUEST = $original_request;
		$_POST    = $original_post;

		$decoded = json_decode( $response, true );
		$this->assertIsArray( $decoded, 'AJAX response should be valid JSON.' );

		return $decoded;
	}

	/**
	 * Guests should save cart data in transients keyed by unique cart ID.
	 */
	public function test_guest_add_to_cart_saves_cart_to_transient() {
		wp_set_current_user( 0 );
		$guest_id = $this->set_guest_cart_id();

		$response = mt_update_cart( $this->get_initial_add_to_cart_payload( 2 ) );

		$this->assertTrue( (bool) $response['success'] );
		$this->assertSame( 2, (int) $response['cart'][ $this->event_id ]['standard'] );

		$stored = mt_get_transient( 'mt_' . $guest_id . '_cart' );
		$this->assertSame( 2, (int) $stored[ $this->event_id ]['standard'] );
	}

	/**
	 * Logged-in users should save cart data in user meta.
	 */
	public function test_logged_in_add_to_cart_saves_cart_to_user_meta() {
		$user_id          = self::factory()->user->create();
		$this->user_ids[] = $user_id;
		$guest_id         = $this->set_guest_cart_id();

		wp_set_current_user( $user_id );

		$response = mt_update_cart( $this->get_initial_add_to_cart_payload( 1 ) );

		$this->assertTrue( (bool) $response['success'] );
		$this->assertSame( 1, (int) $response['cart'][ $this->event_id ]['standard'] );

		$stored = get_user_meta( $user_id, '_mt_user_cart', true );
		$this->assertSame( 1, (int) $stored[ $this->event_id ]['standard'] );
		$this->assertEmpty( mt_get_transient( 'mt_' . $guest_id . '_cart' ) );
	}

	/**
	 * A guest cart should be available after login and then persist to user meta on update.
	 */
	public function test_guest_cart_is_available_after_login_and_updates_to_user_meta() {
		wp_set_current_user( 0 );
		$guest_id = $this->set_guest_cart_id();
		mt_update_cart( $this->get_initial_add_to_cart_payload( 1 ) );

		$guest_stored = mt_get_transient( 'mt_' . $guest_id . '_cart' );
		$this->assertSame( 1, (int) $guest_stored[ $this->event_id ]['standard'] );

		$user_id          = self::factory()->user->create();
		$this->user_ids[] = $user_id;
		wp_set_current_user( $user_id );

		$cart_after_login = mt_get_cart();
		$this->assertSame( 1, (int) $cart_after_login[ $this->event_id ]['standard'] );

		$response = mt_update_cart( $this->get_update_payload( 3 ) );
		$this->assertTrue( (bool) $response['success'] );
		$this->assertSame( 3, (int) $response['cart'][ $this->event_id ]['standard'] );

		$user_stored = get_user_meta( $user_id, '_mt_user_cart', true );
		$this->assertSame( 3, (int) $user_stored[ $this->event_id ]['standard'] );
	}

	/**
	 * AJAX add-to-cart should work for guests and save cart data in transient storage.
	 */
	public function test_ajax_guest_add_to_cart_updates_guest_cart() {
		wp_set_current_user( 0 );
		$guest_id = $this->set_guest_cart_id();

		$response = $this->dispatch_ajax_add_to_cart( 2, false );

		$this->assertTrue( (bool) $response['success'] );
		$this->assertSame( $this->event_id, (int) $response['event_id'] );
		$this->assertSame( 2, (int) $response['data'] );

		$stored = mt_get_transient( 'mt_' . $guest_id . '_cart' );
		$this->assertSame( 2, (int) $stored[ $this->event_id ]['standard'] );
	}

	/**
	 * AJAX add-to-cart should work for authenticated users and save data to user meta.
	 */
	public function test_ajax_logged_in_add_to_cart_updates_user_cart() {
		$user_id          = self::factory()->user->create();
		$this->user_ids[] = $user_id;
		wp_set_current_user( $user_id );

		$response = $this->dispatch_ajax_add_to_cart( 1, true );

		$this->assertTrue( (bool) $response['success'] );
		$this->assertSame( $this->event_id, (int) $response['event_id'] );
		$this->assertSame( 1, (int) $response['data'] );

		$stored = get_user_meta( $user_id, '_mt_user_cart', true );
		$this->assertSame( 1, (int) $stored[ $this->event_id ]['standard'] );
	}
}
