<?php
/**
 * Letsby Avenue theme.
 */
defined('ABSPATH') || exit;

define('LETSBY_VER', '1.0.0');

/* Business details used by schema, footer and forms — change here only. */
function letsby_business(): array {
    return [
        'name'      => 'Letsby Avenue',
        'legal'     => 'Letsby Avenue (North) Ltd',
        'street'    => '548 Broad Lane',
        'locality'  => 'Stanningley, Pudsey',
        'city'      => 'Leeds',
        'postcode'  => 'LS28 6PA',
        'phone'     => '0113 887 9944',
        'phone_e164'=> '+441138879944',
        'email'     => 'hello@letsby-avenue.co.uk',
        'enquiries' => 'hello@letsby-avenue.co.uk',
        // To confirm with Ste before launch (shown in the footer once filled in):
        'company_no'=> '',
        'reg_office'=> '',
        'redress'   => '',   // e.g. 'Member of The Property Redress Scheme'
        'cmp'       => '',   // client money protection scheme
        'review'    => 'https://g.page/r/CavlAdtJEHRMEAE/review',
        'ga4'       => 'G-Z4ZG5702BE',
        'hours'     => ['Mo-Fr 09:00-17:00'],
        'areas'     => ['Leeds', 'Pudsey', 'Stanningley', 'Bramley', 'Farsley', 'Armley', 'Horsforth', 'West Yorkshire'],
        'social'    => ['https://www.facebook.com/letsbyavenueyorkshire/', 'https://x.com/Letsby_Ave'],
    ];
}

add_action('after_setup_theme', function () {
    add_theme_support('wp-block-styles');
    add_theme_support('editor-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('title-tag');
    add_editor_style('assets/css/site.css');
});

add_action('wp_enqueue_scripts', function () {
    $uri = get_template_directory_uri();
    wp_enqueue_style('letsby', $uri . '/assets/css/site.css', [], (string) filemtime(__DIR__ . '/assets/css/site.css'));
    wp_enqueue_script('letsby-consent', $uri . '/assets/js/consent.js', [], (string) filemtime(__DIR__ . '/assets/js/consent.js'), ['strategy' => 'defer', 'in_footer' => true]);
    wp_localize_script('letsby-consent', 'LETSBY', ['ga4' => letsby_business()['ga4']]);
    // Not needed on a brochure site.
    wp_dequeue_style('global-styles-inline-css');
});

/* Preload the two fonts used above the fold. */
add_action('wp_head', function () {
    $f = get_template_directory_uri() . '/assets/fonts/';
    echo '<link rel="preload" href="' . esc_url($f . 'satoshi-normal-900.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
    echo '<link rel="preload" href="' . esc_url($f . 'instrument-sans-latin-400-normal.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 2);

/* Run [la_*] shortcodes inside the header/footer template parts. */
add_filter('render_block_core/template-part', fn($html) => do_shortcode($html));

/* Strip WordPress clutter that adds weight without value. */
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
add_filter('xmlrpc_enabled', '__return_false');

require __DIR__ . '/inc/seo.php';
require __DIR__ . '/inc/forms.php';
require __DIR__ . '/inc/redirects.php';
require __DIR__ . '/inc/shortcodes.php';
if (is_admin()) require __DIR__ . '/inc/setup.php';
