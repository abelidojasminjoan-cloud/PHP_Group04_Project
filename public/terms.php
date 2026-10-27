<?php
declare(strict_types=1);

/* =========================================
   PUREVIA - TERMS AND CONDITIONS
   Public Information Page
========================================= */

require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$baseUrl = htmlspecialchars(
    BASE_URL,
    ENT_QUOTES,
    'UTF-8'
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="robots" content="noindex, nofollow">

    <meta name="description"
          content="Read the Terms and Conditions of PureVia Skin Ecosystem.">

    <title>Terms and Conditions | PureVia</title>

    <!-- Existing Privacy Policy Styles -->
    <link
        rel="stylesheet"
        href="<?= $baseUrl ?>/assets/css/privacy_policy.css"
    >
</head>

<body class="pv-privacy-page">

<!-- ======================================
     HEADER
====================================== -->

<header class="pv-privacy-header">

    <a
        class="pv-privacy-brand"
        href="<?= $baseUrl ?>/public/index.php"
        aria-label="PureVia Homepage"
    >
        PureVia<span>.</span>
    </a>

    <a
        class="pv-privacy-back"
        href="<?= $baseUrl ?>/public/register.php"
    >
        &larr; Back to Registration
    </a>

</header>


<!-- ======================================
     MAIN CONTENT
====================================== -->

<main class="pv-privacy-main">

    <!-- PAGE INTRODUCTION -->

    <section class="pv-privacy-intro">

        <span class="pv-privacy-eyebrow">
            PUREVIA SKIN ECOSYSTEM
        </span>

        <h1>Terms and Conditions</h1>

        <p>
            Please review these Terms and Conditions
            carefully before accessing, registering,
            or using PureVia Skin Ecosystem.
        </p>

        <div class="pv-privacy-meta">

            <span>
                Effective Date: To be confirmed
            </span>

            <span>
                Last Updated: To be confirmed
            </span>

        </div>

    </section>


    <!-- ======================================
         TERMS LAYOUT
    ====================================== -->

    <div class="pv-privacy-layout">


        <!-- ======================================
             SIDEBAR NAVIGATION
        ====================================== -->

        <nav
            class="pv-privacy-nav"
            aria-label="Terms and Conditions Sections"
        >

            <p>ON THIS PAGE</p>

            <a href="#acceptance">
                01. Acceptance of Terms
            </a>

            <a href="#about">
                02. About PureVia
            </a>

            <a href="#accounts">
                03. User Accounts
            </a>

            <a href="#products">
                04. Product Information
            </a>

            <a href="#orders">
                05. Orders and Pricing
            </a>

            <a href="#payments">
                06. Payments
            </a>

            <a href="#shipping">
                07. Shipping and Delivery
            </a>

            <a href="#refunds">
                08. Returns and Refunds
            </a>

            <a href="#acceptable-use">
                09. Acceptable Use
            </a>

            <a href="#privacy">
                10. Privacy and Cookies
            </a>

            <a href="#availability">
                11. Website Availability
            </a>

            <a href="#liability">
                12. Limitation of Liability
            </a>

            <a href="#changes">
                13. Changes to Terms
            </a>

            <a href="#law">
                14. Applicable Law
            </a>

            <a href="#contact">
                15. Contact Information
            </a>

        </nav>


        <!-- ======================================
             TERMS CONTENT
        ====================================== -->

        <article class="pv-privacy-content">


            <!-- 01 - ACCEPTANCE -->

            <section id="acceptance">

                <h2>
                    <span>01</span>
                    Acceptance of Terms
                </h2>

                <p>
                    By accessing or using PureVia Skin
                    Ecosystem, users agree to comply with
                    these Terms and Conditions.
                </p>

                <p>
                    Users who do not agree with these
                    terms should discontinue using
                    the website.
                </p>

            </section>


            <!-- 02 - ABOUT PUREVIA -->

            <section id="about">

                <h2>
                    <span>02</span>
                    About PureVia
                </h2>

                <p>
                    PureVia Skin Ecosystem is an e-commerce
                    platform designed to allow users to
                    browse skincare products, manage their
                    accounts, and place orders through
                    the features available on the website.
                </p>

            </section>


            <!-- 03 - USER ACCOUNTS -->

            <section id="accounts">

                <h2>
                    <span>03</span>
                    User Accounts
                </h2>

                <p>
                    Users are responsible for providing
                    accurate information when creating
                    an account and for keeping their
                    login credentials confidential.
                </p>

                <p>
                    Users must not access another person's
                    account without authorization or use
                    the website for fraudulent or
                    unlawful activities.
                </p>

                <p>
                    PureVia may restrict or suspend
                    accounts when there is a reasonable
                    basis to believe that the account
                    has been used in violation of these
                    terms, subject to applicable law.
                </p>

            </section>


            <!-- 04 - PRODUCT INFORMATION -->

            <section id="products">

                <h2>
                    <span>04</span>
                    Product Information
                </h2>

                <p>
                    PureVia aims to provide accurate
                    product descriptions, prices,
                    images, and availability information.
                    However, product details, packaging,
                    and availability may change.
                </p>

                <p>
                    Skincare products may affect
                    individuals differently. Users
                    should review product ingredients
                    and directions before use and consult
                    a qualified healthcare professional
                    when appropriate.
                </p>

                <p>
                    Product information on PureVia is not
                    a substitute for professional
                    medical advice.
                </p>

            </section>


            <!-- 05 - ORDERS AND PRICING -->

            <section id="orders">

                <h2>
                    <span>05</span>
                    Orders and Pricing
                </h2>

                <p>
                    Users may place orders through the
                    ordering features available on the
                    website. An order is subject to
                    confirmation and product availability.
                </p>

                <p>
                    PureVia will display applicable
                    prices and charges before order
                    confirmation.
                </p>

                <p>
                    If a pricing or inventory error
                    occurs, PureVia will handle the
                    issue in accordance with applicable
                    law and communicate with the
                    affected customer where appropriate.
                </p>

            </section>


            <!-- 06 - PAYMENTS -->

            <section id="payments">

                <h2>
                    <span>06</span>
                    Payments
                </h2>

                <p>
                    Users must pay through the payment
                    methods made available by PureVia.
                    Orders may remain pending until
                    payment is verified.
                </p>

                <p>
                    Where third-party payment providers
                    are used, their applicable terms
                    and privacy practices may also apply.
                </p>

                <p>
                    Users must not submit false payment
                    confirmations or engage in
                    fraudulent transactions.
                </p>

            </section>


            <!-- 07 - SHIPPING -->

            <section id="shipping">

                <h2>
                    <span>07</span>
                    Shipping and Delivery
                </h2>

                <p>
                    Customers must provide accurate
                    delivery information.
                </p>

                <p>
                    Delivery times may vary depending
                    on location, courier availability,
                    and other circumstances.
                </p>

                <p>
                    PureVia will communicate relevant
                    shipping information through the
                    available system features.
                </p>

                <p>
                    Any applicable shipping fees and
                    delivery conditions should be
                    disclosed before the customer
                    completes the order.
                </p>

            </section>


            <!-- 08 - RETURNS AND REFUNDS -->

            <section id="refunds">

                <h2>
                    <span>08</span>
                    Returns, Refunds,
                    and Cancellations
                </h2>

                <p>
                    Requests for cancellations, returns,
                    replacements, or refunds will be
                    handled according to PureVia's
                    published policies and applicable
                    consumer protection laws.
                </p>

                <p>
                    Any product eligibility requirements,
                    time limits, refund procedures,
                    and exceptions should be clearly
                    communicated to customers before
                    purchase.
                </p>

                <p>
                    Nothing in these terms removes
                    consumer rights that cannot
                    legally be waived.
                </p>

            </section>


            <!-- 09 - ACCEPTABLE USE -->

            <section id="acceptable-use">

                <h2>
                    <span>09</span>
                    Acceptable Use
                </h2>

                <p>
                    Users agree not to:
                </p>

                <ul>

                    <li>
                        Submit false or misleading
                        information.
                    </li>

                    <li>
                        Attempt unauthorized access
                        to the website or its database.
                    </li>

                    <li>
                        Interfere with website security
                        or functionality.
                    </li>

                    <li>
                        Use the system for fraudulent
                        or unlawful transactions.
                    </li>

                    <li>
                        Copy, misuse, or distribute
                        website content in violation
                        of applicable rights.
                    </li>

                </ul>

            </section>


            <!-- 10 - PRIVACY AND COOKIES -->

            <section id="privacy">

                <h2>
                    <span>10</span>
                    Privacy and Cookies
                </h2>

                <p>
                    Use of PureVia is also subject
                    to its Privacy Policy and
                    Cookie Policy.
                </p>

                <p>
                    These documents explain how
                    personal information is handled
                    and how cookie preferences
                    can be managed.
                </p>

                <p>
                    Read our
                    <a
                        href="<?= $baseUrl ?>/public/privacy_policy.php"
                    >
                        Privacy Policy
                    </a>
                    for more information.
                </p>

            </section>


            <!-- 11 - WEBSITE AVAILABILITY -->

            <section id="availability">

                <h2>
                    <span>11</span>
                    Website Availability
                </h2>

                <p>
                    PureVia will make reasonable efforts
                    to maintain website availability
                    and security.
                </p>

                <p>
                    However, temporary interruptions
                    may occur because of maintenance,
                    technical problems, or circumstances
                    beyond its control.
                </p>

            </section>


            <!-- 12 - LIABILITY -->

            <section id="liability">

                <h2>
                    <span>12</span>
                    Limitation of Liability
                </h2>

                <p>
                    To the extent permitted by applicable
                    law, PureVia is not responsible
                    for losses arising from unauthorized
                    use of an account caused by a user's
                    failure to protect their credentials
                    or from temporary website interruptions
                    beyond its reasonable control.
                </p>

                <p>
                    Nothing in these terms excludes
                    or limits liability or consumer
                    rights where such exclusion or
                    limitation is prohibited by law.
                </p>

            </section>


            <!-- 13 - CHANGES -->

            <section id="changes">

                <h2>
                    <span>13</span>
                    Changes to These Terms
                </h2>

                <p>
                    PureVia may update these Terms
                    and Conditions when its features,
                    policies, or legal requirements
                    change.
                </p>

                <p>
                    Material updates should be
                    communicated appropriately,
                    and the current version should
                    be published on the website.
                </p>

            </section>


            <!-- 14 - APPLICABLE LAW -->

            <section id="law">

                <h2>
                    <span>14</span>
                    Applicable Law
                </h2>

                <p>
                    These terms are intended to
                    operate in accordance with
                    applicable laws of the Philippines,
                    including relevant consumer
                    protection and data
                    protection laws.
                </p>

            </section>


            <!-- 15 - CONTACT INFORMATION -->

            <section id="contact">

                <h2>
                    <span>15</span>
                    Contact Information
                </h2>

                <p>
                    For questions about these terms,
                    orders, or use of PureVia, contact:
                </p>

                <dl class="pv-privacy-contact">

                    <div>
                        <dt>System</dt>
                        <dd>PureVia Skin Ecosystem</dd>
                    </div>

                    <div>
                        <dt>
                            Business or Responsible
                            Organization
                        </dt>
                        <dd>
                            To be confirmed
                        </dd>
                    </div>

                    <div>
                        <dt>Email Address</dt>
                        <dd>
                            To be confirmed
                        </dd>
                    </div>

                    <div>
                        <dt>Business Address</dt>
                        <dd>
                            To be provided if applicable
                        </dd>
                    </div>

                </dl>

                <p class="pv-privacy-review">
                    These Terms and Conditions should
                    be reviewed and customized to match
                    PureVia's actual business operations,
                    payment methods, shipping arrangements,
                    and return policies before publication.
                </p>

            </section>

        </article>

    </div>


    <!-- ======================================
         BOTTOM NAVIGATION
    ====================================== -->

    <div class="pv-privacy-footer-action">

        <a href="<?= $baseUrl ?>/public/register.php">
            &larr; Return to Registration
        </a>

    </div>

</main>


<!-- ======================================
     FOOTER
====================================== -->

<footer class="pv-privacy-footer">

    &copy; <?= date('Y') ?>
    PureVia Skin Ecosystem
    &middot;
    Skincare Made Personal

</footer>

</body>

<script
    nonce="<?= pvScriptNonce() ?>"
    src="<?= $baseUrl ?>/assets/js/policy_scrollspy.js"
    defer
></script>

</html>
