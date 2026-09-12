<?php
/**
 * External catalog listing queries (paginated in SQL).
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Resolve well-known catalog columns from the live table.
 *
 * @param array<int, string> $columns Column names.
 * @return array<string, string|null>
 */
function antiques_marketplace_resolve_product_columns( array $columns ) {
	$column_map = array(
		'id'          => array('product_id', 'id'),
		'title'       => array('product_title', 'title', 'name', 'product_name', 'product_subtitle'),
		'description' => array('product_description', 'description', 'detail', 'product_detail'),
		'detail'      => array('product_detail', 'detail'),
		'price'       => array('current_bid_amount', 'buy_now_price', 'price', 'current_price', 'amount', 'min_bid_amount'),
		'bid_count'   => array('bid_count', 'bids', 'number_of_bids', 'total_bids'),
		'image'       => array('thumb_image', 'original_image', 'image_url', 'image', 'thumbnail', 'photo_url'),
		'images'      => array('product_images'),
		'url'         => array('product_url', 'url', 'permalink', 'link'),
		'created'     => array('created_at', 'created', 'date_created', 'published_at'),
		'end'         => array('bidding_end_time', 'product_end_time', 'auction_end_time', 'ends_at', 'end_time', 'end_date'),
		'active'      => array('is_active', 'active', 'status'),
		'sold'        => array('is_sold', 'sold', 'is_archived'),
		'bids_json'   => array('bidding_history'),
		'seller_country' => array('seller_country'),
		'estimate_min'   => array('expert_estimate_min'),
		'estimate_max'   => array('expert_estimate_max'),
	);

	$resolved = array();
	foreach ($column_map as $key => $candidates) {
		$resolved[ $key ] = null;
		foreach ($candidates as $candidate) {
			if (in_array($candidate, $columns, true)) {
				$resolved[ $key ] = $candidate;
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

	return $resolved;
}

/**
 * Whether the catalog table has both an autoincrement row id and a Catawiki lot id.
 *
 * The scraper PRIMARY KEY is `id`, not `product_id`, so re-scrapes insert extra rows.
 *
 * @param array<int, string> $columns Column names.
 * @return bool
 */
function antiques_marketplace_has_lot_snapshot_columns( array $columns ) {
	return in_array('id', $columns, true) && in_array('product_id', $columns, true);
}

/**
 * FROM clause that keeps the latest scrape row per Catawiki lot.
 *
 * @param string             $table_name Products table.
 * @param array<int, string> $columns    Column names.
 * @return string
 */
function antiques_marketplace_products_from_latest_lot( $table_name, array $columns ) {
	$table_name = str_replace('`', '', (string) $table_name);
	if (!antiques_marketplace_has_lot_snapshot_columns($columns)) {
		return "`{$table_name}`";
	}

	return "`{$table_name}` INNER JOIN (SELECT MAX(`id`) AS `id` FROM `{$table_name}` GROUP BY `product_id`) latest_lot ON latest_lot.`id` = `{$table_name}`.`id`";
}

/**
 * WHERE fragment that keeps the latest scrape row per Catawiki lot.
 *
 * Use this on LIMIT listing SELECTs so idx_amount_end can stop early.
 * Do not use it for full-table COUNT/dashboard scans — those should use
 * antiques_marketplace_products_from_latest_lot().
 *
 * @param string             $table_name Products table.
 * @param array<int, string> $columns    Column names.
 * @return string Empty when the table has no snapshot columns.
 */
function antiques_marketplace_latest_lot_where_sql( $table_name, array $columns ) {
	$table_name = str_replace('`', '', (string) $table_name);
	if (!antiques_marketplace_has_lot_snapshot_columns($columns)) {
		return '';
	}

	return "`{$table_name}`.`id` = (SELECT MAX(`p2`.`id`) FROM `{$table_name}` `p2` WHERE `p2`.`product_id` = `{$table_name}`.`product_id`)";
}

/**
 * Append dynamic WHERE clauses for listing filters.
 *
 * @param array<string, mixed>       $args          Filter args.
 * @param array<string, string|null> $resolved      Column map.
 * @param wpdb                       $external_db   DB handle.
 * @param array<int, string>         $where_clauses WHERE parts (by ref).
 * @param array<int, mixed>          $query_params  Params (by ref).
 * @param bool                       $has_fulltext  Title FULLTEXT available.
 */
function antiques_marketplace_append_product_filters( $args, $resolved, $external_db, &$where_clauses, &$query_params, $has_fulltext = false ) {
	if (!empty($args['search'])) {
		$boolean = antiques_marketplace_fulltext_boolean_query((string) $args['search']);
		if ($has_fulltext && $resolved['title'] && 'product_title' === $resolved['title'] && '' !== $boolean) {
			$where_clauses[] = 'MATCH(`product_title`) AGAINST (%s IN BOOLEAN MODE)';
			$query_params[]  = $boolean;
		} elseif ($resolved['title']) {
			$like            = '%' . $external_db->esc_like((string) $args['search']) . '%';
			$where_clauses[] = "`{$resolved['title']}` LIKE %s";
			$query_params[]  = $like;
		}
	}

	$min_price = $args['min_price'] ?? null;
	$max_price = $args['max_price'] ?? null;
	if (null !== $min_price && '' !== $min_price && $resolved['price']) {
		$where_clauses[] = "`{$resolved['price']}` >= %f";
		$query_params[]  = antiques_marketplace_convert_display_to_eur((float) $min_price);
	}
	if (null !== $max_price && '' !== $max_price && $resolved['price']) {
		$where_clauses[] = "`{$resolved['price']}` <= %f";
		$query_params[]  = antiques_marketplace_convert_display_to_eur((float) $max_price);
	}

	if (!empty($args['has_price']) && $resolved['price']) {
		$where_clauses[] = "`{$resolved['price']}` > 0";
	}

	if (!empty($args['category']) && $resolved['title']) {
		$slug = sanitize_title((string) $args['category']);
		$ft   = antiques_marketplace_category_fulltext_query($slug);
		if ('other' === $slug) {
			$exclude_parts = array();
			foreach (array_keys(antiques_marketplace_catalog_taxonomy()) as $other_slug) {
				$other_ft = antiques_marketplace_category_fulltext_query($other_slug);
				if ('' === $other_ft || !$has_fulltext) {
					continue;
				}
				$exclude_parts[] = 'NOT MATCH(`product_title`) AGAINST (%s IN BOOLEAN MODE)';
				$query_params[]  = $other_ft;
			}
			if (!empty($exclude_parts)) {
				$where_clauses[] = '(' . implode(' AND ', $exclude_parts) . ')';
			}
		} elseif ($has_fulltext && '' !== $ft && 'product_title' === $resolved['title']) {
			$where_clauses[] = 'MATCH(`product_title`) AGAINST (%s IN BOOLEAN MODE)';
			$query_params[]  = $ft;
		} else {
			$taxonomy = antiques_marketplace_catalog_taxonomy();
			if (isset($taxonomy[ $slug ])) {
				$likes = array();
				foreach ($taxonomy[ $slug ]['keywords'] as $keyword) {
					$likes[]        = "`{$resolved['title']}` LIKE %s";
					$query_params[] = '%' . $external_db->esc_like($keyword) . '%';
				}
				if (!empty($likes)) {
					$where_clauses[] = '(' . implode(' OR ', $likes) . ')';
				}
			}
		}
	}

	if (!empty($args['listing_status']) && $resolved['end']) {
		$end_col = $resolved['end'];
		if ('active' === $args['listing_status']) {
			$where_clauses[] = "(`{$end_col}` IS NULL OR `{$end_col}` >= NOW())";
		} elseif ('ended' === $args['listing_status']) {
			$where_clauses[] = "(`{$end_col}` IS NOT NULL AND `{$end_col}` < NOW())";
		}
	}

	if ($resolved['end'] && !empty($args['ending_within']) && 'ended' !== ($args['listing_status'] ?? '')) {
		$hours           = (int) $args['ending_within'];
		$end_col         = $resolved['end'];
		$where_clauses[] = "`{$end_col}` IS NOT NULL AND `{$end_col}` >= NOW() AND `{$end_col}` <= DATE_ADD(NOW(), INTERVAL %d HOUR)";
		$query_params[]  = $hours;
	}

	if (
		'ended' === ($args['listing_status'] ?? '') &&
		!empty($args['sold_within']) &&
		$resolved['end']
	) {
		$days            = (int) $args['sold_within'];
		$end_col         = $resolved['end'];
		$where_clauses[] = "`{$end_col}` IS NOT NULL AND `{$end_col}` >= DATE_SUB(NOW(), INTERVAL %d DAY) AND `{$end_col}` <= NOW()";
		$query_params[]  = $days;
	}
}

/**
 * Distinct catalog categories (the ten taxonomy sections).
 *
 * @return array<int, string>
 */
function antiques_marketplace_get_external_categories() {
	return array_keys(antiques_marketplace_catalog_taxonomy());
}

/**
 * Build products from the external MySQL catalog.
 *
 * Pagination and filters run in SQL. Duplicate scrape rows are collapsed in SQL
 * (latest `id` per Catawiki `product_id`). Do not PHP-dedupe thousands of rows.
 *
 * @param array<string, mixed> $args Query arguments.
 * @return array<string, mixed>
 */
function antiques_marketplace_get_external_products( $args = array() ) {
	$defaults = array(
		'search'         => '',
		'min_price'      => null,
		'max_price'      => null,
		'sort'           => 'latest',
		'limit'          => 20,
		'page'           => 1,
		'listing_status' => 'ended',
		'ending_within'  => 0,
		'sold_within'    => 0,
		'category'       => '',
		'has_price'      => false,
		'dedupe'         => false,
	);
	$args = wp_parse_args($args, $defaults);

	$external_db = antiques_marketplace_external_db();
	if (is_wp_error($external_db)) {
		return array(
			'products' => array(),
			'error'    => $external_db->get_error_message(),
		);
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return array(
			'products' => array(),
			'error'    => __('No product tables found in external database.', 'antiques-marketplace'),
		);
	}

	$columns = antiques_marketplace_product_columns($external_db, $table_name);
	if (empty($columns)) {
		return array(
			'products' => array(),
			'error'    => __('Could not read product table columns.', 'antiques-marketplace'),
		);
	}

	$resolved = antiques_marketplace_resolve_product_columns($columns);
	if (!$resolved['title']) {
		return array(
			'products' => array(),
			'error'    => __('No title-like column found in external products table.', 'antiques-marketplace'),
		);
	}

	$has_fulltext = antiques_marketplace_has_title_fulltext($external_db, $table_name);
	$id_column    = $resolved['id'] ? $resolved['id'] : $resolved['title'];
	$price_expr   = $resolved['price'] ? "`{$resolved['price']}`" : 'NULL';
	$bids_expr    = $resolved['bid_count'] ? "`{$resolved['bid_count']}`" : 'NULL';
	$image_expr   = $resolved['image'] ? "`{$resolved['image']}`" : 'NULL';
	$url_expr     = $resolved['url'] ? "`{$resolved['url']}`" : 'NULL';
	$created_expr = $resolved['created'] ? "`{$resolved['created']}`" : 'NULL';
	$end_expr     = $resolved['end'] ? "`{$resolved['end']}`" : 'NULL';

	$from_sql      = antiques_marketplace_products_from_latest_lot($table_name, $columns);
	$latest_where  = antiques_marketplace_latest_lot_where_sql($table_name, $columns);
	$where_clauses = array();
	$query_params  = array();
	antiques_marketplace_append_product_filters($args, $resolved, $external_db, $where_clauses, $query_params, $has_fulltext);

	$count_sql = "SELECT COUNT(*) FROM {$from_sql}";
	if (!empty($where_clauses)) {
		$count_sql .= ' WHERE ' . implode(' AND ', $where_clauses);
	}

	$count_key    = 'am_cnt_' . md5($count_sql . wp_json_encode($query_params));
	$cached_total = get_transient($count_key);
	if (is_numeric($cached_total)) {
		$total_count = (int) $cached_total;
	} else {
		$total_count = (int) $external_db->get_var(
			!empty($query_params) ? $external_db->prepare($count_sql, $query_params) : $count_sql
		);
		set_transient($count_key, $total_count, 10 * MINUTE_IN_SECONDS);
	}

	$sql = "
		SELECT
			`{$id_column}` AS product_id,
			`{$resolved['title']}` AS product_title,
			{$price_expr} AS product_price,
			{$bids_expr} AS product_bid_count,
			{$image_expr} AS product_image,
			{$url_expr} AS product_url,
			{$created_expr} AS product_created,
			{$end_expr} AS product_end_time
		FROM `{$table_name}`
	";

	$select_wheres = $where_clauses;
	if ('' !== $latest_where) {
		array_unshift($select_wheres, $latest_where);
	}
	if (!empty($select_wheres)) {
		$sql .= ' WHERE ' . implode(' AND ', $select_wheres);
	}

	if ('price_low' === $args['sort'] && $resolved['price']) {
		$sql .= " ORDER BY `{$resolved['price']}` ASC";
	} elseif ('price_high' === $args['sort'] && $resolved['price']) {
		$sql .= " ORDER BY `{$resolved['price']}` DESC";
	} elseif ('bids_high' === $args['sort'] && $resolved['bid_count']) {
		$sql .= " ORDER BY `{$resolved['bid_count']}` DESC";
	} elseif ('bids_low' === $args['sort'] && $resolved['bid_count']) {
		$sql .= " ORDER BY `{$resolved['bid_count']}` ASC";
	} elseif ('end_asc' === $args['sort'] && $resolved['end']) {
		$sql .= " ORDER BY `{$resolved['end']}` ASC";
	} elseif ('end_desc' === $args['sort'] && $resolved['end']) {
		$sql .= " ORDER BY `{$resolved['end']}` DESC";
	} elseif ($resolved['end'] && 'ended' === $args['listing_status']) {
		$sql .= " ORDER BY `{$resolved['end']}` DESC";
	} elseif ($resolved['created']) {
		$sql .= " ORDER BY `{$resolved['created']}` DESC";
	} else {
		$sql .= " ORDER BY `{$resolved['title']}` ASC";
	}

	$limit          = max(1, (int) $args['limit']);
	$page           = max(1, (int) $args['page']);
	$offset         = ($page - 1) * $limit;
	$sql           .= ' LIMIT %d OFFSET %d';
	$query_params[] = $limit;
	$query_params[] = $offset;

	$prepared_sql = $external_db->prepare($sql, $query_params);
	$products     = $external_db->get_results($prepared_sql, ARRAY_A);

	return array(
		'products' => is_array($products) ? $products : array(),
		'error'    => !empty($external_db->error) ? $external_db->error : null,
		'table'    => $table_name,
		'total'    => $total_count,
	);
}

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

	$external_db = antiques_marketplace_external_db();
	if (is_wp_error($external_db)) {
		return array('product' => null, 'error' => $external_db->get_error_message());
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return array('product' => null, 'error' => __('No product table found.', 'antiques-marketplace'));
	}

	$columns = antiques_marketplace_product_columns($external_db, $table_name);
	if (empty($columns)) {
		return array('product' => null, 'error' => __('Could not read product table columns.', 'antiques-marketplace'));
	}

	$resolved = antiques_marketplace_resolve_product_columns($columns);
	if (!$resolved['id']) {
		return array('product' => null, 'error' => __('No product ID column found.', 'antiques-marketplace'));
	}
	if (!$resolved['title']) {
		return array('product' => null, 'error' => __('No product title column found.', 'antiques-marketplace'));
	}

	$sql_expr = function( $column ) {
		return $column ? "`{$column}`" : 'NULL';
	};

	$lot_column = in_array('product_id', $columns, true) ? 'product_id' : $resolved['id'];
	$row_column = in_array('id', $columns, true) ? 'id' : $resolved['id'];

	$sql = "
		SELECT
			`{$lot_column}` AS product_id,
			`{$resolved['title']}` AS product_title,
			{$sql_expr($resolved['description'])} AS product_description,
			{$sql_expr($resolved['detail'])} AS product_detail,
			{$sql_expr($resolved['price'])} AS product_price,
			{$sql_expr($resolved['bid_count'])} AS product_bid_count,
			{$sql_expr($resolved['image'])} AS product_image,
			{$sql_expr($resolved['images'])} AS product_images,
			{$sql_expr($resolved['url'])} AS product_url,
			{$sql_expr($resolved['created'])} AS product_created,
			{$sql_expr($resolved['end'])} AS product_end_time,
			{$sql_expr($resolved['bids_json'])} AS product_bid_history,
			{$sql_expr($resolved['seller_country'])} AS product_seller_country,
			{$sql_expr($resolved['estimate_min'])} AS product_estimate_min,
			{$sql_expr($resolved['estimate_max'])} AS product_estimate_max
		FROM `{$table_name}`
	";

	if ($lot_column !== $row_column) {
		$sql .= " WHERE (`{$lot_column}` = %d OR `{$row_column}` = %d) ORDER BY (`{$lot_column}` = %d) DESC, `{$row_column}` DESC LIMIT 1";
		$prepared = $external_db->prepare($sql, $product_id, $product_id, $product_id);
	} else {
		$sql     .= " WHERE `{$lot_column}` = %d LIMIT 1";
		$prepared = $external_db->prepare($sql, $product_id);
	}

	$product = $external_db->get_row($prepared, ARRAY_A);
	if (empty($product)) {
		return array('product' => null, 'error' => __('Product not found.', 'antiques-marketplace'));
	}

	return array('product' => $product, 'error' => null);
}
