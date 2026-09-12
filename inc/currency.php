<?php
/**
 * EUR source prices converted to the active UI currency.
 *
 * Catawiki stores hammer prices in EUR. English UI shows USD; Japanese UI shows JPY.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Display currency for the active language.
 *
 * @return string USD or JPY
 */
function antiques_marketplace_display_currency() {
	return 'ja' === antiques_marketplace_get_lang() ? 'JPY' : 'USD';
}

/**
 * Live EUR rates for USD and JPY (ECB via Frankfurter, cached).
 *
 * @return array{base:string,date:string,usd:float,jpy:float,source:string}
 */
function antiques_marketplace_exchange_rates() {
	$cached = get_transient('antiques_marketplace_fx_eur');
	if (is_array($cached) && isset($cached['usd'], $cached['jpy'])) {
		return $cached;
	}

	$fallback = array(
		'base'   => 'EUR',
		'date'   => gmdate('Y-m-d'),
		'usd'    => 1.16,
		'jpy'    => 178.0,
		'source' => 'fallback',
	);

	$response = wp_remote_get(
		'https://api.frankfurter.dev/v1/latest?from=EUR&to=USD,JPY',
		array(
			'timeout' => 6,
		)
	);

	if (is_wp_error($response)) {
		return $fallback;
	}

	$body = json_decode((string) wp_remote_retrieve_body($response), true);
	if (!is_array($body) || empty($body['rates']['USD']) || empty($body['rates']['JPY'])) {
		return $fallback;
	}

	$rates = array(
		'base'   => 'EUR',
		'date'   => isset($body['date']) ? (string) $body['date'] : gmdate('Y-m-d'),
		'usd'    => (float) $body['rates']['USD'],
		'jpy'    => (float) $body['rates']['JPY'],
		'source' => 'ECB',
	);

	set_transient('antiques_marketplace_fx_eur', $rates, 6 * HOUR_IN_SECONDS);
	return $rates;
}

/**
 * EUR → display-currency multiplier.
 *
 * @param string|null $currency USD or JPY.
 * @return float
 */
function antiques_marketplace_eur_to_display_rate( $currency = null ) {
	$currency = $currency ? strtoupper((string) $currency) : antiques_marketplace_display_currency();
	$rates    = antiques_marketplace_exchange_rates();
	return 'JPY' === $currency ? (float) $rates['jpy'] : (float) $rates['usd'];
}

/**
 * Convert a EUR catalog amount to the active display currency.
 *
 * @param float $eur_amount Amount in EUR.
 * @return float
 */
function antiques_marketplace_convert_eur_to_display( $eur_amount ) {
	return (float) $eur_amount * antiques_marketplace_eur_to_display_rate();
}

/**
 * Convert a display-currency filter amount back to EUR for SQL.
 *
 * @param float $display_amount Amount typed by the user.
 * @return float
 */
function antiques_marketplace_convert_display_to_eur( $display_amount ) {
	$rate = antiques_marketplace_eur_to_display_rate();
	if ($rate <= 0) {
		return (float) $display_amount;
	}
	return (float) $display_amount / $rate;
}

/**
 * Format a EUR catalog price in the active UI currency.
 *
 * @param float|int|string|null $eur_amount Catalog amount.
 * @return string|null
 */
function antiques_marketplace_format_display_price( $eur_amount ) {
	if (!is_numeric($eur_amount)) {
		return null;
	}

	$amount   = antiques_marketplace_convert_eur_to_display((float) $eur_amount);
	$currency = antiques_marketplace_display_currency();

	if ('JPY' === $currency) {
		return '¥' . number_format_i18n(round($amount), 0);
	}

	return '$' . number_format_i18n($amount, 2);
}

/**
 * Format a EUR amount with a currency code (dashboard tables).
 *
 * @param float  $eur_amount Catalog amount.
 * @param string $currency   USD or JPY.
 * @return string
 */
function antiques_marketplace_format_currency_amount( $eur_amount, $currency = null ) {
	$currency = $currency ? strtoupper((string) $currency) : antiques_marketplace_display_currency();
	$rate     = antiques_marketplace_eur_to_display_rate($currency);
	$amount   = (float) $eur_amount * $rate;
	if ('JPY' === $currency) {
		return '¥' . number_format_i18n(round($amount), 0);
	}
	return '$' . number_format_i18n($amount, 2);
}

/**
 * Human-readable FX caption for the active language.
 *
 * @return string
 */
function antiques_marketplace_exchange_rate_caption() {
	$rates    = antiques_marketplace_exchange_rates();
	$currency = antiques_marketplace_display_currency();
	$rate     = antiques_marketplace_eur_to_display_rate($currency);
	$date     = $rates['date'];
	$source   = 'ECB' === $rates['source'] ? __('ECB', 'antiques-marketplace') : __('cached fallback', 'antiques-marketplace');

	if ('JPY' === $currency) {
		return sprintf(
			__('Prices converted from EUR. 1 EUR = %1$s JPY (%2$s, %3$s).', 'antiques-marketplace'),
			number_format_i18n($rate, 2),
			$source,
			$date
		);
	}

	return sprintf(
		__('Prices converted from EUR. 1 EUR = %1$s USD (%2$s, %3$s).', 'antiques-marketplace'),
		number_format_i18n($rate, 4),
		$source,
		$date
	);
}
