<?php
/**
 * Small shortcodes so business details live in one place (letsby_business()).
 *   [la_phone] [la_phone link="0"] [la_email] [la_address] [la_review_link] [la_year]
 */
defined('ABSPATH') || exit;

add_shortcode('la_phone', function ($a) {
    $b = letsby_business();
    $a = shortcode_atts(['link' => '1'], $a);
    return $a['link'] === '0' ? esc_html($b['phone']) : '<a href="tel:' . esc_attr($b['phone_e164']) . '">' . esc_html($b['phone']) . '</a>';
});
add_shortcode('la_email', function () {
    $e = letsby_business()['email'];
    return '<a href="mailto:' . esc_attr($e) . '">' . esc_html($e) . '</a>';
});
add_shortcode('la_address', function () {
    $b = letsby_business();
    return esc_html("{$b['street']}, {$b['locality']}, {$b['city']} {$b['postcode']}");
});
add_shortcode('la_review_link', fn() => '<a href="' . esc_url(letsby_business()['review']) . '" target="_blank" rel="noopener">Leave us a Google review</a>');
add_shortcode('la_year', fn() => date('Y'));
add_shortcode('la_company', function () {
    $b = letsby_business();
    $out = [];
    if ($b['company_no']) $out[] = 'Registered in England and Wales, company no. ' . $b['company_no'] . '.';
    if ($b['reg_office']) $out[] = 'Registered office: ' . $b['reg_office'] . '.';
    if ($b['redress'])    $out[] = $b['redress'] . '.';
    if ($b['cmp'])        $out[] = $b['cmp'] . '.';
    return esc_html(implode(' ', $out));
});
add_shortcode('la_legal', fn() => esc_html(letsby_business()['legal']));
