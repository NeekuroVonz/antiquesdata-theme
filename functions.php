<?php
/**
 * Theme setup and marketplace helpers.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Setup theme defaults.
 */
function antiques_marketplace_setup() {
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
	wp_enqueue_style('antiques-marketplace-style', get_stylesheet_uri(), array(), '1.0.0');
	wp_enqueue_script(
		'antiques-marketplace-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		'1.0.0',
		true
	);
}
add_action('wp_enqueue_scripts', 'antiques_marketplace_assets');

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
 * @return array<string, string|int>
 */
function antiques_marketplace_external_db_config() {
	return array(
		'host'     => getenv('MYSQL_HOST') ? getenv('MYSQL_HOST') : '45.76.101.127',
		'port'     => (int) (getenv('MYSQL_PORT') ? getenv('MYSQL_PORT') : 3306),
		'username' => getenv('MYSQL_USERNAME') ? getenv('MYSQL_USERNAME') : 'catawiki',
		'password' => getenv('MYSQL_PASSWORD') ? getenv('MYSQL_PASSWORD') : 'Catawiki@2026Strong!',
		'database' => getenv('MYSQL_DATABASE') ? getenv('MYSQL_DATABASE') : 'catawiki_products',
	);
}

/**
 * Resolve a likely products table.
 *
 * @param wpdb $external_db External DB client.
 * @return string|null
 */
function antiques_marketplace_detect_products_table( $external_db ) {
	$tables = $external_db->get_col('SHOW TABLES');
	if (empty($tables)) {
		return null;
	}

	$preferred_names = array(
		'products',
		'product',
		'listings',
		'items',
		'catawiki_products',
	);

	foreach ($preferred_names as $preferred_name) {
		foreach ($tables as $table_name) {
			if (strtolower((string) $table_name) === $preferred_name) {
				return (string) $table_name;
			}
		}
	}

	return (string) $tables[0];
}

/**
 * Build products from an external MySQL table.
 *
 * @param array<string, mixed> $args Query arguments.
 * @return array<string, mixed>
 */
function antiques_marketplace_get_external_products( $args = array() ) {
	$defaults = array(
		'search'    => '',
		'min_price' => null,
		'max_price' => null,
		'sort'      => 'latest',
		'limit'     => 12,
		'page'      => 1,
	);
	$args     = wp_parse_args($args, $defaults);
	$config   = antiques_marketplace_external_db_config();

	$external_db = new wpdb(
		(string) $config['username'],
		(string) $config['password'],
		(string) $config['database'],
		sprintf('%s:%d', (string) $config['host'], (int) $config['port'])
	);
	$external_db->show_errors(false);

	if (!empty($external_db->error)) {
		return array(
			'products' => array(),
			'error'    => $external_db->error,
		);
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return array(
			'products' => array(),
			'error'    => __('No product tables found in external database.', 'antiques-marketplace'),
		);
	}

	$columns = $external_db->get_col("SHOW COLUMNS FROM `{$table_name}`", 0);
	if (empty($columns)) {
		return array(
			'products' => array(),
			'error'    => __('Could not read product table columns.', 'antiques-marketplace'),
		);
	}

	$column_map = array(
		'id'      => array('id', 'product_id'),
		'title'   => array('title', 'name', 'product_name', 'product_title', 'product_subtitle'),
		'price'   => array('price', 'current_price', 'amount', 'current_bid_amount', 'buy_now_price', 'min_bid_amount'),
		'image'   => array('image_url', 'image', 'thumbnail', 'photo_url', 'thumb_image', 'original_image'),
		'url'     => array('product_url', 'url', 'permalink', 'link'),
		'created' => array('created_at', 'created', 'date_created', 'published_at'),
		'end'     => array('bidding_end_time', 'auction_end_time', 'ends_at', 'end_time'),
		'active'  => array('is_active', 'active', 'status'),
		'sold'    => array('is_sold', 'sold', 'is_archived'),
	);

	$resolved = array();
	foreach ($column_map as $key => $candidates) {
		$resolved[$key] = null;
		foreach ($candidates as $candidate) {
			if (in_array($candidate, $columns, true)) {
				$resolved[$key] = $candidate;
				break;
			}
		}
	}

	if (!$resolved['title']) {
		foreach ($columns as $column_name) {
			$lc_column_name = strtolower((string) $column_name);
			if (false !== strpos($lc_column_name, 'title') || false !== strpos($lc_column_name, 'name')) {
				$resolved['title'] = (string) $column_name;
				break;
			}
		}
	}

	if (!$resolved['title']) {
		foreach ($columns as $column_name) {
			$lc_column_name = strtolower((string) $column_name);
			if (false === strpos($lc_column_name, 'id') && false === strpos($lc_column_name, 'time') && false === strpos($lc_column_name, 'date')) {
				$resolved['title'] = (string) $column_name;
				break;
			}
		}
	}

	if (!$resolved['title']) {
		return array(
			'products' => array(),
			'error'    => __('No title-like column found in external products table.', 'antiques-marketplace'),
		);
	}

	$id_column      = $resolved['id'] ? $resolved['id'] : $resolved['title'];
	$price_column   = $resolved['price'] ? $resolved['price'] : 'NULL';
	$image_column   = $resolved['image'] ? $resolved['image'] : 'NULL';
	$url_column     = $resolved['url'] ? $resolved['url'] : 'NULL';
	$created_column = $resolved['created'] ? $resolved['created'] : 'NULL';
	$end_column     = $resolved['end'] ? $resolved['end'] : 'NULL';

	$sql = "
		SELECT
			`{$id_column}` AS product_id,
			`{$resolved['title']}` AS product_title,
			{$price_column} AS product_price,
			{$image_column} AS product_image,
			{$url_column} AS product_url,
			{$created_column} AS product_created,
			{$end_column} AS product_end_time
		FROM `{$table_name}`
	";

	$where_clauses = array();
	$query_params  = array();

	if (!empty($args['search'])) {
		$where_clauses[] = "`{$resolved['title']}` LIKE %s";
		$query_params[]  = '%' . $external_db->esc_like((string) $args['search']) . '%';
	}

	if ($resolved['active']) {
		$where_clauses[] = "`{$resolved['active']}` = 1";
	}

	if ($resolved['sold']) {
		$where_clauses[] = "`{$resolved['sold']}` = 0";
	}

	if (null !== $args['min_price'] && '' !== $args['min_price'] && $resolved['price']) {
		$where_clauses[] = "`{$resolved['price']}` >= %f";
		$query_params[]  = (float) $args['min_price'];
	}

	if (null !== $args['max_price'] && '' !== $args['max_price'] && $resolved['price']) {
		$where_clauses[] = "`{$resolved['price']}` <= %f";
		$query_params[]  = (float) $args['max_price'];
	}

	if (!empty($where_clauses)) {
		$sql .= ' WHERE ' . implode(' AND ', $where_clauses);
	}

	$count_sql = "SELECT COUNT(*) FROM `{$table_name}`";
	if (!empty($where_clauses)) {
		$count_sql .= ' WHERE ' . implode(' AND ', $where_clauses);
	}
	$total_count = (int) $external_db->get_var($external_db->prepare($count_sql, $query_params));

	if ('low' === $args['sort'] && $resolved['price']) {
		$sql .= " ORDER BY `{$resolved['price']}` ASC";
	} elseif ('high' === $args['sort'] && $resolved['price']) {
		$sql .= " ORDER BY `{$resolved['price']}` DESC";
	} elseif ($resolved['created']) {
		$sql .= " ORDER BY `{$resolved['created']}` DESC";
	} else {
		$sql .= " ORDER BY `{$resolved['title']}` ASC";
	}

	$limit  = max(1, (int) $args['limit']);
	$page   = max(1, (int) $args['page']);
	$offset = ($page - 1) * $limit;
	$sql   .= ' LIMIT %d OFFSET %d';
	$query_params[] = $limit;
	$query_params[] = $offset;

	$prepared_sql = $external_db->prepare($sql, $query_params);
	$products     = $external_db->get_results($prepared_sql, ARRAY_A);

	$used_status_filters = $resolved['active'] || $resolved['sold'];
	if (empty($products) && $used_status_filters) {
		$fallback_sql = "
			SELECT
				`{$id_column}` AS product_id,
				`{$resolved['title']}` AS product_title,
				{$price_column} AS product_price,
				{$image_column} AS product_image,
				{$url_column} AS product_url,
				{$created_column} AS product_created,
				{$end_column} AS product_end_time
			FROM `{$table_name}`
		";

		$fallback_where  = array();
		$fallback_params = array();
		if (!empty($args['search'])) {
			$fallback_where[] = "`{$resolved['title']}` LIKE %s";
			$fallback_params[] = '%' . $external_db->esc_like((string) $args['search']) . '%';
		}
		if (null !== $args['min_price'] && '' !== $args['min_price'] && $resolved['price']) {
			$fallback_where[] = "`{$resolved['price']}` >= %f";
			$fallback_params[] = (float) $args['min_price'];
		}
		if (null !== $args['max_price'] && '' !== $args['max_price'] && $resolved['price']) {
			$fallback_where[] = "`{$resolved['price']}` <= %f";
			$fallback_params[] = (float) $args['max_price'];
		}
		if (!empty($fallback_where)) {
			$fallback_sql .= ' WHERE ' . implode(' AND ', $fallback_where);
		}
		$fallback_count_sql = "SELECT COUNT(*) FROM `{$table_name}`";
		if (!empty($fallback_where)) {
			$fallback_count_sql .= ' WHERE ' . implode(' AND ', $fallback_where);
		}
		$total_count = (int) $external_db->get_var($external_db->prepare($fallback_count_sql, $fallback_params));
		if ('low' === $args['sort'] && $resolved['price']) {
			$fallback_sql .= " ORDER BY `{$resolved['price']}` ASC";
		} elseif ('high' === $args['sort'] && $resolved['price']) {
			$fallback_sql .= " ORDER BY `{$resolved['price']}` DESC";
		} elseif ($resolved['created']) {
			$fallback_sql .= " ORDER BY `{$resolved['created']}` DESC";
		} else {
			$fallback_sql .= " ORDER BY `{$resolved['title']}` ASC";
		}
		$fallback_limit  = max(1, (int) $args['limit']);
		$fallback_page   = max(1, (int) $args['page']);
		$fallback_offset = ($fallback_page - 1) * $fallback_limit;
		$fallback_sql   .= ' LIMIT %d OFFSET %d';
		$fallback_params[] = $fallback_limit;
		$fallback_params[] = $fallback_offset;
		$fallback_prepared = $external_db->prepare($fallback_sql, $fallback_params);
		$products          = $external_db->get_results($fallback_prepared, ARRAY_A);
	}

	return array(
		'products' => is_array($products) ? $products : array(),
		'error'    => !empty($external_db->error) ? $external_db->error : null,
		'table'    => $table_name,
		'total'    => $total_count,
	);
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
 * Translate text to English with transient caching.
 *
 * @param string $text Source text.
 * @return string
 */
function antiques_marketplace_translate_to_english( $text ) {
	$text = trim((string) $text);
	if ('' === $text) {
		return '';
	}

	// Skip remote translation for plain short ASCII labels.
	if (strlen($text) < 80 && !preg_match('/[^\x00-\x7F]/', $text)) {
		return $text;
	}

	$cache_key = 'ant_tx_' . md5($text);
	$cached    = get_transient($cache_key);
	if (false !== $cached && is_string($cached)) {
		return $cached;
	}

	$response = wp_remote_get(
		'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=en&dt=t&q=' . rawurlencode($text),
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
			'label' => antiques_marketplace_translate_to_english($label),
			'value' => antiques_marketplace_translate_to_english($value),
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
	return home_url('/product/' . absint($product_id) . '/');
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
 * Retrieve one external product by ID.
 *
 * @param int $product_id Product ID.
 * @return array<string, mixed>
 */
function antiques_marketplace_get_external_product_by_id( $product_id ) {
	$product_id = absint($product_id);
	if ($product_id < 1) {
		return array('product' => null, 'error' => __('Invalid product ID.', 'antiques-marketplace'));
	}

	$config      = antiques_marketplace_external_db_config();
	$external_db = new wpdb(
		(string) $config['username'],
		(string) $config['password'],
		(string) $config['database'],
		sprintf('%s:%d', (string) $config['host'], (int) $config['port'])
	);
	$external_db->show_errors(false);

	if (!empty($external_db->error)) {
		return array('product' => null, 'error' => $external_db->error);
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return array('product' => null, 'error' => __('No product table found.', 'antiques-marketplace'));
	}

	$columns = $external_db->get_col("SHOW COLUMNS FROM `{$table_name}`", 0);
	if (empty($columns)) {
		return array('product' => null, 'error' => __('Could not read product table columns.', 'antiques-marketplace'));
	}

	$column_map = array(
		'id'          => array('id', 'product_id'),
		'title'       => array('product_title', 'title', 'name'),
		'description' => array('product_description', 'product_detail', 'description', 'detail'),
		'detail'      => array('product_detail', 'detail', 'description'),
		'price'       => array('current_bid_amount', 'buy_now_price', 'price', 'current_price'),
		'image'       => array('thumb_image', 'original_image', 'image_url', 'image'),
		'images'      => array('product_images'),
		'url'         => array('product_url', 'url'),
		'created'     => array('created_at'),
		'end'         => array('bidding_end_time', 'ends_at', 'end_time'),
	);

	$resolved = array();
	foreach ($column_map as $key => $candidates) {
		$resolved[$key] = null;
		foreach ($candidates as $candidate) {
			if (in_array($candidate, $columns, true)) {
				$resolved[$key] = $candidate;
				break;
			}
		}
	}

	if (!$resolved['id']) {
		return array('product' => null, 'error' => __('No product ID column found.', 'antiques-marketplace'));
	}
	if (!$resolved['title']) {
		return array('product' => null, 'error' => __('No product title column found.', 'antiques-marketplace'));
	}

	$desc_column    = $resolved['description'] ? "`{$resolved['description']}`" : 'NULL';
	$detail_column  = $resolved['detail'] ? "`{$resolved['detail']}`" : 'NULL';
	$price_column   = $resolved['price'] ? "`{$resolved['price']}`" : 'NULL';
	$image_column   = $resolved['image'] ? "`{$resolved['image']}`" : 'NULL';
	$images_column  = $resolved['images'] ? "`{$resolved['images']}`" : 'NULL';
	$url_column     = $resolved['url'] ? "`{$resolved['url']}`" : 'NULL';
	$created_column = $resolved['created'] ? "`{$resolved['created']}`" : 'NULL';
	$end_column     = $resolved['end'] ? "`{$resolved['end']}`" : 'NULL';

	$sql = "
		SELECT
			`{$resolved['id']}` AS product_id,
			`{$resolved['title']}` AS product_title,
			{$desc_column} AS product_description,
			{$detail_column} AS product_detail,
			{$price_column} AS product_price,
			{$image_column} AS product_image,
			{$images_column} AS product_images,
			{$url_column} AS product_url,
			{$created_column} AS product_created,
			{$end_column} AS product_end_time
		FROM `{$table_name}`
		WHERE `{$resolved['id']}` = %d
		LIMIT 1
	";

	$product = $external_db->get_row($external_db->prepare($sql, $product_id), ARRAY_A);
	if (empty($product)) {
		return array('product' => null, 'error' => __('Product not found.', 'antiques-marketplace'));
	}

	return array('product' => $product, 'error' => null);
}

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
