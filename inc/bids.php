<?php
/**
 * Auction bid history and last-sold buyer country.
 *
 * bidding_history JSON from Catawiki includes bidderName and country.code.
 * The first array element is the winning / highest bid.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Decode bidding_history JSON into a list of bids (highest first).
 *
 * @param mixed $raw JSON string or array.
 * @return array<int, array{bidder_name:string,country_code:string,amount:float,created_at:string,bid_type:string}>
 */
function antiques_marketplace_parse_bid_history( $raw ) {
	$decoded = $raw;
	if (is_string($raw) && '' !== trim($raw)) {
		$decoded = json_decode($raw, true);
	}
	if (!is_array($decoded)) {
		return array();
	}

	$bids = array();
	foreach ($decoded as $row) {
		if (!is_array($row)) {
			continue;
		}
		$country = '';
		if (isset($row['country']['code'])) {
			$country = strtolower((string) $row['country']['code']);
		} elseif (isset($row['country']) && is_string($row['country'])) {
			$country = strtolower((string) $row['country']);
		}

		$bids[] = array(
			'bidder_name'  => isset($row['bidderName']) ? (string) $row['bidderName'] : '',
			'country_code' => $country,
			'amount'       => isset($row['localizedBidAmount']) ? (float) $row['localizedBidAmount'] : 0.0,
			'created_at'   => isset($row['createdAt']) ? (string) $row['createdAt'] : '',
			'bid_type'     => isset($row['bidType']) ? (string) $row['bidType'] : '',
		);
	}

	return $bids;
}

/**
 * Winning (last) bid from history.
 *
 * @param mixed $raw bidding_history payload.
 * @return array{bidder_name:string,country_code:string,amount:float,created_at:string,bid_type:string}|null
 */
function antiques_marketplace_winning_bid( $raw ) {
	$bids = antiques_marketplace_parse_bid_history($raw);
	if (empty($bids)) {
		return null;
	}
	return $bids[0];
}

/**
 * English country name from ISO 3166-1 alpha-2.
 *
 * @param string $code Country code.
 * @return string
 */
function antiques_marketplace_country_name( $code ) {
	$code = strtolower(trim((string) $code));
	if ('' === $code) {
		return '';
	}

	if (function_exists('locale_get_display_region')) {
		$name = locale_get_display_region('-' . strtoupper($code), 'ja' === antiques_marketplace_get_lang() ? 'ja' : 'en');
		if (is_string($name) && '' !== $name && '-' . strtoupper($code) !== $name) {
			return $name;
		}
	}

	$map = array(
		'nl' => 'Netherlands',
		'fr' => 'France',
		'de' => 'Germany',
		'be' => 'Belgium',
		'it' => 'Italy',
		'es' => 'Spain',
		'pt' => 'Portugal',
		'gb' => 'United Kingdom',
		'uk' => 'United Kingdom',
		'us' => 'United States',
		'jp' => 'Japan',
		'cn' => 'China',
		'hk' => 'Hong Kong',
		'tw' => 'Taiwan',
		'kr' => 'South Korea',
		'sg' => 'Singapore',
		'au' => 'Australia',
		'ca' => 'Canada',
		'ch' => 'Switzerland',
		'at' => 'Austria',
		'se' => 'Sweden',
		'no' => 'Norway',
		'dk' => 'Denmark',
		'fi' => 'Finland',
		'ie' => 'Ireland',
		'pl' => 'Poland',
		'cz' => 'Czechia',
		'hu' => 'Hungary',
		'ro' => 'Romania',
		'gr' => 'Greece',
		'ru' => 'Russia',
		'in' => 'India',
		'br' => 'Brazil',
		'mx' => 'Mexico',
		'ae' => 'United Arab Emirates',
	);

	$english = isset($map[ $code ]) ? $map[ $code ] : strtoupper($code);
	return antiques_marketplace_translate_for_locale($english);
}

/**
 * Whether source listing links should render.
 *
 * @return bool
 */
function antiques_marketplace_show_source_listing() {
	return false;
}
