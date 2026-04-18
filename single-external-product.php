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

get_header();
?>

<section class="panel product-detail-panel" style="margin-top: 1rem;">
	<?php if (!$product) : ?>
		<h1><?php esc_html_e('Product not found', 'antiques-marketplace'); ?></h1>
		<p><?php echo esc_html($error ? $error : __('This product is unavailable.', 'antiques-marketplace')); ?></p>
		<p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to listings', 'antiques-marketplace'); ?></a></p>
	<?php else : ?>
		<?php
		$title          = isset($product['product_title']) ? (string) $product['product_title'] : '';
		$description    = isset($product['product_description']) ? (string) $product['product_description'] : '';
		$product_detail = isset($product['product_detail']) ? (string) $product['product_detail'] : '';
		$image          = isset($product['product_image']) ? (string) $product['product_image'] : '';
		$image_gallery  = isset($product['product_images']) ? (string) $product['product_images'] : '';
		$created        = isset($product['product_created']) ? (string) $product['product_created'] : '';
		$end_time       = isset($product['product_end_time']) ? (string) $product['product_end_time'] : '';
		$external_url   = isset($product['product_url']) ? (string) $product['product_url'] : '';
		$price_formatted = isset($product['product_price']) && is_numeric($product['product_price'])
			? number_format_i18n((float) $product['product_price'], 2)
			: null;

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
		if (empty($image)) {
			$image = 'https://via.placeholder.com/1024x640?text=No+Image';
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
		$details_source         = trim($product_detail) !== '' ? $product_detail : $description;
		$translated_details_txt = antiques_marketplace_translate_to_english($details_source);
		$translated_description = antiques_marketplace_translate_to_english($description);
		?>
		<div class="product-detail-grid">
			<div class="product-detail-image-wrap" data-product-gallery>
				<a class="product-image-main-btn" href="<?php echo esc_url($image); ?>" target="_blank" rel="noopener noreferrer" data-main-image-link>
					<img class="product-detail-image" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" data-main-image>
				</a>
				<?php if (count($gallery_images) > 1) : ?>
					<div class="product-thumb-row">
						<?php foreach ($gallery_images as $index => $gallery_image_url) : ?>
							<button
								type="button"
								class="product-thumb <?php echo 0 === $index ? 'is-active' : ''; ?>"
								data-thumb-src="<?php echo esc_url($gallery_image_url); ?>"
								aria-label="<?php echo esc_attr(sprintf(__('Preview image %d', 'antiques-marketplace'), $index + 1)); ?>"
							>
								<img src="<?php echo esc_url($gallery_image_url); ?>" alt="<?php echo esc_attr($title); ?>">
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="product-detail-info">
				<h1 class="product-detail-title"><?php echo esc_html($title); ?></h1>
				<div class="price-label"><?php esc_html_e('CURRENT BID', 'antiques-marketplace'); ?></div>
				<div class="product-detail-price">
					<?php echo esc_html($price_formatted ? '$' . $price_formatted : __('Bid not available', 'antiques-marketplace')); ?>
				</div>
				<div class="meta"><?php echo esc_html(antiques_marketplace_format_time_left($end_time)); ?></div>
				<?php if ($created) : ?>
					<div class="meta meta-secondary"><?php echo esc_html(wp_date(get_option('date_format'), strtotime($created))); ?></div>
				<?php endif; ?>
				<p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to listings', 'antiques-marketplace'); ?></a></p>
				<?php if (!empty($external_url)) : ?>
					<p><a class="product-external-link" href="<?php echo esc_url($external_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View source listing', 'antiques-marketplace'); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<?php if (!empty($details)) : ?>
			<div class="product-details-table">
				<h3><?php esc_html_e('Details', 'antiques-marketplace'); ?></h3>
				<div class="product-details-grid">
					<?php foreach ($details as $item) : ?>
						<div class="product-detail-item">
							<div class="product-detail-label"><?php echo esc_html($item['label']); ?></div>
							<div class="product-detail-value"><?php echo esc_html($item['value']); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($translated_details_txt) : ?>
			<div class="product-detail-description">
				<h3><?php esc_html_e('Details overview', 'antiques-marketplace'); ?></h3>
				<p><?php echo nl2br(esc_html($translated_details_txt)); ?></p>
			</div>
		<?php endif; ?>

		<?php if ($translated_description) : ?>
			<div class="product-detail-description">
				<h3><?php esc_html_e('Description', 'antiques-marketplace'); ?></h3>
				<p><?php echo nl2br(esc_html($translated_description)); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var gallery = document.querySelector('[data-product-gallery]');
  if (!gallery) return;

  var mainImage = gallery.querySelector('[data-main-image]');
  var mainImageLink = gallery.querySelector('[data-main-image-link]');
  var thumbs = gallery.querySelectorAll('.product-thumb');

  thumbs.forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var src = thumb.getAttribute('data-thumb-src');
      if (!src || !mainImage) return;
      mainImage.setAttribute('src', src);
      if (mainImageLink) {
        mainImageLink.setAttribute('href', src);
      }
      thumbs.forEach(function (t) { t.classList.remove('is-active'); });
      thumb.classList.add('is-active');
    });
  });
});
</script>

<?php get_footer(); ?>
