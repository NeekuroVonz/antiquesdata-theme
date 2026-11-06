<?php
/**
 * Scheduled market summary for the Trend Dashboard.
 *
 * A daily WP-Cron job writes one summary per language (en/ja) and range (1m/6m/12m)
 * from the cached dashboard metrics. Pages render the stored text; visitors never
 * wait on (or pay for) a model call. Without OPENAI_API_KEY the summary is written
 * from the numbers alone.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

const ANTIQUES_DASHBOARD_SUMMARY_OPTION = 'antiques_dashboard_summary_v1';
const ANTIQUES_DASHBOARD_SUMMARY_HOOK   = 'antiques_dashboard_summary_refresh';

/**
 * Range keys shown on the dashboard.
 *
 * @return string[]
 */
function antiques_marketplace_dashboard_ranges() {
	return array('1m', '6m', '12m');
}

/**
 * % change between the first and second half of a series (median of each half).
 * Buckets with under 30% of the typical lot count are skipped: the newest day or month
 * is usually still being ingested and would otherwise read as a crash.
 *
 * @param array<int, array<string, mixed>> $points Series points with key "v".
 * @param array<int, array<string, mixed>> $volume Lots per bucket, same order.
 * @return float|null Null when fewer than 4 usable buckets.
 */
function antiques_marketplace_dashboard_series_change( array $points, array $volume ) {
	$lots = array();
	foreach ($volume as $i => $point) {
		$lots[ $i ] = is_array($point) ? (int) ( $point['v'] ?? 0 ) : 0;
	}
	$typical = antiques_marketplace_median_amount(array_values(array_filter($lots)));

	$values = array();
	foreach ($points as $i => $point) {
		$v = is_array($point) && isset($point['v']) ? (float) $point['v'] : 0.0;
		if ($v > 0 && ( $lots[ $i ] ?? 0 ) >= 0.3 * $typical) {
			$values[] = $v;
		}
	}
	if (count($values) < 4) {
		return null;
	}
	$half  = (int) floor(count($values) / 2);
	$start = antiques_marketplace_median_amount(array_slice($values, 0, $half));
	$end   = antiques_marketplace_median_amount(array_slice($values, -$half));
	return $start > 0 ? ( $end - $start ) / $start * 100 : null;
}

/**
 * Compact, model-safe facts for one range (display currency of the active language).
 *
 * @param array<string, mixed> $metrics Dashboard metrics.
 * @param string               $range   1m|6m|12m.
 * @return array<string, mixed>
 */
function antiques_marketplace_dashboard_summary_facts( array $metrics, $range ) {
	$period = isset($metrics['periods'][ $range ]) && is_array($metrics['periods'][ $range ]) ? $metrics['periods'][ $range ] : array();
	$lots   = (int) ( $period['lots'] ?? 0 );

	$countries = array();
	foreach (array_slice((array) ( $period['countries'] ?? array() ), 0, 5) as $country) {
		if (!is_array($country)) {
			continue;
		}
		$cats = array();
		foreach (array_slice((array) ( $country['categories'] ?? array() ), 0, 3) as $category) {
			if (is_array($category) && !empty($category['label'])) {
				$cats[] = (string) $category['label'];
			}
		}
		$country_lots = (int) ( $country['lots'] ?? 0 );
		$countries[]  = array(
			'name'          => (string) ( $country['name'] ?? '' ),
			'lots'          => $country_lots,
			'share_percent' => $lots > 0 ? round($country_lots / $lots * 100, 1) : 0,
			'median'        => antiques_marketplace_format_currency_amount((float) ( $country['median_eur'] ?? 0 )),
			'categories'    => $cats,
		);
	}

	$volume_series = (array) ( $period['volume'] ?? array() );
	$price_change  = antiques_marketplace_dashboard_series_change((array) ( $period['price'] ?? array() ), $volume_series);
	$volume_change = antiques_marketplace_dashboard_series_change($volume_series, $volume_series);

	return array(
		'range'                 => $range,
		'from'                  => substr((string) ( $period['first_end'] ?? '' ), 0, 10),
		'to'                    => substr((string) ( $period['last_end'] ?? '' ), 0, 10),
		'median'                => antiques_marketplace_format_currency_amount((float) ( $period['median_eur'] ?? 0 )),
		'lots'                  => $lots,
		// First vs second half of the window, ignoring buckets that are still being ingested.
		'median_change_percent' => null === $price_change ? null : round($price_change, 1),
		'volume_change_percent' => null === $volume_change ? null : round($volume_change, 1),
		'top_countries'         => $countries,
	);
}

/**
 * Plain summary written from the facts (no model).
 *
 * @param array<string, mixed> $facts Facts from antiques_marketplace_dashboard_summary_facts().
 * @param string               $lang  en|ja.
 * @return string Lines; bullets start with "• ".
 */
function antiques_marketplace_dashboard_summary_from_facts( array $facts, $lang ) {
	$ja    = 'ja' === $lang;
	$lines = array();
	$lots  = number_format_i18n((int) $facts['lots']);

	if ((int) $facts['lots'] < 1) {
		return $ja ? 'この期間に落札データはありません。' : 'No sold lots in this period.';
	}

	$lines[] = $ja
		? sprintf('• 落札中央値は%s、落札ロット数は%s件です。', $facts['median'], $lots)
		: sprintf('• Median hammer of %s across %s sold lots.', $facts['median'], $lots);

	$change = $facts['median_change_percent'];
	if (null !== $change) {
		$abs = number_format_i18n(abs((float) $change), 1);
		if (abs((float) $change) < 3) {
			$lines[] = $ja ? '• 期間を通じて価格水準はほぼ横ばいです。' : '• Prices held roughly flat across the window.';
		} elseif ($change > 0) {
			$lines[] = $ja ? sprintf('• 期間の前半と比べて、後半の中央値は約%s%%高くなっています。', $abs) : sprintf('• Medians in the second half of the window run about %s%% above the first half.', $abs);
		} else {
			$lines[] = $ja ? sprintf('• 期間の前半と比べて、後半の中央値は約%s%%低くなっています。', $abs) : sprintf('• Medians in the second half of the window run about %s%% below the first half.', $abs);
		}
	}

	$volume = $facts['volume_change_percent'];
	if (null !== $volume && abs((float) $volume) >= 10) {
		$abs     = number_format_i18n(abs((float) $volume), 0);
		$lines[] = $volume > 0
			? ( $ja ? sprintf('• 落札件数は増加傾向です（約%s%%）。', $abs) : sprintf('• Volume is rising (about %s%%).', $abs) )
			: ( $ja ? sprintf('• 落札件数は減少傾向です（約%s%%）。', $abs) : sprintf('• Volume is easing (about %s%%).', $abs) );
	}

	if (!empty($facts['top_countries'][0]['name'])) {
		$top     = $facts['top_countries'][0];
		$cat     = !empty($top['categories'][0]) ? $top['categories'][0] : '';
		$share   = number_format_i18n((float) $top['share_percent'], 0);
		$lines[] = $ja
			? sprintf('• 最も多く落札しているのは%s（全体の約%s%%）%s。', $top['name'], $share, $cat ? '、中心は#' . $cat : '')
			: sprintf('• %s is the most active buyer (about %s%% of lots)%s.', $top['name'], $share, $cat ? ', led by #' . $cat : '');
	}

	return implode("\n", $lines);
}

/**
 * Ask the model for a summary; empty string when unavailable.
 *
 * @param array<string, mixed> $facts Facts.
 * @param string               $lang  en|ja.
 * @return string
 */
function antiques_marketplace_dashboard_summary_from_model( array $facts, $lang ) {
	$api_key = antiques_marketplace_env('OPENAI_API_KEY');
	if ('' === $api_key || (int) $facts['lots'] < 1) {
		return '';
	}

	$system = 'You write a short market summary for Japanese antique dealers. '
		. 'Use ONLY the JSON facts; never invent numbers, countries, categories, or dates. '
		. 'Percent changes compare the first and second half of the window; treat them as direction, not forecasts. '
		. 'Write in ' . ( 'ja' === $lang ? 'Japanese' : 'English' ) . '. '
		. 'Output 3 to 4 lines, each starting with "• ", one plain sentence each. No markdown, no headings, no advice to buy or sell.';

	$payload = wp_json_encode(
		array(
			'model'       => 'gpt-4o-mini',
			'temperature' => 0.3,
			'messages'    => array(
				array('role' => 'system', 'content' => $system),
				array('role' => 'user', 'content' => (string) wp_json_encode($facts, JSON_UNESCAPED_UNICODE)),
			),
		)
	);

	$response = wp_remote_post(
		'https://api.openai.com/v1/chat/completions',
		array(
			'timeout' => 25,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => $payload ? $payload : '{}',
		)
	);
	if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
		return '';
	}
	$body = json_decode((string) wp_remote_retrieve_body($response), true);
	$text = $body['choices'][0]['message']['content'] ?? '';
	return is_string($text) ? trim($text) : '';
}

/**
 * Run a callback with the site language forced (metrics and currency follow the language).
 *
 * @param string   $lang     en|ja.
 * @param callable $callback Callback.
 * @return mixed
 */
function antiques_marketplace_with_lang( $lang, callable $callback ) {
	$previous = $GLOBALS['antiques_marketplace_forced_lang'] ?? null;
	$GLOBALS['antiques_marketplace_forced_lang'] = $lang;
	try {
		return $callback();
	} finally {
		$GLOBALS['antiques_marketplace_forced_lang'] = $previous;
	}
}

/**
 * Cron job: rebuild every summary and store it.
 */
function antiques_marketplace_dashboard_summary_refresh() {
	if (function_exists('set_time_limit')) {
		set_time_limit(300); // Up to 6 model calls.
	}
	$items = array();
	foreach (array('en', 'ja') as $lang) {
		antiques_marketplace_with_lang(
			$lang,
			function () use ($lang, &$items) {
				$metrics = antiques_marketplace_get_dashboard_metrics();
				if (!empty($metrics['error'])) {
					return;
				}
				foreach (antiques_marketplace_dashboard_ranges() as $range) {
					$facts = antiques_marketplace_dashboard_summary_facts($metrics, $range);
					$text  = antiques_marketplace_dashboard_summary_from_model($facts, $lang);
					$items[ $lang ][ $range ] = array(
						'text'   => '' !== $text ? $text : antiques_marketplace_dashboard_summary_from_facts($facts, $lang),
						'source' => '' !== $text ? 'model' : 'facts',
					);
				}
			}
		);
	}
	if (!$items) {
		return; // DB unavailable: keep the last good summary.
	}
	update_option(
		ANTIQUES_DASHBOARD_SUMMARY_OPTION,
		array(
			'generated_at' => time(),
			'items'        => $items,
		),
		false
	);
}
add_action(ANTIQUES_DASHBOARD_SUMMARY_HOOK, 'antiques_marketplace_dashboard_summary_refresh');

/**
 * Keep the daily job scheduled (runs at 06:00 Japan time).
 */
function antiques_marketplace_dashboard_summary_schedule() {
	if (wp_next_scheduled(ANTIQUES_DASHBOARD_SUMMARY_HOOK)) {
		return;
	}
	$next = new DateTimeImmutable('tomorrow 06:00', new DateTimeZone('Asia/Tokyo'));
	wp_schedule_event($next->getTimestamp(), 'daily', ANTIQUES_DASHBOARD_SUMMARY_HOOK);
}
add_action('init', 'antiques_marketplace_dashboard_summary_schedule');

add_action('switch_theme', function () {
	wp_clear_scheduled_hook(ANTIQUES_DASHBOARD_SUMMARY_HOOK);
	wp_clear_scheduled_hook(ANTIQUES_DASHBOARD_SUMMARY_HOOK . '_once');
});

/**
 * Stored summaries for the active language, plus when they were written.
 * Before the first cron run, returns numbers-only text and queues a run.
 *
 * @param array<string, mixed> $metrics Metrics for the active language.
 * @return array{generated_at:int, items:array<string, string>}
 */
function antiques_marketplace_dashboard_summary( array $metrics ) {
	$lang   = antiques_marketplace_get_lang();
	$stored = get_option(ANTIQUES_DASHBOARD_SUMMARY_OPTION);
	$items  = array();

	if (is_array($stored) && !empty($stored['items'][ $lang ])) {
		foreach (antiques_marketplace_dashboard_ranges() as $range) {
			$items[ $range ] = (string) ( $stored['items'][ $lang ][ $range ]['text'] ?? '' );
		}
		return array(
			'generated_at' => (int) $stored['generated_at'],
			'items'        => $items,
		);
	}

	foreach (antiques_marketplace_dashboard_ranges() as $range) {
		$items[ $range ] = antiques_marketplace_dashboard_summary_from_facts(antiques_marketplace_dashboard_summary_facts($metrics, $range), $lang);
	}
	if (!wp_next_scheduled(ANTIQUES_DASHBOARD_SUMMARY_HOOK . '_once')) {
		wp_schedule_single_event(time(), ANTIQUES_DASHBOARD_SUMMARY_HOOK . '_once');
	}
	return array(
		'generated_at' => time(),
		'items'        => $items,
	);
}
add_action(ANTIQUES_DASHBOARD_SUMMARY_HOOK . '_once', 'antiques_marketplace_dashboard_summary_refresh');

/**
 * Summary text to safe HTML: "• " / "・" / "- " lines become a list, the rest paragraphs.
 *
 * @param string $text Summary text.
 * @return string
 */
function antiques_marketplace_dashboard_summary_html( $text ) {
	$html  = '';
	$items = array();
	foreach (preg_split('/\R/u', (string) $text) as $line) {
		$line = trim($line);
		if ('' === $line) {
			continue;
		}
		if (preg_match('/^(?:•|・|-|\*)\s*(.+)$/u', $line, $m)) {
			$items[] = '<li>' . esc_html($m[1]) . '</li>';
			continue;
		}
		if ($items) {
			$html .= '<ul>' . implode('', $items) . '</ul>';
			$items = array();
		}
		$html .= '<p>' . esc_html($line) . '</p>';
	}
	if ($items) {
		$html .= '<ul>' . implode('', $items) . '</ul>';
	}
	return $html;
}
