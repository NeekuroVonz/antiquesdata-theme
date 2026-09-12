<?php
/**
 * Ten-section catalog taxonomy derived from listing titles.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Canonical antique categories (slug => definition).
 *
 * @return array<string, array{label:string, label_ja:string, keywords:array<int,string>}>
 */
function antiques_marketplace_catalog_taxonomy() {
	return array(
		'swords-blades'    => array(
			'label'    => 'Swords & Blades',
			'label_ja' => '刀剣',
			'keywords' => array('katana', 'tachi', 'wakizashi', 'tanto', 'naginata', 'nihonto', 'sword', 'blade', 'ken ', 'naginata', 'nodachi', 'uchigatana'),
		),
		'armor-helmets'    => array(
			'label'    => 'Armor & Helmets',
			'label_ja' => '甲冑・兜',
			'keywords' => array('yoroi', 'armor', 'armour', 'kabuto', 'helmet', 'menpo', 'mempo', 'cuirass', 'do-maru', 'domaru', 'tosei', 'gusoku', 'suneate', 'kote'),
		),
		'sword-fittings'   => array(
			'label'    => 'Sword Fittings',
			'label_ja' => '刀装具',
			'keywords' => array('tsuba', 'koshirae', 'menuki', 'fuchi', 'kashira', 'kozuka', 'kogai', 'habaki', 'seppa', 'fittings', 'kodogu', 'saya'),
		),
		'lacquerware'      => array(
			'label'    => 'Lacquerware',
			'label_ja' => '漆器',
			'keywords' => array('lacquer', 'urushi', 'inro', 'makie', 'maki-e', 'suzuribako', 'writing box', 'negoro', 'ryukyu'),
		),
		'ceramics'         => array(
			'label'    => 'Ceramics & Porcelain',
			'label_ja' => '陶磁器',
			'keywords' => array('porcelain', 'ceramic', 'pottery', 'imari', 'satsuma', 'kutani', 'raku', 'seto', 'oribe', 'karatsu', 'bizen', 'vase', 'bowl', 'plate', 'jar'),
		),
		'paintings-screens' => array(
			'label'    => 'Paintings & Screens',
			'label_ja' => '絵画・屏風',
			'keywords' => array('painting', 'hanging scroll', 'kakemono', 'byobu', 'folding screen', 'ukiyo-e', 'woodblock', 'print', 'calligraphy', 'sumi-e', 'scroll'),
		),
		'sculpture'        => array(
			'label'    => 'Sculpture & Okimono',
			'label_ja' => '彫刻・置物',
			'keywords' => array('sculpture', 'okimono', 'netsuke', 'figure', 'bronze', 'statue', 'buddha', 'ivory', 'wood carving', 'mask'),
		),
		'tea-ceremony'     => array(
			'label'    => 'Tea Ceremony',
			'label_ja' => '茶道具',
			'keywords' => array('tea ceremony', 'chawan', 'natsume', 'chashaku', 'tetsubin', 'chanoyu', 'tea bowl', 'tea caddy', 'kettle', 'chaire'),
		),
		'textiles'         => array(
			'label'    => 'Textiles & Clothing',
			'label_ja' => '染織・着物',
			'keywords' => array('kimono', 'obi', 'textile', 'silk', 'embroidery', 'brocade', 'fabric', 'kesa', 'uchikake', 'haori'),
		),
		'other'            => array(
			'label'    => 'Other Antiques',
			'label_ja' => 'その他',
			'keywords' => array(),
		),
	);
}

/**
 * Localized label for a category slug.
 *
 * @param string $slug Category slug.
 * @return string
 */
function antiques_marketplace_category_label( $slug ) {
	$taxonomy = antiques_marketplace_catalog_taxonomy();
	if (!isset($taxonomy[ $slug ])) {
		$slug = 'other';
	}
	$entry = $taxonomy[ $slug ];
	return 'ja' === antiques_marketplace_get_lang() ? $entry['label_ja'] : $entry['label'];
}

/**
 * Classify a listing from title (and optional description) into one taxonomy slug.
 *
 * Uses weighted keyword scoring so overlapping terms (e.g. chawan) resolve
 * to the more specific category. Optional OpenAI refinement is cached per title.
 *
 * @param string $title       Product title.
 * @param string $description Optional description.
 * @return string Category slug.
 */
function antiques_marketplace_classify_listing( $title, $description = '', $allow_ai = false ) {
	$haystack = strtolower(trim((string) $title . ' ' . (string) $description));
	$best_slug  = 'other';
	$best_score = 0;
	foreach (antiques_marketplace_catalog_taxonomy() as $slug => $entry) {
		if ('other' === $slug) {
			continue;
		}
		$score = 0;
		foreach ($entry['keywords'] as $keyword) {
			$keyword = strtolower((string) $keyword);
			if ('' === $keyword) {
				continue;
			}
			if (false !== strpos($haystack, $keyword)) {
				$score += max(1, (int) strlen($keyword) - 2);
			}
		}
		if ($score > $best_score) {
			$best_score = $score;
			$best_slug  = $slug;
		}
	}

	if ($allow_ai) {
		$refined = antiques_marketplace_maybe_refine_category_with_ai($title, $best_slug);
		return $refined ? $refined : $best_slug;
	}

	return $best_slug;
}

/**
 * Optional OpenAI refinement when OPENAI_API_KEY is set. Cached 30 days.
 *
 * @param string $title           Product title.
 * @param string $heuristic_slug  Keyword-based slug.
 * @return string|null
 */
function antiques_marketplace_maybe_refine_category_with_ai( $title, $heuristic_slug ) {
	$api_key = antiques_marketplace_env('OPENAI_API_KEY');
	if ('' === $api_key) {
		return $heuristic_slug;
	}

	$cache_key = 'ant_cat_' . md5(strtolower(trim((string) $title)));
	$cached    = get_transient($cache_key);
	if (is_string($cached) && isset(antiques_marketplace_catalog_taxonomy()[ $cached ])) {
		return $cached;
	}

	$slugs    = implode(', ', array_keys(antiques_marketplace_catalog_taxonomy()));
	$payload  = wp_json_encode(
		array(
			'model'           => 'gpt-4o-mini',
			'temperature'     => 0,
			'response_format' => array('type' => 'json_object'),
			'messages'        => array(
				array(
					'role'    => 'system',
					'content' => 'Classify Japanese antique auction titles into exactly one slug: ' . $slugs . '. Reply JSON {"slug":"..."} only.',
				),
				array(
					'role'    => 'user',
					'content' => (string) $title,
				),
			),
		)
	);

	$response = wp_remote_post(
		'https://api.openai.com/v1/chat/completions',
		array(
			'timeout' => 8,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => $payload ? $payload : '{}',
		)
	);

	if (is_wp_error($response)) {
		return $heuristic_slug;
	}

	$body = json_decode((string) wp_remote_retrieve_body($response), true);
	$text = $body['choices'][0]['message']['content'] ?? '';
	$json = is_string($text) ? json_decode($text, true) : null;
	$slug = is_array($json) && isset($json['slug']) ? sanitize_title((string) $json['slug']) : '';
	if (!isset(antiques_marketplace_catalog_taxonomy()[ $slug ])) {
		return $heuristic_slug;
	}

	set_transient($cache_key, $slug, 30 * DAY_IN_SECONDS);
	return $slug;
}

/**
 * FULLTEXT boolean query covering a category's keywords (OR).
 *
 * @param string $slug Category slug.
 * @return string
 */
function antiques_marketplace_category_fulltext_query( $slug ) {
	$taxonomy = antiques_marketplace_catalog_taxonomy();
	if (!isset($taxonomy[ $slug ]) || 'other' === $slug) {
		return '';
	}

	$parts = array();
	foreach ($taxonomy[ $slug ]['keywords'] as $keyword) {
		$clean = preg_replace('/[^\p{L}\p{N}]+/u', ' ', (string) $keyword);
		$clean = trim((string) $clean);
		if (strlen($clean) < 3) {
			continue;
		}
		$token = preg_replace('/\s+/', '', $clean);
		if (!is_string($token) || strlen($token) < 3) {
			continue;
		}
		$parts[] = $token . '*';
	}

	return implode(' ', array_unique($parts));
}

/**
 * Cached listing counts for the ten categories (ended lots).
 *
 * @return array<string, int>
 */
function antiques_marketplace_category_counts() {
	$cached = get_transient('antiques_marketplace_category_counts_v2');
	if (is_array($cached)) {
		return $cached;
	}

	$stale = get_option('antiques_marketplace_category_counts_stale_v2', array());
	if (!wp_next_scheduled('antiques_marketplace_refresh_category_counts')) {
		wp_schedule_single_event(time(), 'antiques_marketplace_refresh_category_counts');
	}

	return is_array($stale) ? $stale : array();
}

/**
 * Recompute category hub counts (slow FULLTEXT COUNTs). Runs from WP-Cron, not during render.
 *
 * @return array<string, int>
 */
function antiques_marketplace_rebuild_category_counts() {
	$counts = array();
	foreach (array_keys(antiques_marketplace_catalog_taxonomy()) as $slug) {
		$counts[ $slug ] = 0;
	}

	$result = antiques_marketplace_get_external_products(
		array(
			'listing_status' => 'ended',
			'limit'          => 1,
			'page'           => 1,
			'dedupe'         => false,
			'has_price'      => true,
		)
	);
	$total_ended = isset($result['total']) ? (int) $result['total'] : 0;

	$classified = 0;
	foreach (array_keys(antiques_marketplace_catalog_taxonomy()) as $slug) {
		if ('other' === $slug) {
			continue;
		}
		$cat_result = antiques_marketplace_get_external_products(
			array(
				'listing_status' => 'ended',
				'category'       => $slug,
				'limit'          => 1,
				'page'           => 1,
				'dedupe'         => false,
				'has_price'      => true,
			)
		);
		$counts[ $slug ] = isset($cat_result['total']) ? (int) $cat_result['total'] : 0;
		$classified     += $counts[ $slug ];
	}
	$counts['other'] = max(0, $total_ended - $classified);

	set_transient('antiques_marketplace_category_counts_v2', $counts, 30 * MINUTE_IN_SECONDS);
	update_option('antiques_marketplace_category_counts_stale_v2', $counts, false);
	return $counts;
}
add_action('antiques_marketplace_refresh_category_counts', 'antiques_marketplace_rebuild_category_counts');

/**
 * Permalink that filters listings to one category.
 *
 * @param string $slug Category slug.
 * @return string
 */
function antiques_marketplace_category_url( $slug ) {
	return add_query_arg(
		array(
			'category'       => $slug,
			'listing_status' => 'ended',
		),
		antiques_marketplace_listings_url()
	);
}
