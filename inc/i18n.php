<?php
/**
 * English / Japanese language switcher (cookie + gettext).
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Active UI language code.
 *
 * @return string "en" or "ja"
 */
function antiques_marketplace_get_lang() {
	if (isset($_COOKIE['antiques_lang']) && in_array($_COOKIE['antiques_lang'], array('en', 'ja'), true)) {
		return (string) $_COOKIE['antiques_lang'];
	}
	return 'en';
}

/**
 * Persist language preference.
 *
 * @param string $lang Language code.
 */
function antiques_marketplace_set_lang_cookie( $lang ) {
	if (!in_array($lang, array('en', 'ja'), true)) {
		return;
	}
	$path   = defined('COOKIEPATH') ? COOKIEPATH : '/';
	$domain = defined('COOKIEDOMAIN') ? COOKIEDOMAIN : '';
	setcookie('antiques_lang', $lang, time() + YEAR_IN_SECONDS, $path, $domain, is_ssl(), true);
	$_COOKIE['antiques_lang'] = $lang;
}

/**
 * URL that switches language while keeping current query args.
 *
 * @param string $lang Target language.
 * @return string
 */
function antiques_marketplace_lang_switch_url( $lang ) {
	$target = remove_query_arg('lang');
	if (isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
		$uri    = wp_unslash($_SERVER['REQUEST_URI']);
		$try    = $scheme . '://' . $host . $uri;
		$target = wp_validate_redirect($try, home_url('/'));
	}
	return add_query_arg('lang', $lang, $target);
}

/**
 * Handle ?lang=en|ja and set cookie.
 */
function antiques_marketplace_handle_lang_switch() {
	if (!isset($_GET['lang'])) {
		return;
	}
	$lang = sanitize_text_field(wp_unslash($_GET['lang']));
	if (!in_array($lang, array('en', 'ja'), true)) {
		return;
	}
	antiques_marketplace_set_lang_cookie($lang);
	$redirect = remove_query_arg('lang');
	if (!$redirect) {
		$redirect = home_url('/');
	}
	wp_safe_redirect($redirect);
	exit;
}
add_action('init', 'antiques_marketplace_handle_lang_switch', 1);

/**
 * WordPress locale for translations.
 *
 * @param string $locale Current locale.
 * @return string
 */
function antiques_marketplace_filter_locale( $locale ) {
	return 'ja' === antiques_marketplace_get_lang() ? 'ja' : 'en_US';
}
add_filter('locale', 'antiques_marketplace_filter_locale');

/**
 * Japanese string map for theme text domain.
 *
 * @return array<string, string>
 */
function antiques_marketplace_ja_translations() {
	static $map = null;
	if (null !== $map) {
		return $map;
	}
	$file = get_template_directory() . '/inc/translations/ja.php';
	$map  = is_readable($file) ? require $file : array();
	if (!is_array($map)) {
		$map = array();
	}
	$legal_file = get_template_directory() . '/inc/translations/ja-legal.php';
	if (is_readable($legal_file)) {
		$legal = require $legal_file;
		if (is_array($legal)) {
			$map = array_merge($map, $legal);
		}
	}
	return $map;
}

/**
 * Translate a string for the active language.
 *
 * @param string $text English msgid.
 * @return string
 */
function antiques_marketplace_t( $text ) {
	$text = (string) $text;
	if ('ja' !== antiques_marketplace_get_lang()) {
		return $text;
	}
	$map = antiques_marketplace_ja_translations();
	if (isset($map[ $text ])) {
		return $map[ $text ];
	}
	// Fallback: translate any remaining English UI string (cached via translate API).
	if (preg_match('/[A-Za-z]/', $text) && strlen($text) <= 500) {
		return antiques_marketplace_translate_text($text, 'ja');
	}
	return $text;
}

/**
 * Echo escaped translated string.
 *
 * @param string $text English msgid.
 */
function antiques_marketplace_te( $text ) {
	echo esc_html(antiques_marketplace_t($text));
}

/**
 * Apply Japanese translations when locale is ja.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function antiques_marketplace_gettext_ja( $translated, $text, $domain ) {
	if ('antiques-marketplace' !== $domain || 'ja' !== antiques_marketplace_get_lang()) {
		return $translated;
	}
	return antiques_marketplace_t($text);
}
add_filter('gettext', 'antiques_marketplace_gettext_ja', 20, 3);

/**
 * Plural forms for Japanese.
 *
 * @param string $translated Translated text.
 * @param string $single     Singular.
 * @param string $plural     Plural.
 * @param int    $number     Count.
 * @param string $domain     Text domain.
 * @return string
 */
function antiques_marketplace_ngettext_ja( $translated, $single, $plural, $number, $domain ) {
	if ('antiques-marketplace' !== $domain || 'ja' !== antiques_marketplace_get_lang()) {
		return $translated;
	}
	$key = (1 === (int) $number) ? $single : $plural;
	$try = antiques_marketplace_t($key);
	if ($try !== $key) {
		return $try;
	}
	return antiques_marketplace_t($single);
}
add_filter('ngettext', 'antiques_marketplace_ngettext_ja', 20, 5);

/**
 * Load legal page body template for current language.
 *
 * @param string $page One of privacy, terms, commerce.
 */
function antiques_marketplace_render_legal_body( $page ) {
	$lang = 'ja' === antiques_marketplace_get_lang() ? 'ja' : 'en';
	$slug = sanitize_key($page);
	get_template_part('template-parts/legal/' . $slug, $lang);
}

/**
 * English msgids for WordPress page slugs (browser title, etc.).
 *
 * @return array<string, string>
 */
function antiques_marketplace_page_title_msgids() {
	return array(
		'login'               => 'Log in',
		'register'            => 'Create account',
		'subscribe'           => 'Subscribe',
		'privacy-policy'      => 'Privacy Policy',
		'terms-of-service'    => 'Terms of Service',
		'commerce-disclosure' => 'Commerce disclosure',
		'market-trends'       => 'Trend Dashboard',
	);
}

/**
 * Localized title for a theme page slug.
 *
 * @param string $slug Page slug.
 * @return string
 */
function antiques_marketplace_page_title( $slug ) {
	$map = antiques_marketplace_page_title_msgids();
	$msgid = isset($map[ $slug ]) ? $map[ $slug ] : '';
	if ('' === $msgid) {
		return '';
	}
	return antiques_marketplace_t($msgid);
}

/**
 * Translate document title parts for theme pages and products.
 *
 * @param array<string, string> $title Title parts.
 * @return array<string, string>
 */
function antiques_marketplace_filter_document_title( $title ) {
	$product_id = absint(get_query_var('antiques_product_id'));
	if ($product_id > 0) {
		$result = antiques_marketplace_get_external_product_by_id($product_id);
		if (!empty($result['product']['product_title'])) {
			$raw = (string) $result['product']['product_title'];
			$title['title'] = antiques_marketplace_is_latin_text($raw)
				? antiques_marketplace_normalize_latin_punctuation($raw)
				: $raw;
		}
		return $title;
	}

	if (!is_page()) {
		return $title;
	}

	$post = get_queried_object();
	if (!$post instanceof WP_Post) {
		return $title;
	}

	$localized = antiques_marketplace_page_title($post->post_name);
	if ('' !== $localized) {
		$title['title'] = $localized;
	}

	return $title;
}
add_filter('document_title_parts', 'antiques_marketplace_filter_document_title', 20);

/**
 * Localize visible page titles when templates use the_title().
 *
 * @param string $title Post title.
 * @return string
 */
function antiques_marketplace_filter_the_title( $title ) {
	if (!is_page() || is_admin()) {
		return $title;
	}

	$post = get_queried_object();
	if (!$post instanceof WP_Post) {
		return $title;
	}

	$localized = antiques_marketplace_page_title($post->post_name);
	return '' !== $localized ? $localized : $title;
}
add_filter('the_title', 'antiques_marketplace_filter_the_title', 20);

/**
 * Count Latin letters in text.
 *
 * @param string $text Source text.
 * @return int
 */
function antiques_marketplace_latin_letter_count( $text ) {
	return (int) preg_match_all('/[A-Za-z]/', (string) $text);
}

/**
 * Count CJK characters in text.
 *
 * @param string $text Source text.
 * @return int
 */
function antiques_marketplace_cjk_char_count( $text ) {
	return (int) preg_match_all('/[\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}]/u', (string) $text);
}

/**
 * Whether product copy should be translated for the active UI language.
 *
 * Catawiki lots often embed Japanese signatures inside English prose. Presence of
 * any CJK must not skip translating the surrounding English for JA UI.
 *
 * @param string      $text Source text.
 * @param string|null $lang UI language.
 * @return bool
 */
function antiques_marketplace_text_needs_ui_translate( $text, $lang = null ) {
	$text = trim((string) $text);
	if ('' === $text) {
		return false;
	}

	if (null === $lang) {
		$lang = antiques_marketplace_get_lang();
	}
	$lang = 'ja' === $lang ? 'ja' : 'en';

	$latin = antiques_marketplace_latin_letter_count($text);
	$cjk   = antiques_marketplace_cjk_char_count($text);

	if ('ja' === $lang) {
		// Long English seller blurbs / item descriptions (even with mei in JP).
		// Skip when text is already mostly Japanese with only brand/loanword Latin.
		if ($latin >= 40 && (0 === $cjk || $latin > (int) ( $cjk * 0.25 ))) {
			return true;
		}
		// Short Latin-only labels/values.
		if (0 === $cjk && $latin >= 3) {
			return true;
		}
		return false;
	}

	// English UI: translate predominantly Japanese copy.
	if ($cjk >= 8 && $latin < 48) {
		return true;
	}
	if ($cjk > 0 && $latin < 12) {
		return true;
	}
	return false;
}

/**
 * Replace common English catalog section headers after machine translation.
 *
 * @param string $text Source or translated text.
 * @param string $lang UI language.
 * @return string
 */
function antiques_marketplace_localize_catalog_section_headers( $text, $lang ) {
	if ('ja' !== $lang || '' === trim((string) $text)) {
		return $text;
	}
	$map = array(
		'ABOUT US:'          => '私たちについて:',
		'ABOUT US'           => '私たちについて',
		'ITEM DESCRIPTION:'  => '商品説明:',
		'ITEM DESCRIPTION'   => '商品説明',
		'DESCRIPTION:'       => '説明:',
		'Description:'       => '説明:',
	);
	return str_ireplace(array_keys($map), array_values($map), (string) $text);
}

/**
 * Translate many strings, packing short ones into fewer HTTP calls.
 *
 * @param array<string, string> $texts       Source map.
 * @param string                $target_lang Target language.
 * @param int                   $timeout     Timeout seconds.
 * @return array<string, string>
 */
function antiques_marketplace_translate_many_packed( array $texts, $target_lang, $timeout = 5 ) {
	$sep     = "\n⟦ADJT⟧\n";
	$long    = array();
	$short   = array();
	$results = array();

	foreach ($texts as $key => $text) {
		$text = trim((string) $text);
		if (strlen($text) > 140) {
			$long[ $key ] = $text;
		} else {
			$short[ $key ] = $text;
		}
	}

	$packs      = array();
	$pack_map   = array();
	$current    = array();
	$current_len = 0;
	$pack_index = 0;

	foreach ($short as $key => $text) {
		$extra = strlen($text) + (empty($current) ? 0 : strlen($sep));
		if (!empty($current) && ($current_len + $extra) > 420) {
			$packs[ 'pack_' . $pack_index ] = implode($sep, $current);
			$current                         = array();
			$current_len                     = 0;
			$pack_index++;
		}
		$pack_map[ $key ] = array(
			'pack'  => 'pack_' . $pack_index,
			'index' => count($current),
		);
		$current[]    = $text;
		$current_len += $extra;
	}
	if (!empty($current)) {
		$packs[ 'pack_' . $pack_index ] = implode($sep, $current);
	}

	$batch_in  = $long + $packs;
	$batch_out = empty($batch_in) ? array() : antiques_marketplace_translate_many($batch_in, $target_lang, $timeout);

	foreach ($long as $key => $text) {
		$results[ $key ] = isset($batch_out[ $key ]) ? (string) $batch_out[ $key ] : $text;
	}

	foreach ($pack_map as $key => $meta) {
		$pack_key = $meta['pack'];
		$packed   = isset($batch_out[ $pack_key ]) ? (string) $batch_out[ $pack_key ] : '';
		$parts    = '' === $packed ? array() : explode($sep, $packed);
		if (isset($parts[ $meta['index'] ]) && '' !== trim($parts[ $meta['index'] ])) {
			$results[ $key ] = trim($parts[ $meta['index'] ]);
		} else {
			$results[ $key ] = $short[ $key ];
		}
	}

	return $results;
}

/**
 * Build async-translated product copy payload (cached).
 *
 * @param array<string, mixed> $product Product row.
 * @param string               $lang    Target UI language.
 * @param string               $mode    "title" (fast) or "full".
 * @return array<string, mixed>
 */
function antiques_marketplace_build_product_translate_payload( $product, $lang, $mode = 'full' ) {
	$lang        = 'ja' === $lang ? 'ja' : 'en';
	$mode        = 'title' === $mode ? 'title' : 'full';
	$raw_title   = isset($product['product_title']) ? (string) $product['product_title'] : '';
	$title       = antiques_marketplace_is_latin_text($raw_title)
		? antiques_marketplace_normalize_latin_punctuation($raw_title)
		: $raw_title;
	$description = isset($product['product_description']) ? (string) $product['product_description'] : '';
	$detail      = isset($product['product_detail']) ? (string) $product['product_detail'] : '';
	$overview    = trim($detail) !== '' ? $detail : $description;

	$details = antiques_marketplace_parse_product_details($description);
	$details = array_values(
		array_filter(
			$details,
			static function( $item ) {
				return strlen((string) $item['value']) <= 140;
			}
		)
	);
	$details = array_slice($details, 0, 24);

	$fingerprint = md5($mode . '|' . $lang . '|' . $title . '|' . $overview . '|' . $description . '|' . wp_json_encode($details));
	$cache_key   = 'ant_product_tx_v7_' . (isset($product['product_id']) ? (int) $product['product_id'] : 0) . '_' . $fingerprint;
	$cached      = get_transient($cache_key);
	if (is_array($cached) && isset($cached['title'])) {
		return $cached;
	}

	if ('title' === $mode) {
		$jobs = array();
		if (antiques_marketplace_text_needs_ui_translate($title, $lang)) {
			$jobs['title'] = $title;
		}
		$translated = !empty($jobs) ? antiques_marketplace_translate_many($jobs, $lang, 5) : array();
		$translated_title = isset($translated['title']) ? (string) $translated['title'] : $title;
		$payload          = array(
			'changed' => $translated_title !== $title,
			'mode'    => 'title',
			'title'   => $translated_title,
		);
		if (antiques_marketplace_translate_jobs_complete($jobs, $translated)) {
			set_transient($cache_key, $payload, DAY_IN_SECONDS * 7);
		}
		return $payload;
	}

	$jobs = array();
	if (antiques_marketplace_text_needs_ui_translate($title, $lang)) {
		$jobs['title'] = $title;
	}
	if (antiques_marketplace_text_needs_ui_translate($overview, $lang)) {
		$jobs['overview'] = $overview;
	}
	if ($description !== $overview && antiques_marketplace_text_needs_ui_translate($description, $lang)) {
		$jobs['description'] = $description;
	}

	foreach ($details as $index => $item) {
		$label = (string) $item['label'];
		$value = (string) $item['value'];
		if (antiques_marketplace_text_needs_ui_translate($label, $lang)) {
			$jobs[ 'label_' . $index ] = $label;
		}
		if (antiques_marketplace_text_needs_ui_translate($value, $lang)) {
			$jobs[ 'value_' . $index ] = $value;
		}
	}

	if (empty($jobs)) {
		$payload = array(
			'changed'          => false,
			'mode'             => 'full',
			'title'            => $title,
			'details_overview' => $overview,
			'description'      => $description,
			'details'          => $details,
		);
		set_transient($cache_key, $payload, DAY_IN_SECONDS);
		return $payload;
	}

	$translated = antiques_marketplace_translate_many_packed($jobs, $lang, 5);

	$translated_title = isset($translated['title']) ? (string) $translated['title'] : $title;
	$translated_overview = isset($translated['overview']) ? (string) $translated['overview'] : $overview;
	if (isset($translated['description'])) {
		$translated_description = (string) $translated['description'];
	} elseif ($description === $overview) {
		$translated_description = $translated_overview;
	} else {
		$translated_description = $description;
	}

	$translated_details = array();
	foreach ($details as $index => $item) {
		$label_key = 'label_' . $index;
		$value_key = 'value_' . $index;
		$translated_details[] = array(
			'label' => isset($translated[ $label_key ]) ? (string) $translated[ $label_key ] : (string) $item['label'],
			'value' => isset($translated[ $value_key ]) ? (string) $translated[ $value_key ] : (string) $item['value'],
		);
	}

	$payload = array(
		'changed'          => true,
		'mode'             => 'full',
		'title'            => antiques_marketplace_localize_catalog_section_headers($translated_title, $lang),
		'details_overview' => antiques_marketplace_localize_catalog_section_headers($translated_overview, $lang),
		'description'      => antiques_marketplace_localize_catalog_section_headers($translated_description, $lang),
		'details'          => $translated_details,
	);
	if (antiques_marketplace_translate_jobs_complete($jobs, $translated)) {
		set_transient($cache_key, $payload, DAY_IN_SECONDS * 7);
	}
	return $payload;
}

/**
 * Whether every translate job came back translated (not the source fallback).
 *
 * Translate APIs fall back to the source text when rate-limited; caching that
 * payload would pin the untranslated copy for a week.
 *
 * @param array<string, string> $jobs       Source texts.
 * @param array<string, string> $translated Results keyed like $jobs.
 * @return bool
 */
function antiques_marketplace_translate_jobs_complete( array $jobs, array $translated ) {
	foreach ($jobs as $key => $source) {
		if (!isset($translated[ $key ]) || trim((string) $translated[ $key ]) === trim((string) $source)) {
			return false;
		}
	}
	return true;
}

/**
 * AJAX: translate product title / details / description after page load.
 */
function antiques_marketplace_ajax_translate_product() {
	if (!antiques_marketplace_user_has_membership() && !current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Forbidden'), 403);
	}

	check_ajax_referer('antiques_translate_product', 'nonce');

	$product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
	$lang       = isset($_POST['lang']) ? sanitize_text_field(wp_unslash($_POST['lang'])) : antiques_marketplace_get_lang();
	$lang       = 'ja' === $lang ? 'ja' : 'en';
	$mode       = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'full';
	$mode       = 'title' === $mode ? 'title' : 'full';

	if ($product_id <= 0) {
		wp_send_json_error(array('message' => 'Invalid product'), 400);
	}

	$result = antiques_marketplace_get_external_product_by_id($product_id);
	if (empty($result['product']) || !is_array($result['product'])) {
		wp_send_json_error(array('message' => 'Not found'), 404);
	}

	$payload = antiques_marketplace_build_product_translate_payload($result['product'], $lang, $mode);
	wp_send_json_success($payload);
}
add_action('wp_ajax_antiques_translate_product', 'antiques_marketplace_ajax_translate_product');
add_action('wp_ajax_nopriv_antiques_translate_product', 'antiques_marketplace_ajax_translate_product');
