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
			$title['title'] = antiques_marketplace_translate_for_locale((string) $result['product']['product_title']);
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
