<?php
/**
 * Ticket transfer across payments tests.
 *
 * @package My Tickets
 */

/**
 * Verify ticket reassignment between payment records.
 */
class Tests_My_Tickets_Ticket_Payment_Transfer extends WP_UnitTestCase {
	/**
	 * Build basic registration data.
	 *
	 * @param int $tickets Number of available tickets.
	 * @param int $sold Number of sold tickets.
	 *
	 * @return array
	 */
	private function get_registration( $tickets = 20, $sold = 0 ) {
		return array(
			'counting_method' => 'discrete',
			'total'           => 'inherit',
			'prices'          => array(
				'standard' => array(
					'label'   => 'Standard',
					'price'   => 25,
					'tickets' => $tickets,
					'sold'    => $sold,
					'close'   => '',
				),
			),
		);
	}

	/**
	 * Create a published event fixture with registration configuration.
	 *
	 * @param string $title Event title.
	 * @param int    $sold Sold ticket count to initialize.
	 *
	 * @return int
	 */
	private function create_event( $title, $sold = 0 ) {
		$event_id = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);

		update_post_meta( $event_id, '_mt_registration_options', $this->get_registration( 20, $sold ) );

		return $event_id;
	}

	/**
	 * Create a payment fixture.
	 *
	 * @param string $title Payment title.
	 *
	 * @return int
	 */
	private function create_payment( $title ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'mt-payments',
				'post_status' => 'draft',
				'post_title'  => $title,
			)
		);
	}

	/**
	 * Seed an existing ticket attached to a source payment/event pair.
	 *
	 * @param int    $event_id Source event ID.
	 * @param int    $payment_id Source payment ID.
	 * @param string $ticket_id Ticket identifier.
	 * @param int    $price Ticket price.
	 */
	private function seed_ticket( $event_id, $payment_id, $ticket_id, $price = 25 ) {
		$purchase_data = array(
			$event_id => array(
				'standard' => array(
					'count' => 1,
					'price' => $price,
				),
			),
		);

		$event_purchase_data = array(
			$payment_id => array(
				'standard' => array(
					'count' => 1,
					'price' => $price,
				),
			),
		);

		add_post_meta( $event_id, '_ticket', $ticket_id );
		update_post_meta(
			$event_id,
			'_' . $ticket_id,
			array(
				'type'        => 'standard',
				'price'       => $price,
				'purchase_id' => $payment_id,
			)
		);
		update_post_meta( $payment_id, '_purchased', $purchase_data );
		update_post_meta( $event_id, '_purchase', $event_purchase_data );
	}

	/**
	 * A ticket can be removed from one payment and re-added to another for the same event.
	 */
	public function test_ticket_can_be_reassigned_to_another_payment_on_same_event() {
		$event_id       = $this->create_event( 'Transfer Event', 1 );
		$source_payment = $this->create_payment( 'Source Payment' );
		$target_payment = $this->create_payment( 'Target Payment' );
		$ticket_id      = 'ticket-transfer-same-event-1';

		$this->seed_ticket( $event_id, $source_payment, $ticket_id );

		$ticket_data = get_post_meta( $event_id, '_' . $ticket_id, true );
		$removed     = mt_remove_ticket( $event_id, $ticket_id, $ticket_data, $source_payment );
		$this->assertTrue( $removed );

		$ticket_data['purchase_id'] = $target_payment;
		$added                      = mt_add_ticket( $event_id, $ticket_id, $ticket_data, $target_payment );
		$this->assertTrue( $added );

		$source_purchase = get_post_meta( $source_payment, '_purchased' );
		$target_purchase = get_post_meta( $target_payment, '_purchased' );
		$event_purchase  = get_post_meta( $event_id, '_purchase' );
		$registration    = get_post_meta( $event_id, '_mt_registration_options', true );
		$moved_ticket    = get_post_meta( $event_id, '_' . $ticket_id, true );

		$this->assertSame( 0, (int) $source_purchase[0][ $event_id ]['standard']['count'] );
		$this->assertSame( 1, (int) $target_purchase[0][ $event_id ]['standard']['count'] );
		$this->assertSame( 0, (int) $event_purchase[0][ $source_payment ]['standard']['count'] );
		$this->assertSame( 1, (int) $event_purchase[1][ $target_payment ]['standard']['count'] );
		$this->assertSame( 1, (int) $registration['prices']['standard']['sold'] );
		$this->assertSame( $target_payment, (int) $moved_ticket['purchase_id'] );
	}

	/**
	 * A ticket can be moved to a different event and attached to a new payment.
	 */
	public function test_ticket_can_be_moved_to_different_event_and_payment() {
		$source_event   = $this->create_event( 'Source Event', 1 );
		$target_event   = $this->create_event( 'Target Event', 0 );
		$source_payment = $this->create_payment( 'Source Payment Two' );
		$target_payment = $this->create_payment( 'Target Payment Two' );
		$ticket_id      = 'ticket-transfer-cross-event-1';

		$this->seed_ticket( $source_event, $source_payment, $ticket_id );

		$ticket_data = get_post_meta( $source_event, '_' . $ticket_id, true );
		$removed     = mt_remove_ticket( $source_event, $ticket_id, $ticket_data, $source_payment );
		$this->assertTrue( $removed );

		$ticket_data['purchase_id'] = $target_payment;
		$added                      = mt_add_ticket( $target_event, $ticket_id, $ticket_data, $target_payment );
		$this->assertTrue( $added );

		$source_registration = get_post_meta( $source_event, '_mt_registration_options', true );
		$target_registration = get_post_meta( $target_event, '_mt_registration_options', true );
		$source_ticket_meta  = get_post_meta( $source_event, '_' . $ticket_id, true );
		$target_ticket_meta  = get_post_meta( $target_event, '_' . $ticket_id, true );
		$target_purchase     = get_post_meta( $target_payment, '_purchased' );

		$this->assertFalse( $source_ticket_meta );
		$this->assertSame( 0, (int) $source_registration['prices']['standard']['sold'] );
		$this->assertSame( 1, (int) $target_registration['prices']['standard']['sold'] );
		$this->assertSame( $target_payment, (int) $target_ticket_meta['purchase_id'] );
		$this->assertSame( 1, (int) $target_purchase[0][ $target_event ]['standard']['count'] );
	}

	/**
	 * A ticket can be moved to a different event while remaining on the same payment.
	 */
	public function test_ticket_can_be_moved_to_different_event_on_same_payment() {
		$source_event = $this->create_event( 'Source Event Same Payment', 1 );
		$target_event = $this->create_event( 'Target Event Same Payment', 0 );
		$payment_id   = $this->create_payment( 'Shared Payment' );
		$ticket_id    = 'ticket-transfer-same-payment-cross-event-1';

		$this->seed_ticket( $source_event, $payment_id, $ticket_id );

		$result = mt_move_ticket( $payment_id, $source_event, $target_event, $ticket_id );

		$this->assertTrue( $result['added'] );
		$this->assertTrue( $result['removed'] );

		$source_registration = get_post_meta( $source_event, '_mt_registration_options', true );
		$target_registration = get_post_meta( $target_event, '_mt_registration_options', true );
		$source_ticket_meta  = get_post_meta( $source_event, '_' . $ticket_id, true );
		$target_ticket_meta  = get_post_meta( $target_event, '_' . $ticket_id, true );
		$purchase_entries    = get_post_meta( $payment_id, '_purchased' );

		$source_count = null;
		$target_count = null;
		foreach ( $purchase_entries as $entry ) {
			if ( isset( $entry[ $source_event ]['standard']['count'] ) ) {
				$source_count = (int) $entry[ $source_event ]['standard']['count'];
			}
			if ( isset( $entry[ $target_event ]['standard']['count'] ) ) {
				$target_count = (int) $entry[ $target_event ]['standard']['count'];
			}
		}

		$this->assertFalse( $source_ticket_meta );
		$this->assertSame( $payment_id, (int) $target_ticket_meta['purchase_id'] );
		$this->assertSame( 0, (int) $source_registration['prices']['standard']['sold'] );
		$this->assertSame( 1, (int) $target_registration['prices']['standard']['sold'] );
		$this->assertSame( 0, $source_count );
		$this->assertSame( 1, $target_count );
	}
}
