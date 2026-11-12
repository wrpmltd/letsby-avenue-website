<?php
/**
 * Enquiry / free rental valuation form, no plugin needed.
 *   [la_enquiry button="Get My Free Rental Valuation" source="Homepage"]
 *
 * Every submission is saved in WP Admin › Enquiries (so nothing is lost if an
 * email fails) and emailed to the enquiries address in letsby_business().
 * Spam protection: hidden honeypot field + minimum fill time + rate limit.
 */
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('la_enquiry', [
        'label' => 'Enquiries',
        'labels' => ['singular_name' => 'Enquiry'],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => ['title', 'editor'],
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'map_meta_cap' => true,
    ]);
});

add_shortcode('la_enquiry', function ($atts) {
    $a = shortcode_atts(['button' => 'Send enquiry', 'source' => ''], $atts);
    $action = esc_url(admin_url('admin-post.php'));
    $source = esc_attr($a['source'] ?: (is_singular() ? get_the_title() : ''));
    $err = isset($_GET['la_err']) ? '<p class="la-form__error" role="alert">Please fill in your name, a valid email or phone number, and try again.</p>' : '';
    $t = time();
    $btn = esc_html($a['button']);
    return <<<HTML
<form class="la-form" method="post" action="{$action}">
  {$err}
  <input type="hidden" name="action" value="la_enquiry">
  <input type="hidden" name="la_source" value="{$source}">
  <input type="hidden" name="la_t" value="{$t}">
  <div class="la-form__hp" aria-hidden="true"><label>Leave this empty<input type="text" name="la_website" tabindex="-1" autocomplete="off"></label></div>
  <div class="la-form__row">
    <label>Name<input type="text" name="la_name" autocomplete="name" required></label>
    <label>Email<input type="email" name="la_email" autocomplete="email" required></label>
  </div>
  <div class="la-form__row">
    <label>Phone<input type="tel" name="la_phone" autocomplete="tel"></label>
    <label>Property address or postcode<input type="text" name="la_address" autocomplete="street-address"></label>
  </div>
  <label>Message<textarea name="la_message" rows="5"></textarea></label>
  <p class="la-form__note">We'll only use these details to reply to your enquiry. <a href="/privacy-policy">Privacy policy</a></p>
  <button type="submit" class="la-btn la-btn--red">{$btn}</button>
</form>
HTML;
});

function letsby_handle_enquiry(): void {
    $back = wp_get_referer() ?: home_url('/contact');
    $f = fn($k) => isset($_POST[$k]) ? trim(wp_unslash($_POST[$k])) : '';

    // Bots: filled honeypot, or submitted in under 3 seconds → pretend success.
    if ($f('la_website') !== '' || (time() - (int) $f('la_t')) < 3) {
        wp_safe_redirect(home_url('/thank-you'));
        exit;
    }
    // Rate limit: 5 per hour per IP.
    $ipkey = 'la_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $n = (int) get_transient($ipkey);
    if ($n >= 5) { wp_safe_redirect(home_url('/thank-you')); exit; }
    set_transient($ipkey, $n + 1, HOUR_IN_SECONDS);

    $d = [
        'Name'     => sanitize_text_field($f('la_name')),
        'Email'    => sanitize_email($f('la_email')),
        'Phone'    => sanitize_text_field($f('la_phone')),
        'Property' => sanitize_text_field($f('la_address')),
        'Message'  => sanitize_textarea_field($f('la_message')),
        'Page'     => sanitize_text_field($f('la_source')),
    ];
    if ($d['Name'] === '' || ($d['Email'] === '' && $d['Phone'] === '')) {
        wp_safe_redirect(add_query_arg('la_err', 1, $back) . '#enquiry');
        exit;
    }

    $body = '';
    foreach ($d as $k => $v) $body .= "{$k}: {$v}\n";
    wp_insert_post([
        'post_type' => 'la_enquiry', 'post_status' => 'private',
        'post_title' => $d['Name'] . ($d['Property'] ? ' — ' . $d['Property'] : ''),
        'post_content' => esc_html($body),
    ]);

    $b = letsby_business();
    $headers = [];
    if ($d['Email']) $headers[] = 'Reply-To: ' . $d['Name'] . ' <' . $d['Email'] . '>';
    wp_mail($b['enquiries'], 'Website enquiry: ' . $d['Name'] . ($d['Page'] ? ' (' . $d['Page'] . ')' : ''), $body, $headers);

    wp_safe_redirect(home_url('/thank-you'));
    exit;
}
add_action('admin_post_nopriv_la_enquiry', 'letsby_handle_enquiry');
add_action('admin_post_la_enquiry', 'letsby_handle_enquiry');
