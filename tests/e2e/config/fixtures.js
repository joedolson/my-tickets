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
 * Create a published post with ticket sales enabled and a `[ticket]` shortcode
 * pointed at itself, so the Add to Cart form renders on the front end.
 *
 * @param {Object} requestUtils The @wordpress/e2e-test-utils-playwright RequestUtils fixture.
 * @param {string} title        Post title.
 *
 * @return {Promise<{id: number, link: string}>} The created post's ID and permalink.
 */
async function createTicketedEvent( requestUtils, title ) {
	const post = await requestUtils.createPost( {
		title,
		status: 'publish',
		content: '',
	} );

	setPostMeta( post.id, '_mt_registration_options', REGISTRATION_OPTIONS );

	const updated = await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/posts/${ post.id }`,
		data: { content: `[ticket event="${ post.id }"]` },
	} );

	return { id: post.id, link: updated.link };
}

module.exports = { createTicketedEvent };
