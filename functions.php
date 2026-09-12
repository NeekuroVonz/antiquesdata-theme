<?php
/**
 * Theme setup and marketplace helpers.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once get_template_directory() . '/inc/load-env.php';
antiques_marketplace_load_env();

require_once get_template_directory() . '/inc/membership.php';
require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/database.php';
require_once get_template_directory() . '/inc/currency.php';
require_once get_template_directory() . '/inc/categories.php';
require_once get_template_directory() . '/inc/bids.php';
require_once get_template_directory() . '/inc/catalog.php';
require_once get_template_directory() . '/inc/dashboard.php';
require_once get_template_directory() . '/inc/listings.php';

/**
 * Setup theme defaults.
 */
function antiques_marketplace_setup() {
	load_theme_textdomain('antiques-marketplace', get_template_directory() . '/languages');
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));

	register_nav_menus(
		array(
			'primary' => __('Primary Menu', 'antiques-marketplace'),
		)
	);
}
add_action('after_setup_theme', 'antiques_marketplace_setup');

/**
 * Register scripts and styles.
 */
function antiques_marketplace_assets() {
	wp_enqueue_style(
		'antiques-fonts',
		'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&family=Noto+Serif+JP:wght@500;600;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style('antiques-marketplace-style', get_stylesheet_uri(), array('antiques-fonts'), '1.2.5');
	if (is_front_page()) {
		wp_enqueue_style(
			'antiques-landing',
			get_template_directory_uri() . '/assets/css/landing.css',
			array('antiques-marketplace-style'),
			'1.2.3'
		);
	}
	wp_enqueue_script(
		'antiques-marketplace-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		'1.2.3',
		true
	);
	if (is_page('market-trends')) {
		$dashboard_metrics = antiques_marketplace_get_dashboard_metrics();
		wp_localize_script(
			'antiques-marketplace-main',
			'antiquesMarketplaceDashboard',
			antiques_marketplace_dashboard_front_data(is_array($dashboard_metrics) ? $dashboard_metrics : array())
		);
	}
}
add_action('wp_enqueue_scripts', 'antiques_marketplace_assets');

/**
 * Distinguish the public landing from gated app pages.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function antiques_marketplace_body_classes( $classes ) {
	$classes[] = is_front_page() ? 'is-landing' : 'is-app';
	$classes[] = 'lang-' . antiques_marketplace_get_lang();
	if (is_page('market-trends')) {
		$classes[] = 'is-trends';
	}
	return $classes;
}
add_filter('body_class', 'antiques_marketplace_body_classes');

/**
 * Preconnect font and catalog image CDNs.
 *
 * @param array  $urls          URLs.
 * @param string $relation_type Hint type.
 * @return array
 */
function antiques_marketplace_resource_hints( $urls, $relation_type ) {
	if ('preconnect' !== $relation_type) {
		return $urls;
	}
	$urls[] = 'https://fonts.googleapis.com';
	$urls[] = array(
		'href'        => 'https://fonts.gstatic.com',
		'crossorigin' => 'anonymous',
	);
	if (!is_front_page()) {
		$urls[] = 'https://assets.catawiki.com';
	}
	return $urls;
}
add_filter('wp_resource_hints', 'antiques_marketplace_resource_hints', 10, 2);

/**
 * Register sidebar for filter widgets.
 */
function antiques_marketplace_widgets_init() {
	register_sidebar(
		array(
			'name'          => __('Marketplace Filters', 'antiques-marketplace'),
			'id'            => 'marketplace-filters',
			'description'   => __('Add filter widgets for the left sidebar.', 'antiques-marketplace'),
			'before_widget' => '<div class="panel">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);
}
add_action('widgets_init', 'antiques_marketplace_widgets_init');

/**
 * Permalink for a page by slug (legal pages, etc.).
 *
 * @param string $slug Page slug.
 * @return string
 */
function antiques_marketplace_page_link( $slug ) {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		return get_permalink( $page );
	}
	return home_url( '/' . sanitize_title( $slug ) . '/' );
}

/**
 * Ensure legal pages exist for footer links.
 */
function antiques_marketplace_ensure_legal_pages() {
	$required_pages = array(
		array(
			'title'   => 'Terms of Service',
			'slug'    => 'terms-of-service',
			'content' => '<h2>Terms of Service</h2><p>Welcome to AntiquesData. By using this website, you agree to these terms.</p><h3>Use of the platform</h3><p>You agree to use this website lawfully and provide accurate information when placing bids or contacting sellers.</p><h3>Listings and bids</h3><p>Product details and bidding information are provided from external sources. Final transaction terms are between buyer and seller.</p><h3>Limitation of liability</h3><p>We do our best to keep listing data up to date, but we cannot guarantee uninterrupted service or absolute data accuracy.</p><h3>Contact</h3><p>For questions regarding these terms, contact our support team.</p>',
		),
		array(
			'title'   => 'Privacy Policy',
			'slug'    => 'privacy-policy',
			'content' => '<h2>Privacy Policy</h2><p>This policy explains how AntiquesData handles your personal information.</p><h3>What we collect</h3><p>We may collect information such as your name, email address, and browsing activity when you interact with the site.</p><h3>How we use data</h3><p>We use data to operate the marketplace, improve user experience, and provide customer support.</p><h3>Cookies</h3><p>Cookies may be used for analytics, preferences, and session management.</p><h3>Data rights</h3><p>You may request access, correction, or deletion of your personal data according to applicable law.</p>',
		),
		array(
			'title'   => 'Commerce Disclosure',
			'slug'    => 'commerce-disclosure',
			'content' => '<h2>Commerce Disclosure</h2><p>AntiquesData may receive compensation from sellers, partners, or service providers featured on this website.</p><h3>Affiliate relationships</h3><p>Some links may be affiliate links, meaning we may earn a commission if you complete a purchase.</p><h3>Sponsored placements</h3><p>Certain listings or promotional blocks may be highlighted as paid placements.</p><h3>Editorial integrity</h3><p>Compensation does not guarantee favorable treatment; listings are presented according to available data and marketplace rules.</p>',
		),
	);

	foreach ($required_pages as $page_data) {
		$existing_page = get_page_by_path($page_data['slug']);
		if ($existing_page instanceof WP_Post) {
			$current_content = trim((string) $existing_page->post_content);
			if ('' === $current_content || false !== strpos($current_content, 'content goes here')) {
				wp_update_post(
					array(
						'ID'           => $existing_page->ID,
						'post_content' => $page_data['content'],
					)
				);
			}
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
add_action('after_switch_theme', 'antiques_marketplace_ensure_legal_pages');

/**
 * One-time legal page bootstrap for already-active theme.
 */
function antiques_marketplace_bootstrap_legal_pages() {
	$content_version = '3';
	if (get_option('antiques_marketplace_legal_pages_version') === $content_version) {
		return;
	}

	antiques_marketplace_ensure_legal_pages();
	update_option('antiques_marketplace_legal_pages_version', $content_version);
}
add_action('init', 'antiques_marketplace_bootstrap_legal_pages');

/**
 * Get external database credentials.
 *
 * Set MYSQL_* in the server environment or in a `.env` file in this theme directory
 * (see `.env.example`). Avoid committing real credentials to version control.
 *
 * @return array<string, string|int>
 */
function antiques_marketplace_external_db_config() {
	return array(
		'host'     => antiques_marketplace_env('MYSQL_HOST'),
		'port'     => (int) antiques_marketplace_env('MYSQL_PORT', '3306'),
		'username' => antiques_marketplace_env('MYSQL_USERNAME'),
		'password' => antiques_marketplace_env('MYSQL_PASSWORD'),
		'database' => antiques_marketplace_env('MYSQL_DATABASE'),
	);
}

/**
 * Stripe API keys from environment or `.env`.
 *
 * Use test keys (pk_test_… / sk_test_…) in development.
 *
 * @return array{publishable_key:string, secret_key:string}
 */
function antiques_marketplace_stripe_config() {
	return array(
		'publishable_key' => antiques_marketplace_env('STRIPE_PUBLISHABLE_KEY'),
		'secret_key'      => antiques_marketplace_env('STRIPE_SECRET_KEY'),
	);
}

/**
 * Parse listing filter values from the current request.
 *
 * @return array<string, mixed>
 */
function antiques_marketplace_parse_listing_filters() {
	$status = isset($_GET['listing_status']) ? sanitize_text_field(wp_unslash($_GET['listing_status'])) : 'ended';
	if (!in_array($status, array('all', 'active', 'ended'), true)) {
		$status = 'ended';
	}

	$default_sort = 'ended' === $status ? 'price_high' : 'latest';
	$sort         = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : $default_sort;
	$legacy_sort_map = array(
		'low'    => 'price_low',
		'high'   => 'price_high',
		'ending' => 'end_asc',
	);
	if (isset($legacy_sort_map[ $sort ])) {
		$sort = $legacy_sort_map[ $sort ];
	}
	if (!in_array($sort, array('latest', 'price_low', 'price_high', 'bids_high', 'bids_low', 'end_asc', 'end_desc'), true)) {
		$sort = $default_sort;
	}

	$sold_within_days = isset($_GET['sold_within']) ? (int) wp_unslash($_GET['sold_within']) : 0;
	if (!in_array($sold_within_days, array(0, 7, 30, 90, 365), true)) {
		$sold_within_days = 0;
	}

	$ending_within = isset($_GET['ending_within']) ? (int) wp_unslash($_GET['ending_within']) : 0;
	if (!in_array($ending_within, array(0, 24, 72, 168), true)) {
		$ending_within = 0;
	}

	$per_page = isset($_GET['per_page']) ? (int) wp_unslash($_GET['per_page']) : 20;
	if (!in_array($per_page, array(12, 20, 24, 48), true)) {
		$per_page = 20;
	}

	$listing_search = '';
	if (isset($_GET['listing_search'])) {
		$listing_search = sanitize_text_field(wp_unslash($_GET['listing_search']));
	}

	$min_price = null;
	if (isset($_GET['min_price']) && '' !== trim((string) wp_unslash($_GET['min_price']))) {
		$min_price = (float) wp_unslash($_GET['min_price']);
	}

	$max_price = null;
	if (isset($_GET['max_price']) && '' !== trim((string) wp_unslash($_GET['max_price']))) {
		$max_price = (float) wp_unslash($_GET['max_price']);
	}

	$category = isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : '';
	if ($category && !isset(antiques_marketplace_catalog_taxonomy()[ $category ])) {
		$category = '';
	}

	return array(
		'search'         => $listing_search,
		'min_price'      => $min_price,
		'max_price'      => $max_price,
		'sort'           => $sort,
		'limit'          => $per_page,
		'page'           => isset($_GET['pg']) ? max(1, (int) wp_unslash($_GET['pg'])) : 1,
		'listing_status' => $status,
		'ending_within'  => $ending_within,
		'sold_within'    => $sold_within_days,
		'category'       => $category,
		'has_price'      => !empty($_GET['has_price']),
		'dedupe'         => !isset($_GET['dedupe']) || '0' !== (string) wp_unslash($_GET['dedupe']),
	);
}

/**
 * Build query args for pagination / filter links.
 *
 * @param array<string, mixed> $filters Parsed filters.
 * @return array<string, string>
 */
function antiques_marketplace_listing_filter_query_args( $filters ) {
	$query = array();
	if (!empty($filters['search'])) {
		$query['listing_search'] = (string) $filters['search'];
	}
	if (null !== $filters['min_price']) {
		$query['min_price'] = (string) $filters['min_price'];
	}
	if (null !== $filters['max_price']) {
		$query['max_price'] = (string) $filters['max_price'];
	}
	$default_sort = 'ended' === ($filters['listing_status'] ?? 'ended') ? 'price_high' : 'latest';
	if (!empty($filters['sort']) && $default_sort !== $filters['sort']) {
		$query['sort'] = (string) $filters['sort'];
	}
	if (!empty($filters['listing_status']) && 'ended' !== $filters['listing_status']) {
		$query['listing_status'] = (string) $filters['listing_status'];
	}
	if (isset($filters['dedupe']) && !$filters['dedupe']) {
		$query['dedupe'] = '0';
	}
	if (!empty($filters['ending_within'])) {
		$query['ending_within'] = (string) $filters['ending_within'];
	}
	if (!empty($filters['sold_within'])) {
		$query['sold_within'] = (string) $filters['sold_within'];
	}
	if (!empty($filters['category'])) {
		$query['category'] = (string) $filters['category'];
	}
	if (!empty($filters['has_price'])) {
		$query['has_price'] = '1';
	}
	if (!empty($filters['limit']) && 20 !== (int) $filters['limit']) {
		$query['per_page'] = (string) $filters['limit'];
	}
	return $query;
}

/**
 * Whether the listing grid should show sold/ended items (past transaction prices).
 *
 * @param array<string, mixed> $filters Parsed or query args.
 * @return bool
 */
function antiques_marketplace_is_sold_listing_view( $filters ) {
	return 'ended' === ($filters['listing_status'] ?? 'ended');
}

/**
 * Merge rows that share the same title; keep the highest price (then newest date).
 *
 * @param array<int, array<string, mixed>> $products Product rows.
 * @return array<int, array<string, mixed>>
 */
function antiques_marketplace_dedupe_products( array $products ) {
	$by_key = array();
	foreach ($products as $product) {
		$title = isset($product['product_title']) ? (string) $product['product_title'] : '';
		$key   = strtolower(trim(preg_replace('/\s+/u', ' ', $title)));
		if ('' === $key) {
			$key = 'id:' . (string) ( $product['product_id'] ?? '' );
		}
		if (!isset($by_key[ $key ])) {
			$by_key[ $key ] = $product;
			continue;
		}
		$existing_price = (float) ( $by_key[ $key ]['product_price'] ?? 0 );
		$new_price      = (float) ( $product['product_price'] ?? 0 );
		if ($new_price > $existing_price) {
			$by_key[ $key ] = $product;
			continue;
		}
		if (abs($new_price - $existing_price) < 0.0001) {
			$existing_ts = strtotime((string) ( $by_key[ $key ]['product_end_time'] ?? $by_key[ $key ]['product_created'] ?? '' ));
			$candidate_ts = strtotime((string) ( $product['product_end_time'] ?? $product['product_created'] ?? '' ));
			if (false !== $candidate_ts && (false === $existing_ts || $candidate_ts > $existing_ts)) {
				$by_key[ $key ] = $product;
			}
		}
	}
	return array_values($by_key);
}

/**
 * Sort product rows in PHP (used after deduplication).
 *
 * @param array<int, array<string, mixed>> $products Product rows.
 * @param string                           $sort     Sort key.
 * @return array<int, array<string, mixed>>
 */
function antiques_marketplace_sort_products( array $products, $sort ) {
	usort(
		$products,
		function( $a, $b ) use ( $sort ) {
			$price_a = (float) ( $a['product_price'] ?? 0 );
			$price_b = (float) ( $b['product_price'] ?? 0 );
			$bids_a  = (int) ( $a['product_bid_count'] ?? 0 );
			$bids_b  = (int) ( $b['product_bid_count'] ?? 0 );
			if ('price_low' === $sort) {
				return $price_a <=> $price_b;
			}
			if ('price_high' === $sort) {
				return $price_b <=> $price_a;
			}
			if ('bids_high' === $sort) {
				return $bids_b <=> $bids_a;
			}
			if ('bids_low' === $sort) {
				return $bids_a <=> $bids_b;
			}
			if ('end_asc' === $sort) {
				$end_a = strtotime((string) ( $a['product_end_time'] ?? '' ));
				$end_b = strtotime((string) ( $b['product_end_time'] ?? '' ));
				return ( false === $end_a ? PHP_INT_MAX : $end_a ) <=> ( false === $end_b ? PHP_INT_MAX : $end_b );
			}
			if ('end_desc' === $sort) {
				$end_a = strtotime((string) ( $a['product_end_time'] ?? '' ));
				$end_b = strtotime((string) ( $b['product_end_time'] ?? '' ));
				return ( false === $end_b ? 0 : $end_b ) <=> ( false === $end_a ? 0 : $end_a );
			}
			$created_a = strtotime((string) ( $a['product_created'] ?? '' ));
			$created_b = strtotime((string) ( $b['product_created'] ?? '' ));
			return ( false === $created_b ? 0 : $created_b ) <=> ( false === $created_a ? 0 : $created_a );
		}
	);
	return $products;
}

/**
 * Format time left until auction end.
 *
 * @param string|null $end_time Raw end datetime.
 * @return string
 */
function antiques_marketplace_format_time_left( $end_time ) {
	if (empty($end_time)) {
		return __('Time not available', 'antiques-marketplace');
	}

	$end_timestamp = strtotime((string) $end_time);
	if (false === $end_timestamp) {
		return __('Time not available', 'antiques-marketplace');
	}

	$now_timestamp = current_time('timestamp');
	$remaining     = $end_timestamp - $now_timestamp;
	if ($remaining <= 0) {
		return __('Ended', 'antiques-marketplace');
	}

	$days = (int) floor($remaining / DAY_IN_SECONDS);
	$remaining -= $days * DAY_IN_SECONDS;
	$hours = (int) floor($remaining / HOUR_IN_SECONDS);
	$remaining -= $hours * HOUR_IN_SECONDS;
	$minutes = (int) floor($remaining / MINUTE_IN_SECONDS);

	if ($days > 0) {
		return sprintf(_n('%1$d day %2$d hr left', '%1$d days %2$d hrs left', $days, 'antiques-marketplace'), $days, $hours);
	}
	if ($hours > 0) {
		return sprintf(__('%1$d hrs %2$d mins left', 'antiques-marketplace'), $hours, $minutes);
	}

	return sprintf(__('%d mins left', 'antiques-marketplace'), max(1, $minutes));
}

/**
 * Translate text for a target UI language (cached).
 *
 * @param string      $text        Source text.
 * @param string|null $target_lang "en" or "ja"; defaults to active UI language.
 * @return string
 */
function antiques_marketplace_translate_text( $text, $target_lang = null ) {
	$text = trim((string) $text);
	if ('' === $text) {
		return '';
	}

	if (null === $target_lang) {
		$target_lang = antiques_marketplace_get_lang();
	}
	$target_lang = 'ja' === $target_lang ? 'ja' : 'en';

	if ('ja' === $target_lang && preg_match('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $text)) {
		return $text;
	}

	if ('en' === $target_lang && strlen($text) < 80 && !preg_match('/[^\x00-\x7F]/', $text)) {
		return $text;
	}

	$cache_key = 'ant_tx_' . $target_lang . '_' . md5($text);
	$cached    = get_transient($cache_key);
	if (false !== $cached && is_string($cached)) {
		return $cached;
	}

	$response = wp_remote_get(
		'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' . rawurlencode($target_lang) . '&dt=t&q=' . rawurlencode($text),
		array(
			'timeout' => 8,
		)
	);

	if (is_wp_error($response)) {
		return $text;
	}

	$body = wp_remote_retrieve_body($response);
	if (empty($body)) {
		return $text;
	}

	$decoded = json_decode($body, true);
	if (!is_array($decoded) || empty($decoded[0]) || !is_array($decoded[0])) {
		return $text;
	}

	$translated = '';
	foreach ($decoded[0] as $segment) {
		if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
			$translated .= $segment[0];
		}
	}

	$translated = trim($translated);
	if ('' === $translated) {
		return $text;
	}

	set_transient($cache_key, $translated, DAY_IN_SECONDS * 7);
	return $translated;
}

/**
 * Translate text to English with transient caching.
 *
 * @param string $text Source text.
 * @return string
 */
function antiques_marketplace_translate_to_english( $text ) {
	return antiques_marketplace_translate_text($text, 'en');
}

/**
 * Translate catalog text for the active UI language.
 *
 * @param string $text Source text.
 * @return string
 */
function antiques_marketplace_translate_for_locale( $text ) {
	return antiques_marketplace_translate_text($text, antiques_marketplace_get_lang());
}

/**
 * Placeholder image URL when a listing has no image.
 *
 * @return string
 */
function antiques_marketplace_no_image_url( $width = 640, $height = 420 ) {
	$label = rawurlencode(__('No image', 'antiques-marketplace'));
	return 'https://via.placeholder.com/' . absint($width) . 'x' . absint($height) . '?text=' . $label;
}

/**
 * Parse details from raw description-like text.
 *
 * @param string $raw_text Raw details text.
 * @return array<int, array{label:string, value:string}>
 */
function antiques_marketplace_parse_product_details( $raw_text ) {
	$raw_text = trim((string) $raw_text);
	if ('' === $raw_text) {
		return array();
	}

	$lines   = preg_split('/\r\n|\r|\n/', $raw_text);
	$details = array();
	if (!is_array($lines)) {
		return $details;
	}

	foreach ($lines as $line) {
		$line = trim((string) $line);
		if ('' === $line) {
			continue;
		}
		if (false === strpos($line, ':')) {
			continue;
		}

		list($label, $value) = array_map('trim', explode(':', $line, 2));
		if ('' === $label || '' === $value) {
			continue;
		}

		$details[] = array(
			'label' => antiques_marketplace_translate_for_locale($label),
			'value' => antiques_marketplace_translate_for_locale($value),
		);
	}

	return $details;
}

/**
 * Build internal product detail permalink.
 *
 * @param int|string $product_id Product identifier.
 * @return string
 */
function antiques_marketplace_product_permalink( $product_id ) {
	$product_id = absint($product_id);
	if ($product_id < 1) {
		return home_url('/');
	}

	/*
	 * Use query-string URLs when pretty permalinks are disabled
	 * (or rewrite rules are unavailable) to avoid Apache-level 404s.
	 */
	if ('' === (string) get_option('permalink_structure')) {
		return add_query_arg('antiques_product_id', $product_id, home_url('/'));
	}

	return home_url('/product/' . $product_id . '/');
}

/**
 * Register internal external-product routes.
 */
function antiques_marketplace_register_product_routes() {
	add_rewrite_rule('^product/([0-9]+)/?$', 'index.php?antiques_product_id=$matches[1]', 'top');
}
add_action('init', 'antiques_marketplace_register_product_routes');

/**
 * Register custom query vars.
 *
 * @param array<int, string> $vars Existing vars.
 * @return array<int, string>
 */
function antiques_marketplace_add_query_vars( $vars ) {
	$vars[] = 'antiques_product_id';
	return $vars;
}
add_filter('query_vars', 'antiques_marketplace_add_query_vars');

/**
 * Render custom product detail template for external products.
 *
 * @param string $template Current template.
 * @return string
 */
function antiques_marketplace_product_template_include( $template ) {
	$product_id = absint(get_query_var('antiques_product_id'));
	if ($product_id > 0) {
		$custom_template = get_template_directory() . '/single-external-product.php';
		if (file_exists($custom_template)) {
			return $custom_template;
		}
	}

	return $template;
}
add_filter('template_include', 'antiques_marketplace_product_template_include');

/**
 * Flush rewrite rules on theme switch.
 */
function antiques_marketplace_flush_rewrites_on_switch() {
	antiques_marketplace_register_product_routes();
	flush_rewrite_rules();
}
add_action('after_switch_theme', 'antiques_marketplace_flush_rewrites_on_switch');

/**
 * One-time rewrite flush for active installs.
 */
function antiques_marketplace_bootstrap_rewrite_flush() {
	$version = '1';
	if (get_option('antiques_marketplace_rewrite_version') === $version) {
		return;
	}

	antiques_marketplace_register_product_routes();
	flush_rewrite_rules(false);
	update_option('antiques_marketplace_rewrite_version', $version);
}
add_action('init', 'antiques_marketplace_bootstrap_rewrite_flush', 20);
