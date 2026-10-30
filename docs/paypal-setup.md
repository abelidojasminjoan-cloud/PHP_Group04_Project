# PayPal Sandbox checkout

Checkout offers Cash on Delivery and PayPal. The PayPal integration uses server-side
[Orders v2 create/approve/capture](https://developer.paypal.com/whats-an-order/).

## Configuration

The private `.env` must contain credentials from the same Sandbox business app:

```dotenv
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID="your-sandbox-client-id"
PAYPAL_CLIENT_SECRET="your-sandbox-client-secret"
```

PHP requires cURL and a working CA certificate bundle. TLS verification stays on.
The tracking table is included in `database/purevia_db.sql`. PayPal uses the existing `online` payment method in the database.

From the project root run `php -S localhost:8000 -t public` and open
http://localhost:8000/. The built-in server automatically uses that origin for
PayPal return URLs. Under Apache, `.env` APP_URL must match the website address.
Use the same hostname throughout checkout to retain the sign-in session.

## Payment flow

1. Choose PayPal and select Place order. PHP saves an unpaid order using database
   prices and reserves the selected stock.
2. The same checkout POST creates/resumes the gateway order and returns PayPal's
   validated approval URL. The browser opens PayPal directly without an intermediate
   payment page.
3. Sign in with a separate Sandbox personal buyer account and approve payment.
4. PayPal returns directly to PureVia's original Order Success / Order Placed page.
   The returned token must match the saved gateway order.
   A same-origin, CSRF-protected POST captures or reconciles the approved payment.
5. PHP verifies the gateway order ID, local order identity, PHP currency, exact
   amount, and completed capture status before marking the order paid and opening
   order confirmation. Refresh/retry reuses the saved order and request IDs.

If interrupted, use My orders → order details → Continue or cancel PayPal payment.
Check payment retries verification without creating a second local order. Closing
PayPal leaves the unpaid order available to resume. Cancel unpaid order first
checks the remote status; it reconciles an already completed capture or restores
reserved stock once. A gateway error leaves the order available for a safe retry.
Only unchanged purchased cart rows are cleared on verified payment.

Schedule `php tools/expire-paypal-checkouts.php` hourly to reconcile/cancel unpaid
reservations older than three hours. No scheduler is installed by this feature.
PayPal errors and cancellation return to the same original confirmation layout,
showing the actual unpaid/cancelled status and recovery controls. The initial
redirect and return handling need JavaScript; manual retry controls
remain on the payment page after an error. Private keys never enter browser code.

## Verification and limits

Sandbox OAuth authentication and creation of an unpaid PHP 1.00 gateway order were
verified against the configured PayPal app. Capture, mismatch rejection, retries,
cancellation, and stock restoration were checked with simulated gateway responses
and connection-local temporary database tables. No real local orders, inventory,
or customer payments were changed by those checks.

A complete Sandbox purchase still requires a buyer to sign in and approve on
PayPal. No buyer credentials were used and no actual capture was performed during
automated checks. This does not enable live payments, automated refunds, or refund
and dispute webhooks. Historical card-payment recovery code remains for existing
orders; new checkout orders accept only COD or PayPal.

## Render payment webhook

In the same PayPal business app used by `PAYPAL_CLIENT_ID`, add a webhook with:

- URL: `https://YOUR-RENDER-DOMAIN/paypal-webhook.php`
- Event: `PAYMENT.CAPTURE.COMPLETED`

Copy the registered **Webhook ID** into Render's Environment:

```dotenv
PAYPAL_WEBHOOK_ID="your-registered-webhook-id"
```

Keep `PAYPAL_MODE`, Client ID, Secret, and Webhook ID in the same Sandbox or Live
environment. The buyer account does not supply these values. The webhook ID is
different from the Client ID and payment/order IDs. For local testing, a public
HTTPS forwarding URL is required; PayPal cannot deliver to `localhost`.

The endpoint verifies the signature through PayPal before accessing orders, then
fetches the remote order to verify its identity, capture ID, PHP currency, and
exact amount. Repeated notifications do not charge again or deduct stock again.
It only reconciles completed captures; it does not initiate captures or change
checkout redirects. A payment for an already cancelled order needs manual review.
No database migration is needed for this webhook.

Test with a real Sandbox checkout, then inspect delivery in PayPal's Webhook
Events dashboard. The Webhooks Simulator's mock signatures do not verify against
your registered ID with this verification API. Existing unpaid checkouts still
need the normal approval/capture flow.

Reference: [PayPal webhook integration](https://developer.paypal.com/api/rest/webhooks/rest/).
