const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const { createTicketedEvent } = require( '../config/fixtures' );

test.describe( 'My Tickets front end', () => {
	test( 'add to cart form renders for an event with ticket sales', async ( { page, requestUtils } ) => {
		const event = await createTicketedEvent( requestUtils, 'My Tickets E2E Test' );

		await page.goto( event.link );

		await expect( page.locator( 'form.ticket-orders' ) ).toBeVisible();
		await expect( page.locator( 'form.ticket-orders' ) ).toContainText( 'Standard' );
		await expect(
			page.locator( 'form.ticket-orders button[name="mt_add_to_cart"]' )
		).toBeVisible();
	} );
} );
