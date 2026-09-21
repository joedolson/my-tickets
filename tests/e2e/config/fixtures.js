const { setPostMeta } = require( './wp-cli' );

const REGISTRATION_OPTIONS = {
	counting_method: 'discrete',
	total: 'inherit',
	reg_expires: '3',
	sales_type: 'tickets',
	prices: {
		standard: {
			label: 'Standard',
			price: 25,
			tickets: 50,
			sold: 0,
			close: '',
		},
	},
};

/**
 * Create a published page with ticket sales enabled and a `[ticket]` shortcode
 * pointed at itself, so the Add to Cart form renders on the front end.
 *
 * A page is used because My Tickets' default `mt_post_types` setting is
 * `[ 'mc-events', 'page' ]` with `mt_singular` enabled, so a plain `post` is
 * rejected before the form ever renders.
 *
 * @param {Object} requestUtils The @wordpress/e2e-test-utils-playwright RequestUtils fixture.
 * @param {string} title        Page title.
 *
 * @return {Promise<{id: number, link: string}>} The created page's ID and permalink.
 */
async function createTicketedEvent( requestUtils, title ) {
	const page = await requestUtils.createPage( {
		title,
		status: 'publish',
		content: '',
	} );

	setPostMeta( page.id, '_mt_registration_options', REGISTRATION_OPTIONS );

	const updated = await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/pages/${ page.id }`,
		data: { content: `[ticket event="${ page.id }"]` },
	} );

	return { id: page.id, link: updated.link };
}

module.exports = { createTicketedEvent };
