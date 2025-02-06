=== My Tickets - Accessible Event Ticketing ===
Contributors: joedolson
Donate link: https://www.joedolson.com/donate/
Tags: ticket sales, registration, reservations, event tickets, accessibility
Requires at least: 4.9
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv2 or later
Text domain: my-tickets
Stable tag: 2.0.11

My Tickets is a simple, flexible platform for selling event tickets with WordPress.

== Description ==

Sell tickets or take reservations for events easily! My Tickets integrates with <a href="http://wordpress.org/plugins/my-calendar/">My Calendar</a> or can be used for ticket sales as a stand-alone plugin. Sell tickets for box office pick up, shipping, or accept print-at-home and e-tickets for an easy experience for your ticket holders!

My Tickets ships with PayPal Standard payments, so you can sell tickets immediately. You can also take offline payments, to use My Tickets as a reservation tool while you handle payments by mail or at the door.

Explore the <a href="https://www.joedolson.com/my-tickets/add-ons/">premium add-ons for My Tickets</a>!

Premium event ticketing add-ons include:

* Offer a variety of secure ticket payments with premium gateways, including <a href="https://www.joedolson.com/awesome/my-tickets-stripe/">Stripe</a>, <a href="https://www.joedolson.com/awesome/my-tickets-paypal-pro/">PayPal Pro</a>, and <a href="https://www.joedolson.com/awesome/tickets-authorize-net/">Authorize.net</a>.
* Encourage users to support your organization by taking <a href="https://www.joedolson.com/awesome/my-tickets-donations/">in-cart donations</a>.
* Offer your customers special discount opportunities with <a href="https://www.joedolson.com/awesome/my-tickets-discounts/">discount codes and coupons</a>
* Improve conversion and avoid lost opportunities with <a href="https://www.joedolson.com/awesome/my-tickets-waiting-list/">automatic waiting lists</a>.

= Basic Features: =

For buyers:

* Create an account to save your address and shipping preferences
* Automatically convert shopping carts to your account if you log-in after adding purchases
* Buy tickets for multiple events, with multiple ticket types.

For sellers:

* Get reports on ticket sales by time, event, or specific ticket options within an event.
* Easily add new ticket sales from the box office, when somebody pays in person, by phone, or by mail.
* Use your mobile phone and a standard QRCode reader to verify print-at-home tickets or e-tickets
* Send email to a single purchaser with questions about their ticket purchase, or mass email all purchasers for an event.
* Select default event settings grouped by audience type (Adult, Student, Child), seating sections (Section 1, Section 2, Section 3) or individual event dates.
* Offer member-only discounts for logged-in users
* General Admission tickets: Sell tickets for events without dates, valid for days, weeks, or months after purchase.

My Tickets is flexible and easy to extend - check out the <a href="https://www.joedolson.com/my-tickets/add-ons/">library of Premium add-ons</a>!

= Accessibility =

My Tickets is built with accessibility in mind, and tested using assistive technology.

= Documentation =

Read the <a href="http://docs.joedolson.com/my-tickets/">My Tickets online documentation</a>.

== Installation ==

1. Upload the `/my-tickets/` directory into your WordPress plugins directory.

2. Activate the plugin on your WordPress plugins page

3. Configure My Tickets using the following pages in the admin panel:

   My Tickets -> Settings
   My Tickets -> Payment Settings
   My Tickets -> Ticket Settings
   My Tickets -> Reports
   My Tickets -> Ticketing Help

4. With <a href="https://wordpress.org/plugins/my-calendar/">My Calendar</a>, add ticketing to an event. Without My Calendar, choose what post types will support tickets from My Tickets -> Settings, and add ticketing to any post or Page!

== Changelog ==

= 2.0.11 =

* Bug fix: Event lookup when moving tickets queried posts instead of ticketed events.
* Bug fix: Fix logic so ticket sales can be turned off.
* Bug fix: Ensure expire_date is numeric before turning into a date.
* Bug fix: Check for the loop before rendering the shopping cart.
* Bug fix: Don't modify the total number of tickets when moving tickets between events.
* Change: Add general class for shopping cart field rows to make layout easier to style.
* Change: Sort reports event list alphabetically by event title.
* Change: Expire unique ID cookies after the current cart life plus one week. The unique ID should always be longer than the cart.
* Docs: Add filter documentation for a handful of additional filters.

= 2.0.10 =

* Security: Fixes a Broken Access Control in reporting. Props Mika & Patchstack.
* Bug fix: Broken ID attribute in metabox ticket creation form.
* Replace uses of `json_encode` with `wp_json_encode`.
* Add file size and name verification before importing settings files.
* Use WP_Filesystem to handle settings imports.

= 2.0.9 =

* Bug fix: Limit how long a cart expiration can be extended.
* Filter: add `mt_virtual_inventory` filter to dynamically modify the virtual inventory.

= 2.0.8 =

* Bug fix: Add to cart forms checked submitted count incorrectly for making availability comparisons.
* Improved JS event isolation for individual ticketing forms.

= 2.0.7 =

* Bug fix: Don't send ticket type closed messages if they were closed well in the past.
* Bug fix: Don't attempt to display ticket fields if options array is incomplete.
* Bug fix: Ensure returned close date is an integer.

= 2.0.6 =

* Remove textdomain loader (Obsolete since WP 4.6)
* Bug fix: issue where hidden ticket creation fields still marked as required.
* Bug fix: Update payment data when moving tickets to a different ticket type.
* Bug fix: Incorrect variable reference in `mt_handle_expiration_status()`.
* Bug fix: Ensure remaining count doesn't display negative numbers.
* Bug fix: Check virtual inventory when adding tickets to cart, to see if it has changed since page was loaded.
* Bug fix: Remove `remove_filter` on add to cart form.
* Change: Set default installation behavior to only display forms on singular views.

= 2.0.5 =

* Bug fix: Ticket creation form should not show in My Calendar Pro's front-end submissions form.
* Change: `[ticket]` shortcode no longer requires the current page's ID to render the form.
* Feature: `[ticket_venue]` shortcode to render an event's hcard on the page.
* Feature: Attribute 'location' added to `[ticket]` shortcode with options 'false', 'before', and 'after'.

= 2.0.4 =

* Bug fix: Hiding remaining tickets failed because I passed the summary value instead of the individual event value.
* Bug fix: Support using HTML in the offline gateway payment notes field. Props https://github.com/Martin-OHV
* Accessibility: Save address action should use a button.
* Docs: Added filter documentation for add to cart form filters.

= 2.0.3 =

* Bug fix: Individual event type email notifications not sent if entire event has closed.
* Bug fix: Current ticket group not marked as selected in reports.
* Bug fix: Custom field headers inserted one space too far to the left.
* Add: filter `mt_field_paramters` to dynamically change custom field characteristics.
* Add: filter `mt_after_remaining_text` to append text after the remaining tickets in cart.
* Add: action `mt_custom_field_saved` executed after a custom field is saved to a payment.
* Add: support for `report_callback` for custom fields for displaying in reports.
* Docs: Document `mt_cart_custom_fields` filter.
* Docs: Document `mt_show_in_cart_fields` filter.
* Docs: Document `mt_purchase_completed` action.
* Change: Pass additional arguments to `mt_show_in_cart_fields` filter. 
* Change: Pass payment ID into `mt_generate_cart_table()` when available.
* Change: Remove filter on `the_content` after running to prevent duplication.

= 2.0.2 =

* Bug fix: Reports on tickets didn't limit by ticket groups.
* Bug fix: If label not retrieved from post meta, admin tickets render with incorrect time.
* Bug fix: Don't use the virtual inventory when sending admin notifications.
* Bug fix: If a ticket type was not already in the sold cart, switching a ticket to that ticket type failed in the admin.
* Bug fix: If 'all' passed as ticket type, tickets reports were empty.
* Bug fix: Moving tickets between events was broken due to invalid falsey value check.
* Bug fix: Don't run ticket type expired action during cart processing, only check existing values.
* Documentation: Add filter docs on closed and sold out admin email filters.

= 2.0.1 =

* Enhancement: Add email & purchaser name to purchase when moving a cart from public to admin.
* Enhancement: Styling for 'create admin cart' link.
* Enhancement: Move admin cart listing to top of create payment screen.
* Change: Update readme for new version.
* Change: Add field styling for inline report forms.
* Change: Move daily cron to an hourly cron.
* Document: `mt_confirmed_transaction` filter.
* Document: `mt_successful_payment` action.
* Document: `mt_link_title` filter.
* Bug fix: Move price label out of remaining tickets filter.
* Bug fix: Total incrementor displayed in wrong location in purchase list.
* Bug fix: Apply cart styling in admin cart creation.
* Bug fix: Send Playground preview link to page list rather than direct to edit, as page ID not dependable.
* Bug fix: Add to cart was hidden if ticket count selector changed to `select` input.
* Bug fix: Reports of purchases with multiple ticket types only listed the last type purchased.
* Bug fix: Function to update virtual inventory failed during cron because data about what to remove was not passed.
* Bug fix: If mt_is_cart_expired() is executed with a cart ID and a logged-in user, the cart ID was ignored.
* Bug fix: After sales are closed, only display the real inventory, not the virtual inventory.

= 2.0.0 =

* Feature: Set dates as ticket types with independent expirations.
* Feature: Introduce virtual inventory option to remove tickets from inventory when added to cart.
* Feature: Add setting to control cart expiration time.
* Feature: Add setting to extend expiration of cart when less than 60 minutes remaining.
* Feature: Add ability to switch between different saved default ticket models when creating new event.
* Feature: Add ability to set general admission tickets with no expiration.
* Feature: Add ability to set a specific custom expiration date for general admission tickets.
* Feature: Bulk ticket check-in for groups.
* Feature: Reports specific to ticket groups.
* Feature: Individual date-based ticket groups send sold-out and sales expired messages independently.
* Feature: Add ability to move ticket between different ticket groups.
* Feature: Improved templating and template filters for HTML email messages.
* Change: Move ticket label index to post meta.
* Change: Add custom buttons to increment ticket count due to incredibly small browser input design.
* Change: Revamp data storage model for public users.
* Change: Secret keys displayed as password fields when filled.
* Change: Match fields displayed in admin view reports & CSV downloads.
* Design & visual changes.
* Rename ticket types from 'Discrete' and 'Continous' to 'Seating Sections' and 'Audience Types'.
* Wide variety of miscellaneous bug fixes that would be difficult to isolate.
* Remove payments JS not used in core plugin.

= 1.11.2 =

* Change: Add cache-control headers to prevent browser caching of cart pages.
* Bug fix: Fix minor misnamed variable reference.

= 1.11.1 =

* Bug fix: Misnamed variable in ticket templates displayed wrong expiration date for fixed date events.
* Bug fix: Cart handling charges generated error in offline payments.

= 1.11.0 =

* Feature change: Extend General Admission tickets to set specific expiration dates.
* Feature: Option to ignore cart-wide ticket handling fees for specific gateways.
* Feature: Add autocomplete on move ticket feature.
* Bug fix: No default padding on cart handling costs.
* Bug fix: Incorrect permission check for ticket check-in in admin.
* Bug fix: Setting the number format in the total paid field breaks values with thousands separators.
* Bug fix: Counting error after moving tickets between events.
* Change: Add payment gateway into purchase report download.
* Change: Change default colors to use WordPress palette colors.
* Change: Default event report changed to purchases & download instead of tickets & view.

= 1.10.2 =

* Bug fix: Stripslashes before sending payment confirmation to PayPal.
* Improve: Prefix some invalid nonce errors.

= 1.10.1 =

* Bug fix: List of payment tickets sourced from event instead of payment.
* Change: refactor payment storage of ticket IDs.

= 1.10.0 =

* Feature: UI to move tickets from one event to another on a payment.
* Feature: Show tickets used in payments view.
* Feature: Remove tickets from a payment.
* Bug fix: Minor styling improvements for shopping cart.
* Bug fix: Exit early if ticketing options do not exist.
* Bug fix: Hide short cart when empty.
* Bug fix: Correctly handle singular/plural values in quick cart.
* Change: Add script debugging to break caches on styles and scripts.
* Change: Style - wider price column.
* Change: Expand information shown on verification views. Props @masonwolf.

= Future =

* Improve options when there are multiple dates available for a specific event. Multiple ticket patterns w/separate pricing & availability options, etc.?
* Add option to use radio buttons instead of checkboxes
* Email QR code for e-tickets
* Server-side validation of required fields in purchase process
* Make admin-only ticketing options configurable in settings
* Limit number of discount tickets per event for registered users
* Add filter to pull user data into ticketing reports if user is registered.
* Make address fields required & validated
* BUG FIX: problem with passing address fields to notifications when shipping off but collect address on using PayPal (and other gateways?)
* TODO: add ability to add or remove tickets from an existing payment
* TODO: option to filter ticket purchasers by ticket type purchased (requires JS)

== Frequently Asked Questions ==

= I'm trying to sell tickets with My Calendar, but can't see how to add sales information =

The My Tickets sales information is entered in the 'Registration Information' panel of the My Calendar add event screen. This may be turned off in your installation. There are two places to look to enable it. First, go to My Calendar > Settings and go to the Input Settings section. If the 'registration' option isn't checked, check it and save settings. Second, go to the Add Event screen. If the registration options still aren't visible, you may need to enable them in your personal Screen Options. Open the Screen Options panel and check the option there - these are settings that apply only to your account.

= I'm trying to sell tickets on a recurring event in My Calendar, but all the dates are the same =

My Tickets data is associated with post IDs, and My Calendar's recurring events are all based off the same event post. As a result, My Tickets doesn't work with recurring events in My Calendar.

= If I visit the 'Tickets' or 'Receipts' pages, I end up on the Purchase page. What's happening? =

The Tickets and Receipts pages are only for displaying purchased tickets or purchase receipts. If no valid ID for one of those resources is included, then they'll redirect to the shopping cart.

= How do I scan QR Codes for events? =

You can use any QR Code scanning app for a mobile phone or other mobile device with a camera. In order to get the ticket status confirmation, you'll need to be connected to a network.

= Is the "number of tickets available" field required? =

Yes. My Tickets won't sell an unlimited number of tickets for an event; in order for My Tickets to sell anything, you need to specify how many tickets it's allowed to sell.

== Screenshots ==

1. Add to Cart Form
2. Shopping Cart
3. Payment Admin