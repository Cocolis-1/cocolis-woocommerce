# Testing guide — Cocolis plugin for WooCommerce

This document describes how to test the plugin end to end, and what must work, in what way. Run the full procedure before each release, on the current WordPress/WooCommerce/PHP stack and on the legacy stacks (see [Compatibility matrix](#8-compatibility-matrix)).

## Prerequisites

- Docker (the repo ships its own `docker-compose.yml`).
- [ngrok](https://ngrok.com) with a configured authtoken, to receive real webhooks from the Cocolis sandbox.
- A **sandbox API key** (or app-id/password pair) from Cocolis.
- A carrier account on https://sandbox.cocolis.fr to play the carrier side of the delivery.

## 1. Environment setup

```bash
# From the repo root: WordPress + MySQL, plugin mounted at wp-content/plugins/cocolis
docker compose up -d --build
# WordPress answers on http://127.0.0.1:8189 (admin / admin123)

# Expose the site publicly so the Cocolis sandbox can reach the webhooks
ngrok http 8189
```

Point WordPress at the ngrok URL (webhook URLs are built from it):

```bash
docker compose exec wordpress sudo -u www-data wp --path=/var/www/html option update home https://<your-id>.ngrok-free.app
docker compose exec wordpress sudo -u www-data wp --path=/var/www/html option update siteurl https://<your-id>.ngrok-free.app
```

Store setup (admin → WooCommerce → Settings): a French store address with a real postcode (e.g. 75011 Paris), currency EUR, "Cash on delivery" payment enabled, and at least one product with price and dimensions (e.g. 199.99 €, 90 × 50 × 40 cm).

## 2. Installation and upgrade

### Fresh install

Activate the plugin (Plugins → Cocolis → Activate).

**Expected:**

- Activation redirects to WooCommerce → Settings → Shipping → Cocolis.
- An admin notice shows while the module is not fully configured (credentials, dimensions, email, phone), and disappears once it is.
- No PHP warning/notice in the logs, including with `WP_DEBUG` on.

### Upgrade from a previous version

Install the previous release from wordpress.org, configure it, then replace the plugin files with the new version (or update through wp-admin).

**Expected:**

- The plugin stays active; settings are preserved.
- On the first admin page load, the upgrade routine runs once: option `cocolis_plugin_version` is set to the current version, the WooCommerce shipping-rate transient cache is invalidated, and the webhook signing secret is fetched into the option `cocolis_webhook_signing_secret` **without** re-saving the settings.

## 3. Configuration and webhook registration

In WooCommerce → Settings → Shipping → Cocolis, set mode **Sandbox**, fill the **API Key**, dimensions, email and phone, then save.

**Expected:**

- With a valid API key, saving succeeds. The API key takes precedence: the app-id / password pair may stay empty (legacy pair alone must also still work).
- With invalid credentials, saving stops on an explicit "Authentication error on the API server" screen.
- On the Cocolis side (`GET /applications/webhooks`), the 8 events are registered, active, and point to `https://<ngrok>/wp-json/cocolis/v1/webhook_<event>`: `ride_published`, `ride_expired`, `offer_accepted`, `offer_cancelled`, `offer_completed`, `pickup_slot_accepted_by_sender`, `deposit_slot_accepted_by_recipient`, `ride_availabilities_pending`. Re-saving must update URLs, never duplicate them.
- The signing secret is stored in the option `cocolis_webhook_signing_secret`.

## 4. Shipping rate at cart

Add the test product to the cart and enter a French destination postcode eligible for Cocolis (e.g. 69001 Lyon).

**Expected:**

- Exactly **one** Cocolis rate shows, with the Cocolis logo and the mention "Cocolis insurance included" plus a link to the insurance notice. There is **no** separate "with insurance" rate (insurance is always included by the Cocolis API).
- The rate's price matches the sandbox `canMatch` estimate for the cart's volume and value. Cents must be preserved in the value sent (a 19.99 € product is sent as 1999, not 1900).
- With an ineligible destination or a cart the API cannot match, no Cocolis rate shows and checkout still works with other methods.
- Products without dimensions fall back to the default width/height/length from the settings.

## 5. Order → ride creation

Place the order (cash on delivery), then move it to **Processing** (or let the payment gateway do it).

**Expected:**

- A ride is created on the sandbox; the order gets the meta `_cocolis_ride_id` and two order notes with the **seller** and **buyer** tracking links (`https://sandbox.cocolis.fr/tracking/...`).
- Setting the same order to Processing again must **not** create a second ride (`_cocolis_ride_id` guard), and replaying the creation must return the same ride (idempotency key `cocolis_woocommerce_<order_id>`).
- If the seller phone is missing from the settings, checkout must **not** break: the order gets an explanatory note and the error is logged.
- Admin side, the order page shows the "Manage my delivery with Cocolis" metabox with a "Create Cocolis Ride" button (nonce-protected; works on both classic and HPOS order screens).
- Refunding the order removes the ride on Cocolis and adds a note saying so.

## 6. Webhook security (HMAC signature)

Every webhook route must verify the `X-Cocolis-Signature` header: `t=<unix timestamp>,v1=<hex HMAC-SHA256 of "t.rawBody" with the signing secret>`, constant-time comparison, 5-minute replay tolerance.

Test with curl, using the stored secret:

```bash
SECRET=$(docker compose exec wordpress sudo -u www-data wp --path=/var/www/html option get cocolis_webhook_signing_secret)
URL=https://<ngrok>/wp-json/cocolis/v1/webhook_pickup_slot_accepted_by_sender
BODY='{"event":"pickup_slot_accepted_by_sender","external_id":"<order_id>"}'
T=$(date +%s)
SIG=$(printf '%s.%s' "$T" "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $NF}')

curl -X POST "$URL" -H "Content-Type: application/json" -H "X-Cocolis-Signature: t=$T,v1=$SIG" -d "$BODY"
```

**Expected:**

| Case | Request | Must return |
| --- | --- | --- |
| Valid signature | correct `t` and `v1` | `200` `{"success":true}` + effect on the order |
| Forged signature | altered `v1` | `401` `cocolis_invalid_signature`, order untouched |
| Missing signature | no header | `401` `cocolis_missing_signature`, order untouched |
| Expired timestamp | `t` older than 5 min, HMAC correct | `401` `cocolis_expired_signature`, order untouched |
| Unknown order | valid signature, bogus `external_id` | `404` `cocolis_order_not_found` |
| Malformed payload | valid signature, missing `event`/`external_id` | `400` `cocolis_invalid_payload` |

After a secret rotation on the Cocolis side (`POST /applications/webhook_signing_secret/rotate`), the next signed webhook must still pass: the plugin refreshes the secret once on mismatch (rate-limited to once per 5 minutes).

## 7. Full delivery flow on sandbox.cocolis.fr (manual, two roles)

This is the real end-to-end test: one person acts as the shop (steps above), the other as the **carrier** on https://sandbox.cocolis.fr. Each sandbox event must reach the shop through ngrok and update the order.

| Step (carrier side on sandbox.cocolis.fr) | Webhook fired | Expected on the WooCommerce order |
| --- | --- | --- |
| Ride is published (automatic after order) | `ride_published` | Notes: "The delivery offer has just been published" + public ad link |
| Carrier offers their services on the ad | `ride_availabilities_pending` | Note: "A carrier offers their services…" |
| Seller accepts the pickup slot (seller tracking link) | `pickup_slot_accepted_by_sender` | Note: "The seller accepted the carrier's pickup slot" |
| Buyer accepts the delivery slot (buyer tracking link) | `deposit_slot_accepted_by_recipient` | Note: "The buyer has accepted the carrier's delivery slot" |
| Offer is accepted | `offer_accepted` | Note + status → **Processing** |
| Delivery completed and confirmed | `offer_completed` | Note "Delivery completed by Cocolis" + status → **Completed** |
| (variant) Carrier cancels | `offer_cancelled` | Note: "The delivery is cancelled by the carrier…" |
| (variant) Ride expires with no carrier | `ride_expired` | Note + status → **Failed** |

Notes:

- `offer_completed` may arrive a few minutes after the delivery is confirmed on the sandbox (asynchronous job on the Cocolis side) — check the Apache logs (`docker compose logs wordpress | grep webhook`) if in doubt.
- Every received webhook must be signed; check in the logs that each `POST /wp-json/cocolis/v1/webhook_*` returned `200`.

## 8. Compatibility matrix

The full procedure above runs on the current stack. For legacy stacks, run sections 2–6 at minimum (section 7 can be run on any stack by pointing ngrok at its port and re-saving the plugin settings so the webhooks re-register to that stack — **one stack at a time**, as the Cocolis application has a single set of webhook URLs).

Recommended matrix (a compose file with pinned `wordpress:<wp>-php<ver>-apache` images + `wp plugin install woocommerce --version=<wc>` makes this quick):

| Stack | WordPress | PHP | WooCommerce | Why |
| --- | --- | --- | --- | --- |
| Current | latest | 8.3+ | latest | What new shops run |
| ~2 years old | 6.6 | 8.2 | 9.3 | Typical maintained shop |
| ~5 years old | 5.8 | 7.4 | 5.8 | Oldest supported (`Requires PHP: 7.4`) |
| Bleeding edge | latest | 8.5 | latest | Catch upcoming PHP deprecations |

**Expected on every stack:** all sections above pass, and with `WP_DEBUG` enabled no PHP deprecation/warning/notice originates from `wp-content/plugins/cocolis` (SDK included).

## 9. Last run

Full campaign run on 2026-10-13 against sandbox, all green: stacks WP 7.1.3/PHP 8.5.11/WC 11.2.1, WP 6.6.2/PHP 8.2.25/WC 9.3.3, WP 5.8.3/PHP 7.4.27/WC 5.8.0, plus the real two-role sandbox flow (section 7) on WC 11.2.1 — ride 9707, order completed through signed webhooks. Section 7 was run on the current stack only.
