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

	test( 'clicking Add to Cart shows a cart updated notification', async ( { page, requestUtils } ) => {
		const event = await createTicketedEvent( requestUtils, 'My Tickets E2E Cart Notice' );

		await page.goto( event.link );

		const form = page.locator( 'form.ticket-orders' );
		await form.locator( 'input[name^="mt_tickets"]' ).first().fill( '1' );
		await form.locator( 'button[name="mt_add_to_cart"]' ).click();

		const response = page.locator( '.mt-order .mt-response' );
		await expect( response ).toBeVisible();
		await expect( response ).toContainText( 'Your cart is updated' );
		await expect( response.getByRole( 'link', { name: 'Checkout' } ) ).toBeVisible();
	} );

	test( 'increment and decrement buttons change the ticket quantity within limits', async ( { page, requestUtils } ) => {
		const event = await createTicketedEvent( requestUtils, 'My Tickets E2E Quantity Buttons' );

		await page.goto( event.link );

		const form = page.locator( 'form.ticket-orders' );
		const quantity = form.locator( 'input[name^="mt_tickets"]' ).first();
		const increment = form.locator( '.mt-increment' ).first();
		const decrement = form.locator( '.mt-decrement' ).first();

		await expect( quantity ).toHaveValue( '0' );

		await increment.click();
		await expect( quantity ).toHaveValue( '1' );
		await increment.click();
		await expect( quantity ).toHaveValue( '2' );

		await decrement.click();
		await expect( quantity ).toHaveValue( '1' );

		// Does not go below the minimum.
		await decrement.click();
		await decrement.click();
		await expect( quantity ).toHaveValue( '0' );

		// Does not exceed the maximum.
		await quantity.fill( await quantity.getAttribute( 'max' ) );
		await increment.click();
		await expect( quantity ).toHaveValue( await quantity.getAttribute( 'max' ) );
	} );
} );
