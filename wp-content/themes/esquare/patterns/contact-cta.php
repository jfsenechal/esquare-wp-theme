<?php
/**
 * Title: Bandeau contact
 * Slug: esquare/contact-cta
 * Categories: esquare
 * Description: Bandeau d'appel à l'action avec coordonnées.
 * Keywords: contact, cta, appel
 */
?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"gradient":"linear-gradient(135deg, #0E1F33 0%, #1A2D4A 100%)"}},"textColor":"white","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-white-color has-text-color has-background" style="background:linear-gradient(135deg, #0E1F33 0%, #1A2D4A 100%);padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40)">

    <!-- wp:heading {"textAlign":"center","level":2,"style":{"color":{"text":"#ffffff"}}} -->
    <h2 class="wp-block-heading has-text-align-center" style="color:#ffffff">Envie d'en savoir plus ?</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem"},"color":{"text":"#E2E8F0"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|40"}}}} -->
    <p class="has-text-align-center" style="color:#E2E8F0;margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--40);font-size:1.125rem">Passez nous voir, écrivez-nous, ou appelez. On vous fait visiter.</p>
    <!-- /wp:paragraph -->

    <!-- wp:group {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|50"}}} -->
    <div class="wp-block-group">

        <!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
        <div class="wp-block-group">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.8125rem","letterSpacing":"0.15em","textTransform":"uppercase","fontWeight":"600"},"color":{"text":"#F2B33D"}}} --><p class="has-text-align-center" style="color:#F2B33D;font-size:0.8125rem;font-weight:600;letter-spacing:0.15em;text-transform:uppercase">Téléphone</p><!-- /wp:paragraph -->
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem","fontWeight":"600"}}} --><p class="has-text-align-center" style="font-size:1.125rem;font-weight:600"><a href="tel:+3284327054" style="color:#ffffff">+32 (0)84 32 70 54</a></p><!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
        <div class="wp-block-group">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.8125rem","letterSpacing":"0.15em","textTransform":"uppercase","fontWeight":"600"},"color":{"text":"#F2B33D"}}} --><p class="has-text-align-center" style="color:#F2B33D;font-size:0.8125rem;font-weight:600;letter-spacing:0.15em;text-transform:uppercase">E-mail</p><!-- /wp:paragraph -->
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem","fontWeight":"600"}}} --><p class="has-text-align-center" style="font-size:1.125rem;font-weight:600"><a href="mailto:info@esquare.be" style="color:#ffffff">info@esquare.be</a></p><!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

        <!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
        <div class="wp-block-group">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"0.8125rem","letterSpacing":"0.15em","textTransform":"uppercase","fontWeight":"600"},"color":{"text":"#F2B33D"}}} --><p class="has-text-align-center" style="color:#F2B33D;font-size:0.8125rem;font-weight:600;letter-spacing:0.15em;text-transform:uppercase">Adresse</p><!-- /wp:paragraph -->
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem","fontWeight":"600"}}} --><p class="has-text-align-center" style="font-size:1.125rem;font-weight:600">Rue Victor Libert 36 J<br>6900 Marche-en-Famenne</p><!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->

    </div>
    <!-- /wp:group -->

    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
    <div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)">
        <!-- wp:button {"backgroundColor":"yellow","textColor":"navy","style":{"border":{"radius":"999px"},"typography":{"fontWeight":"600"}}} -->
        <div class="wp-block-button"><a class="wp-block-button__link has-navy-color has-yellow-background-color has-text-color has-background wp-element-button" href="/contact/" style="border-radius:999px;font-weight:600">Nous écrire</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

</section>
<!-- /wp:group -->
