# Website XSS review

Reviewed the application PHP templates and endpoints, first-party JavaScript, image URL handling, and product/profile uploads across the public site, customer account, admin, and staff areas. Existing text output was mostly escaped already. The changes standardize that protection and add browser and upload safeguards.

| Change | Where | Why |
| --- | --- | --- |
| Shared HTML encoding with both quote types escaped and invalid UTF-8 replaced | `includes/output-security.php`; existing escape helpers and output calls throughout the PHP pages and email templates | Customer names, addresses, reviews, product descriptions, catalog labels, errors, and logs must display as text rather than HTML or new attributes. Existing escaping was retained through the shared helper. |
| Shared JSON encoding with `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS`, and `JSON_HEX_QUOT` | Inline configuration in public/account pages and the admin Skin Match footer | A value containing `</script>` must not close a script element and introduce executable markup. JSON in HTML attributes still passes through HTML encoding as well. |
| A new random script nonce per response and an enforced Content Security Policy | `config/app.php`, `includes/output-security.php`, and explicit nonce attributes on application script tags | Unmarked injected scripts, JavaScript URLs, inline event handlers, and plugin objects are blocked. The policy also restricts base URLs, framing, and form destinations. Authorized application and third-party scripts retain their nonce. No output-buffer rewriting is used to grant nonces to arbitrary HTML. |
| Removed inline `onerror` and `onclick` handlers | Product images in home, cart, checkout, orders, and admin/staff products; homepage Skin Quiz link | Inline event attributes can stay blocked without breaking image fallbacks or quiz navigation. Image fallbacks now use `assets/js/image-fallbacks.js`; the quiz uses a normal link. |
| Removed remaining first-party `innerHTML` assignments | `assets/js/forms.js`, `assets/js/payments.js`, `assets/js/product_form.js` | These assignments previously used fixed markup. They now use DOM elements and `textContent`, avoiding HTML parsing and reducing the risk if dynamic data is added later. |
| Enforced same-origin HTTP/HTTPS checkout redirects | `assets/js/checkout.js` | A malformed confirmation URL cannot send the browser to a JavaScript URL or another origin. |
| Blocked active uploaded documents and disabled MIME sniffing | `uploads/.htaccess`; global `X-Content-Type-Options: nosniff` for PHP responses | Uploaded HTML, JavaScript, SVG/XML, and PHP files must not execute as site content. Ordinary JPG, PNG, and WebP uploads remain usable. Existing upload type checks and image URL scheme checks were reviewed and retained. |
| Escaped remaining catalog status and analytics label outputs | Skin Types, Suitability, and Quiz Analytics templates | Database text follows the same HTML encoding rule even where current enum fields already limit possible values. |
| Removed output before opening PHP tags | Affected public, account, and action files | Security headers and redirects can be sent reliably. This also fixed an existing `strict_types` fatal error in `account/address_form.php`. |
| Corrected the staff product size query | `staff/products/products.php` | The existing query requested a missing `variant_name` column. It now reads `size_capacity` under that alias so the page can render and its output protections can be verified. |

## Verification

- Syntax checks cover all 113 application PHP files; dependency code under `vendor` is excluded.
- Temporary MySQL tables shadow the real tables for malicious-output and browser checks. Payloads are placed in names, addresses, reviews, catalog labels, descriptions, audit logs, and login messages without changing real customer or catalog data.
- All 43 tested public, account, admin, staff, and authenticator pages passed escaped-output, script-nonce, unexpected CSP-violation, JavaScript-error, and payload-execution checks. All 41 JavaScript files passed browser parsing checks.
- Browser probes exercise product editing, address configuration, authenticator QR rendering, image fallbacks, and quiz navigation. Separate injection probes check that an unmarked script and an inline image handler are blocked.
- Apache upload probes verify that active file types receive HTTP 403 while a PNG remains accessible with the expected MIME type and security headers.

The authenticator testing setting, password checks, account status rules, and database records are unchanged by this review. Temporary verification files are removed after execution; no test directory is added to the project.

## Maintaining these protections

- Use `pvHtml()` for HTML text and quoted attributes. It is not a JavaScript, CSS, or URL validator.
- Use `pvJson()` for data embedded in scripts. Add `nonce="<?= pvScriptNonce() ?>"` explicitly to new trusted script elements.
- Use `textContent`, DOM creation, and event listeners for dynamic UI. Keep descriptions, reviews, and street addresses as plain text.
- Validate URL protocols separately; retain the existing HTTP/HTTPS image allowlists.
- Preserve `uploads/.htaccess` on Apache deployments. Other web servers need equivalent upload restrictions and headers.

These checks cover the current application and representative payloads. They are not a guarantee against every XSS technique or an independent audit of bundled dependencies and third-party services. CSP supplements context-appropriate encoding; it does not replace it. See the [OWASP XSS prevention guidance](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) and [OWASP CSP guidance](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html).
