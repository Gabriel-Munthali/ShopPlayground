# ShopPlayground

A local WordPress + WooCommerce sandbox used to develop and test the **OneKhusa RTP** payment gateway and the **`khusa-shop`** storefront theme. The stack runs entirely in Docker.

## Stack

- WordPress (`wordpress:php8.3-apache`)
- MySQL 8.0
- Docker Compose
- Optional public HTTPS tunnel (ngrok / Cloudflare Tunnel) for webhook testing

## Project layout

- `wp-content/plugins/woocommerce-onekhusa-rtp/` — OneKhusa RTP gateway plugin (in-development)
- `wp-content/themes/khusa-shop/` — custom storefront theme
- `docker-compose.yml` — local dev stack (WordPress + MySQL)
- `wp-config.php` — Docker-aware config; reads env vars and honors `WORDPRESS_PUBLIC_URL`
- `.env.example` — copy to `.env` to set the public tunnel URL and other variables

## Getting started

1. Copy the env file:

	```bash
	cp .env.example .env
	```

2. Start the stack:

	```bash
	docker compose up -d
	```

3. Open http://localhost:8080 and run the WordPress installer.

## Public URL / webhooks

For OneKhusa webhook testing the site needs a public HTTPS URL. Expose port `8080` through ngrok (or any equivalent tunnel) and set `WORDPRESS_PUBLIC_URL` in `.env` to that URL — no trailing slash, e.g.:

```
WORDPRESS_PUBLIC_URL=https://your-subdomain.ngrok-free.app
```

`wp-config.php` pins `WP_HOME` and `WP_SITEURL` to that value so REST URLs, redirects, and webhook callbacks all resolve through the tunnel host.

## Database (dev defaults)

Defined in `docker-compose.yml` and intended for **local development only**:

- Host: `db:3306`
- Database: `wordpress`
- User: `wordpress`
- Password: `wordpress`

Do not reuse these credentials anywhere else.

## OneKhusa integration

The gateway plugin treats <https://docs.onekhusa.com> as the source of truth for endpoints, payloads, auth, and webhook verification. The primary checkout flow is hosted RTP initiate (`{api_base}/checkout/rtp/initiate`, with sandbox vs live chosen in WooCommerce settings); any local notes under `docs/onekhusa-*.md` are supplementary and must reconcile with the official docs.


