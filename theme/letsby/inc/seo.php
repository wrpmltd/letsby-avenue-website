<?php
/**
 * SEO without a plugin: per-page title + description (post meta), canonical,
 * Open Graph, robots, and RealEstateAgent / WebPage / Article schema.
 *
 * Page meta keys (set by tools/import.php, editable in the page's Custom Fields):
 *   _la_title   full <title>
 *   _la_desc    meta description
 *   _la_noindex "1" to keep a page out of Google (e.g. thank-you)
 *   _la_type    "guide" for landlord guides (Article schema)
 */
defined('ABSPATH') || exit;

foreach (['_la_title', '_la_desc', '_la_noindex', '_la_type'] as $k) {
    register_post_meta('page', $k, ['type' => 'string', 'single' => true, 'show_in_rest' => true,
        'auth_callback' => fn() => current_user_can('edit_pages')]);
}

function letsby_meta(string $key): string {
    return is_singular() ? (string) get_post_meta(get_queried_object_id(), $key, true) : '';
}

add_filter('pre_get_document_title', function ($t) {
    $custom = letsby_meta('_la_title');
    return $custom !== '' ? $custom : $t;
});
add_filter('document_title_separator', fn() => '|');

/* WP's own canonical/robots are replaced by ours so there is one of each. */
remove_action('wp_head', 'rel_canonical');
remove_filter('wp_robots', 'wp_robots_max_image_preview_large');

add_action('wp_head', function () {
    $desc = letsby_meta('_la_desc');
    $url  = is_front_page() ? home_url('/') : (is_singular() ? get_permalink() : '');
    // Match the current site: no trailing slash on inner pages.
    if ($url && !is_front_page()) $url = untrailingslashit($url);
    $title = wp_get_document_title();
    $img   = get_template_directory_uri() . '/assets/img/social-share.png';

    $robots = letsby_meta('_la_noindex') === '1' || is_404() || is_search() ? 'noindex, follow' : 'index, follow, max-image-preview:large';
    echo '<meta name="robots" content="' . esc_attr($robots) . "\">\n";
    if ($desc) echo '<meta name="description" content="' . esc_attr($desc) . "\">\n";
    if ($url)  echo '<link rel="canonical" href="' . esc_url($url) . "\">\n";
    foreach ([
        'og:locale' => 'en_GB', 'og:type' => 'website', 'og:site_name' => 'Letsby Avenue',
        'og:title' => $title, 'og:description' => $desc, 'og:url' => $url, 'og:image' => $img,
    ] as $p => $v) {
        if ($v) echo '<meta property="' . $p . '" content="' . esc_attr($v) . "\">\n";
    }
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<link rel="icon" href="' . esc_url(get_template_directory_uri() . '/assets/img/favicon.png') . '">' . "\n";

    letsby_schema($title, $desc, $url);
}, 1);

function letsby_schema(string $title, string $desc, string $url): void {
    $b    = letsby_business();
    $home = home_url('/');
    $org  = [
        '@type' => 'RealEstateAgent',
        '@id' => $home . '#agency',
        'name' => $b['name'],
        'legalName' => $b['legal'],
        'url' => $home,
        'logo' => get_template_directory_uri() . '/assets/img/logo.png',
        'image' => get_template_directory_uri() . '/assets/img/social-share.png',
        'telephone' => $b['phone'],
        'email' => $b['email'],
        'slogan' => 'Your Property. Our Priority.',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $b['street'],
            'addressLocality' => $b['locality'],
            'addressRegion' => 'West Yorkshire',
            'postalCode' => $b['postcode'],
            'addressCountry' => 'GB',
        ],
        'openingHours' => $b['hours'],
        'areaServed' => array_map(fn($a) => ['@type' => 'Place', 'name' => $a], $b['areas']),
        'sameAs' => $b['social'],
    ];
    $graph = [$org, ['@type' => 'WebSite', '@id' => $home . '#website', 'url' => $home, 'name' => $b['name'], 'publisher' => ['@id' => $home . '#agency'], 'inLanguage' => 'en-GB']];

    if (is_singular() && $url) {
        $is_guide = letsby_meta('_la_type') === 'guide';
        $page = [
            '@type' => $is_guide ? 'Article' : 'WebPage',
            '@id' => $url . '#page',
            'url' => $url,
            'name' => $title,
            'description' => $desc,
            'isPartOf' => ['@id' => $home . '#website'],
            'inLanguage' => 'en-GB',
        ];
        if ($is_guide) {
            $page['headline'] = get_the_title();
            $page['author'] = ['@id' => $home . '#agency'];
            $page['publisher'] = ['@id' => $home . '#agency'];
            $page['datePublished'] = get_the_date('c');
            $page['dateModified'] = get_the_modified_date('c');
        }
        $graph[] = $page;
    }
    echo '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}

/* Inner pages without trailing slash, to keep the URLs Google already has. */
add_filter('user_trailingslashit', function ($url, $type) {
    return in_array($type, ['page', 'single'], true) ? untrailingslashit($url) : $url;
}, 10, 2);

/* Sitemap: pages only, skip noindex pages. */
add_filter('wp_sitemaps_add_provider', fn($p, $name) => $name === 'users' ? false : $p, 10, 2);
add_filter('wp_sitemaps_post_types', fn($t) => array_intersect_key($t, ['page' => 1]));
add_filter('wp_sitemaps_taxonomies', fn() => []);
add_filter('wp_sitemaps_posts_query_args', function ($args, $type) {
    if ($type === 'page') {
        $args['meta_query'] = [['relation' => 'OR', ['key' => '_la_noindex', 'compare' => 'NOT EXISTS'], ['key' => '_la_noindex', 'value' => '1', 'compare' => '!=']]];
    }
    return $args;
}, 10, 2);
