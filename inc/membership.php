<?php
/**
 * Membership: login, registration, $20 Stripe access, and content gating.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/** User meta: one-time access flag after successful payment. */
const ANTIQUES_MEMBERSHIP_META = 'antiques_membership_active';

/**
 * Store latest checkout error for current user (short-lived).
 *
 * @param string $message Error message.
 */
function antiques_marketplace_set_checkout_error( $message ) {
	$user_id = get_current_user_id();
	if ($user_id < 1) {
		return;
	}
	set_transient('antiques_checkout_error_' . $user_id, sanitize_text_field((string) $message), 10 * MINUTE_IN_SECONDS);
}

/**
 * Read and clear latest checkout error for current user.
 *
 * @return string
 */
function antiques_marketplace_pop_checkout_error() {
	$user_id = get_current_user_id();
	if ($user_id < 1) {
		return '';
	}
	$key     = 'antiques_checkout_error_' . $user_id;
	$message = get_transient($key);
	delete_transient($key);
	return is_string($message) ? $message : '';
}

/**
 * Open the entire site without login or payment (local development).
 */
function antiques_marketplace_bypass_membership() {
	$v = strtolower(antiques_marketplace_env('ANTIQUES_BYPASS_MEMBERSHIP', ''));
	return in_array($v, array('1', 'true', 'yes', 'on'), true);
}

/**
 * Logged-in users skip the paid subscription (still must have an account).
 */
function antiques_marketplace_bypass_subscription_only() {
	$v = strtolower(antiques_marketplace_env('ANTIQUES_BYPASS_SUBSCRIPTION', ''));
	return in_array($v, array('1', 'true', 'yes', 'on'), true);
}

/**
 * Subscription price in smallest currency unit (e.g. cents for USD).
 */
function antiques_marketplace_subscription_amount_cents() {
	$cents = (int) antiques_marketplace_env('SUBSCRIPTION_PRICE_CENTS', '2000');
	return max(50, $cents);
}

/**
 * ISO currency code for Stripe Checkout.
 */
function antiques_marketplace_subscription_currency() {
	$cur = strtoupper(preg_replace('/[^A-Z]/', '', antiques_marketplace_env('SUBSCRIPTION_CURRENCY', 'USD')));
	return '' !== $cur ? $cur : 'USD';
}

/**
 * Whether the user may view gated marketplace content.
 *
 * @param int|null $user_id User ID or null for current user.
 */
function antiques_marketplace_user_has_membership( $user_id = null ) {
	if (antiques_marketplace_bypass_membership()) {
		return true;
	}

	if (null === $user_id) {
		$user_id = get_current_user_id();
	}

	if (!$user_id) {
		return false;
	}

	if (user_can($user_id, 'manage_options')) {
		return true;
	}

	if (antiques_marketplace_bypass_subscription_only()) {
		return true;
	}

	return get_user_meta($user_id, ANTIQUES_MEMBERSHIP_META, true) === '1';
}

/**
 * Grant lifetime access after confirmed payment.
 *
 * @param int $user_id User ID.
 */
function antiques_marketplace_grant_membership( $user_id ) {
	$user_id = absint($user_id);
	if ($user_id < 1) {
		return;
	}
	update_user_meta($user_id, ANTIQUES_MEMBERSHIP_META, '1');
	update_user_meta($user_id, 'antiques_membership_paid_at', time());
}

/**
 * Create login / register / subscribe pages if missing.
 */
function antiques_marketplace_ensure_auth_pages() {
	$pages = array(
		array(
			'title'   => 'Log In',
			'slug'    => 'login',
			'content' => '',
		),
		array(
			'title'   => 'Register',
			'slug'    => 'register',
			'content' => '',
		),
		array(
			'title'   => 'Subscribe',
			'slug'    => 'subscribe',
			'content' => '',
		),
	);

	foreach ($pages as $page_data) {
		$existing = get_page_by_path($page_data['slug']);
		if ($existing instanceof WP_Post) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page_data['title'],
				'post_name'    => $page_data['slug'],
				'post_content' => $page_data['content'],
			)
		);
	}
}

/**
 * Bootstrap auth pages once.
 */
function antiques_marketplace_bootstrap_auth_pages() {
	if (get_option('antiques_marketplace_auth_pages_version') === '1') {
		return;
	}
	antiques_marketplace_ensure_auth_pages();
	update_option('antiques_marketplace_auth_pages_version', '1');
}
add_action('init', 'antiques_marketplace_bootstrap_auth_pages', 5);

add_action('after_switch_theme', 'antiques_marketplace_ensure_auth_pages');

/**
 * Allow front-end registration without visiting wp-admin.
 */
add_filter('pre_option_users_can_register', '__return_true');

/**
 * Use theme login page in login links.
 *
 * @param string $login_url Login URL.
 * @param string $redirect  Redirect target.
 */
function antiques_marketplace_login_url( $login_url, $redirect ) {
	$page = get_page_by_path('login');
	if (!$page instanceof WP_Post) {
		return $login_url;
	}
	$url = get_permalink($page);
	if ($redirect) {
		$url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
	}
	return $url;
}
add_filter('login_url', 'antiques_marketplace_login_url', 10, 2);

/**
 * Public paths that never require login or subscription.
 *
 * @return array<int, string>
 */
function antiques_marketplace_public_slugs() {
	return array(
		'login',
		'register',
		'subscribe',
		'terms-of-service',
		'privacy-policy',
		'commerce-disclosure',
	);
}

/**
 * Whether the current request is only for public pages (legal + auth).
 */
function antiques_marketplace_is_public_page_request() {
	if (!is_page()) {
		return false;
	}
	$oid = get_queried_object_id();
	if ($oid < 1) {
		return false;
	}
	$slug = (string) get_post_field('post_name', $oid);
	return in_array($slug, antiques_marketplace_public_slugs(), true);
}

/**
 * Full URL for the current front-end request (for safe redirects).
 *
 * @return string
 */
function antiques_marketplace_current_request_url() {
	if (empty($_SERVER['HTTP_HOST'])) {
		return home_url('/');
	}
	$scheme = is_ssl() ? 'https' : 'http';
	$host   = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
	$uri    = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
	$try    = $scheme . '://' . $host . $uri;
	return wp_validate_redirect($try, home_url('/'));
}

/**
 * Redirect unauthenticated or unpaid users away from gated content.
 */
function antiques_marketplace_enforce_membership() {
	if (antiques_marketplace_bypass_membership()) {
		return;
	}

	if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}

	if (is_user_logged_in() && current_user_can('manage_options')) {
		return;
	}

	$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
	if (preg_match('#wp-login\.php#', $request_uri)) {
		return;
	}

	if (is_user_logged_in() && is_page()) {
		$oid  = get_queried_object_id();
		$slug = $oid > 0 ? (string) get_post_field('post_name', $oid) : '';
		if ('login' === $slug || 'register' === $slug) {
			$subscribe = get_page_by_path('subscribe');
			$sub_url   = $subscribe instanceof WP_Post ? get_permalink($subscribe) : home_url('/subscribe/');
			$target    = antiques_marketplace_user_has_membership() ? home_url('/') : $sub_url;
			wp_safe_redirect($target);
			exit;
		}
	}

	if (antiques_marketplace_is_public_page_request()) {
		return;
	}

	if (!is_user_logged_in()) {
		$login_page = get_page_by_path('login');
		$target     = $login_page instanceof WP_Post ? get_permalink($login_page) : wp_login_url();
		$redirect   = antiques_marketplace_current_request_url();
		wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($redirect), $target));
		exit;
	}

	if (antiques_marketplace_user_has_membership()) {
		return;
	}

	$subscribe = get_page_by_path('subscribe');
	$url       = $subscribe instanceof WP_Post ? get_permalink($subscribe) : home_url('/subscribe/');
	wp_safe_redirect($url);
	exit;
}
add_action('template_redirect', 'antiques_marketplace_enforce_membership', 1);

/**
 * Handle login form.
 */
function antiques_marketplace_handle_login() {
	if (!isset($_POST['antiques_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['antiques_login_nonce'])), 'antiques-login')) {
		wp_die(esc_html__('Invalid login request.', 'antiques-marketplace'));
	}

	$login_page = get_page_by_path('login');
	$fail_url   = $login_page instanceof WP_Post ? get_permalink($login_page) : wp_login_url();

	$user_login    = isset($_POST['log']) ? sanitize_text_field(wp_unslash($_POST['log'])) : '';
	$user_password = isset($_POST['pwd']) ? (string) wp_unslash($_POST['pwd']) : '';
	$remember      = !empty($_POST['rememberme']);

	if ('' === $user_login || '' === $user_password) {
		wp_safe_redirect(add_query_arg('login', 'empty', $fail_url));
		exit;
	}

	$user = wp_signon(
		array(
			'user_login'    => $user_login,
			'user_password' => $user_password,
			'remember'      => $remember,
		),
		false
	);

	if (is_wp_error($user)) {
		wp_safe_redirect(add_query_arg('login', 'failed', $fail_url));
		exit;
	}

	wp_set_current_user($user->ID);
	wp_set_auth_cookie($user->ID, $remember);

	$redirect_to = isset($_POST['redirect_to']) ? wp_validate_redirect(wp_unslash($_POST['redirect_to']), home_url('/')) : home_url('/');
	wp_safe_redirect($redirect_to);
	exit;
}
add_action('admin_post_nopriv_antiques_theme_login', 'antiques_marketplace_handle_login');
add_action('admin_post_antiques_theme_login', 'antiques_marketplace_handle_login');

/**
 * Handle registration form.
 */
function antiques_marketplace_handle_register() {
	if (!isset($_POST['antiques_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['antiques_register_nonce'])), 'antiques-register')) {
		wp_die(esc_html__('Invalid registration request.', 'antiques-marketplace'));
	}

	$register_page = get_page_by_path('register');
	$fail_url      = $register_page instanceof WP_Post ? get_permalink($register_page) : home_url('/register/');

	$user_login = isset($_POST['user_login']) ? sanitize_user(wp_unslash($_POST['user_login']), true) : '';
	$email      = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';
	$password   = isset($_POST['user_pass']) ? (string) wp_unslash($_POST['user_pass']) : '';
	$password2  = isset($_POST['user_pass2']) ? (string) wp_unslash($_POST['user_pass2']) : '';

	if ('' === $user_login || '' === $email || '' === $password) {
		wp_safe_redirect(add_query_arg('register', 'empty', $fail_url));
		exit;
	}

	if (!is_email($email)) {
		wp_safe_redirect(add_query_arg('register', 'invalid_email', $fail_url));
		exit;
	}

	if ($password !== $password2) {
		wp_safe_redirect(add_query_arg('register', 'mismatch', $fail_url));
		exit;
	}

	if (strlen($password) < 8) {
		wp_safe_redirect(add_query_arg('register', 'weak', $fail_url));
		exit;
	}

	$user_id = wp_create_user($user_login, $password, $email);

	if (is_wp_error($user_id)) {
		$code = $user_id->get_error_code();
		wp_safe_redirect(add_query_arg('register', rawurlencode((string) $code), $fail_url));
		exit;
	}

	wp_signon(
		array(
			'user_login'    => $user_login,
			'user_password' => $password,
			'remember'      => true,
		),
		false
	);

	$subscribe = get_page_by_path('subscribe');
	$target    = $subscribe instanceof WP_Post ? get_permalink($subscribe) : home_url('/subscribe/');
	wp_safe_redirect($target);
	exit;
}
add_action('admin_post_nopriv_antiques_theme_register', 'antiques_marketplace_handle_register');

/**
 * Start Stripe Checkout (logged-in users).
 */
function antiques_marketplace_handle_start_checkout() {
	$subscribe_page = get_page_by_path('subscribe');
	$base_url       = $subscribe_page instanceof WP_Post ? get_permalink($subscribe_page) : home_url('/subscribe/');

	if (!is_user_logged_in()) {
		$login_page = get_page_by_path('login');
		$login_url  = $login_page instanceof WP_Post ? get_permalink($login_page) : wp_login_url();
		wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($base_url), $login_url));
		exit;
	}

	if (!isset($_POST['antiques_checkout_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['antiques_checkout_nonce'])), 'antiques-checkout')) {
		wp_safe_redirect(add_query_arg('checkout', 'invalid_request', $base_url));
		exit;
	}

	$stripe = antiques_marketplace_stripe_config();
	if ('' === $stripe['secret_key']) {
		wp_safe_redirect(add_query_arg('checkout', 'stripe_not_configured', $base_url));
		exit;
	}

	$user_id = get_current_user_id();
	$cents   = antiques_marketplace_subscription_amount_cents();
	$currency = strtolower(antiques_marketplace_subscription_currency());

	$success_url = add_query_arg(
		array(
			'checkout'   => 'success',
			'session_id' => '{CHECKOUT_SESSION_ID}',
		),
		$base_url
	);

	$cancel_url = add_query_arg('checkout', 'cancel', $base_url);

	$product_label = __('Full site access', 'antiques-marketplace');

	$flat_body = array(
		'mode'                                     => 'payment',
		'locale'                                   => 'ja' === antiques_marketplace_get_lang() ? 'ja' : 'en',
		'client_reference_id'                      => (string) $user_id,
		'success_url'                              => $success_url,
		'cancel_url'                               => $cancel_url,
		'line_items[0][quantity]'                  => '1',
		'line_items[0][price_data][currency]'      => $currency,
		'line_items[0][price_data][unit_amount]'   => (string) $cents,
		'line_items[0][price_data][product_data][name]' => $product_label,
	);

	$response = wp_remote_post(
		'https://api.stripe.com/v1/checkout/sessions',
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization'  => 'Bearer ' . $stripe['secret_key'],
				'Content-Type'   => 'application/x-www-form-urlencoded',
			),
			'body'    => http_build_query($flat_body, '', '&'),
		)
	);

	if (is_wp_error($response)) {
		antiques_marketplace_set_checkout_error($response->get_error_message());
		wp_safe_redirect(add_query_arg('checkout', 'stripe_error', $base_url));
		exit;
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code >= 400 || empty($data['url'])) {
		$detail = '';
		if (is_array($data) && isset($data['error']['message']) && is_string($data['error']['message'])) {
			$detail = $data['error']['message'];
		}
		if ('' === $detail) {
			$detail = 'Stripe returned HTTP ' . (string) $code . '.';
		}
		antiques_marketplace_set_checkout_error($detail);
		wp_safe_redirect(add_query_arg('checkout', 'stripe_error', $base_url));
		exit;
	}

	// Stripe Checkout URL is external, so use wp_redirect (not wp_safe_redirect).
	wp_redirect(esc_url_raw($data['url']));
	exit;
}
add_action('admin_post_antiques_start_checkout', 'antiques_marketplace_handle_start_checkout');
add_action('admin_post_nopriv_antiques_start_checkout', 'antiques_marketplace_handle_start_checkout');

/**
 * Confirm Stripe session after redirect and unlock content.
 */
function antiques_marketplace_confirm_checkout_session() {
	if (antiques_marketplace_bypass_membership() || antiques_marketplace_bypass_subscription_only()) {
		return;
	}

	if (!is_user_logged_in()) {
		return;
	}

	if (empty($_GET['checkout']) || 'success' !== $_GET['checkout'] || empty($_GET['session_id'])) {
		return;
	}

	$session_id = sanitize_text_field(wp_unslash($_GET['session_id']));
	if (strpos($session_id, '{') !== false) {
		return;
	}

	if (antiques_marketplace_user_has_membership()) {
		return;
	}

	$stripe = antiques_marketplace_stripe_config();
	if ('' === $stripe['secret_key']) {
		return;
	}

	$url      = 'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($session_id);
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $stripe['secret_key'],
			),
		)
	);

	if (is_wp_error($response)) {
		return;
	}

	$data = json_decode(wp_remote_retrieve_body($response), true);
	if (empty($data['payment_status']) || 'paid' !== $data['payment_status']) {
		return;
	}

	$ref_uid = isset($data['client_reference_id']) ? absint($data['client_reference_id']) : 0;
	if ($ref_uid !== get_current_user_id()) {
		return;
	}

	antiques_marketplace_grant_membership($ref_uid);

	$subscribe_page = get_page_by_path('subscribe');
	$clean_url        = $subscribe_page instanceof WP_Post ? get_permalink($subscribe_page) : home_url('/subscribe/');
	wp_safe_redirect(add_query_arg('welcome', '1', $clean_url));
	exit;
}
add_action('template_redirect', 'antiques_marketplace_confirm_checkout_session', 2);

/**
 * Log out handler.
 */
function antiques_marketplace_handle_logout() {
	if (!isset($_GET['antiques_logout_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['antiques_logout_nonce'])), 'antiques-logout')) {
		wp_die(esc_html__('Invalid logout request.', 'antiques-marketplace'));
	}
	wp_logout();
	wp_safe_redirect(home_url('/'));
	exit;
}
add_action('admin_post_nopriv_antiques_theme_logout', 'antiques_marketplace_handle_logout');
add_action('admin_post_antiques_theme_logout', 'antiques_marketplace_handle_logout');
