=== OneKhusa Request To Pay (RTP) for WooCommerce ===
Contributors: onekhusa
Tags: woocommerce, payment-gateway, onekhusa, rtp, malawi
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept payments via OneKhusa Request To Pay (RTP) Hosted Checkout in WooCommerce.

== Description ==

This plugin adds an official OneKhusa RTP payment gateway to WooCommerce. After checkout, customers are redirected to OneKhusa Hosted Checkout to authorize payment.

* Works with **classic checkout** and **Checkout / Cart Blocks** (redirect gateway).
* High-Performance Order Storage (HPOS) compatible.
* API details: [docs.onekhusa.com](https://docs.onekhusa.com)

OneKhusa RTP uses **Hosted Checkout RTP Initiate** (`{api_base}/checkout/rtp/initiate`). You choose **Sandbox** or **Live** in WooCommerce; initiate and hosted-checkout URLs follow [OneKhusa’s documentation](https://docs.onekhusa.com)—they are **not** editable in the admin screen.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install the ZIP from the WordPress admin.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooCommerce → Settings → Payments**, enable **OneKhusa (Request To Pay)**, then click **Manage** and configure:
	* **Environment** — Sandbox (testing) or Live (production)
	* **Title** and **Description** (shown at checkout)
	* **Merchant Account Number** and **Organisation ID** (from the OneKhusa portal)
	* **Sandbox** and **Live** API key and secret (from the OneKhusa portal; whichever matches **Environment** is used at checkout)
	* Optional **Detailed logging** for troubleshooting (WooCommerce → Status → Logs, source `onekhusa_rtp`)

== Frequently Asked Questions ==

= Does this support Checkout Blocks? =

Yes. The gateway registers as a WooCommerce Blocks payment method; the storefront flow matches classic checkout (redirect after place order).

= Can I set custom API or hosted-checkout URLs? =

No. Those endpoints are defined in the plugin to match OneKhusa’s documented bases. Use **Environment** to switch between sandbox and live API hosts.

= Are refunds supported? =

Not in this release. Refund and capture flows are not implemented; use your OneKhusa workflows as needed outside WooCommerce refunds.

== Screenshots ==

1. Payment method at checkout — OneKhusa RTP with Hosted Checkout redirect.
2. WooCommerce payment settings — environment, credentials, and optional logging.

== Changelog ==

= 0.1.0 =
* Initial release: OneKhusa Request To Pay (RTP) hosted checkout for WooCommerce.
* Classic checkout and Cart/Checkout Blocks support (redirect after place order).
* HPOS compatible.
* Sandbox/Live **Environment** with separate Sandbox and Live API key/secret fields; API and hosted-checkout URLs are fixed in code per [OneKhusa documentation](https://docs.onekhusa.com) (not editable in admin).
* REST webhook (`onekhusa/v1/webhook`) with secret token query for `route.callbackApiUrl`.
* Optional detailed logging (WooCommerce → Status → Logs, source `onekhusa_rtp`).
