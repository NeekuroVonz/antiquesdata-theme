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
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/database.php';
require_once get_template_directory() . '/inc/currency.php';
require_once get_template_directory() . '/inc/categories.php';
require_once get_template_directory() . '/inc/bids.php';
require_once get_template_directory() . '/inc/catalog.php';
require_once get_template_directory() . '/inc/dashboard.php';
require_once get_template_directory() . '/inc/dashboard-summary.php';
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
	wp_enqueue_style('antiques-marketplace-style', get_stylesheet_uri(), array('antiques-fonts'), '1.5.2');
	if (is_front_page()) {
		wp_enqueue_style(
			'antiques-landing',
			get_template_directory_uri() . '/assets/css/landing.css',
			array('antiques-marketplace-style'),
			'1.4.2'
		);
	}
	wp_enqueue_script(
		'antiques-marketplace-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		'1.5.2',
		true
	);
	if (is_page('market-trends')) {
		$dashboard_metrics = antiques_marketplace_get_dashboard_metrics();
		$front             = antiques_marketplace_dashboard_front_data(is_array($dashboard_metrics) ? $dashboard_metrics : array());
		$summary           = antiques_marketplace_dashboard_summary(is_array($dashboard_metrics) ? $dashboard_metrics : array());
		$front['summary']  = array_map('antiques_marketplace_dashboard_summary_html', $summary['items']);
		wp_enqueue_style(
			'antiques-dashboard',
			get_template_directory_uri() . '/assets/css/dashboard.css',
			array('antiques-marketplace-style'),
			'1.0.2'
		);
		wp_localize_script(
			'antiques-marketplace-main',
			'antiquesMarketplaceDashboard',
			$front
		);
	}

	$product_id = absint(get_query_var('antiques_product_id'));
	if ($product_id > 0) {
		wp_localize_script(
			'antiques-marketplace-main',
			'antiquesProductTranslate',
			array(
				'ajaxUrl'           => admin_url('admin-ajax.php'),
				'nonce'             => wp_create_nonce('antiques_translate_product'),
				'productId'         => $product_id,
				'lang'              => antiques_marketplace_get_lang(),
				'action'            => 'antiques_translate_product',
				'statusTranslating' => __('Translating product text…', 'antiques-marketplace'),
				'statusDone'        => __('Translated', 'antiques-marketplace'),
				'statusError'       => __('Could not translate product text.', 'antiques-marketplace'),
			)
		);
	}
}
add_action('wp_enqueue_scripts', 'antiques_marketplace_assets');

/**
 * Drop unused WooCommerce storefront scripts on catalog pages.
 */
function antiques_marketplace_dequeue_unused_woocommerce_assets() {
	if (is_admin() || !function_exists('is_woocommerce')) {
		return;
	}
	if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
		return;
	}

	wp_dequeue_style('woocommerce-general');
	wp_dequeue_style('woocommerce-layout');
	wp_dequeue_style('woocommerce-smallscreen');
	wp_dequeue_script('wc-add-to-cart');
	wp_dequeue_script('woocommerce');
	wp_dequeue_script('wc-cart-fragments');
	wp_dequeue_script('wc-order-attribution');
	wp_dequeue_script('sourcebuster-js');
}
add_action('wp_enqueue_scripts', 'antiques_marketplace_dequeue_unused_woocommerce_assets', 100);

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
		$urls[] = 'https://assets.catawiki.nl';
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
 * Normalize typographic punctuation so Latin text is not sent to Translate.
 *
 * @param string $text Source text.
 * @return string
 */
function antiques_marketplace_normalize_latin_punctuation( $text ) {
	return strtr(
		(string) $text,
		array(
			"\xC2\xA0"     => ' ',
			"\xE2\x80\x93" => '-',
			"\xE2\x80\x94" => '-',
			"\xE2\x80\x98" => "'",
			"\xE2\x80\x99" => "'",
			"\xE2\x80\x9C" => '"',
			"\xE2\x80\x9D" => '"',
			'–'            => '-',
			'—'            => '-',
			'‘'            => "'",
			'’'            => "'",
			'“'            => '"',
			'”'            => '"',
		)
	);
}

/**
 * Whether text is effectively Latin/English after punctuation normalization.
 * Allows Hepburn macrons (ū, ō, …) used in romanized Japanese titles.
 *
 * @param string $text Source text.
 * @return bool
 */
function antiques_marketplace_is_latin_text( $text ) {
	$normalized = antiques_marketplace_normalize_latin_punctuation($text);
	$normalized = preg_replace('/[\x{00C0}-\x{024F}\x{1E00}-\x{1EFF}]/u', 'a', $normalized);
	return is_string($normalized) && !preg_match('/[^\x00-\x7F]/', $normalized);
}

/**
 * Translate text for a target UI language (cached).
 *
 * @param string      $text        Source text.
 * @param string|null $target_lang "en" or "ja"; defaults to active UI language.
 * @param bool        $allow_long  Allow long product copy (async/background only).
 * @return string
 */
function antiques_marketplace_translate_text( $text, $target_lang = null, $allow_long = false ) {
	$text = trim((string) $text);
	if ('' === $text) {
		return '';
	}

	if (null === $target_lang) {
		$target_lang = antiques_marketplace_get_lang();
	}
	$target_lang = 'ja' === $target_lang ? 'ja' : 'en';

	if (!antiques_marketplace_text_needs_ui_translate($text, $target_lang)) {
		if ('en' === $target_lang && antiques_marketplace_is_latin_text($text)) {
			return antiques_marketplace_normalize_latin_punctuation($text);
		}
		return $text;
	}

	if (antiques_marketplace_is_latin_text($text)) {
		$text = antiques_marketplace_normalize_latin_punctuation($text);
	}

	// Keep the HTML request free of multi-kilobyte Translate calls.
	if (!$allow_long && strlen($text) > 180) {
		return $text;
	}

	if ($allow_long && strlen($text) > 1200) {
		return antiques_marketplace_translate_text_chunked($text, $target_lang);
	}

	return antiques_marketplace_translate_text_request($text, $target_lang, $allow_long ? 8 : 1);
}

/**
 * Split long product copy into Translate-safe chunks (UTF-8 aware).
 *
 * @param string $text Source text.
 * @param int    $max  Max bytes per chunk.
 * @return array<int, string>
 */
function antiques_marketplace_translate_chunks( $text, $max = 1100 ) {
	$max   = max(200, (int) $max);
	$lines = preg_split('/\r\n|\r|\n/', (string) $text);
	if (!is_array($lines)) {
		$lines = array( (string) $text );
	}

	$chunks  = array();
	$current = '';
	foreach ($lines as $line) {
		$candidate = '' === $current ? $line : $current . "\n" . $line;
		if (strlen($candidate) > $max && '' !== $current) {
			$chunks[] = $current;
			$current  = $line;
			continue;
		}
		$current = $candidate;
	}
	if ('' !== $current) {
		$chunks[] = $current;
	}

	$safe = array();
	foreach ($chunks as $chunk) {
		if (strlen($chunk) <= $max) {
			$safe[] = $chunk;
			continue;
		}
		$offset = 0;
		$length = function_exists('mb_strlen') ? (int) mb_strlen($chunk, 'UTF-8') : strlen($chunk);
		while ($offset < $length) {
			$piece = function_exists('mb_substr')
				? (string) mb_substr($chunk, $offset, 400, 'UTF-8')
				: substr($chunk, $offset, $max);
			if ('' === $piece) {
				break;
			}
			$safe[]  = $piece;
			$offset += function_exists('mb_strlen') ? (int) mb_strlen($piece, 'UTF-8') : strlen($piece);
		}
	}

	return $safe;
}

/**
 * Translate long product copy in chunks (async path only).
 *
 * @param string $text        Source text.
 * @param string $target_lang Target language.
 * @return string
 */
function antiques_marketplace_translate_text_chunked( $text, $target_lang ) {
	$cache_key = 'ant_tx_long_' . $target_lang . '_' . md5($text);
	$cached    = get_transient($cache_key);
	if (false !== $cached && is_string($cached)) {
		return $cached;
	}

	$chunks      = antiques_marketplace_translate_chunks($text, 450);
	$translated  = antiques_marketplace_translate_many($chunks, $target_lang);
	$joined      = implode("\n", array_values($translated));
	set_transient($cache_key, $joined, DAY_IN_SECONDS * 7);
	return $joined;
}

/**
 * Resolve source language for a Translate request.
 *
 * @param string $text        Source text.
 * @param string $target_lang Target language.
 * @return string
 */
function antiques_marketplace_translate_source_lang( $text, $target_lang ) {
	$latin = antiques_marketplace_latin_letter_count($text);
	$cjk   = antiques_marketplace_cjk_char_count($text);

	if ('ja' === $target_lang && $latin >= 40) {
		return 'en';
	}
	if ('en' === $target_lang && $cjk >= 8 && $latin < 48) {
		return 'ja';
	}
	if (antiques_marketplace_is_latin_text($text)) {
		return 'en';
	}
	return $cjk > 0 ? 'ja' : 'en';
}

/**
 * Single Translate request (cached). Prefer batch via translate_many for product pages.
 *
 * @param string $text        Source text.
 * @param string $target_lang Target language.
 * @param int    $timeout     HTTP timeout seconds.
 * @return string
 */
function antiques_marketplace_translate_text_request( $text, $target_lang, $timeout = 1 ) {
	$batch = antiques_marketplace_translate_many(array( $text ), $target_lang, max(3, (int) $timeout));
	return isset($batch[0]) ? (string) $batch[0] : $text;
}

/**
 * Parallel Translate for many strings (cached per string).
 *
 * Primary: Google clients5. Fallback: MyMemory with site email for higher free quota.
 *
 * @param array<int|string, string> $texts       Source strings (keys preserved).
 * @param string                    $target_lang Target language.
 * @param int                       $timeout     Per-request timeout seconds.
 * @return array<int|string, string>
 */
function antiques_marketplace_translate_many( array $texts, $target_lang, $timeout = 5 ) {
	$target_lang = 'ja' === $target_lang ? 'ja' : 'en';
	$timeout     = max(2, (int) $timeout);
	$results     = array();
	$pending     = array();

	foreach ($texts as $key => $text) {
		$text = trim((string) $text);
		if ('' === $text) {
			$results[ $key ] = '';
			continue;
		}

		if (!antiques_marketplace_text_needs_ui_translate($text, $target_lang)) {
			if ('en' === $target_lang && antiques_marketplace_is_latin_text($text)) {
				$results[ $key ] = antiques_marketplace_normalize_latin_punctuation($text);
			} else {
				$results[ $key ] = $text;
			}
			continue;
		}

		$cache_key = 'ant_tx_v2_' . $target_lang . '_' . md5($text);
		$cached    = get_transient($cache_key);
		if (false !== $cached && is_string($cached)) {
			$results[ $key ] = $cached;
			continue;
		}

		$chunks = strlen($text) > 450 ? antiques_marketplace_translate_chunks($text, 450) : array( $text );
		$chunks = antiques_marketplace_merge_tiny_translate_chunks($chunks, 100);
		$pending[ $key ] = array(
			'source' => $text,
			'chunks' => $chunks,
			'parts'  => array_fill(0, count($chunks), null),
		);
	}

	$jobs = array();
	foreach ($pending as $key => $item) {
		$source_lang = antiques_marketplace_translate_source_lang($item['source'], $target_lang);
		foreach ($item['chunks'] as $index => $chunk) {
			$jobs[ $key . "\n" . $index ] = array(
				'key'         => $key,
				'index'       => $index,
				'text'        => $chunk,
				'source_lang' => $source_lang,
			);
		}
	}

	if (!empty($jobs)) {
		$waves = array_chunk($jobs, 12, true);
		foreach ($waves as $wave) {
			$google_urls = array();
			foreach ($wave as $job_id => $job) {
				$google_urls[ $job_id ] = antiques_marketplace_google_clients5_url($job['text'], $job['source_lang'], $target_lang);
			}

			$bodies = antiques_marketplace_http_get_many($google_urls, $timeout);
			$failed = array();
			foreach ($wave as $job_id => $job) {
				$body       = isset($bodies[ $job_id ]) ? $bodies[ $job_id ] : null;
				$translated = antiques_marketplace_parse_google_clients5_body($body, $job['text'], $target_lang);
				if (null !== $translated) {
					$pending[ $job['key'] ]['parts'][ $job['index'] ] = $translated;
				} else {
					$failed[ $job_id ] = $job;
				}
			}

			if (!empty($failed)) {
				$memory_urls = array();
				$email       = antiques_marketplace_env('ANTIQUES_MYMEMORY_EMAIL', 'translate@antiquedatajapan.com');
				foreach ($failed as $job_id => $job) {
					$query = array(
						'q'        => $job['text'],
						'langpair' => $job['source_lang'] . '|' . $target_lang,
					);
					if (is_email($email)) {
						$query['de'] = $email;
					}
					$memory_urls[ $job_id ] = 'https://api.mymemory.translated.net/get?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
				}
				$memory_bodies = antiques_marketplace_http_get_many($memory_urls, $timeout);
				foreach ($failed as $job_id => $job) {
					$body       = isset($memory_bodies[ $job_id ]) ? $memory_bodies[ $job_id ] : null;
					$translated = antiques_marketplace_parse_mymemory_body($body, $job['text'], $target_lang);
					$pending[ $job['key'] ]['parts'][ $job['index'] ] = null !== $translated ? $translated : $job['text'];
				}
			}
		}
	}

	foreach ($pending as $key => $item) {
		$joined = implode("\n", $item['parts']);
		if (antiques_marketplace_translated_text_is_usable($item['source'], $joined, $target_lang)) {
			set_transient('ant_tx_v2_' . $target_lang . '_' . md5($item['source']), $joined, DAY_IN_SECONDS * 7);
			$results[ $key ] = $joined;
		} else {
			$results[ $key ] = $item['source'];
		}
	}

	return $results;
}

/**
 * Merge tiny Translate chunks into neighbors (avoids "ABOUT US:" alone).
 *
 * @param array<int, string> $chunks Chunks.
 * @param int                $min    Minimum preferred chunk size.
 * @return array<int, string>
 */
function antiques_marketplace_merge_tiny_translate_chunks( array $chunks, $min = 100 ) {
	$min    = max(20, (int) $min);
	$merged = array();
	foreach ($chunks as $chunk) {
		$chunk = (string) $chunk;
		if (empty($merged)) {
			$merged[] = $chunk;
			continue;
		}
		$last_index = count($merged) - 1;
		if (strlen($merged[ $last_index ]) < $min) {
			$merged[ $last_index ] .= "\n" . $chunk;
			continue;
		}
		$merged[] = $chunk;
	}
	return $merged;
}

/**
 * Google Translate clients5 URL (works server-side; CORS-friendly).
 *
 * @param string $text        Source text.
 * @param string $source_lang Source language.
 * @param string $target_lang Target language.
 * @return string
 */
function antiques_marketplace_google_clients5_url( $text, $source_lang, $target_lang ) {
	return 'https://clients5.google.com/translate_a/t?' . http_build_query(
		array(
			'client' => 'dict-chrome-ex',
			'sl'     => $source_lang ? $source_lang : 'auto',
			'tl'     => $target_lang,
			'q'      => $text,
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
}

/**
 * Parse Google clients5 JSON body.
 *
 * @param string|null $body        Response body.
 * @param string      $source      Source text.
 * @param string      $target_lang Target language.
 * @return string|null
 */
function antiques_marketplace_parse_google_clients5_body( $body, $source, $target_lang ) {
	if (!is_string($body) || '' === $body || ('[' !== $body[0] && '{' !== $body[0])) {
		return null;
	}
	$decoded = json_decode($body, true);
	if (!is_array($decoded)) {
		return null;
	}

	$translated = '';
	if (isset($decoded[0]) && is_string($decoded[0])) {
		$translated = $decoded[0];
	} elseif (isset($decoded[0]) && is_array($decoded[0])) {
		foreach ($decoded as $row) {
			if (is_array($row) && isset($row[0]) && is_string($row[0])) {
				$translated .= $row[0];
			} elseif (is_string($row)) {
				$translated .= $row;
			}
		}
	}

	$translated = trim($translated);
	return antiques_marketplace_translated_text_is_usable($source, $translated, $target_lang) ? $translated : null;
}

/**
 * Concurrent HTTP GETs (curl_multi when available).
 *
 * @param array<string, string> $url_by_key URL map.
 * @param int                   $timeout   Timeout seconds.
 * @return array<string, string|null>
 */
function antiques_marketplace_http_get_many( array $url_by_key, $timeout = 5 ) {
	$timeout = max(2, (int) $timeout);
	$bodies  = array();

	if (empty($url_by_key)) {
		return $bodies;
	}

	if (!function_exists('curl_multi_init')) {
		foreach ($url_by_key as $key => $url) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'    => $timeout,
					'user-agent' => 'AntiqueDataJapan/1.0',
				)
			);
			$bodies[ $key ] = is_wp_error($response) ? null : (string) wp_remote_retrieve_body($response);
		}
		return $bodies;
	}

	$multi   = curl_multi_init();
	$handles = array();
	foreach ($url_by_key as $key => $url) {
		$handle = curl_init($url);
		if (false === $handle) {
			$bodies[ $key ] = null;
			continue;
		}
		curl_setopt_array(
			$handle,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 2,
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_USERAGENT      => 'AntiqueDataJapan/1.0',
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
			)
		);
		curl_multi_add_handle($multi, $handle);
		$handles[ $key ] = $handle;
	}

	$running = null;
	do {
		$status = curl_multi_exec($multi, $running);
		if ($status === CURLM_CALL_MULTI_PERFORM) {
			continue;
		}
		if ($running > 0) {
			curl_multi_select($multi, 0.5);
		}
	} while ($running > 0 && ($status === CURLM_OK || $status === CURLM_CALL_MULTI_PERFORM));

	foreach ($handles as $key => $handle) {
		$error = curl_error($handle);
		$body  = curl_multi_getcontent($handle);
		if ('' !== $error || !is_string($body) || '' === $body) {
			$bodies[ $key ] = null;
		} else {
			$bodies[ $key ] = $body;
		}
		curl_multi_remove_handle($multi, $handle);
		curl_close($handle);
	}
	curl_multi_close($multi);

	return $bodies;
}

/**
 * Parse a MyMemory JSON body into usable translated text.
 *
 * @param string|null $body        Response body.
 * @param string      $source      Source text.
 * @param string      $target_lang Target language.
 * @return string|null
 */
function antiques_marketplace_parse_mymemory_body( $body, $source, $target_lang ) {
	if (!is_string($body) || '' === $body) {
		return null;
	}
	$decoded = json_decode($body, true);
	if (!is_array($decoded) || empty($decoded['responseData']['translatedText']) || !is_string($decoded['responseData']['translatedText'])) {
		return null;
	}
	if (isset($decoded['responseStatus']) && (int) $decoded['responseStatus'] !== 200) {
		return null;
	}
	$translated = trim($decoded['responseData']['translatedText']);
	if (preg_match('/^(MYMEMORY WARNING|QUERY LENGTH|INVALID)/i', $translated)) {
		return null;
	}
	return antiques_marketplace_translated_text_is_usable($source, $translated, $target_lang) ? $translated : null;
}

/**
 * Whether a Translate result looks usable for the target UI language.
 *
 * @param string $source      Source text.
 * @param string $translated  Candidate translation.
 * @param string $target_lang Target language.
 * @return bool
 */
function antiques_marketplace_translated_text_is_usable( $source, $translated, $target_lang ) {
	$translated = trim((string) $translated);
	if ('' === $translated) {
		return false;
	}
	if ('ja' === $target_lang) {
		if (preg_match('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $translated)) {
			return true;
		}
		return $translated !== trim((string) $source);
	}
	if (preg_match('/[A-Za-z]{3,}/', $translated)) {
		return true;
	}
	return $translated !== trim((string) $source);
}

/**
 * Google Translate unofficial endpoint (may be rate-limited).
 *
 * @param string $text        Source text.
 * @param string $source_lang Source language code.
 * @param string $target_lang Target language code.
 * @param int    $timeout     Timeout seconds.
 * @return string|null
 */
function antiques_marketplace_translate_via_google( $text, $source_lang, $target_lang, $timeout = 2 ) {
	$response = wp_remote_get(
		'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' . rawurlencode($source_lang) . '&tl=' . rawurlencode($target_lang) . '&dt=t&q=' . rawurlencode($text),
		array(
			'timeout'    => max(1, (int) $timeout),
			'user-agent' => 'Mozilla/5.0 (compatible; AntiqueDataJapan/1.0)',
		)
	);
	if (is_wp_error($response)) {
		return null;
	}
	$code = (int) wp_remote_retrieve_response_code($response);
	$body = (string) wp_remote_retrieve_body($response);
	if ($code < 200 || $code >= 300 || '' === $body || ('[' !== $body[0] && '{' !== $body[0])) {
		return null;
	}
	$decoded = json_decode($body, true);
	if (!is_array($decoded) || empty($decoded[0]) || !is_array($decoded[0])) {
		return null;
	}
	$translated = '';
	foreach ($decoded[0] as $segment) {
		if (is_array($segment) && isset($segment[0]) && is_string($segment[0])) {
			$translated .= $segment[0];
		}
	}
	$translated = trim($translated);
	return antiques_marketplace_translated_text_is_usable($text, $translated, $target_lang) ? $translated : null;
}

/**
 * MyMemory free Translate API (single string).
 *
 * @param string $text        Source text.
 * @param string $source_lang Source language code.
 * @param string $target_lang Target language code.
 * @param int    $timeout     Timeout seconds.
 * @return string|null
 */
function antiques_marketplace_translate_via_mymemory( $text, $source_lang, $target_lang, $timeout = 5 ) {
	unset($source_lang);
	$batch = antiques_marketplace_translate_many(array( $text ), $target_lang, $timeout);
	$out   = isset($batch[0]) ? (string) $batch[0] : '';
	return antiques_marketplace_translated_text_is_usable($text, $out, $target_lang) ? $out : null;
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
	unset($width, $height);
	return get_template_directory_uri() . '/assets/images/catalog-image-placeholder.jpg';
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
			'label' => $label,
			'value' => $value,
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
