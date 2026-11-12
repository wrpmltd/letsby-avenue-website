<?php
/**
 * Permanent (301) redirects for old WordPress-era and Framer-era URLs.
 * Add rows to $map as old => new. Matching ignores a trailing slash and case.
 * Patterns (old property listings, blog posts) are handled below the map.
 * Full list to be filled from Search Console's 404 report + the ExpertWeb backup.
 */
defined('ABSPATH') || exit;

function letsby_redirect_map(): array {
    return [
        '/sitemap.xml'        => '/wp-sitemap.xml',
        '/about-us'           => '/about',
        '/contact-us'         => '/contact',
        '/landlord'           => '/landlords',
        '/landlord-services'  => '/landlords',
        '/property-management'=> '/landlords',
        '/fees'               => '/landlords',
        '/landlord-fees'      => '/landlords',
        '/valuation'          => '/contact',
        '/free-valuation'     => '/contact',
        '/account/login'      => '/',
        '/account'            => '/',
        '/blog'               => '/landlord-guides',
        '/news'               => '/landlord-guides',
        '/properties'         => '/',
        '/property-search'    => '/',
        '/to-let'             => '/',
        '/for-sale'           => '/',
        '/buyers-guide'       => '/landlord-guides',
        '/valuations'         => '/contact',
    ];
}

add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = strtolower(untrailingslashit(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
    $map  = letsby_redirect_map();
    $to   = $map[$path] ?? null;

    if (!$to) {
        // Old WordPress patterns.
        if (preg_match('#^/property/#', $path))                 $to = '/';               // old Alto/Vebra listings
        elseif (preg_match('#^/(category|tag|author)/#', $path)) $to = '/landlord-guides';
        elseif (preg_match('#^/\d{4}/\d{2}/#', $path))           $to = '/landlord-guides'; // dated blog posts
        elseif (preg_match('#^/wp-content/uploads/#', $path))    return;                  // let missing files 404
    }
    if ($to) {
        wp_redirect(home_url($to), 301, 'Letsby Avenue');
        exit;
    }
});
