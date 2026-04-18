<?php
/**
 * Listing card template.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

$price_raw = get_post_meta(get_the_ID(), '_listing_price', true);
$price     = is_numeric($price_raw) ? number_format_i18n((float) $price_raw, 2) : null;
?>

<article class="listing-card">
	<a href="<?php the_permalink(); ?>">
		<?php if (has_post_thumbnail()) : ?>
			<?php the_post_thumbnail('medium'); ?>
		<?php else : ?>
			<img src="https://via.placeholder.com/640x420?text=No+Image" alt="<?php the_title_attribute(); ?>">
		<?php endif; ?>
	</a>
	<div class="card-content">
		<h4 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
		<div class="price">
			<?php
			if ($price) {
				echo esc_html('$' . $price);
			} else {
				esc_html_e('Set _listing_price', 'antiques-marketplace');
			}
			?>
		</div>
		<div class="meta"><?php echo esc_html(get_the_date()); ?></div>
		<span class="badge"><?php esc_html_e('Buy It Now', 'antiques-marketplace'); ?></span>
	</div>
</article>
