<?php
/**
 * Tools › Letsby setup — one-click setup for a fresh WordPress install.
 *   1. Download fonts and images from the old Framer site into the theme.
 *   2. Create or update every page from theme/setup/content/*.html (matched by slug),
 *      with SEO title/description, homepage, permalinks, timezone and language.
 * Safe to run again: pages are updated in place, assets already present are skipped.
 */
defined('ABSPATH') || exit;

function letsby_asset_list(): array {
    $img = 'https://framerusercontent.com/images/';
    $fs  = 'https://framerusercontent.com/third-party-assets/fontshare/wf/';
    $fsrc = 'https://cdn.jsdelivr.net/npm/@fontsource/instrument-sans@5.3.0/files/';
    return [
        'assets/fonts/satoshi-700.woff2' => $fs . 'CDEBEFT2R7XKNGXSBBLZGMY4MMHZG75P/HEVKDGQCYDZ7Z6CDVR2ZQGBCTUD6ZARH/BKWEE3VKGTFABE37K2DTH625VUSN2N35.woff2',
        'assets/fonts/satoshi-900.woff2' => $fs . '5ICO57VJIN252OT5L2KLEIPW754DTLAO/ZU64SLR2IEO66N27IVY5Z6JJJRTJECJK/TCTRLNEXANFIGSFCZTGQL7PZ5362GYK6.woff2',
        'assets/fonts/instrument-sans-latin-400-normal.woff2' => $fsrc . 'instrument-sans-latin-400-normal.woff2',
        'assets/fonts/instrument-sans-latin-500-normal.woff2' => $fsrc . 'instrument-sans-latin-500-normal.woff2',
        'assets/fonts/instrument-sans-latin-600-normal.woff2' => $fsrc . 'instrument-sans-latin-600-normal.woff2',
        'assets/fonts/instrument-sans-latin-700-normal.woff2' => $fsrc . 'instrument-sans-latin-700-normal.woff2',
        'assets/img/logo.png'                 => $img . 'NdpYsdp0JAdUB45hMBGIfykY.png?width=720',
        'assets/img/favicon.png'              => $img . 'Ophr1BTBw8XCKC87Pn6LtzonL5A.png',
        'assets/img/social-share.png'         => $img . 'Um3DWXQF6IhYC9YkBt9hSY4mk.png',
        'assets/img/hero-leeds-town-hall.jpg' => $img . 'LDGmkWPuOQIGD4D0KyMxCLntKU.jpg?width=1800',
        'assets/img/home-personal.png'        => $img . '5gWF1bLLPJzbIx03CqSjtwQ4.png?width=1200',
        'assets/img/home-managed.png'         => $img . 'vDrWDzDLFxgnn7nh2sOk5dDEJVk.png?width=1200',
        'assets/img/home-guides.png'          => $img . 'b3kGrAoDJvpJWu0Aim1tjEKuE.png?width=1200',
        'assets/img/terrace-red-door.png'     => $img . 'c7uXtTsYO8qyvKLUaRkNLkXnEaM.png?width=1400',
        'assets/img/keys-house-model.png'     => $img . 'NFdCbWA6drrkvj1V6PvZ3W01gU.png?width=1400',
        'assets/img/guides-homes.png'         => $img . 'dsa5W8IXNAjoSi4ZeSUK5Av1vbQ.png?width=1400',
        'assets/img/contact-street.jpg'       => $img . '9UH8YT2NTTengeF141tXEI0dRE.jpg?width=1400',
        'assets/img/leeds-rental-market.png'  => $img . 'hPAGXz1zQv9ALESG6M0c8hgKvM4.png?width=1400',
        'assets/img/guide-preparing.png'      => $img . '3xHDiJVgsF2n7w1znMrIzGZiI.png?width=1000',
        'assets/img/guide-managed.png'        => $img . '95vzTciihfE7WiddlvUV2QTrWjU.png?width=1000',
        'assets/img/guide-market.png'         => $img . 'H7L54L0hqIdx0Ll8qxPdxNZvP8.png?width=1000',
        'assets/img/guide-records.png'        => $img . 'Y2NyBauK1kpxra1T0ih8XsTRAc4.png?width=1000',
        'assets/img/guide-obligations.png'    => $img . 'JO1KU2e0rlcg28bsLPSClKpGVU.png?width=1000',
        'assets/img/guide-switching.png'      => $img . 'OPH0EGsdy9Y28s2UioAN08h74QM.png?width=1000',
    ];
}

function letsby_fetch_assets(): array {
    $log = [];
    $dir = get_template_directory();
    foreach (letsby_asset_list() as $dest => $url) {
        $path = $dir . '/' . $dest;
        if (file_exists($path) && filesize($path) > 0) { $log[] = "skip  $dest"; continue; }
        wp_mkdir_p(dirname($path));
        $r = wp_remote_get($url, ['timeout' => 60]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) {
            $log[] = "FAIL  $dest (" . (is_wp_error($r) ? $r->get_error_message() : wp_remote_retrieve_response_code($r)) . ')';
            continue;
        }
        file_put_contents($path, wp_remote_retrieve_body($r));
        $log[] = sprintf('saved %s (%d KB)', $dest, filesize($path) / 1024);
    }
    return $log;
}

function letsby_import_pages(): array {
    $log  = [];
    $base = get_template_directory() . '/setup/content/';
    $list = json_decode((string) file_get_contents($base . 'pages.json'), true);
    if (!is_array($list)) return ['FAIL  pages.json missing or invalid'];

    $menu_order = 0;
    foreach ($list as $p) {
        $file = $base . $p['slug'] . '.html';
        if (!file_exists($file)) { $log[] = "FAIL  no content file for {$p['slug']}"; continue; }
        $existing = get_page_by_path($p['slug'], OBJECT, 'page');
        $data = [
            'post_type' => 'page', 'post_status' => 'publish',
            'post_title' => $p['title'], 'post_name' => $p['slug'],
            'post_content' => file_get_contents($file), 'menu_order' => $menu_order++,
            'comment_status' => 'closed', 'ping_status' => 'closed',
        ];
        if ($existing) $data['ID'] = $existing->ID;
        // Block markup must not be mangled by kses on save.
        kses_remove_filters();
        $id = wp_insert_post(wp_slash($data), true);
        kses_init_filters();
        if (is_wp_error($id)) { $log[] = "FAIL  {$p['slug']}: " . $id->get_error_message(); continue; }
        update_post_meta($id, '_la_title', $p['seo']);
        update_post_meta($id, '_la_desc', $p['desc']);
        empty($p['noindex']) ? delete_post_meta($id, '_la_noindex') : update_post_meta($id, '_la_noindex', '1');
        empty($p['guide']) ? delete_post_meta($id, '_la_type') : update_post_meta($id, '_la_type', 'guide');
        if (!empty($p['front'])) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $id);
        }
        $log[] = ($existing ? 'updated ' : 'created ') . $p['slug'];
    }

    // Site settings.
    update_option('blogname', 'Letsby Avenue');
    update_option('blogdescription', 'Letting agents and property management in Leeds');
    update_option('timezone_string', 'Europe/London');
    update_option('date_format', 'j F Y');
    update_option('default_comment_status', 'closed');
    update_option('permalink_structure', '/%postname%/');
    flush_rewrite_rules();
    if (!function_exists('wp_download_language_pack')) require_once ABSPATH . 'wp-admin/includes/translation-install.php';
    if (wp_download_language_pack('en_GB')) { update_option('WPLANG', 'en_GB'); $log[] = 'language set to English (UK)'; }

    // Remove WordPress sample content.
    foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $type) {
        $p = get_page_by_path($slug, OBJECT, $type);
        if ($p) { wp_delete_post($p->ID, true); $log[] = "removed sample $slug"; }
    }
    $wp_privacy = (int) get_option('wp_page_for_privacy_policy');
    $ours = get_page_by_path('privacy-policy', OBJECT, 'page');
    if ($ours) update_option('wp_page_for_privacy_policy', $ours->ID);
    if ($wp_privacy && (!$ours || $wp_privacy !== $ours->ID) && get_post_status($wp_privacy) === 'draft') { wp_delete_post($wp_privacy, true); $log[] = 'removed WordPress draft privacy page'; }

    // Keep staging out of Google; the live domain is indexable.
    $live = str_contains(home_url(), 'letsby-avenue.co.uk');
    update_option('blog_public', $live ? 1 : 0);
    $log[] = $live ? 'search engines: allowed (live domain)' : 'search engines: blocked (staging)';
    return $log;
}

add_action('admin_menu', function () {
    add_management_page('Letsby setup', 'Letsby setup', 'manage_options', 'letsby-setup', function () {
        $log = [];
        if (isset($_POST['letsby_go']) && check_admin_referer('letsby_setup')) {
            if (!empty($_POST['assets'])) $log = array_merge($log, letsby_fetch_assets());
            if (!empty($_POST['pages']))  $log = array_merge($log, letsby_import_pages());
        }
        echo '<div class="wrap"><h1>Letsby setup</h1><form method="post">';
        wp_nonce_field('letsby_setup');
        echo '<p><label><input type="checkbox" name="assets" value="1" checked> Download fonts and images</label></p>';
        echo '<p><label><input type="checkbox" name="pages" value="1" checked> Create / update all pages and site settings</label></p>';
        echo '<p><button class="button button-primary" name="letsby_go" value="1">Run setup</button></p></form>';
        if ($log) echo '<h2>Result</h2><pre id="letsby-log" style="background:#fff;padding:12px;border:1px solid #ccd0d4">' . esc_html(implode("\n", $log)) . '</pre>';
        echo '</div>';
    });
});
