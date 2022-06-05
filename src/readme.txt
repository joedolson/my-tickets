=== My Tickets ===
Contributors: joedolson
Donate link: http://www.joedolson.com/donate/
Tags: events, ticket sales, tickets, ticketing, registration, reservations, event tickets, sell tickets, event registration, box office
Requires at least: 4.7
Tested up to: 6.0
Requires PHP: 7.0
License: GPLv2 or later
Text domain: my-tickets
Stable tag: 1.9.6
My Tickets is an simple, flexible platform for selling event tickets with WordPress.

== Description ==

My Tickets integrates with <a href="http://wordpress.org/plugins/my-calendar/">My Calendar</a> or can be used for ticket sales on its own. Sell tickets for box office pick up, shipping, or accept print-at-home and e-tickets for an easy experience for your ticket holders!

My Tickets ships with PayPal Standard payments, so you can sell tickets immediately. You can also take offline payments, to use My Tickets as a reservation tool.

Explore the <a href="https://www.joedolson.com/my-tickets/add-ons/">premium add-ons for My Tickets</a>!

Premium add-ons include:

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

4. With <a href="https://wordpress.org/plugins/my-calendar/">My Calendar</a>, add ticket parameters to an event. Without My Calendar, choose what post types will support tickets from My Tickets -> Settings, and add ticket parameters to any post or Page!

== Changelog ==

= 1.9.6 =

* Bug fix: If ticket price is greater than 1,000, cart calculations broke due to thousands separator.
* Bug fix: PHP warning thrown if $type not an array.
* Bug fix: Tickets remaining needs to be present in DOM for JS calculation purposes, even if hidden.
* Change: Confirmation message that ticket usage has been saved.

= 1.9.5 =

* Feature: Change ticket check-in status from Payment admin.
* Update tested to value.

= 1.9.4 =

* Bug fix: Prevent ticket settings from being edited on My Calendar group editing screen
* Feature: Setting to configure redirecting to cart after add to cart.
* Feature: New template function `mt_ticket_event_notes()` to get event-specific notes on ticket templates.
* Change: Minor style change to front-end button CSS.
* Change: Admin notice if any of the core display pages are not published or assigned.

= 1.9.3 =

* Bug fix: trim() called on array value PHP error fixed.
* Bug fix: Error in test to determine whether a ticket is General Admission or not.
* Bug fix: Don't set a default value for event validity if ticket is not General Admission.
* Bug fix: URL Encode postback to PayPal to support special characters stored at PayPal.

= 1.9.2 =

* Bug fix: Checkbox to sell tickets only appeared if GET parameter set.

= 1.9.1 =

* Bug fix: undefined array key in cart if collect shipping not set.
* Bug fix: Checkbox event to sell tickets only registered if tickets panel open.
* Change: CSS updates for premium plugin features

= 1.9.0 =

* New: General Admission ticketing.
* New: Collect VAT numbers at checkout.
* New: Set close date to take a ticket type off sale. E.g. early bird sales.
* New: shortcode `[my-payments]` to allow logged-in purchaser to view their ticket history.
* Bug fix: Automatically enable offline payment if cart price is 0.
* Change: allow multiple email addresses in 'Send to' field.
* Filter: `mt_price_in_label` move ticket price into label element.

= 1.8.37 =

* Bug fix: Strip slashes in mass emails.
* Bug fix: Better notification on mass email tests.
* Bug fix: Show email text in fields after sending test.
* Bug fix: Fix PayPal postback values.
* Change: Handle PayPal IPN via purchase page, not home page.
* Change: Render ticket verification via purchase page, not home page.

= 1.8.36 =

* Bug fix: Prefix generic CSS class 'controls'
* Bug fix: misnamed variable in email validation.

= 1.8.35 =

* Bug fix: HTML wraps link in event title even when email HTML disabled.
* Bug fix: PayPal verification may not be all caps.
* Bug fix: Default gateway doesn't retain selection.

= 1.8.34 =

* Bug fix: Disallowed name attribute on textarea
* Bug fix: Fatal error on PHP 8 if handling is a string.

= 1.8.33 =

* Bug fix: two instances of a misnamed function.

= 1.8.32 =

* Bug fix: One string replacement placeholder mistyped in receipts template.

= 1.8.31 =

* Security: XSS vulnerability due to unsanitized email address.
* Security: Plug-in wide security review and hardening.
* Standards: Use WordPress standard methods for stylesheets and scripts throughout plug-in.
* Bug fix: Required address fields should be labeled as required.
* Bug fix: Add language attributes and doctypes to custom templates.
* Change: New QRCode library (https://github.com/chillerlan/php-qrcode)
* Change: Transition error log to log all transactions.
* Change: QRCode template function now has a getter (mt_get_ticket_qrcode()).

= 1.8.30 =

* Bug fix: Custom field values overwrite the purchase value field in reports.

= 1.8.29 =

* Add important to some key mobile CSS.
* New: Setting to configure symbol order in money formatting.
* Bug fix: `[remaining]` shortcode incorrectly checked the variable type passed as event ID.
* Bug fix: Add scripting to check and verify required fields when custom fields added.
* Bug fix: Change time period for default reports lists from one month to two years.

= 1.8.28 =

* Bug fix: typo in option index for cache refreshing.
* Bug fix: reversed needle and haystack in in_array verification.
* Change: default number of events shown in select list for reports from 50 to 100.

= 1.8.27 =

* Bug fix: Accidentally overwrote the total sales value when parsing sales by date.

= 1.8.26 =

* Use autocomplete for name, email, and address fields.
* Bug fix: Don't prevent down arrow in number fields.
* Bug fix: Directly passed report IDs not shown in report dropdowns.
* Add cache busting for common caching plugins whenever cart, user meta, or cookies are updated.
* Bug fix: Retain name & email when a user goes back to edit their cart.
* Display: Stylesheet changes. Additional classes.

= 1.8.25 =

* Bug fix: Use COOKIEPATH instead of SITECOOKIEPATH. (See also 1.0.5. D'oh.)
* Bug fix: Handle situation where negative value ticket counts are passed to cart & payment.
* Bug fix: Don't cast ticket counts using absint
* Bug fix: Don't allow setting ticket counts to negative values.
* Change: Ticket verification styles. (Core colors, font sizing, used indicator.)

= 1.8.24 =

* Critical bug fix: error in unique ID cookie setting.

= 1.8.23 =

* Improved bug fix for removing events when ticket counts set to 0.
* Bug fix: Duplication of event IDs also needs to be handled in notifications.
* Add debugging log visible on payment screen when MT_DEBUG true.

= 1.8.22 =

* Bug fix: If purchase data array gets duplicated, can yield double-processing of tickets.
* Bug fix: Ensure that event is removed from purchases when ticket count set to 0.
* Bug fix: Prevent submission of cart to payment if cart update still processing.
* Change: Move marking of ticket delivery into notifications process.
* Change: Support SameSite parameter when setting cookies on PHP 7.3.0+
* Begin enriching debugging process.

= 1.8.21 =

* Bug fix: Variable assigned to non-existent value in user data checking.

= 1.8.20 =

* Bug fix: Use of 'is_int' instead of 'is_numeric' on value not of integer vartype broke purchase form.

= 1.8.19 =

* Bug fix: 'Sell tickets on this post' showing up twice in some contexts.
* Bug fix: Changed attr to prop, but didn't change the value checked.
* Bug fix: Change cart update button CSS to better meet WCAG 2.1 requirements for click target size.
* Bug fix: json_decode should only run on strings.
* Bug fix: Verify variable type when checking availability.

= 1.8.18 =

* Version bump due to failing to add prior tag before committing.

= 1.8.17 =

* Ensure value matching uses the same variable type.
* Query checked property instead of attribute.
* Add `[remaining]` shortcode to display tickets remaining for an event.

= 1.8.16 =

* Bug fix: notice thrown when event ID exists in array, but data is not an array.
* Bug fix: mt-sold-out class needs to be reset in discrete counting.
* Feature: Send test email from Reports screen before emailing whole event.

= 1.8.15 =

* Don't double display currency symbol when symbol is same as currency code.
* Add responsive table styles for cart.
* Bug fix: don't add offset when time saved.

= 1.8.14 =

* Add event ID in a class on shopping cart row.
* Missing occurrences of stripslashes() in settings fields.

= 1.8.13 =

* Bug fix: Ensure that available tickets is an integer.
* Filter change: Pass $type variable to the mt cart default filter.
* Replace usage of current_time( 'timestamp' ) & date()

= 1.8.12 =

* Bug fix: Mass emails should only send to sales with 'Completed' status.
* Bug fix: Payment status on offline payments should reflect status as configured in admin.
* Bug fix: Email addresses in PayPal should be compared with both converted to lower case.
* Bug fix: Make purchase transaction notice only viewable for 5 minutes after purchase.
* Change: Warn user if departing payment page without submitting purchase.
* New filter: 'mt_ticket_settings' to filter configuration for a given ticket class on an event.

= 1.8.11 =

* Only changes are code standards changes with strict comparisons.
* This release is only being shipped because the 1.8.10 update produced a bad installation file (because I made a mistake in the commit.)

= 1.8.10 =

* Bug fix: Using strict matching on PayPal values causing problems; need time to figure out why.

= 1.8.9 =

* Accessibility: Ticket input fields labelling pattern incorrect.
* Bug fix: type checking in strict comparisons when comparing pricing.
* Bug fix: error checking receiver email in PayPal gateway.
* Improvement: better error messages from PayPal gateway.
* Design Change: Change cart table to three columns, collapsing 'price' and 'count' into 'order'
* Correct copyright date

= 1.8.8 =

* Bug fix: Need to support payment gateways with no field settings.
* Bug fix: checkbox gateway settings should not have line breaks after label.
* Bug fix: Fixed logic error impacting application of discount codes.
* Bug fix: Shipping not available as template tag or included in notification totals.
* New filter: 'mt_paypal_item_name' to change the description passed to PayPal.
* Change: move custom fields output above ticket type selector.

= 1.8.7 =

* Bug fix: Non-numeric variable type used in math expression.
* Bug fix: Ensure that default ticket logo is square, not overridden by theme's post thumbnail shape.

= 1.8.6 =

* Bug fix: Strict comparison requirement yielded incorrect check for sold out status.
* Bug fix: Incorrect return value possible on sold out status
* Bug fix: Show admin-only purchase statuses in Reports
* Bug fix: Ensure handling is a float value so it can be operated on

= 1.8.5 =

* Bug fix: Flaw in mt_has_tickets() function triggered false if any type of tickets had 0 remaining.

= 1.8.4 =

* Discount notice showing when price unchanged
* Add sequential ticket IDs to report
* Add payment status to reports of tickets
* Fix issue with remaining tickets in {ticket_status} template tag for My Calendar.
* Add custom fields to reports by date as well as reports by event.

= 1.8.3 =

* Bug fix: Ensure that discount calculations aren't run twice.
* Bug fix: Get Payment ID before totaling cart.
* Bug fix: Check that values are numeric for shipping & handling before applying.
* Change: Allow receipts to display when payments aren't completed yet.

= 1.8.2 =

* Add template tag for event status in My Calendar
* Misc. minor bug fixes
* Add class & wrapper to sold out ticket types
* Bug fix: Missing payment total on receipts if shipping/handling applied.

= 1.8.1 =

* Bug fix: Don't attempt to return sold tickets to deleted events.
* Bug fix: Incorrect headings hierarchy (used old WordPress headings system)
* Bug fix: Label errors in Payments page

= 1.8.0 =

* Add field to define custom selector label for offline payment gateway
* Add aria-current support to gateway selector
* Add default output for My Tickets [tickets] shortcode, showing upcoming events with ticket sales.
* Add filter to customize default output for [tickets] shortcode, `mt_default_ticketed_events`
* Change price input to number type
* Add support for native bulk actions. (Available since WP 4.7)

= 1.7.13 =

* Improvement: Pass field array to display_callback in fields API.
* Feature: Send email notification when event is sold out.

= 1.7.12 =

* Bug fix: If event has been deleted, exit early in payment UI.
* Bug fix: Exit in ticket template if event does not exist.
* Bug fix: Global $wpdb not declared on Help form.
* New action: 'mt_successful_payment', executes prior to sending purchase notifications.
* Style change on payment page
* Text change on payment page
* Add Privacy information to Help screen

= 1.7.11 =

* Bug fix: 'Sell tickets on this post' checkbox appeared twice in post meta box
* Bug fix: Don't copy 'sold' value when copying My Calendar events
* Bug fix: 'adminitrators only' notice not appearing on complimentary field.
* New filter: `mt_home_breadcrumb_url` to change home link in breadcrumb path.
* New display option: Sequential ticket IDs on ticket templates.

= 1.7.10 =

* Increase prominence of sales & help sidebar blocks.
* Update dashicon implementation for accessibility.
* Missing ID on 'Get Support' heading.
* Bug fix: Must exit after wp_safe_redirect
* Bug fix: improve code locating wp-load for QR codes

= 1.7.9 =

* Bug fix: execute discount calculation in cart data.
* Bug fix: Ensure address is collected using PayPal if always collect address enabled.
* New filters for discount handling.
* Change: Update references to My Calendar template function.
* Change: Pass payment ID in more contexts.

= 1.7.8 =

* Bug fix: Tickets duplicated when offline payments completed.
* Bug fix: Fix headers on date report CSV.
* Bug fix: Delete both logged-in and logged-out carts if both exist after purchase.
* Change: add additional parameters to some functions for filtering purposes.
* Change: add email as output in date report CSV.
* Add: mt_purchase_completed action on confirmation screen.

= 1.7.7 =

* Bug fix: PHP notice due to incorrectly named variable.
* Bug fix: Offline pending payments should still deliver tickets.
* Change: exlude .mt-plugin class from cart button actions.

= 1.7.6 =

* Bug fix: Further rewriting of offline payment handling.
* Bug fix: Improve sending of email notifications
* Change: Track prior status for transitions
* Add filter: mt_ticketing_availability to filter ticket types available in a given cart. (https://github.com/joedolson/my-tickets/pull/1)

= 1.7.5 =

* Bug fix: misc.issues in offline gateway
* Change: unify behavior of offline gateway with other gateways to eliminate code exceptions and related issues...

= 1.7.4 =

* Bug fix: offline payment better redirection
* Code sniffing and conformance.
* Button to remove ticket types.
* Move development to GitHub

= 1.7.3 =

* Bug fix: custom rules for custom fields filter awkwardly written.
* Bug fix: if event has been deleted, do not attempt to handle tickets in cart.
* Bug fix: missing second argument in the_title filter usage

= 1.7.2 =

* Bug fix: hiding tickets missing $options variable.
* Change ticket hiding logic to be more intuitive.

= 1.7.1 =

* Bug fix: Incorrect body reassignment in bulk messaging
* Added: logging of bulk messages sent
* Added: logging of initial message sent to purchaser
* Updated styling for message log

= 1.7.0 =

* Bug fix: inappropriate usage of esc_attr_e on non-translateable string
* Force TLS 1.2 in PayPal gateway (https://www.paypal.com/webapps/mpp/tls-http-upgrade)
* Shift hiding tickets into single function for consistency.
* Remove usages of 'create_function'
* Remove usages of 'extract'
* New template & template tag: bulk tickets. Print view of all tickets for a given purchase.

= 1.6.5 =

* Bug fix: PayPal Merchant ID not rendered in 1.6.4

= 1.6.4 =

* Compatibility fix: Polylang filtered queries such that tickets sold on events not in site default language were not visible
* Bug fix: reports displayed incorrect total sales numbers
* Bug fix: if there was no post object in global scope, toggle confirming whether to sell tickets did not appear
* Bug fix: Member discounts were applied after Ticket handling charges were in 'Add to Cart' contexts
* Added 'required' attribute for custom inputs.

= 1.6.3 =

* Bug fix: Errors with counting ticket purchases when only single tickets are permitted.
* Minor text changes in settings
* Bug fix: error with display of remaining tickets count
* Remove German translation in favor of WordPress.org version

= 1.6.2 =

* Bug fix: logical error in shipping fee totals

= 1.6.1 =

* Bug fix to reports: don't display payments which 0 purchases applied to currently shown event
* Bug fix: Hide remaining logic for discrete counting
* Bug fix: unresolved PHP Notice saving ticket options
* Bug fix: Total Paid registered incorrectly if shipping fee applied

= 1.6.0 =

* New option: redirect to cart after adding tickets to cart
* New option: Hide tickets when num. available falls below x
* New option: Custom text that can be inserted in purchase notification per event
* Display fixes: address information
* Display purchase information after cart completion instead of "your cart is empty"
* Minor text revisions
* store mass email text in event post meta
* Improve ticket number values when switched between discrete & continuous
* Revise information shared in purchase reports for better clarity
* New filter: mt_select_events_args to modify list of events in reports environment.
* SECURITY: Reflected XSS vulnerability resolved

= 1.5.0 =

* New function: check whether post ID is a ticketed event
* Bug fix: render QR code like eticket on will call view
* Bug fix: PHP 7 non-numeric value error
* Bug fix: If deleting ticket types from an event, maintain data associations correctly.
* Bug fix: PHP notice on payment settings screen
* Bug fix: Now possible to fetch correct ticket type instead of doing type conversion witwp_mah str functions.
* Bug fix: Show only statuses with relevant purchases in reports
* Bug fix: Clarify what 'price' means in reports of purchases
* Bug fix: removed HTML from CSV output of reports by date
* Bug fix: incorrect arguments for My Calendar class filter
* Minor layout tweaks in admin
* Refined print view of reports
* Moved 'Notes' field to final column of print view
* Added column & cell classes for reports
* Added toggles to disable columns in print reports
* Added date/time to reports by date
* Added ticket type to reports by date
* Removed unused file mt-events.php
* Make PayPal capable of handling IPN responses that are formatted as cart responses.
* New filter: mt_the_title to change displayed title for events.
* New permission: mt-order-comps; users with this capability can order complimentary tickets
* New statuses for tickets: Turned Back, Waiting List, & Reserved (for admin use only)
* Remove nl_NL language; .org language pack now shipped

= 1.4.13 =

* Bug fix: disallow purchase of partial tickets
* Feature: new message to be sent to purchasers with Offline payment in Pending status
* Add 'required' attribute to address fields
* Add double-entry for accurate email address

= 1.4.12 =

* Add wrapper div & classes for soldout/expired buttons
* Bug fix: sold out events using discrete ticket counting not displaying sold out text.

= 1.4.11 =

* Bug fix: Add wrapping element around response container & form in add to cart
* Bug fix: Display issue with multiple custom fields
* Bug fix: Prevent invalid argument warning in mt-button.php
* Add options for decimal and thousands separators in money formatting
* Compatibility with WordPress 4.7

= 1.4.10 =

* New action executed during ticket verification process 'mt_ticket_verified'
* Bug fix: checked in tickets did not appear in Payment admin
* Bug fix: mt_format_purchase() exclude titles for events in cart but excluded due to expiration
* Added: show checked in tickets in ticketing reports

= 1.4.9 =

* Bug fix: accidental inclusion of JS from My Calendar caused dual occurrences in My Cal.
* Bug fix: label & ID issue when multiple add to cart forms present.
* Bug fix: checked value printed to page instead of in HTML
* Add purchase ID function to Ticket templating
* Add purchase ID function to Receipt templating
* Add option to ask for shipping address without using postal tickets

= 1.4.8 =

* Style change: Change post type selector to checkboxes, move to top of settings
* Bug fix: Opt out URL for email contacts was invalid
* Bug fix: Swapped CSV column headings in reports
* Added: filter to modify opt out message texts.
* Pass event_id when calculating discounts

= 1.4.7 =

* Bug fix: undefined variable notice in custom fields API
* Bug fix: receipt ID could be changed to an invalid value when post updated in admin
* Bug fix: custom fields repeated in notifications
* Bug fix: remove custom ticket fields from payment custom fields list (shown in formatted purchase data)
* Tested for WordPress 4.6

= 1.4.6 =

* Improvement: CC admin on mass emails.
* Bug fix: process custom field template tags better in email templates (associated with events)
* Bug fix: incorrect payment ID passed to purchase generation when sending notifications
* Bug fix: Reports permissions did not grant non-admins access to reports
* Add option to hide tickets remaining count in settings
* Add notice in payment settings while in testing mode.

= 1.4.5 =

* Bug fix: Only display default ticket/receipt content to users with ability to edit it.
* Bug fix: refund processing called price matching function, which threw an error.
* Bug fix: calling receipt ID in tickets template filter instead of ticket ID
* New template function: mt_get_ticket_custom_field( $fieldname, $display_callback );
* New template tag: {buyer_email}
* Filter a ticket type as admin-only using 'mt_admin_only_ticket' [currently only supports free tickets]

= 1.4.4 =

* Bug fix: Don't send offline payment notices until after shipping info has been entered, if required.

= 1.4.3 =

* Bug fix: deprecated function add_object_page
* Bug fix: deprecated function get_currentuserinfo
* Feature: new template tag {gateway_notes} and fields associated with gateways to save custom notes per gateway.

= 1.4.2 =

* Bug fix: Mass email always used first value for {name} replacement
* Bug fix: If payment price is zero (only free events being reserved) mark payment as complete
* Bug fix: Refresh closure meta data if tickets closing time is updated.

= 1.4.1 =

* Bug fix: Fixed PHP notice if no 'multiple' value registered in defaults array
* Bug fix: Correct pricing shown on cart update if user is logged in and receiving a discount on tickets
* Add: widget to display live cart updates (supports selective refresh)
* Change: post meta value for My Calendar events to indicate whether tickets are being sold. May cause ticket forms to be hidden after event edit.

= 1.4.0 =

* Add support for additional input types in Payment Gateway settings
* Support for fetching name and email from pending payment info on credit card error
* Bug fix: PHP notice on error messages
* Bug fix: Receipts on refunded payments didn't indicate that payment was refunded.
* Improvements to currency handling
* Better support for zero decimal currencies
* Bug fix: Collect shipping information when postal tickets ordered with offline payment.
* Improved visibility if money owed on purchase.

= 1.3.5 =

* Send email notification in case of WP error in PayPal processing.
* Bug fix: license activations for Premium add-ons
* Bug fix: duplicate creation of tickets when re-sending notification emails

= 1.3.4 =

* Bug fix: Failed to check for EDD class presence before including
* Bug fix: Misc. PHP notices in reports
* Bug fix: corrected spelling of complimentary tickets. Guh.

= 1.3.3 =

* Provide links to either download purchases or tickets in closure notification email
* Add filter to convert input type used with add to cart form.
* Add support for EDD-driven auto updates in premium add-ons

= 1.3.2 =

* Remove a few more instances of 'money_format()'

= 1.3.1 =

* Add filter on currency types.
* Bug fix: callbacks in fields API generated error if invalid function defined by user
* Bug fix: stop using money_format entirely due to currency locale issues.
* Bug fix: issue with possible invalid argument in admin ticket creation
* Clarification: add reference for PayPal IPN URL value.
* Added Help tabs on the ticketing defaults settings page to answer some common questions.
* Miscellaneous text clarifications

= 1.3.0 =

* Feature: Ability to select default ticketing type when multiple are available
* Feature: option to use external link to add event tickets to cart. Format: /?mt_add_to_cart=true&event_id=POSTID&ticket_type=TYPE&count=COUNT
* Bug fix: not saving correctly when multiple events sold in one cart order
* Bug fix: handling values properly if purchase submitted with no numeric value; prevent empty values in form
* Bug fix: Make sure that payment checks are non-negative values.
* Bug fix: For consistent display, ensure that monetary locale is null when generating money format.
* Bug fix: Incorrect custom field data in purchase reports

= 1.2.9 =

* New filter: 'mt_button_legend_text' to edit 'Buy Tickets' legend
* Add note to mention need to enable IPN on PayPal
* Bug fix: Don't create additional copies of tickets when admin saves edits to Payment
* Bug fix: display ticket ID on both print & e-tickets
* Bug fix: custom fields not saved in admin creation of payment

= 1.2.8 =

* Bug fix: debugging query left in place in autoupdates class
* Bug fix: more reliable deletion of user data
* Bug fix: Not possible to disable ticket sales if created unintentionally
* Bug fix: No longer possible to sell tickets for My Tickets cart pages

= 1.2.7 =

* Bug fix: Something broken on Payment settings page. No idea what it was...

= 1.2.6 =

* Fixes to automatic updating class for premium plugin license holders
* Add label to shortcode textarea for context.
* Expand FAQ.
* Pass provided purchaser name into PayPal item information.
* Return 200 header on PayPal data mismatch so notification email is only sent once.
* Bug fix: Custom fields API did not pass data if saved data was a string and user was not logged in.
* Misplaced sprintf argument in ticket closure notice.

= 1.2.5 =

* Bug fix: Switch esc_url to esc_url_raw on QR Code URL so parameters will be followable. [Broken in 1.2.2]

= 1.2.4 =

* There was an incomplete SVN commit on 1.2.3 that caused update issues. This enables people with "this isn't really 1.2.3" to update.

= 1.2.3 =

* Moved logic that switched to offline gateway if total = 0
* Only display login link if public registration is enabled
* Fallback function for money_format(), since that function is not supported on Windows
* Clearly label complementary tickets as admin only
* Delete My Tickets pages on uninstall
* Move focus to Cart link when Add to Cart

= 1.2.2 =

* Allow tickets to be sold on posts with status 'private'
* Show event date in reports drop down
* Revised HTML so it's easy to hide remaining tickets notices
* Prevent user from increasing number of tickets in cart to more than available.
* Add filter to enable max ticket limit per purchase/type for an event. 'mt_max_sale_per_event'
* Add fees field to reports output in addition to sales values.
* Add filter on cart total used to determine whether or not to show payment gateway form.

= 1.2.1 =

* Re-send email and ticket delivery status checkboxes would not uncheck.
* Invalid Ticket URLs sent in notification messages.

= 1.2.0 =

* Modify reports to include notes field & payment ID.
* Modify default report view to only show maximum of most recent 50 payments.
* Modify reports to remove payment status, since status reports are displayed based on status.
* Added delivery checkbox to Payment to indicate whether tickets on that payment have been delivered.
* Added option to copy front-end cart into admin payment when logged-in as user with appropriate permissions.
* Added ability to view processing errors on payments
* Added ability to add complementary tickets through admin.
* Added template tag for ticket_ids
* Added function to get purchaser name for ticket templates.
* Added date to title output in default template for [tickets] shortcode.
* Added view of [ticket] shortcode after editing event details in registration form panel.
* Improved handling of PayPal IPN errors.
* Bug fix: Some broken currency symbols.
* Bug fix: Reports list was limited to 10 events.
* Translation: Polish

= 1.1.0 =
* New option: pull ticket image from event featured image instead of ticket page featured image.
* Feature: Include email address in CSV report format
* Security: Double-verify that the price paid by gateway matches price expected.
* Added link to receipt in Payment record
* New filter: customize text displayed for ticket prices.
* New filter: add custom template tags for ticketing form output.
* New filter: alter default number of tickets
* New template tag: return purchase ID in notifications.
* New template tag: return purchase edit URL in notifications.
* Bug fix: Prevent notices on invalid events
* Bug fix: If a user submitted two payments in a row, 2nd payment might not be recorded.
* Bug fix: Ticket IDs were generated using purchase ID only, so multiple tickets on same purchase had same ID.
* Bug fix: QR Code URL is incorrect if site not rendered at domain root
* Minor CSS change in default cart CSS.
* Translation: Norwegian (Bokmal)

= 1.0.7 =

* Bug fix: Unassigned variable after filter.
* Bug fix: Don't throw errors if invalid event IDs passed to shortcode
* Translation: Dutch
* Removed license key string from Help page

= 1.0.6 =

* Bug fix: Prevent submitting ticket order form if there are no tickets in the form.
* Feature: add filter to receipt template so plug-ins can add custom data to template
* Feature: make printable report view filterable so plug-ins can add print views

= 1.0.5 =

* Feature: Add per-ticket handling fee
* Feature: Shut off online ticket sales when 'x' tickets or 'x' percentage of total tickets are left.
* Feature: Print This Report button (table version only)
* Change: Save timestamp in custom field in order to create lists of tickets by date.
* Change: Only display last month of events in reports dropdown.
* Change: Added more filters to further ability to extend My Tickets.
* Change: Text change for clarity in what "total" is.
* Bug fix: re-sending email could create new tickets.
* Bug fix: Use COOKIEPATH instead of SITECOOKIEPATH to support WP installed in a separate directory.

= 1.0.4 =

* Bug fix: Invalid argument error on user profiles
* Bug fix: Don't attempt to use default payment gateway if that gateway has been deactivated.
* Bug fix: When total updated, currency was changed to $.
* Bug fix: Plus/minus buttons in cart could take number of tickets below 0
* Bug fix: Cart total calculation included deleted cart items
* Bug fix: Cart total value could go negative without disabling cart submission.
* Bug fix: Add a couple missing textdomains.
* Bug fix: Handling fee not shown to offline payments.
* Bug fix: Amount due pulled from wrong data on offline payments.
* Bug fix: Updating posts with tickets could modify the count of sold tickets.
* Bug fix: If cart submitted with 0 tickets on a ticket type, do not display those values in reports/admin.
* Include address fields in purchase reports
* Include phone number in purchase reports.
* Add note that 'x' tickets are still available for sale after sales are closed.

= 1.0.3 =

* Add documentation of ticket shortcodes on Help screen.
* Add administrative/handling charge for tickets.
* Add option to require phone number from purchasers.
* Bug fix: Payments search didn't work.
* Two new template tags: {handling} and {phone}

= 1.0.2 =

* Add lang directory and translation .pot
* Fix issue: not asked to enter address with offline payment/postal mail combination.

= 1.0.1 =

* Bug fix: If an expired event was in cart, Postal Mail would not show as an option for ticket methods.
* Bug fix: If an event had was not supposed to sell tickets and user was logged-in, 'Sold out' notice would display.

= 1.0.0 =

* Initial launch!

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

== Upgrade Notice ==

1.8.31 - Important: Security update.