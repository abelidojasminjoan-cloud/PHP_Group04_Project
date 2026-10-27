<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

// Initialize the PHP session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Base URL for website links
$baseUrl = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8');
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Learn how PureVia Skin Ecosystem collects, uses, stores, and protects information.">

    <title>Privacy Policy | PureVia</title>

    <link rel="stylesheet"
      href="<?= $baseUrl ?>/assets/css/privacy_policy.css">
</head>

<body class="pv-privacy-page">

<header class="pv-privacy-header">
    <a class="pv-privacy-brand"
       href="<?= $baseUrl ?>/public/index.php"
       aria-label="PureVia homepage">
        PureVia<span>.</span>
    </a>

    <a class="pv-privacy-back"
       href="<?= $baseUrl ?>/public/register.php">
        ← Back to Registration
    </a>
</header>

<main id="main-content" class="pv-privacy-main">

    <section class="pv-privacy-intro" aria-labelledby="privacy-title">
        <span class="pv-privacy-eyebrow">
            PUREVIA SKIN ECOSYSTEM
        </span>

        <h1 id="privacy-title">Privacy Policy</h1>

        <p>
            How PureVia collects, uses, stores, and protects
            information when you use our skincare e-commerce website.
        </p>

        <div class="pv-privacy-meta">
            <span>Effective date: October 17, 2026</span>
            <span>For review before public release</span>
        </div>
    </section>

    <div class="pv-privacy-layout">

        <!-- POLICY NAVIGATION -->

        <nav class="pv-privacy-nav"
             aria-label="Privacy Policy sections">

            <p>ON THIS PAGE</p>

            <a href="#introduction">01. Introduction</a>
            <a href="#information-collected">02. Information Collected</a>
            <a href="#purpose">03. Purpose of Collection</a>
            <a href="#cookies">04. Cookies</a>
            <a href="#sharing">05. Sharing of Information</a>
            <a href="#protection">06. Protection and Retention</a>
            <a href="#rights">07. User Rights</a>
            <a href="#third-parties">08. Third-Party Services</a>
            <a href="#changes">09. Changes to This Policy</a>
            <a href="#contact">10. Contact Information</a>
        </nav>

        <!-- POLICY CONTENT -->

        <article class="pv-privacy-content">

            <section id="introduction">
                <h2><span>01</span> Introduction</h2>

                <p>
                    PureVia Skin Ecosystem values the privacy of its
                    users and is committed to protecting their
                    personal information. This Privacy Policy explains
                    how information is collected, used, stored, and
                    protected when users access the website and its
                    available services.
                </p>

                <p>
                    PureVia aims to handle personal information in
                    accordance with applicable data protection laws,
                    including the Philippine Data Privacy Act of 2012
                    (Republic Act No. 10173).
                </p>
            </section>

            <section id="information-collected">
                <h2><span>02</span> Information Collected</h2>

                <p>
                    Depending on the features used, PureVia may collect:
                </p>

                <ul>
                    <li>
                        <strong>Personal information:</strong>
                        Name, email address, and contact details
                        provided when registering or managing an account.
                    </li>

                    <li>
                        <strong>Skin profile information:</strong>
                        Skin type, selected skin concerns, sensitivity
                        preferences, and ingredients a customer chooses
                        to avoid when using personalization features.
                    </li>

                    <li>
                        <strong>Order information:</strong>
                        Products ordered, order history, delivery
                        details, and order status.
                    </li>

                    <li>
                        <strong>Payment information:</strong>
                        Payment status and transaction references
                        needed to process and verify orders.
                    </li>

                    <li>
                        <strong>Technical information:</strong>
                        Browser type, device information, IP address,
                        and website activity where collected.
                    </li>

                    <li>
                        <strong>Security information:</strong>
                        Login records and CAPTCHA verification
                        information used to help protect accounts.
                    </li>
                </ul>

                <p>
                    PureVia should collect only the information
                    necessary for its stated purposes.
                </p>
            </section>

            <section id="purpose">
                <h2><span>03</span> Purpose of Collection</h2>

                <p>
                    Information may be used to manage accounts,
                    provide skin profile matching and ingredient
                    compatibility features, process orders,
                    coordinate deliveries, verify payments, improve
                    website functionality, respond to customer
                    concerns, and protect the system against
                    unauthorized activities.
                </p>
            </section>

            <section id="cookies">
                <h2><span>04</span> Cookies</h2>

                <p>
                    PureVia may use essential cookies to maintain
                    user sessions and support security and core
                    website functionality. Optional preference,
                    analytics, or marketing cookies will be used
                    only if those features are implemented and the
                    required consent has been obtained.
                </p>

                <p>
                    Users can manage cookies through their browser
                    settings. Dedicated website cookie controls
                    should be described here only when those
                    controls are available.
                </p>
            </section>

            <section id="sharing">
                <h2><span>05</span> Sharing of Information</h2>

                <p>
                    Information may be shared with authorized service
                    providers when necessary to operate the website,
                    process payments, deliver orders, or provide
                    technical support. Information may also be
                    disclosed when required by law.
                </p>

                <p>
                    PureVia does not intend to sell users'
                    personal information.
                </p>
            </section>

            <section id="protection">
                <h2><span>06</span> Data Protection and Retention</h2>

                <p>
                    PureVia will apply reasonable technical and
                    organizational safeguards to protect personal
                    information from unauthorized access, disclosure,
                    alteration, loss, or misuse.
                </p>

                <p>
                    Information will be retained only for as long
                    as necessary for its intended purposes and
                    applicable legal obligations. When no longer
                    required, it should be securely deleted or
                    disposed of, subject to applicable retention
                    requirements.
                </p>
            </section>

            <section id="rights">
                <h2><span>07</span> User Rights</h2>

                <p>
                    Subject to applicable law, users may request
                    access to their personal information, correction
                    of inaccurate information, or deletion or
                    blocking when legally applicable.
                </p>

                <p>
                    Users may also exercise other rights available
                    under applicable data protection laws.
                    Requests should be submitted through PureVia's
                    designated privacy contact once the official
                    contact channel has been established.
                </p>
            </section>

            <section id="third-parties">
                <h2><span>08</span> Third-Party Services</h2>

                <p>
                    PureVia may use third-party providers for
                    hosting, payments, delivery, or other website
                    functions. These providers may process
                    information under their own privacy policies
                    and applicable agreements.
                </p>
            </section>

            <section id="changes">
                <h2><span>09</span> Changes to This Policy</h2>

                <p>
                    PureVia may revise this policy when its
                    features, data practices, or legal requirements
                    change. The current version should be published
                    on the website with an updated revision date.
                </p>
            </section>

            <section id="contact">
                <h2><span>10</span> Contact Information</h2>

                <dl class="pv-privacy-contact">
                    <div>
                        <dt>System</dt>
                        <dd>PureVia Skin Ecosystem</dd>
                    </div>

                    <div>
                        <dt>Responsible organization</dt>
                        <dd>To be confirmed before publication</dd>
                    </div>

                    <div>
                        <dt>Privacy contact email</dt>
                        <dd>To be confirmed before publication</dd>
                    </div>

                    <div>
                        <dt>Business address</dt>
                        <dd>To be provided if applicable</dd>
                    </div>
                </dl>

                <p class="pv-privacy-review">
                    This policy must be reviewed against PureVia's
                    actual data practices before public publication.
                    Replace the outstanding contact details with
                    verified information.
                </p>

            </section>

        </article>
    </div>


    <div class="pv-privacy-footer-action">
        <a href="<?= $baseUrl ?>/public/register.php">
            ← Return to Registration
        </a>
    </div>

</main>

</main>

<footer class="pv-privacy-footer">
    &copy; <?= date('Y') ?> PureVia Skin Ecosystem
    &middot; Skincare Made Personal
</footer>

<script
    nonce="<?= pvScriptNonce() ?>"
    src="<?= $baseUrl ?>/assets/js/policy_scrollspy.js"
    defer
></script>

</body>
</html>
