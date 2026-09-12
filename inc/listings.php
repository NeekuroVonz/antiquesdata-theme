<?php
/**
 * Catalog listings page URL and bootstrap.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Permalink for the searchable catalog (not the marketing homepage).
 *
 * @return string
 */
function antiques_marketplace_listings_url() {
	$page = get_page_by_path('data');
	if ($page instanceof WP_Post) {
		return get_permalink($page);
	}
	return home_url('/data/');
}

/**
 * Theme asset URL under assets/.
 *
 * @param string $relative Path relative to the theme assets directory.
 * @return string
 */
function antiques_marketplace_theme_asset( $relative ) {
	return get_template_directory_uri() . '/assets/' . ltrim((string) $relative, '/');
}

/**
 * Landing-page section hash, or homepage URL plus hash on other templates.
 *
 * @param string $section_id Section id without #.
 * @return string
 */
function antiques_marketplace_landing_hash( $section_id ) {
	$hash = '#' . preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $section_id));
	if (is_front_page()) {
		return $hash;
	}
	return home_url('/' . $hash);
}

/**
 * Primary trial / subscribe CTA.
 *
 * @return string
 */
function antiques_marketplace_trial_url() {
	if (is_user_logged_in() && antiques_marketplace_user_has_membership()) {
		return antiques_marketplace_listings_url();
	}
	if (is_user_logged_in()) {
		return antiques_marketplace_page_link('subscribe');
	}
	return antiques_marketplace_page_link('register');
}

/**
 * Create the catalog page if missing.
 */
function antiques_marketplace_ensure_listings_page() {
	if (get_option('antiques_marketplace_listings_page_version') === '1') {
		return;
	}

	$existing = get_page_by_path('data');
	if (!$existing instanceof WP_Post) {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Past sales',
				'post_name'    => 'data',
				'post_content' => '',
			)
		);
		if (is_int($page_id) && $page_id > 0) {
			update_post_meta($page_id, '_wp_page_template', 'page-listings.php');
		}
	}

	update_option('antiques_marketplace_listings_page_version', '1');
}
add_action('init', 'antiques_marketplace_ensure_listings_page');

/**
 * Load listings template by slug.
 *
 * @param string $template Current template.
 * @return string
 */
function antiques_marketplace_listings_template_include( $template ) {
	if (!is_page('data')) {
		return $template;
	}
	$custom = get_template_directory() . '/page-listings.php';
	return file_exists($custom) ? $custom : $template;
}
add_filter('template_include', 'antiques_marketplace_listings_template_include', 20);
