<?php
declare(strict_types=1);

/* =====================================
   PUREVIA - ABOUT US / OUR STORY
===================================== */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Our Story | PureVia Skin Ecosystem';

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

    <meta name="description"
          content="Discover the story behind PureVia Skin Ecosystem, a student-developed skincare e-commerce platform.">

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        rel="stylesheet"
    >

    <!-- Existing PureVia CSS -->
    <link rel="stylesheet"
          href="<?= $baseUrl ?>/assets/css/style.css">

    <link rel="stylesheet"
          href="<?= $baseUrl ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= $baseUrl ?>/assets/css/footer.css">

    <link rel="stylesheet"
          href="<?= $baseUrl ?>/assets/css/responsive.css">

    <!-- About Us CSS -->
    <link rel="stylesheet"
          href="<?= $baseUrl ?>/assets/css/about_us.css">

</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pv-about">

    <!-- =====================================
         INTRODUCTION / HERO
    ====================================== -->

    <section class="pv-about-hero" aria-labelledby="about-title">

        <div class="pv-about-shell pv-about-hero-grid">

            <div class="pv-about-hero-copy">

                <p class="pv-about-eyebrow">
                    PUREVIA SKIN ECOSYSTEM
                    <span>/</span>
                    OUR STORY
                </p>

                <h1 id="about-title">
                    Discover the Story
                    <em>Behind PureVia.</em>
                </h1>

                <p class="pv-about-lead">
                    Personalized skincare shopping,
                    designed with purpose.
                </p>

                <p>
                    PureVia was created with a simple goal:
                    to make skincare shopping more convenient,
                    organized, and personalized.

                    With so many skincare products available,
                    choosing the right one can be challenging,
                    especially when every individual has different
                    skin types, concerns, and ingredient preferences.
                </p>

                <p>
                    Recognizing this challenge, our team developed
                    PureVia, a skincare e-commerce system that
                    combines online shopping with features designed
                    to help users find products that better match
                    their skincare needs.
                </p>

                <a href="#our-beginning"
                   class="pv-about-primary-link">
                    Discover our beginnings
                    <span aria-hidden="true">&nearr;</span>
                </a>

            </div>

            <div class="pv-about-hero-visual">

                <img
                    src="<?= $baseUrl ?>/assets/images/website/story-landscape.jpg"
                    alt="Nature-inspired landscape"
                    loading="eager"
                >

                <div class="pv-about-image-note">
                    A more personalized way to explore skincare.
                </div>

            </div>

        </div>

    </section>


    <!-- =====================================
         WHERE IT ALL BEGAN
    ====================================== -->

    <section
        id="our-beginning"
        class="pv-about-section pv-about-beginning"
        aria-labelledby="beginning-title"
    >

        <div class="pv-about-shell pv-about-two-col">

            <div class="pv-about-photo-frame">

                <img
                    src="<?= $baseUrl ?>/assets/images/website/story-apothecary.jpg"
                    alt="Botanical skincare preparation"
                    loading="lazy"
                >

            </div>

            <div class="pv-about-text-block">

                <p class="pv-about-eyebrow">
                    01 / WHERE IT ALL BEGAN
                </p>

                <h2 id="beginning-title">
                    An idea rooted in
                    <em>better choices.</em>
                </h2>

                <p>
                    PureVia began as an open-source programming
                    project developed by a group of Bachelor of
                    Science in Computer Science students from
                    the College of Computer Studies at
                    Pamantasan ng Lungsod ng Pasig.
                </p>

                <p>
                    Our team wanted to create more than an
                    ordinary online shopping platform.

                    We envisioned a system that would help
                    customers explore skincare products while
                    providing useful information about their
                    ingredients, suitability for different
                    skin types, and targeted skin concerns.
                </p>

                <p>
                    Through this idea, PureVia Skin Care was
                    developed as a platform that brings product
                    discovery, personalized matching, and
                    convenient online purchasing together
                    in one place.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================
         MORE THAN JUST AN ONLINE STORE
    ====================================== -->

    <section
        class="pv-about-section pv-about-features"
        aria-labelledby="features-title"
    >

        <div class="pv-about-shell">

            <div class="pv-about-heading-row">

                <div>

                    <p class="pv-about-eyebrow">
                        02 / WHAT MAKES US DIFFERENT
                    </p>

                    <h2 id="features-title">
                        More than just
                        <em>an online store.</em>
                    </h2>

                </div>

                <p>
                    PureVia focuses on helping customers
                    make more informed skincare shopping
                    decisions with information relevant
                    to their needs.
                </p>

            </div>

            <div class="pv-about-feature-grid">

                <!-- SKIN TYPE MATCHING -->

                <article class="pv-about-feature-card">

                    <span class="pv-about-feature-no">01</span>

                    <span class="pv-about-feature-icon"
                          aria-hidden="true">
                        <i class="fa-regular fa-user"></i>
                    </span>

                    <h3>Skin Type Matching</h3>

                    <p>
                        Customers can create a skin profile
                        by selecting their known skin type
                        and concerns to explore products
                        relevant to their skincare needs.
                    </p>

                </article>


                <!-- GUIDED SKIN TYPE FINDER -->

                <article class="pv-about-feature-card">

                    <span class="pv-about-feature-no">02</span>

                    <span class="pv-about-feature-icon"
                          aria-hidden="true">
                        <i class="fa-solid fa-list-check"></i>
                    </span>

                    <h3>Guided Skin Type Finder</h3>

                    <p>
                        Customers who are unsure of their skin
                        type can use a guided finder based on
                        predefined rules to suggest a possible
                        skin type and identify relevant products.
                    </p>

                </article>


                <!-- INGREDIENT COMPATIBILITY -->

                <article class="pv-about-feature-card">

                    <span class="pv-about-feature-no">03</span>

                    <span class="pv-about-feature-icon"
                          aria-hidden="true">
                        <i class="fa-solid fa-leaf"></i>
                    </span>

                    <h3>Ingredient Compatibility</h3>

                    <p>
                        Customers can select ingredients they
                        prefer to avoid.

                        When an ingredient appears in a
                        product's ingredient list, the system
                        displays an informational notice
                        for the customer to review.
                    </p>

                </article>

            </div>

            <p class="pv-about-feature-bottom">
                Alongside these features, customers can browse
                skincare products, manage their shopping carts,
                place orders, select payment methods, and
                monitor their order status through PureVia.
            </p>

        </div>

    </section>


    <!-- =====================================
         DEVELOPMENT TEAM
    ====================================== -->

    <section
        class="pv-about-section pv-about-team"
        aria-labelledby="team-title"
    >

        <div class="pv-about-shell">

            <p class="pv-about-eyebrow">
                03 / THE PEOPLE BEHIND PUREVIA
            </p>

            <h2 id="team-title">
                Built by a team with
                <em>a shared purpose.</em>
            </h2>

            <p class="pv-about-section-lead">
                PureVia was developed through the combined
                efforts of four student developers who shared
                a common goal of applying their knowledge of
                computer science to a practical digital solution.
            </p>

            <ul class="pv-about-team-grid">

                <li>
                    <span class="pv-about-team-initials">JA</span>
                    <span>Jasmin Joan R. Abelido</span>
                </li>

                <li>
                    <span class="pv-about-team-initials">MC</span>
                    <span>Maria Dianne R. Cuvin</span>
                </li>

                <li>
                    <span class="pv-about-team-initials">AM</span>
                    <span>Ahmhida M. Macadato</span>
                </li>

                <li>
                    <span class="pv-about-team-initials">BT</span>
                    <span>Bryan Roi R. Tambe</span>
                </li>

            </ul>

            <p class="pv-about-team-closing">
                As a team, we worked toward transforming our
                concept into a functional skincare e-commerce
                system.

                The project allowed us to apply our knowledge
                of web development, database management,
                system functionality, and user experience
                while learning how technology can respond
                to everyday needs.

                PureVia represents our shared effort,
                collaboration, and commitment to bringing
                skincare information and digital convenience
                together.
            </p>

        </div>

    </section>


    <!-- =====================================
         PURPOSE AND VISION
    ====================================== -->

    <section
        class="pv-about-section pv-about-vision"
        aria-labelledby="vision-title"
    >

        <div class="pv-about-shell pv-about-vision-grid">

            <div>

                <p class="pv-about-eyebrow">
                    04 / OUR PURPOSE &amp; VISION
                </p>

                <h2 id="vision-title">
                    Helping you shop with
                    <em>greater awareness.</em>
                </h2>

            </div>

            <div>

                <p>
                    Our purpose is to provide a convenient
                    platform where customers can discover
                    skincare products and explore information
                    relevant to their skin profiles and
                    ingredient preferences.
                </p>

                <p>
                    We envision PureVia as a user-friendly
                    skincare shopping experience where product
                    information is easier to access and
                    purchasing decisions can be made
                    with greater awareness.
                </p>

                <p class="pv-about-disclaimer">
                    <strong>A note about personalization:</strong>

                    PureVia's product matching and ingredient
                    information are for informational purposes
                    only.

                    They do not provide medical diagnoses or
                    guarantee that a product will be suitable
                    or safe for every individual.
                </p>

            </div>

        </div>

    </section>


    <!-- =====================================
         OUR JOURNEY CONTINUES
    ====================================== -->

    <section
        class="pv-about-journey"
        aria-labelledby="journey-title"
    >

        <div class="pv-about-shell pv-about-journey-inner">

            <p class="pv-about-eyebrow">
                05 / OUR JOURNEY CONTINUES
            </p>

            <h2 id="journey-title">
                Technology and skincare,
                <em>thoughtfully connected.</em>
            </h2>

            <p>
                PureVia is more than a programming project.

                It represents our team's journey of learning,
                collaboration, and turning an idea into a
                digital experience.

                From its initial concept to the development
                of its core features, every step has
                contributed to our goal of making skincare
                shopping more convenient and personalized.
            </p>

            <p>
                As we continue to improve the system,
                our vision remains the same: to bring
                technology and skincare together in a
                way that helps users explore their
                options with greater confidence.
            </p>

            <a
                href="<?= $baseUrl ?>/public/shop.php"
                class="pv-about-primary-link pv-about-cta-link"
            >
                Explore skincare products
                <span aria-hidden="true">&nearr;</span>
            </a>

            <p class="pv-about-signature">
                PureVia — A more personalized way to explore skincare.
            </p>

        </div>

    </section>

</main>


<!-- EXISTING PUREVIA FOOTER -->
<?php include __DIR__ . '/../includes/footer.php'; ?>


<!-- EXISTING PUREVIA JAVASCRIPT -->
<script>
    window.PUREVIA_BASE_URL =
        <?= json_encode(
            BASE_URL,
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
        ) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
