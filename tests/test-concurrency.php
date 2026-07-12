<?php
/**
 * Concurrency protection tests.
 *
 * @package My Tickets
 */

/**
 * Verify stock claim and duplicate-processing safeguards.
 */
class Tests_My_Tickets_Concurrency extends WP_UnitTestCase {
	/**
	 * Event ID used by tests.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Payment ID used by tests.
	 *
	 * @var int
	 */
	private $payment_id;

	/**
	 * Set up an event and payment fixture.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->event_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Concurrency Event',
			)
		);

		$this->payment_id = self::factory()->post->create(
			array(
				'post_type'   => 'mt-payments',
				'post_status' => 'draft',
				'post_title'  => 'Concurrency Payment',
			)
		);
	}

	/**
	 * Clean up lock filters after each test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'mt_acquire_db_lock' );
		remove_all_filters( 'mt_release_db_lock' );
		parent::tear_down();
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
	 * Build purchase data for one event.
	 *
	 * @param int $count Number of tickets requested.
	 *
	 * @return array
	 */
	private function get_purchase_data( $count = 1 ) {
		return array(
			$this->event_id => array(
				'standard' => array(
					'count' => $count,
					'price' => 25,
				),
			),
		);
	}

	/**
	 * Competing claims for the last ticket should reject the second claim.
	 */
	public function test_mt_claim_stock_rejects_second_competing_claim() {
		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 1 ) );

		$first = mt_claim_stock_for_purchase( $this->event_id, $this->get_purchase_data( 1 )[ $this->event_id ] );
		$this->assertTrue( $first );

		$second = mt_claim_stock_for_purchase( $this->event_id, $this->get_purchase_data( 1 )[ $this->event_id ] );
		$this->assertWPError( $second );
		$this->assertSame( 'mt_out_of_stock', $second->get_error_code() );
	}

	/**
	 * Duplicate ticket creation calls should not double-claim stock.
	 */
	public function test_mt_create_tickets_is_idempotent_after_first_success() {
		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 2 ) );
		update_post_meta( $this->payment_id, '_purchase_data', $this->get_purchase_data( 1 ) );

		$first = mt_create_tickets( $this->payment_id );
		$this->assertTrue( $first );

		$second = mt_create_tickets( $this->payment_id );
		$this->assertTrue( $second );

		$registration = get_post_meta( $this->event_id, '_mt_registration_options', true );
		$this->assertSame( 1, (int) $registration['prices']['standard']['sold'] );
	}

	/**
	 * Payment lock contention should be treated as retry state, not failed payment.
	 */
	public function test_mt_send_notifications_treats_payment_lock_contention_as_retry() {
		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 2 ) );
		update_post_meta( $this->payment_id, '_purchase_data', $this->get_purchase_data( 1 ) );
		update_post_meta( $this->payment_id, '_is_paid', 'Completed' );
		update_post_meta( $this->payment_id, '_gateway', 'offline' );
		update_post_meta( $this->payment_id, '_email', 'test@example.com' );
		delete_post_meta( $this->payment_id, '_notified' );

		add_filter(
			'mt_acquire_db_lock',
			function ( $acquired, $lock_name ) {
				if ( false !== strpos( $lock_name, 'mt_payment_process_' ) ) {
					return false;
				}

				return $acquired;
			},
			10,
			2
		);

		$details = array(
			'id'    => $this->payment_id,
			'name'  => 'Ticket Buyer',
			'email' => 'test@example.com',
		);

		mt_send_notifications( 'Completed', $details );

		$this->assertSame( 'Completed', get_post_meta( $this->payment_id, '_is_paid', true ) );
		$this->assertSame( '', get_post_meta( $this->payment_id, '_notified', true ) );
	}

	/**
	 * Save-post integration should not mark payment failed or send email when payment lock is contested.
	 */
	public function test_save_post_flow_does_not_send_failed_email_on_payment_lock_contention() {
		$mail_attempts = 0;

		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 2 ) );
		update_post_meta( $this->payment_id, '_purchase_data', $this->get_purchase_data( 1 ) );
		update_post_meta( $this->payment_id, '_is_paid', 'Completed' );
		update_post_meta( $this->payment_id, '_last_status', 'Pending' );
		update_post_meta( $this->payment_id, '_gateway', 'offline' );
		update_post_meta( $this->payment_id, '_email', 'test@example.com' );
		delete_post_meta( $this->payment_id, '_notified' );
		delete_post_meta( $this->payment_id, '_mt_send_email' );

		add_filter(
			'mt_acquire_db_lock',
			function ( $acquired, $lock_name ) {
				if ( false !== strpos( $lock_name, 'mt_payment_process_' ) ) {
					return false;
				}

				return $acquired;
			},
			10,
			2
		);

		add_filter(
			'pre_wp_mail',
			function ( $return_value ) use ( &$mail_attempts ) {
				$mail_attempts++;

				return $return_value;
			},
			10,
			1
		);

		wp_update_post(
			array(
				'ID'          => $this->payment_id,
				'post_status' => 'publish',
				'post_title'  => 'Concurrency Payment Updated',
			)
		);

		remove_all_filters( 'pre_wp_mail' );

		$this->assertSame( 'Completed', get_post_meta( $this->payment_id, '_is_paid', true ) );
		$this->assertSame( '', get_post_meta( $this->payment_id, '_notified', true ) );
		$this->assertSame( 0, $mail_attempts );
		$this->assertEmpty( get_post_meta( $this->payment_id, '_mt_send_email' ) );
	}

	/**
	 * Save-post integration should send and log notification data when no lock contention exists.
	 */
	public function test_save_post_flow_sends_email_when_no_payment_lock_contention() {
		$mail_attempts = 0;

		update_post_meta( $this->event_id, '_mt_registration_options', $this->get_registration( 2 ) );
		update_post_meta( $this->payment_id, '_purchase_data', $this->get_purchase_data( 1 ) );
		update_post_meta( $this->payment_id, '_is_paid', 'Completed' );
		update_post_meta( $this->payment_id, '_last_status', 'Pending' );
		update_post_meta( $this->payment_id, '_gateway', 'offline' );
		update_post_meta( $this->payment_id, '_email', 'test@example.com' );
		delete_post_meta( $this->payment_id, '_notified' );
		delete_post_meta( $this->payment_id, '_mt_send_email' );

		add_filter(
			'pre_wp_mail',
			function () use ( &$mail_attempts ) {
				$mail_attempts++;

				return true;
			},
			10,
			1
		);

		wp_update_post(
			array(
				'ID'          => $this->payment_id,
				'post_status' => 'publish',
				'post_title'  => 'Concurrency Payment Updated Successfully',
			)
		);

		remove_all_filters( 'pre_wp_mail' );

		$this->assertSame( 'Completed', get_post_meta( $this->payment_id, '_is_paid', true ) );
		$this->assertSame( 'true', get_post_meta( $this->payment_id, '_notified', true ) );
		$this->assertGreaterThanOrEqual( 1, $mail_attempts );
		$this->assertNotEmpty( get_post_meta( $this->payment_id, '_mt_send_email' ) );
	}
}
