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
	$map = array(
		'Account'                         => 'アカウント',
		'Subscribe'                       => '購読',
		'Log out'                         => 'ログアウト',
		'Log in'                          => 'ログイン',
		'Register'                        => '新規登録',
		'Home'                            => 'ホーム',
		'Bid, buy, and discover rare antiques' => '希少な骨董品を入札・購入・発見',
		'A marketplace-style homepage inspired by eBay. Publish listings as posts and start selling.' => 'eBay風のマーケットプレイス。出品して販売を始めましょう。',
		'Filter listings'                 => '出品を絞り込む',
		'Keywords'                        => 'キーワード',
		'Min price'                       => '最低価格',
		'Max price'                       => '最高価格',
		'Sort by'                         => '並び替え',
		'Newest'                          => '新着順',
		'Price: Low to High'              => '価格：安い順',
		'Price: High to Low'              => '価格：高い順',
		'Ending soon'                     => '終了間近',
		'Apply filters'                   => 'フィルターを適用',
		'Clear filters'                   => 'フィルターをクリア',
		'Listing status'                  => '出品ステータス',
		'All listings'                    => 'すべて',
		'Active auctions'                 => '開催中',
		'Ended auctions'                  => '終了済み',
		'Ends within'                     => '終了まで',
		'Any time'                        => '指定なし',
		'24 hours'                        => '24時間以内',
		'3 days'                          => '3日以内',
		'7 days'                          => '7日以内',
		'Category'                        => 'カテゴリー',
		'All categories'                  => 'すべてのカテゴリー',
		'Only with price'                 => '価格ありのみ',
		'Per page'                        => '表示件数',
		'Featured listings'             => '注目の出品',
		'Showing %1$d-%2$d of %3$d products' => '%3$d件中 %1$d–%2$d件を表示',
		'CURRENT BID'                     => '現在の入札',
		'Bid not available'               => '入札情報なし',
		'No external products found.'     => '商品が見つかりませんでした。',
		'Database error: %s'              => 'データベースエラー: %s',
		'Connected to table: %s'          => '接続テーブル: %s',
		'Product pagination'              => 'ページ送り',
		'Prev'                            => '前へ',
		'Next'                            => '次へ',
		'Time not available'              => '時間情報なし',
		'Ended'                           => '終了',
		'%1$d day %2$d hr left'           => '残り %1$d日 %2$d時間',
		'%1$d days %2$d hrs left'         => '残り %1$d日 %2$d時間',
		'%1$d hrs %2$d mins left'       => '残り %1$d時間 %2$d分',
		'%d mins left'                    => '残り %d分',
		'English'                         => 'English',
		'Japanese'                        => '日本語',
		'Language'                        => '言語',
		'Create account'                  => 'アカウント作成',
		'Remember me'                     => 'ログイン状態を保持',
		'Lost your password?'             => 'パスワードをお忘れですか？',
		'Already have an account? Log in' => 'アカウントをお持ちですか？ログイン',
		'Username'                        => 'ユーザー名',
		'Email'                           => 'メールアドレス',
		'Password'                        => 'パスワード',
		'Confirm password'                => 'パスワード（確認）',
		'Pay %s with Stripe'              => 'Stripeで %s を支払う',
		'Browse listings'                 => '出品を見る',
		'Refine your search'              => '検索条件を絞り込む',
		'Search'                          => '検索',
		'Auction'                         => 'オークション',
		'Price'                           => '価格',
		'Display'                         => '表示',
		'Search titles…'                  => 'タイトルで検索…',
	);
	return $map;
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
	$map = antiques_marketplace_ja_translations();
	return isset($map[$text]) ? $map[$text] : $translated;
}
add_filter('gettext', 'antiques_marketplace_gettext_ja', 10, 3);

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
	$map = antiques_marketplace_ja_translations();
	$key = (1 === (int) $number) ? $single : $plural;
	if (isset($map[$key])) {
		return $map[$key];
	}
	if (isset($map[$single])) {
		return $map[$single];
	}
	return $translated;
}
add_filter('ngettext', 'antiques_marketplace_ngettext_ja', 10, 5);
