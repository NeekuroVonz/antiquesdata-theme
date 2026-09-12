<?php
/**
 * External product detail template.
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

$product_id = absint(get_query_var('antiques_product_id'));
$result     = antiques_marketplace_get_external_product_by_id($product_id);
$product    = isset($result['product']) ? $result['product'] : null;
$error      = isset($result['error']) ? $result['error'] : null;

if (!$product) {
	status_header(404);
}

$title                  = '';
$raw_title              = '';
$description            = '';
$product_detail         = '';
$image                  = '';
$created                = '';
$end_time               = '';
$external_url           = '';
$bid_count              = null;
$seller_country         = '';
$price_formatted        = '';
$category_slug          = 'other';
$bid_history            = array();
$winning_bid            = null;
$has_product_image      = false;
$fallback_image         = antiques_marketplace_no_image_url(1024, 640);
$fallback_alt           = '';
$main_display_image     = $fallback_image;
$main_full_image        = $fallback_image;
$gallery_thumbs         = array();
$gallery_display        = array();
$gallery_full           = array();
$details                = array();
$translated_details_txt = '';
$translated_description = '';

if ($product) {
	$raw_title       = isset($product['product_title']) ? (string) $product['product_title'] : '';
	$title           = antiques_marketplace_is_latin_text($raw_title)
		? antiques_marketplace_normalize_latin_punctuation($raw_title)
		: $raw_title;
	$description     = isset($product['product_description']) ? (string) $product['product_description'] : '';
	$product_detail  = isset($product['product_detail']) ? (string) $product['product_detail'] : '';
	$image           = isset($product['product_image']) ? (string) $product['product_image'] : '';
	$image_gallery   = isset($product['product_images']) ? (string) $product['product_images'] : '';
	$created         = isset($product['product_created']) ? (string) $product['product_created'] : '';
	$end_time        = isset($product['product_end_time']) ? (string) $product['product_end_time'] : '';
	$external_url    = isset($product['product_url']) ? (string) $product['product_url'] : '';
	$bid_count       = isset($product['product_bid_count']) && is_numeric($product['product_bid_count']) ? (int) $product['product_bid_count'] : null;
	$seller_country  = isset($product['product_seller_country']) ? (string) $product['product_seller_country'] : '';
	$price_formatted = antiques_marketplace_format_display_price($product['product_price'] ?? null);
	$category_slug   = antiques_marketplace_classify_listing($raw_title, $description, true);
	$bid_history     = antiques_marketplace_parse_bid_history($product['product_bid_history'] ?? '');
	$winning_bid     = !empty($bid_history) ? $bid_history[0] : null;

	$decoded_images = !empty($image_gallery) ? json_decode($image_gallery, true) : array();
	$gallery_images = array();
	if (is_array($decoded_images)) {
		foreach ($decoded_images as $img) {
			if (!is_string($img) || '' === trim($img)) {
				continue;
			}
			$gallery_images[] = $img;
		}
	}
	if (empty($image) && !empty($gallery_images[0])) {
		$image = (string) $gallery_images[0];
	}
	if (!empty($image) && !in_array($image, $gallery_images, true)) {
		array_unshift($gallery_images, $image);
	}

	$has_product_image = !empty($image) || !empty($gallery_images);
	if (empty($image)) {
		$image = $fallback_image;
	}
	$fallback_alt       = sprintf(__('Image unavailable for %s', 'antiques-marketplace'), $title);
	$main_display_image = $has_product_image ? antiques_marketplace_catawiki_image_url($image, 'display') : $image;
	$main_full_image    = $has_product_image ? antiques_marketplace_catawiki_image_url($image, 'full') : $image;

	foreach ($gallery_images as $gallery_image_url) {
		$gallery_thumbs[]  = antiques_marketplace_catawiki_image_url($gallery_image_url, 'thumb');
		$gallery_display[] = antiques_marketplace_catawiki_image_url($gallery_image_url, 'display');
		$gallery_full[]    = antiques_marketplace_catawiki_image_url($gallery_image_url, 'full');
	}

	$details = antiques_marketplace_parse_product_details($description);
	$details = array_values(
		array_filter(
			$details,
			function( $item ) {
				return strlen((string) $item['value']) <= 140;
			}
		)
	);

	// Keep auction source copy as-is. Field-level Translate was adding multiple
	// seconds of TTFB on lots with Japanese certificate text.
	$details_source         = trim($product_detail) !== '' ? $product_detail : $description;
	$translated_details_txt = $details_source;
	$translated_description = $description;

	if ($has_product_image && $main_display_image) {
		add_action(
			'wp_head',
			static function() use ( $main_display_image ) {
				printf(
					'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
					esc_url($main_display_image)
				);
			},
			1
		);
	}
}

get_header();
?>

<section class="panel product-detail-panel" style="margin-top: 1rem;" data-product-translate-root>
	<?php if (!$product) : ?>
		<h1><?php esc_html_e('Product not found', 'antiques-marketplace'); ?></h1>
		<p><?php echo esc_html($error ? antiques_marketplace_translate_for_locale($error) : __('This product is unavailable.', 'antiques-marketplace')); ?></p>
		<p><a class="btn" href="<?php echo esc_url(antiques_marketplace_listings_url()); ?>"><?php esc_html_e('Back to listings', 'antiques-marketplace'); ?></a></p>
	<?php else : ?>
		<p class="product-translate-status" data-product-translate-status hidden aria-live="polite"></p>
		<div class="product-detail-grid">
			<div class="product-detail-image-wrap" data-product-gallery>
				<a
					class="product-image-main-btn"
					href="<?php echo esc_url($main_full_image); ?>"
					aria-label="<?php esc_attr_e('View full image', 'antiques-marketplace'); ?>"
					data-main-image-link
				>
					<img
						class="product-detail-image<?php echo $has_product_image ? '' : ' is-fallback-image'; ?>"
						src="<?php echo esc_url($main_display_image); ?>"
						alt="<?php echo esc_attr($has_product_image ? $title : $fallback_alt); ?>"
						width="640"
						height="430"
						fetchpriority="high"
						decoding="async"
						data-fallback-src="<?php echo esc_url($fallback_image); ?>"
						data-fallback-alt="<?php echo esc_attr($fallback_alt); ?>"
						data-main-image
					>
				</a>
				<?php if (count($gallery_full) > 1) : ?>
					<div class="product-thumb-row">
						<?php foreach ($gallery_full as $index => $gallery_full_url) : ?>
							<button
								type="button"
								class="product-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
								data-thumb-src="<?php echo esc_url($gallery_full_url); ?>"
								data-thumb-display="<?php echo esc_url($gallery_display[ $index ]); ?>"
								aria-label="<?php echo esc_attr(sprintf(__('Preview image %d', 'antiques-marketplace'), $index + 1)); ?>"
							>
								<img
									src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
									data-src="<?php echo esc_url($gallery_thumbs[ $index ]); ?>"
									alt=""
									width="70"
									height="62"
									decoding="async"
									class="product-thumb__image is-deferred"
									data-fallback-src="<?php echo esc_url($fallback_image); ?>"
									data-fallback-alt="<?php echo esc_attr($fallback_alt); ?>"
								>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="product-detail-info">
				<h1 class="product-detail-title" data-product-title><?php echo esc_html($title); ?></h1>
				<?php
				$show_sold_price = false;
				if (!empty($end_time)) {
					$end_ts = strtotime($end_time);
					$show_sold_price = false !== $end_ts && $end_ts < current_time('timestamp');
				}
				?>
				<div class="price-label">
					<?php
					if ($show_sold_price) {
						esc_html_e('Sold price', 'antiques-marketplace');
					} else {
						esc_html_e('CURRENT BID', 'antiques-marketplace');
					}
					?>
				</div>
				<div class="product-detail-price">
					<?php echo esc_html($price_formatted ? $price_formatted : __('Bid not available', 'antiques-marketplace')); ?>
				</div>
				<p class="fx-caption fx-caption--inline"><?php echo esc_html(antiques_marketplace_exchange_rate_caption()); ?></p>
				<?php if (is_numeric($product['product_price'] ?? null)) : ?>
					<div class="meta meta-secondary"><?php echo esc_html(sprintf(__('Original hammer: €%s', 'antiques-marketplace'), number_format_i18n((float) $product['product_price'], 2))); ?></div>
				<?php endif; ?>
				<div class="product-hashtags">
					<a class="category-chip" href="<?php echo esc_url(antiques_marketplace_category_url($category_slug)); ?>">#<?php echo esc_html(antiques_marketplace_category_label($category_slug)); ?></a>
				</div>
				<div class="meta"><?php echo esc_html(antiques_marketplace_format_time_left($end_time)); ?></div>
				<?php if (null !== $bid_count) : ?>
					<div class="meta meta-secondary"><?php echo esc_html(sprintf(__('Bids: %d', 'antiques-marketplace'), $bid_count)); ?></div>
				<?php endif; ?>
				<?php if ($winning_bid && !empty($winning_bid['country_code'])) : ?>
					<div class="meta meta-secondary">
						<?php
						echo esc_html(
							sprintf(
								__('Sold to: %1$s (Bidder %2$s)', 'antiques-marketplace'),
								antiques_marketplace_country_name($winning_bid['country_code']),
								$winning_bid['bidder_name'] ? $winning_bid['bidder_name'] : __('Unknown', 'antiques-marketplace')
							)
						);
						?>
					</div>
				<?php elseif ($seller_country) : ?>
					<div class="meta meta-secondary"><?php echo esc_html(sprintf(__('Seller country: %s', 'antiques-marketplace'), $seller_country)); ?></div>
				<?php endif; ?>
				<?php if ($created) : ?>
					<div class="meta meta-secondary"><?php echo esc_html(wp_date(get_option('date_format'), strtotime($created))); ?></div>
				<?php endif; ?>
				<p><a class="btn" href="<?php echo esc_url(antiques_marketplace_listings_url()); ?>"><?php esc_html_e('Back to listings', 'antiques-marketplace'); ?></a></p>
				<?php if (antiques_marketplace_show_source_listing() && !empty($external_url)) : ?>
					<p><a class="product-external-link" href="<?php echo esc_url($external_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View source listing', 'antiques-marketplace'); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<dialog
			class="product-lightbox"
			aria-label="<?php echo esc_attr(sprintf(__('Image viewer: %s', 'antiques-marketplace'), $title)); ?>"
			data-product-lightbox
		>
			<div class="product-lightbox__surface">
				<button type="button" class="product-lightbox__close" data-lightbox-close>
					<?php esc_html_e('Close', 'antiques-marketplace'); ?>
				</button>
				<div class="product-lightbox__zoom" role="group" aria-label="<?php esc_attr_e('Zoom controls', 'antiques-marketplace'); ?>">
					<button type="button" class="product-lightbox__zoom-btn" aria-label="<?php esc_attr_e('Zoom out', 'antiques-marketplace'); ?>" data-lightbox-zoom-out>−</button>
					<span class="product-lightbox__zoom-level" data-lightbox-zoom-level aria-live="polite">100%</span>
					<button type="button" class="product-lightbox__zoom-btn" aria-label="<?php esc_attr_e('Zoom in', 'antiques-marketplace'); ?>" data-lightbox-zoom-in>+</button>
					<button type="button" class="product-lightbox__zoom-btn product-lightbox__zoom-btn--reset" data-lightbox-zoom-reset><?php esc_html_e('Reset', 'antiques-marketplace'); ?></button>
				</div>
				<button
					type="button"
					class="product-lightbox__nav product-lightbox__nav--previous"
					aria-label="<?php esc_attr_e('Previous image', 'antiques-marketplace'); ?>"
					data-lightbox-previous
				>‹</button>
				<figure class="product-lightbox__figure" data-lightbox-stage>
					<img
						class="product-lightbox__image<?php echo $has_product_image ? '' : ' is-fallback-image'; ?>"
						src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
						data-src="<?php echo esc_url($main_full_image); ?>"
						alt="<?php echo esc_attr($has_product_image ? $title : $fallback_alt); ?>"
						data-fallback-src="<?php echo esc_url($fallback_image); ?>"
						data-fallback-alt="<?php echo esc_attr($fallback_alt); ?>"
						data-lightbox-image
						draggable="false"
					>
					<figcaption class="product-lightbox__counter" data-lightbox-counter aria-live="polite"></figcaption>
				</figure>
				<button
					type="button"
					class="product-lightbox__nav product-lightbox__nav--next"
					aria-label="<?php esc_attr_e('Next image', 'antiques-marketplace'); ?>"
					data-lightbox-next
				>›</button>
			</div>
		</dialog>
		<?php if (!empty($bid_history)) : ?>
			<div class="product-bid-history">
				<h3><?php esc_html_e('Bid history', 'antiques-marketplace'); ?></h3>
				<table class="bid-history-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Bidder', 'antiques-marketplace'); ?></th>
							<th><?php esc_html_e('Country', 'antiques-marketplace'); ?></th>
							<th><?php esc_html_e('Amount', 'antiques-marketplace'); ?></th>
							<th><?php esc_html_e('When', 'antiques-marketplace'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($bid_history as $bid_row) : ?>
							<tr>
								<td><?php echo esc_html($bid_row['bidder_name'] ? sprintf(__('Bidder %s', 'antiques-marketplace'), $bid_row['bidder_name']) : __('Bidder', 'antiques-marketplace')); ?></td>
								<td><?php echo esc_html($bid_row['country_code'] ? antiques_marketplace_country_name($bid_row['country_code']) : '—'); ?></td>
								<td><?php echo esc_html(antiques_marketplace_format_display_price($bid_row['amount'])); ?></td>
								<td>
									<?php
									if ($bid_row['created_at']) {
										echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($bid_row['created_at'])));
									} else {
										echo '—';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

		<?php if (!empty($details)) : ?>
			<div class="product-details-table" data-product-details>
				<h3><?php esc_html_e('Details', 'antiques-marketplace'); ?></h3>
				<div class="product-details-grid">
					<?php foreach ($details as $item) : ?>
						<div class="product-detail-item" data-product-detail-item>
							<div class="product-detail-label" data-product-detail-label><?php echo esc_html($item['label']); ?></div>
							<div class="product-detail-value" data-product-detail-value><?php echo esc_html($item['value']); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($translated_details_txt) : ?>
			<div class="product-detail-description" data-product-details-overview>
				<h3><?php esc_html_e('Details overview', 'antiques-marketplace'); ?></h3>
				<p data-product-richtext><?php echo nl2br(esc_html($translated_details_txt)); ?></p>
			</div>
		<?php endif; ?>

		<?php if ($translated_description && $translated_description !== $translated_details_txt) : ?>
			<div class="product-detail-description" data-product-description>
				<h3><?php esc_html_e('Description', 'antiques-marketplace'); ?></h3>
				<p data-product-richtext><?php echo nl2br(esc_html($translated_description)); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
