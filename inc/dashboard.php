<?php
/**
 * Market-trend dashboard (ended Catawiki lots, EUR then converted).
 *
 * Page equations:
 * 1. Median sold price          = P50(hammer) in the selected window
 * 2. Price trend over time      = P50(hammer) by day (1m) or month (6m/12m)
 * 3. Count of antiques sold     = COUNT(*) by the same buckets
 * 4. Which country buying what  = winning bidder country × title taxonomy
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * P50 of a numeric list.
 *
 * @param array<int, float> $amounts EUR hammers.
 * @return float
 */
function antiques_marketplace_median_amount( array $amounts ) {
	$count = count($amounts);
	if ($count < 1) {
		return 0.0;
	}

	sort($amounts, SORT_NUMERIC);
	$middle = (int) floor(($count - 1) / 2);
	if (0 === $count % 2) {
		return ((float) $amounts[ $middle ] + (float) $amounts[ $middle + 1 ]) / 2;
	}

	return (float) $amounts[ $middle ];
}

/**
 * Display-currency number from a EUR hammer (no symbol).
 *
 * @param float $eur_amount Catalog amount.
 * @return float
 */
function antiques_marketplace_dashboard_display_number( $eur_amount ) {
	$currency = antiques_marketplace_display_currency();
	$rate     = antiques_marketplace_eur_to_display_rate($currency);
	$amount   = (float) $eur_amount * $rate;
	return 'JPY' === $currency ? (float) round($amount) : round($amount, 2);
}

/**
 * Build one period payload from already-filtered sold rows.
 *
 * @param array<int, array<string, mixed>> $rows     Sold lots.
 * @param string                           $grain    day|month.
 * @param array<int, string>               $bucket_keys Ordered keys.
 * @param string                           $range_key 1m|6m|12m.
 * @return array<string, mixed>
 */
function antiques_marketplace_dashboard_period_from_rows( array $rows, $grain, array $bucket_keys, $range_key ) {
	$amounts_all = array();
	$by_bucket   = array();
	$by_country  = array();
	$first_end   = '';
	$last_end    = '';

	foreach ($bucket_keys as $key) {
		$by_bucket[ $key ] = array();
	}

	foreach ($rows as $row) {
		$ended = (string) ( $row['bidding_end_time'] ?? '' );
		$eur   = (float) ( $row['current_bid_amount'] ?? 0 );
		if ('' === $ended || $eur <= 0) {
			continue;
		}

		$bucket = 'day' === $grain ? substr($ended, 0, 10) : substr($ended, 0, 7);
		$amounts_all[] = $eur;
		if ('' === $first_end || $ended < $first_end) {
			$first_end = $ended;
		}
		if ($ended > $last_end) {
			$last_end = $ended;
		}
		if (isset($by_bucket[ $bucket ])) {
			$by_bucket[ $bucket ][] = $eur;
		}

		$code = strtoupper(trim((string) ( $row['country_code'] ?? '' )));
		if ('' === $code || 'NULL' === $code) {
			continue;
		}

		if (!isset($by_country[ $code ])) {
			$by_country[ $code ] = array(
				'amounts'    => array(),
				'categories' => array(),
			);
		}
		$by_country[ $code ]['amounts'][] = $eur;
		$slug = antiques_marketplace_classify_listing((string) ( $row['product_title'] ?? '' ), '', false);
		if (!isset($by_country[ $code ]['categories'][ $slug ])) {
			$by_country[ $code ]['categories'][ $slug ] = 0;
		}
		$by_country[ $code ]['categories'][ $slug ]++;
	}

	$price_series  = array();
	$volume_series = array();
	foreach ($bucket_keys as $key) {
		$bucket_amounts = $by_bucket[ $key ];
		$lots           = count($bucket_amounts);
		$volume_series[] = array(
			't' => $key,
			'v' => $lots,
		);
		$price_series[] = array(
			't' => $key,
			'v' => $lots > 0 ? antiques_marketplace_dashboard_display_number(antiques_marketplace_median_amount($bucket_amounts)) : null,
		);
	}

	uasort(
		$by_country,
		function( $left, $right ) {
			return count($right['amounts']) <=> count($left['amounts']);
		}
	);

	$countries = array();
	$shown     = 0;
	foreach ($by_country as $code => $entry) {
		if ($shown >= 8) {
			break;
		}
		arsort($entry['categories']);
		$top = array();
		foreach (array_keys($entry['categories']) as $slug) {
			if (count($top) >= 3) {
				break;
			}
			if ('other' === $slug && count($entry['categories']) > 1) {
				continue;
			}
			$top[] = array(
				'slug'  => $slug,
				'label' => antiques_marketplace_category_label($slug),
				'url'   => antiques_marketplace_category_url($slug),
			);
		}
		if (empty($top)) {
			$top[] = array(
				'slug'  => 'other',
				'label' => antiques_marketplace_category_label('other'),
				'url'   => antiques_marketplace_category_url('other'),
			);
		}

		$countries[] = array(
			'code'       => $code,
			'name'       => antiques_marketplace_country_name($code),
			'lots'       => count($entry['amounts']),
			'median_eur' => antiques_marketplace_median_amount($entry['amounts']),
			'categories' => $top,
		);
		$shown++;
	}

	$lots = count($amounts_all);
	return array(
		'key'        => $range_key,
		'lots'       => $lots,
		'median_eur' => antiques_marketplace_median_amount($amounts_all),
		'first_end'  => $first_end,
		'last_end'   => $last_end,
		'price'      => $price_series,
		'volume'     => $volume_series,
		'countries'  => $countries,
	);
}

/**
 * Ordered day keys ending at an anchor timestamp.
 *
 * @param int $anchor_ts Unix timestamp of the latest sold lot.
 * @param int $days      Day count.
 * @return array<int, string>
 */
function antiques_marketplace_dashboard_day_keys_until( $anchor_ts, $days ) {
	$keys = array();
	$days = max(1, (int) $days);
	$end  = strtotime(gmdate('Y-m-d', (int) $anchor_ts));
	for ($offset = $days - 1; $offset >= 0; $offset--) {
		$keys[] = gmdate('Y-m-d', $end - ( $offset * DAY_IN_SECONDS ));
	}
	return $keys;
}

/**
 * Ordered month keys ending at an anchor timestamp.
 *
 * @param int $anchor_ts Unix timestamp of the latest sold lot.
 * @param int $months    Month count.
 * @return array<int, string>
 */
function antiques_marketplace_dashboard_month_keys_until( $anchor_ts, $months ) {
	$keys   = array();
	$months = max(1, (int) $months);
	$cursor = strtotime(gmdate('Y-m-01', (int) $anchor_ts));
	for ($offset = $months - 1; $offset >= 0; $offset--) {
		$keys[] = gmdate('Y-m', strtotime('-' . $offset . ' months', $cursor));
	}
	return $keys;
}

/**
 * Cached dashboard payload for the four page equations.
 *
 * @return array<string, mixed>
 */
function antiques_marketplace_get_dashboard_metrics() {
	$cache_key = 'antiques_marketplace_dashboard_v4_' . antiques_marketplace_display_currency();
	$cached    = get_transient($cache_key);
	if (is_array($cached)) {
		return $cached;
	}

	$external_db = antiques_marketplace_external_db();
	if (is_wp_error($external_db)) {
		return array(
			'error' => $external_db->get_error_message(),
		);
	}

	$table_name = antiques_marketplace_detect_products_table($external_db);
	if (!$table_name) {
		return array('error' => __('No product table found.', 'antiques-marketplace'));
	}

	$columns  = antiques_marketplace_product_columns($external_db, $table_name);
	$from_sql = antiques_marketplace_products_from_latest_lot($table_name, $columns);

	$rows = $external_db->get_results(
		"SELECT
			bidding_end_time,
			current_bid_amount,
			product_title,
			UPPER(JSON_UNQUOTE(JSON_EXTRACT(bidding_history, '\$[0].country.code'))) AS country_code
		FROM {$from_sql}
		WHERE bidding_end_time IS NOT NULL
			AND bidding_end_time < NOW()
			AND current_bid_amount > 0
			AND bidding_end_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)",
		ARRAY_A
	);

	if (!is_array($rows)) {
		$rows = array();
	}

	$latest_end = '';
	foreach ($rows as $row) {
		$ended = (string) ( $row['bidding_end_time'] ?? '' );
		if ($ended > $latest_end) {
			$latest_end = $ended;
		}
	}
	$anchor_ts = $latest_end ? strtotime($latest_end) : time();
	$day_keys_1m   = antiques_marketplace_dashboard_day_keys_until($anchor_ts, 30);
	$month_keys_6m = antiques_marketplace_dashboard_month_keys_until($anchor_ts, 6);
	$month_keys_12m = antiques_marketplace_dashboard_month_keys_until($anchor_ts, 12);
	$cutoff_1m     = reset($day_keys_1m) . ' 00:00:00';
	$cutoff_6m     = reset($month_keys_6m) . '-01 00:00:00';
	$cutoff_12m    = reset($month_keys_12m) . '-01 00:00:00';

	$rows_1m = array();
	$rows_6m = array();
	$rows_12m = array();
	foreach ($rows as $row) {
		$ended = (string) ( $row['bidding_end_time'] ?? '' );
		if ($ended >= $cutoff_12m) {
			$rows_12m[] = $row;
		}
		if ($ended >= $cutoff_6m) {
			$rows_6m[] = $row;
		}
		if ($ended >= $cutoff_1m) {
			$rows_1m[] = $row;
		}
	}

	$payload = array(
		'generated_at' => gmdate('c'),
		'currency'     => antiques_marketplace_display_currency(),
		'periods'      => array(
			'1m'  => antiques_marketplace_dashboard_period_from_rows($rows_1m, 'day', $day_keys_1m, '1m'),
			'6m'  => antiques_marketplace_dashboard_period_from_rows($rows_6m, 'month', $month_keys_6m, '6m'),
			'12m' => antiques_marketplace_dashboard_period_from_rows($rows_12m, 'month', $month_keys_12m, '12m'),
		),
		'fx'           => antiques_marketplace_exchange_rates(),
		'error'        => !empty($external_db->error) ? $external_db->error : null,
	);

	set_transient($cache_key, $payload, 30 * MINUTE_IN_SECONDS);
	return $payload;
}

/**
 * Front-end payload for period toggles and SVG charts.
 *
 * @param array<string, mixed> $metrics Dashboard metrics.
 * @return array<string, mixed>
 */
function antiques_marketplace_dashboard_front_data( array $metrics ) {
	$range_labels = array(
		'1m'  => __('1 month', 'antiques-marketplace'),
		'6m'  => __('6 months', 'antiques-marketplace'),
		'12m' => __('12 months', 'antiques-marketplace'),
	);

	$periods = array();
	foreach ($range_labels as $key => $label) {
		$period = isset($metrics['periods'][ $key ]) && is_array($metrics['periods'][ $key ])
			? $metrics['periods'][ $key ]
			: array(
				'lots'       => 0,
				'median_eur' => 0,
				'first_end'  => '',
				'last_end'   => '',
				'price'      => array(),
				'volume'     => array(),
				'countries'  => array(),
			);
		$lots = (int) ( $period['lots'] ?? 0 );
		$countries = array();
		foreach ((array) ( $period['countries'] ?? array() ) as $country) {
			$countries[] = array(
				'name'       => (string) ( $country['name'] ?? '' ),
				'lots'       => sprintf(
					/* translators: %s: formatted lot count */
					__('%s lots', 'antiques-marketplace'),
					number_format_i18n((int) ( $country['lots'] ?? 0 ))
				),
				'median'     => antiques_marketplace_format_currency_amount((float) ( $country['median_eur'] ?? 0 )),
				'categories' => isset($country['categories']) && is_array($country['categories']) ? $country['categories'] : array(),
			);
		}
		$first_end = (string) ( $period['first_end'] ?? '' );
		$last_end  = (string) ( $period['last_end'] ?? '' );
		$date_span = $first_end && $last_end
			? sprintf(
				/* translators: 1: first date, 2: last date */
				__('%1$s to %2$s', 'antiques-marketplace'),
				substr($first_end, 0, 10),
				substr($last_end, 0, 10)
			)
			: __('No available dates', 'antiques-marketplace');
		$periods[ $key ] = array(
			'median'    => antiques_marketplace_format_currency_amount((float) ( $period['median_eur'] ?? 0 )),
			'caption'   => sprintf(
				/* translators: 1: period label, 2: available date span, 3: lot count */
				__('P50 hammer, %1$s view. Available data: %2$s. %3$s lots.', 'antiques-marketplace'),
				$label,
				$date_span,
				number_format_i18n($lots)
			),
			'price'     => isset($period['price']) && is_array($period['price']) ? $period['price'] : array(),
			'volume'    => isset($period['volume']) && is_array($period['volume']) ? $period['volume'] : array(),
			'countries' => $countries,
		);
	}

	return array(
		'currency'      => antiques_marketplace_display_currency(),
		'empty'         => __('No sold lots in this period.', 'antiques-marketplace'),
		'price_label'   => __('Median sold price', 'antiques-marketplace'),
		'volume_label'  => __('Lots sold', 'antiques-marketplace'),
		'periods'       => $periods,
	);
}

/**
 * Create the Market trends page if missing.
 */
function antiques_marketplace_ensure_dashboard_page() {
	if (get_option('antiques_marketplace_dashboard_page_version') === '1') {
		return;
	}

	$existing = get_page_by_path('market-trends');
	if (!$existing instanceof WP_Post) {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Market trends',
				'post_name'    => 'market-trends',
				'post_content' => '',
			)
		);
		if (is_int($page_id) && $page_id > 0) {
			update_post_meta($page_id, '_wp_page_template', 'page-dashboard.php');
		}
	}

	update_option('antiques_marketplace_dashboard_page_version', '1');
}
add_action('init', 'antiques_marketplace_ensure_dashboard_page');

/**
 * Load dashboard template by slug.
 *
 * @param string $template Current template.
 * @return string
 */
function antiques_marketplace_dashboard_template_include( $template ) {
	if (!is_page('market-trends')) {
		return $template;
	}
	$custom = get_template_directory() . '/page-dashboard.php';
	return file_exists($custom) ? $custom : $template;
}
add_filter('template_include', 'antiques_marketplace_dashboard_template_include', 20);
