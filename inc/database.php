<?php
/**
 * External catalog database connection and schema cache.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shared wpdb handle for the Catawiki catalog.
 *
 * @return wpdb|WP_Error
 */
function antiques_marketplace_external_db() {
	static $external_db = null;
	if ($external_db instanceof wpdb) {
		return $external_db;
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
		$error = $external_db->error;
		$external_db = null;
		return new WP_Error('antiques_external_db', (string) $error);
	}

	return $external_db;
}

/**
 * Cached catalog table name.
 *
 * @param wpdb $external_db External DB client.
 * @return string|null
 */
function antiques_marketplace_detect_products_table( $external_db ) {
	static $cached_table = null;
	if (null !== $cached_table) {
		return '' === $cached_table ? null : $cached_table;
	}

	$transient = get_transient('antiques_marketplace_products_table');
	if (is_string($transient) && '' !== $transient) {
		$cached_table = $transient;
		return $cached_table;
	}

	$tables = $external_db->get_col('SHOW TABLES');
	if (empty($tables)) {
		$cached_table = '';
		return null;
	}

	$preferred_names = array(
		'products',
		'product',
		'listings',
		'items',
		'catawiki_products',
	);

	$table_name = (string) $tables[0];
	foreach ($preferred_names as $preferred_name) {
		foreach ($tables as $candidate) {
			if (strtolower((string) $candidate) === $preferred_name) {
				$table_name = (string) $candidate;
				break 2;
			}
		}
	}

	$cached_table = $table_name;
	set_transient('antiques_marketplace_products_table', $table_name, HOUR_IN_SECONDS);
	return $table_name;
}

/**
 * Cached column names for the products table.
 *
 * @param wpdb   $external_db External DB client.
 * @param string $table_name  Table name.
 * @return array<int, string>
 */
function antiques_marketplace_product_columns( $external_db, $table_name ) {
	static $cached = array();
	if (isset($cached[ $table_name ])) {
		return $cached[ $table_name ];
	}

	$key      = 'antiques_marketplace_columns_' . md5($table_name);
	$transient = get_transient($key);
	if (is_array($transient)) {
		$cached[ $table_name ] = $transient;
		return $transient;
	}

	$columns = $external_db->get_col("SHOW COLUMNS FROM `{$table_name}`", 0);
	if (!is_array($columns)) {
		$columns = array();
	}

	$cached[ $table_name ] = $columns;
	set_transient($key, $columns, HOUR_IN_SECONDS);
	return $columns;
}

/**
 * Whether FULLTEXT exists on product_title.
 *
 * @param wpdb   $external_db External DB client.
 * @param string $table_name  Table name.
 * @return bool
 */
function antiques_marketplace_has_title_fulltext( $external_db, $table_name ) {
	static $has_fulltext = null;
	if (null !== $has_fulltext) {
		return $has_fulltext;
	}

	$cached = get_transient('antiques_marketplace_has_ft_title');
	if ('1' === $cached || '0' === $cached) {
		$has_fulltext = '1' === $cached;
		return $has_fulltext;
	}

	$indexes = $external_db->get_results("SHOW INDEX FROM `{$table_name}`", ARRAY_A);
	$has_fulltext = false;
	if (is_array($indexes)) {
		foreach ($indexes as $index) {
			$type   = strtoupper((string) ( $index['Index_type'] ?? $index['index_type'] ?? '' ));
			$column = strtolower((string) ( $index['Column_name'] ?? $index['column_name'] ?? '' ));
			if ('FULLTEXT' === $type && 'product_title' === $column) {
				$has_fulltext = true;
				break;
			}
		}
	}

	set_transient('antiques_marketplace_has_ft_title', $has_fulltext ? '1' : '0', DAY_IN_SECONDS);
	return $has_fulltext;
}

/**
 * Build a BOOLEAN MODE query from a user keyword string.
 *
 * @param string $search Raw search.
 * @return string
 */
function antiques_marketplace_fulltext_boolean_query( $search ) {
	$search = trim((string) $search);
	if ('' === $search) {
		return '';
	}

	$tokens = preg_split('/\s+/u', $search);
	if (!is_array($tokens)) {
		return '';
	}

	$parts = array();
	foreach ($tokens as $token) {
		$clean = preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $token);
		if (!is_string($clean) || strlen($clean) < 3) {
			continue;
		}
		$parts[] = '+' . $clean . '*';
	}

	return implode(' ', $parts);
}

/**
 * Prefer the Catawiki CDN thumb URL so the browser skips a 301 on every card image.
 *
 * @param string $url Stored image URL.
 * @return string
 */
function antiques_marketplace_listing_image_url( $url ) {
	$url = (string) $url;
	if (preg_match('#^https://assets\.catawiki\.nl/assets/(.+)/thumb2_([^/]+)$#', $url, $m)) {
		return 'https://assets.catawiki.com/image/cw_thumb2/plain/assets/catawiki/assets/' . $m[1] . '/' . $m[2];
	}
	return $url;
}

/**
 * Ensure the default Past sales sort can use a price-first index.
 *
 * idx_end_price is (end_time, amount). ORDER BY amount DESC still filesorts
 * ~50k+ ended lots (~14s). idx_amount_end walks price backward and stops at LIMIT 20.
 */
function antiques_marketplace_ensure_amount_end_index() {
	if (get_transient('antiques_marketplace_idx_amount_end') === '1') {
		return;
	}

	$external_db = antiques_marketplace_external_db();
	if ($external_db instanceof WP_Error) {
		return;
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return;
	}

	$indexes = $external_db->get_results("SHOW INDEX FROM `{$table_name}`", ARRAY_A);
	$has     = false;
	if (is_array($indexes)) {
		foreach ($indexes as $index) {
			if ('idx_amount_end' === (string) ( $index['Key_name'] ?? '' )) {
				$has = true;
				break;
			}
		}
	}

	if (!$has) {
		$external_db->query("ALTER TABLE `{$table_name}` ADD INDEX idx_amount_end (`current_bid_amount`, `bidding_end_time`)");
	}

	if ('' === (string) $external_db->last_error) {
		set_transient('antiques_marketplace_idx_amount_end', '1', WEEK_IN_SECONDS);
	}
}
add_action('init', 'antiques_marketplace_ensure_amount_end_index', 25);

/**
 * Ensure Catawiki lot id is UNIQUE so scraper upserts instead of inserting copies.
 */
function antiques_marketplace_ensure_product_id_index() {
	if (get_transient('antiques_marketplace_uniq_product_id') === '1') {
		return;
	}

	$external_db = antiques_marketplace_external_db();
	if ($external_db instanceof WP_Error) {
		return;
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return;
	}

	$indexes = $external_db->get_results("SHOW INDEX FROM `{$table_name}`", ARRAY_A);
	$has_unique = false;
	if (is_array($indexes)) {
		foreach ($indexes as $index) {
			$key        = (string) ( $index['Key_name'] ?? '' );
			$non_unique = (int) ( $index['Non_unique'] ?? 1 );
			$column     = strtolower((string) ( $index['Column_name'] ?? '' ));
			if ('product_id' === $column && 0 === $non_unique) {
				$has_unique = true;
				break;
			}
		}
	}

	if (!$has_unique) {
		$external_db->query("ALTER TABLE `{$table_name}` ADD UNIQUE KEY uniq_product_id (`product_id`)");
	}

	if ('' === (string) $external_db->last_error) {
		set_transient('antiques_marketplace_uniq_product_id', '1', WEEK_IN_SECONDS);
	}
}
add_action('init', 'antiques_marketplace_ensure_product_id_index', 26);
