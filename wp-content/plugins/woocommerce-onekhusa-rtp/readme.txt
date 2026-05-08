=== OneKhusa Request To Pay (RTP) for WooCommerce ===
Contributors: onekhusa
Tags: woocommerce, payment-gateway, onekhusa, rtp, malawi
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept payments via OneKhusa Request To Pay (RTP) Hosted Checkout in WooCommerce.

== Description ==

This plugin adds an official OneKhusa RTP payment gateway to WooCommerce. After checkout, customers are redirected to OneKhusa Hosted Checkout to authorize payment.

* Works with **classic checkout** and **Checkout / Cart Blocks** (redirect gateway).
* High-Performance Order Storage (HPOS) compatible.
* API details: [docs.onekhusa.com](https://docs.onekhusa.com)

OneKhusa RTP is powered by Hosted Checkout RTP Initiate. Store credentials and API base URLs are configured under WooCommerce payment settings.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP from the WordPress admin.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooCommerce → Settings → Payments**, enable **OneKhusa (Request To Pay)**, then click **Manage** and enter API key, API secret, Organisation ID, Merchant Account Number, and API base URL (sandbox or live).

== Frequently Asked Questions ==

= Does this support Checkout Blocks? =

Yes. The gateway registers as a WooCommerce Blocks payment method; the storefront flow matches classic checkout (redirect after place order).

= Are refunds supported? =

Not in this release. Refund and capture flows are not implemented; use your OneKhusa workflows as needed outside WooCommerce refunds.

== Screenshots ==

1. Payment method at checkout — OneKhusa RTP with Hosted Checkout redirect.
2. WooCommerce payment settings — API key, Hosted Checkout URLs, Organisation ID.

== Changelog ==

= 0.1.1 =
* First marketplace-oriented release (metadata, GPL LICENSE, readme, distribution ignore list, scaffolding cleanup).

== Upgrade Notice ==

= 0.1.1 =
Maintenance and packaging updates; no WooCommerce-facing API breaks expected.
