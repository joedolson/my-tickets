<?php
/**
 * Tests for report CSV row/header generation.
 *
 * @package My Tickets
 */

/**
 * Verify CSV-ready data structures produced by mt-reports.php, including custom fields.
 */
class Tests_My_Tickets_Reports_Csv extends WP_UnitTestCase {

	/**
	 * Remove any custom field filter and GET params left over from a test.
	 */
	public function tearDown(): void {
		remove_filter( 'mt_custom_fields', array( $this, 'add_test_custom_field' ), 10 );
		unset( $_GET['event_id'], $_GET['mt_start'], $_GET['mt_end'] );
		parent::tearDown();
	}

	/**
	 * Register a single custom report field with a report_callback.
	 *
	 * @param array  $fields Existing custom fields.
	 * @param string $context Field context.
	 *
	 * @return array
	 */
	public function add_test_custom_field( $fields, $context ) {
		if ( 'reports' === $context ) {
			$fields['_test_custom_field'] = array(
				'title'           => 'Test Custom Field',
				'report_callback' => function ( $payment_id ) {
					return 'Test-Value-' . $payment_id;
				},
			);
		}

		return $fields;
	}

	/**
	 * Create a published mt-payments post with the given meta and date.
	 *
	 * @param array  $meta Post meta, keyed by meta key.
	 * @param string $date Post date, Y-m-d format.
	 *
	 * @return int
	 */
	private function create_payment_post( $meta, $date = '2026-01-01' ) {
		$payment_id = self::factory()->post->create(
			array(
				'post_type'   => 'mt-payments',
				'post_status' => 'publish',
				'post_date'   => $date . ' 00:00:00',
			)
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $payment_id, $key, $value );
		}

		return $payment_id;
	}

	/**
	 * Test: mt_set_column_headers() must return an array of labels for CSV, inserting custom
	 * field labels immediately before the final base column.
	 */
	public function test_set_column_headers_csv_returns_array_with_custom_fields_before_last_column() {
		$headers = mt_get_column_headers( 'purchases', 'csv' );
		$custom  = array(
			'_custom' => array( 'title' => 'Custom Field' ),
		);

		$cols = mt_set_column_headers( $headers, 'csv', $custom );

		$this->assertIsArray( $cols );
		$this->assertSame( 'Last Name', $cols[0] );
		$this->assertSame( 'Custom Field', $cols[ count( $cols ) - 2 ] );
		$this->assertSame( 'Notes', end( $cols ) );
	}

	/**
	 * Test: mt_get_unique_customers() should dedupe by email (case-insensitively), aggregate
	 * totals/counts, and ignore non-publish or non-payment posts.
	 */
	public function test_get_unique_customers_deduplicates_and_aggregates_by_email() {
		$email = 'jane@example.com';
		$this->create_payment_post(
			array(
				'_email'      => $email,
				'_first_name' => 'Jane',
				'_last_name'  => 'Doe',
				'_total_paid' => 25,
			),
			'2026-01-01'
		);
		$this->create_payment_post(
			array(
				'_email'      => strtoupper( $email ),
				'_first_name' => 'Jane',
				'_last_name'  => 'Doe',
				'_total_paid' => 15,
			),
			'2026-02-01'
		);
		$this->create_payment_post(
			array(
				'_email'      => 'other@example.com',
				'_total_paid' => 5,
			),
			'2026-01-15'
		);
		// A draft payment sharing the same email must not be counted.
		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'mt-payments',
				'post_status' => 'draft',
			)
		);
		update_post_meta( $draft, '_email', $email );

		$customers = mt_get_unique_customers();
		$key       = strtolower( $email );

		$this->assertArrayHasKey( $key, $customers );
		$this->assertSame( 2, $customers[ $key ]['payments'] );
		$this->assertEquals( 40, $customers[ $key ]['total'] );
		$this->assertSame( '2026-02-01', $customers[ $key ]['last_date'] );
		$this->assertArrayHasKey( 'other@example.com', $customers );
	}

	/**
	 * Test: mt_purchases() CSV rows should be arrays of raw values with custom field values
	 * inserted immediately before the final column.
	 */
	public function test_mt_purchases_csv_row_includes_custom_field_value() {
		add_filter( 'mt_custom_fields', array( $this, 'add_test_custom_field' ), 10, 2 );

		$event_id   = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		$payment_id = $this->create_payment_post(
			array(
				'_first_name'       => 'Jane',
				'_last_name'        => 'Doe',
				'_email'            => 'jane@example.com',
				'_is_paid'          => 'Completed',
				'_ticketing_method' => 'eticket',
				'_total_paid'       => 50,
			)
		);
		update_post_meta(
			$event_id,
			'_purchase',
			array(
				$payment_id => array(
					'standard' => array(
						'count' => 2,
						'price' => 25,
					),
				),
			)
		);

		$_GET['event_id'] = $event_id;
		$data             = mt_purchases( $event_id, array( 'include_failed' => false ) );

		$this->assertIsArray( $data );
		$rows = $data['report']['csv']['Completed'];
		$this->assertCount( 1, $rows );

		$row            = $rows[0];
		$header_columns = array_keys( mt_get_column_headers( 'purchases', 'csv' ) );
		$last_index     = array_search( 'mt-last', $header_columns, true );
		$first_index    = array_search( 'mt-first', $header_columns, true );
		$email_index    = array_search( 'mt-email', $header_columns, true );
		$id_index       = array_search( 'mt-id', $header_columns, true );

		$this->assertIsArray( $row );
		$this->assertCount( count( $header_columns ) + 1, $row );
		$this->assertSame( 'Doe', $row[ $last_index ] );
		$this->assertSame( 'Jane', $row[ $first_index ] );
		$this->assertSame( 'jane@example.com', $row[ $email_index ] );
		$this->assertSame( $payment_id, $row[ $id_index ] );
		$this->assertSame( 'Test-Value-' . $payment_id, $row[ count( $row ) - 2 ] );
	}

	/**
	 * Test: mt_get_tickets() CSV rows should be arrays, with the ticket ID as the first value.
	 */
	public function test_mt_get_tickets_csv_row_structure() {
		$event_id   = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		$payment_id = $this->create_payment_post(
			array(
				'_first_name' => 'John',
				'_last_name'  => 'Smith',
				'_is_paid'    => 'Completed',
			)
		);
		$ticket_id  = 'tkt' . $payment_id;

		add_post_meta( $event_id, '_ticket', $ticket_id );
		update_post_meta(
			$event_id,
			'_' . $ticket_id,
			array(
				'type'        => 'standard',
				'price'       => 25,
				'purchase_id' => $payment_id,
			)
		);

		$data = mt_get_tickets( $event_id );

		$this->assertCount( 1, $data['csv'] );
		$row            = $data['csv'][0];
		$header_columns = array_keys( mt_get_column_headers( 'tickets', 'csv' ) );
		$last_index     = array_search( 'mt-last', $header_columns, true );
		$status_index   = array_search( 'mt-status', $header_columns, true );

		$this->assertIsArray( $row );
		$this->assertCount( count( $header_columns ), $row );
		$this->assertSame( $ticket_id, $row[0] );
		$this->assertSame( 'Smith', $row[ $last_index ] );
		$this->assertSame( 'Completed', $row[ $status_index ] );
	}

	/**
	 * Test: mt_get_report_data_by_time() should append custom field header/values at the end
	 * of each CSV row (including the header row).
	 */
	public function test_mt_get_report_data_by_time_appends_custom_field_column() {
		add_filter( 'mt_custom_fields', array( $this, 'add_test_custom_field' ), 10, 2 );

		$payment_id = $this->create_payment_post(
			array(
				'_first_name'       => 'Time',
				'_last_name'        => 'Tester',
				'_email'            => 'time@example.com',
				'_is_paid'          => 'Completed',
				'_ticketing_method' => 'eticket',
				'_total_paid'       => 30,
				'_purchased'        => array(),
			),
			'2026-01-15'
		);

		$_GET['mt_start'] = '2026-01-01';
		$_GET['mt_end']   = '2026-01-31';

		$report = mt_get_report_data_by_time();

		$header = $report['csv'][0];
		$this->assertContains( 'Test Custom Field', $header );
		$this->assertSame( 'Test Custom Field', end( $header ) );

		$data_row = null;
		foreach ( array_slice( $report['csv'], 1 ) as $row ) {
			if ( 'Tester' === $row[0] ) {
				$data_row = $row;
				break;
			}
		}

		$this->assertNotNull( $data_row );
		$this->assertSame( 'Test-Value-' . $payment_id, end( $data_row ) );
	}

	/**
	 * Test: mt_purchases() must preserve special characters (quotes, commas, apostrophes,
	 * multibyte, and markup) in row values, and those values must round-trip losslessly
	 * through fputcsv()/fgetcsv().
	 */
	public function test_mt_purchases_csv_row_preserves_special_characters_and_round_trips_through_fputcsv() {
		add_filter( 'mt_custom_fields', array( $this, 'add_test_custom_field' ), 10, 2 );

		$tricky_last  = 'O\'Brien, "The Great"';
		$tricky_first = 'Müller 日本語 😀';

		$event_id   = self::factory()->post->create(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);
		$payment_id = $this->create_payment_post(
			array(
				'_first_name'       => $tricky_first,
				'_last_name'        => $tricky_last,
				'_email'            => 'jane@example.com',
				'_is_paid'          => 'Completed',
				'_ticketing_method' => 'eticket',
				'_total_paid'       => 50,
			)
		);
		update_post_meta(
			$event_id,
			'_purchase',
			array(
				$payment_id => array(
					'standard' => array(
						'count' => 2,
						'price' => 25,
					),
				),
			)
		);

		$_GET['event_id'] = $event_id;
		$data             = mt_purchases( $event_id, array( 'include_failed' => false ) );
		$row              = $data['report']['csv']['Completed'][0];

		$header_columns = array_keys( mt_get_column_headers( 'purchases', 'csv' ) );
		$last_index     = array_search( 'mt-last', $header_columns, true );
		$first_index    = array_search( 'mt-first', $header_columns, true );

		$this->assertSame( $tricky_last, $row[ $last_index ] );
		$this->assertSame( $tricky_first, $row[ $first_index ] );

		$escaped = mt_csv_escape_row( $row );
		$fh      = fopen( 'php://temp', 'r+' );
		fputcsv( $fh, $escaped );
		rewind( $fh );
		$parsed = fgetcsv( $fh );
		fclose( $fh );

		$this->assertSame( $tricky_last, $parsed[ $last_index ] );
		$this->assertSame( $tricky_first, $parsed[ $first_index ] );
	}

	/**
	 * mt_get_unique_customers() must not break, and must preserve the raw value, when
	 * an email contains SQL metacharacters (protected by $wpdb->prepare()).
	 */
	public function test_get_unique_customers_handles_email_with_sql_metacharacters() {
		$malicious_email = "o'brien'); DROP TABLE wp_posts;--@example.com";
		$this->create_payment_post(
			array(
				'_email'      => $malicious_email,
				'_first_name' => 'Jane',
				'_last_name'  => 'Doe',
				'_total_paid' => 10,
			)
		);

		$customers = mt_get_unique_customers();
		$key       = strtolower( $malicious_email );

		$this->assertArrayHasKey( $key, $customers );
		$this->assertSame( $malicious_email, $customers[ $key ]['email'] );
		$this->assertSame( 1, $customers[ $key ]['payments'] );
	}

	/**
	 * Test: mt_csv_escape_row() must prefix values that could be interpreted as spreadsheet
	 * formulas (CSV/formula injection) while leaving ordinary values untouched.
	 */
	public function test_csv_escape_row_neutralizes_formula_injection_vectors() {
		$row = array(
			'=SUM(A1:A2)',
			'+1+1',
			'-1+1',
			'@SUM(1+1)',
			"\tHidden",
			'Regular value',
			42,
			null,
		);

		$escaped = mt_csv_escape_row( $row );

		$this->assertSame( "'=SUM(A1:A2)", $escaped[0] );
		$this->assertSame( "'+1+1", $escaped[1] );
		$this->assertSame( "'-1+1", $escaped[2] );
		$this->assertSame( "'@SUM(1+1)", $escaped[3] );
		$this->assertSame( "'\tHidden", $escaped[4] );
		$this->assertSame( 'Regular value', $escaped[5] );
		$this->assertSame( 42, $escaped[6] );
		$this->assertNull( $escaped[7] );
	}
}
