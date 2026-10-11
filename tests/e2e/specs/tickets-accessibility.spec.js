const { test, expect } = require( '../config/a11y-test' );
const { createTicketedEvent } = require( '../config/fixtures' );
const { setMtSetting } = require( '../config/wp-cli' );

test.describe( 'My Tickets accessibility', () => {
	test( 'add to cart form has no serious or critical axe violations', async ( {
		page,
		requestUtils,
		makeAxeBuilder,
	} ) => {
		const event = await createTicketedEvent( requestUtils, 'My Tickets A11y Test' );

		await page.goto( event.link );
		await page.locator( 'form.ticket-orders' ).waitFor();

		const accessibilityScanResults = await makeAxeBuilder( { page } )
			.include( 'form.ticket-orders' )
			.analyze();

		const seriousOrCritical = accessibilityScanResults.violations.filter( ( violation ) =>
			[ 'serious', 'critical' ].includes( violation.impact )
		);

		expect( seriousOrCritical ).toEqual( [] );
	} );

	test( 'cart and payment views have no serious or critical axe violations', async ( {
		page,
		requestUtils,
		makeAxeBuilder,
	} ) => {
		// Enable every optional input and the offline gateway.
		setMtSetting( 'mt_phone', 'on' );
		setMtSetting( 'mt_vat', 'on' );
		setMtSetting( 'mt_collect_shipping', 'true' );
		setMtSetting( 'mt_gateway', [ 'offline' ] );
		setMtSetting( 'mt_default_gateway', 'offline' );
		setMtSetting( 'mt_ticketing', {
			printable: 'Printable',
			eticket: 'E-ticket',
			postal: 'Postal mail',
			willcall: 'Will call',
		} );

		const event = await createTicketedEvent( requestUtils, 'My Tickets A11y Cart Test' );

		await page.goto( event.link );
		const form = page.locator( 'form.ticket-orders' );
		await form.locator( 'input[name^="mt_tickets"]' ).first().fill( '1' );
		await form.locator( 'button[name="mt_add_to_cart"]' ).click();
		await page.locator( '.mt-order .mt-response' ).getByRole( 'link', { name: 'Checkout' } ).click();

		const cart = page.locator( '.mt_cart form' );
		await expect( cart ).toBeVisible();
		await expect( cart.locator( '#mt_vat' ) ).toBeVisible();
		await expect( cart.locator( '#mt_phone' ) ).toBeVisible();

		const scan = async ( selector ) => {
			const results = await makeAxeBuilder( { page } ).include( selector ).analyze();
			return results.violations.filter( ( violation ) =>
				[ 'serious', 'critical' ].includes( violation.impact )
			);
		};

		expect( await scan( '.mt_cart' ) ).toEqual( [] );

		await cart.locator( '#mt_fname' ).fill( 'Ada' );
		await cart.locator( '#mt_lname' ).fill( 'Lovelace' );
		await cart.locator( '#mt_email' ).fill( 'ada@example.com' );
		await cart.locator( '#mt_phone' ).fill( '555-0100' );
		await cart.locator( '#mt_vat' ).fill( 'GB123456789' );
		await cart.locator( 'p.mt_submit input[type="submit"]' ).click();

		// Payment view: confirmation of entered data plus the offline gateway form with address fields.
		await expect( page.locator( '.mt-confirm-info' ) ).toBeVisible();
		await expect( page.locator( '.mt-shipping-address' ) ).toBeVisible();

		expect( await scan( '.entry-content, main' ) ).toEqual( [] );
	} );
} );
