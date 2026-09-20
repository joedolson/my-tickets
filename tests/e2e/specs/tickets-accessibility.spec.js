const { test, expect } = require( '../config/a11y-test' );
const { createTicketedEvent } = require( '../config/fixtures' );

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
} );
