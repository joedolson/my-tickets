=== My Tickets - Accessible Event Ticketing ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: ticket sales, registration, reservations, event tickets, accessibility
Requires at least: 4.9
Tested up to: 6.6
Requires PHP: 7.4
License: GPLv2 or later
Text domain: my-tickets
Stable tag: 2.0.0
My Tickets is a simple, flexible platform for selling event tickets with WordPress.

== Description ==

Sell tickets for events easily! My Tickets integrates with <a href="http://wordpress.org/plugins/my-calendar/">My Calendar</a> or can be used for ticket sales on its own. Sell tickets for box office pick up, shipping, or accept print-at-home and e-tickets for an easy experience for your ticket holders!

My Tickets ships with PayPal Standard payments, so you can sell tickets immediately. You can also take offline payments, to use My Tickets as a reservation tool while you handle payments by mail or at the door.

Explore the <a href="https://www.joedolson.com/my-tickets/add-ons/">premium add-ons for My Tickets</a>!

Premium event ticketing add-ons include:

* Payment Gateways: Authorize.net, Stripe
* In-cart Donations
* Discount Codes
* Waiting Lists

= Basic Features: =

For buyers:

* Create an account to save your address and shipping preferences
* Automatically converts shopping carts to your account if you log-in after adding purchases
* Buy tickets for multiple events, with multiple ticket types.

For sellers:

* Reports on ticket sales by time or event
* Easily add new ticket sales from the box office, when somebody pays by phone or mail.
* Use your mobile phone and a standard QRCode reader to verify print-at-home tickets or e-tickets
* Send email to a single purchaser with questions about their ticket purchase, or mass email all purchasers for an event.
* Define either continuous (Adult, Student, Child) or discrete (Section 1, Section 2, Section 3) ticket classes for any event
* Offer member-only discounts for your registered members
* General Admission tickets: Sell tickets for events without dates, valid for days, weeks, or months after purchase.

My Tickets is hugely flexible - check out the <a href="https://www.joedolson.com/my-tickets/add-ons/">library of Premium add-ons</a>!

= Accessibility =

My Tickets is built with accessibility in mind, and tested using assistive technology.

= Documentation =

Read the <a href="http://docs.joedolson.com/my-tickets/">online documentation</a>.

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