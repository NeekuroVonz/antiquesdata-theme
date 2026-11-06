<?php
/**
 * SEO head tags: description, canonical + hreflang (EN/JA), Open Graph, JSON-LD,
 * robots rules and a sitemap limited to public pages.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Whether search engines should index this request (only public, non-auth pages).
 *
 * @return bool
 */
function antiques_marketplace_seo_indexable() {
	if (absint(get_query_var('antiques_product_id')) > 0) {
		return false; // Product pages sit behind the membership gate.
	}
	if (is_front_page()) {
		return true;
	}
	if (!is_page()) {
		return false;
	}
	$slug = (string) get_post_field('post_name', get_queried_object_id());
	return in_array($slug, array('subscribe', 'terms-of-service', 'privacy-policy', 'commerce-disclosure'), true);
}

/**
 * Current URL without query args, with ?lang= set.
 *
 * @param string $lang en|ja.
 * @return string
 */
function antiques_marketplace_seo_lang_url( $lang ) {
	$base = is_front_page() ? home_url('/') : get_permalink(get_queried_object_id());
	return add_query_arg('lang', $lang, $base);
}

/**
 * Meta description for the current page, in the active language.
 *
 * @return string
 */
function antiques_marketplace_seo_description() {
	$descriptions = array(
		'front'               => 'Search overseas auction hammer prices for Japanese antiques in Japanese. Compare Sotheby’s, Christie’s, and Bonhams results with domestic prices. 7-day free trial.',
		'subscribe'           => 'One plan for overseas auction data on Japanese antiques: ¥5,000/month with a 7-day free trial. Search, compare in yen, and export CSV.',
		'privacy-policy'      => 'How Antique Data Japan collects, uses, and protects your personal information.',
		'terms-of-service'    => 'Terms of service for the Antique Data Japan overseas auction data service.',
		'commerce-disclosure' => 'Legal disclosure under the Act on Specified Commercial Transactions for Antique Data Japan.',
	);
	$key = is_front_page() ? 'front' : (string) get_post_field('post_name', get_queried_object_id());
	return isset($descriptions[ $key ]) ? antiques_marketplace_t($descriptions[ $key ]) : '';
}

/**
 * Print SEO tags in <head>.
 */
function antiques_marketplace_seo_head() {
	if (!antiques_marketplace_seo_indexable()) {
		return;
	}

	$lang        = antiques_marketplace_get_lang();
	$canonical   = antiques_marketplace_seo_lang_url($lang);
	$title       = wp_get_document_title();
	$description = antiques_marketplace_seo_description();
	$image       = antiques_marketplace_theme_asset('images/og-cover.jpg');

	echo "\n";
	if ('' !== $description) {
		printf("<meta name=\"description\" content=\"%s\">\n", esc_attr($description));
	}
	printf("<link rel=\"canonical\" href=\"%s\">\n", esc_url($canonical));
	printf("<link rel=\"alternate\" hreflang=\"ja\" href=\"%s\">\n", esc_url(antiques_marketplace_seo_lang_url('ja')));
	printf("<link rel=\"alternate\" hreflang=\"en\" href=\"%s\">\n", esc_url(antiques_marketplace_seo_lang_url('en')));
	printf("<link rel=\"alternate\" hreflang=\"x-default\" href=\"%s\">\n", esc_url(antiques_marketplace_seo_lang_url('en')));

	$og = array(
		'og:type'        => is_front_page() ? 'website' : 'article',
		'og:site_name'   => 'Antique Data Japan',
		'og:title'       => $title,
		'og:description' => $description,
		'og:url'         => $canonical,
		'og:image'       => $image,
		'og:locale'      => 'ja' === $lang ? 'ja_JP' : 'en_US',
	);
	foreach ($og as $property => $content) {
		if ('' !== $content) {
			printf("<meta property=\"%s\" content=\"%s\">\n", esc_attr($property), esc_attr($content));
		}
	}
	printf("<meta property=\"og:locale:alternate\" content=\"%s\">\n", 'ja' === $lang ? 'en_US' : 'ja_JP');
	echo "<meta property=\"og:image:width\" content=\"1200\">\n<meta property=\"og:image:height\" content=\"630\">\n";
	echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";

	if (is_front_page()) {
		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type' => 'Organization',
					'@id'   => home_url('/#organization'),
					'name'  => 'Antique Data Japan',
					'url'   => home_url('/'),
				),
				array(
					'@type'       => 'WebSite',
					'@id'         => home_url('/#website'),
					'name'        => 'Antique Data Japan',
					'url'         => home_url('/'),
					'inLanguage'  => array('ja', 'en'),
					'description' => $description,
					'publisher'   => array('@id' => home_url('/#organization')),
				),
			),
		);
		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
	}
}
add_action('wp_head', 'antiques_marketplace_seo_head', 2);
// Our canonical carries ?lang=, so drop the core one to avoid two conflicting tags.
remove_action('wp_head', 'rel_canonical');

/**
 * noindex everything that is gated, auth-only, or a catalog/product view.
 *
 * @param array<string, bool|string> $robots Robots directives.
 * @return array<string, bool|string>
 */
function antiques_marketplace_seo_robots( $robots ) {
	if (!antiques_marketplace_seo_indexable()) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter('wp_robots', 'antiques_marketplace_seo_robots');

/**
 * Localize the site tagline used in the front-page <title>.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function antiques_marketplace_seo_title_tagline( $parts ) {
	if (!empty($parts['tagline'])) {
		$parts['tagline'] = antiques_marketplace_t(html_entity_decode($parts['tagline'], ENT_QUOTES, 'UTF-8'));
	}
	return $parts;
}
add_filter('document_title_parts', 'antiques_marketplace_seo_title_tagline', 30);

/* Sitemap: only the public pages; gated pages would just redirect to login. */
add_filter('wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2);
add_filter('wp_sitemaps_taxonomies', '__return_empty_array');
add_filter('wp_sitemaps_post_types', function ( $post_types ) {
	return isset($post_types['page']) ? array('page' => $post_types['page']) : array();
});
add_filter('wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ('page' === $post_type) {
		$slugs = array('subscribe', 'terms-of-service', 'privacy-policy', 'commerce-disclosure');
		$front = (int) get_option('page_on_front');
		if ($front > 0) {
			$slugs[] = (string) get_post_field('post_name', $front);
		}
		$args['post_name__in'] = $slugs;
	}
	return $args;
}, 10, 2);
