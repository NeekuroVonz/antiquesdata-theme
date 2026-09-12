<?php
/**
 * Catalog listings grid (filters + results).
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

$filters    = antiques_marketplace_parse_listing_filters();
$categories = antiques_marketplace_get_external_categories();
$category_counts = antiques_marketplace_category_counts();
$fx_caption = antiques_marketplace_exchange_rate_caption();
?>

<div class="page-layout">
	<aside class="filters filter-panel">
		<div class="filter-panel__head">
			<h3 class="filter-panel__title"><?php esc_html_e('Filter listings', 'antiques-marketplace'); ?></h3>
			<p class="filter-panel__subtitle"><?php esc_html_e('Refine your search', 'antiques-marketplace'); ?></p>
		</div>
		<form method="get" action="<?php echo esc_url(antiques_marketplace_listings_url()); ?>" class="filter-form">
			<div class="filter-section">
				<h4 class="filter-section__title"><?php esc_html_e('Search', 'antiques-marketplace'); ?></h4>
				<div class="filter-field">
					<label for="listing-search"><?php esc_html_e('Keywords', 'antiques-marketplace'); ?></label>
					<input id="listing-search" type="search" name="listing_search" value="<?php echo esc_attr($filters['search']); ?>" autocomplete="off" placeholder="<?php esc_attr_e('Search titles…', 'antiques-marketplace'); ?>">
				</div>
			</div>

			<div class="filter-section">
				<h4 class="filter-section__title"><?php esc_html_e('Auction', 'antiques-marketplace'); ?></h4>
				<div class="filter-field">
					<label for="listing-status"><?php esc_html_e('Listing status', 'antiques-marketplace'); ?></label>
					<select id="listing-status" name="listing_status">
						<option value="ended" <?php selected($filters['listing_status'], 'ended'); ?>><?php esc_html_e('Ended auctions (sold)', 'antiques-marketplace'); ?></option>
						<option value="active" <?php selected($filters['listing_status'], 'active'); ?>><?php esc_html_e('Active auctions', 'antiques-marketplace'); ?></option>
						<option value="all" <?php selected($filters['listing_status'], 'all'); ?>><?php esc_html_e('All listings', 'antiques-marketplace'); ?></option>
					</select>
				</div>
				<div class="filter-field">
					<label for="ending-within"><?php esc_html_e('Ends within', 'antiques-marketplace'); ?></label>
					<select id="ending-within" name="ending_within">
						<option value="0" <?php selected((int) $filters['ending_within'], 0); ?>><?php esc_html_e('Any time', 'antiques-marketplace'); ?></option>
						<option value="24" <?php selected((int) $filters['ending_within'], 24); ?>><?php esc_html_e('24 hours', 'antiques-marketplace'); ?></option>
						<option value="72" <?php selected((int) $filters['ending_within'], 72); ?>><?php esc_html_e('3 days', 'antiques-marketplace'); ?></option>
						<option value="168" <?php selected((int) $filters['ending_within'], 168); ?>><?php esc_html_e('7 days', 'antiques-marketplace'); ?></option>
					</select>
				</div>
				<div class="filter-field">
					<label for="sold-within"><?php esc_html_e('Sold within', 'antiques-marketplace'); ?></label>
					<select id="sold-within" name="sold_within">
						<option value="0" <?php selected((int) $filters['sold_within'], 0); ?>><?php esc_html_e('Any period', 'antiques-marketplace'); ?></option>
						<option value="7" <?php selected((int) $filters['sold_within'], 7); ?>><?php esc_html_e('1 week', 'antiques-marketplace'); ?></option>
						<option value="30" <?php selected((int) $filters['sold_within'], 30); ?>><?php esc_html_e('1 month', 'antiques-marketplace'); ?></option>
						<option value="90" <?php selected((int) $filters['sold_within'], 90); ?>><?php esc_html_e('3 months', 'antiques-marketplace'); ?></option>
						<option value="365" <?php selected((int) $filters['sold_within'], 365); ?>><?php esc_html_e('1 year', 'antiques-marketplace'); ?></option>
					</select>
				</div>
				<?php if (!empty($categories)) : ?>
					<div class="filter-field">
						<label for="category"><?php esc_html_e('Category', 'antiques-marketplace'); ?></label>
						<select id="category" name="category">
							<option value=""><?php esc_html_e('All categories', 'antiques-marketplace'); ?></option>
							<?php foreach ($categories as $category_slug) : ?>
								<option value="<?php echo esc_attr($category_slug); ?>" <?php selected($filters['category'], $category_slug); ?>>
									<?php echo esc_html(antiques_marketplace_category_label($category_slug)); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>
			</div>

			<div class="filter-section">
				<h4 class="filter-section__title"><?php esc_html_e('Price', 'antiques-marketplace'); ?></h4>
				<div class="filter-row-2">
					<div class="filter-field">
						<label for="min-price"><?php esc_html_e('Min price', 'antiques-marketplace'); ?></label>
						<input id="min-price" type="number" min="0" step="1" name="min_price" value="<?php echo null !== $filters['min_price'] ? esc_attr((string) $filters['min_price']) : ''; ?>" placeholder="<?php echo 'JPY' === antiques_marketplace_display_currency() ? '¥' : '$'; ?>0">
					</div>
					<div class="filter-field">
						<label for="max-price"><?php esc_html_e('Max price', 'antiques-marketplace'); ?></label>
						<input id="max-price" type="number" min="0" step="1" name="max_price" value="<?php echo null !== $filters['max_price'] ? esc_attr((string) $filters['max_price']) : ''; ?>" placeholder="∞">
					</div>
				</div>
				<label class="filter-toggle">
					<input type="checkbox" name="has_price" value="1" <?php checked(!empty($filters['has_price'])); ?>>
					<span><?php esc_html_e('Only with price', 'antiques-marketplace'); ?></span>
				</label>
			</div>

			<div class="filter-section">
				<h4 class="filter-section__title"><?php esc_html_e('Display', 'antiques-marketplace'); ?></h4>
				<div class="filter-field">
					<label for="sort"><?php esc_html_e('Sort by', 'antiques-marketplace'); ?></label>
					<select id="sort" name="sort">
						<option value="latest" <?php selected($filters['sort'], 'latest'); ?>><?php esc_html_e('Newest', 'antiques-marketplace'); ?></option>
						<option value="price_high" <?php selected($filters['sort'], 'price_high'); ?>><?php esc_html_e('Winning price: High to Low', 'antiques-marketplace'); ?></option>
						<option value="price_low" <?php selected($filters['sort'], 'price_low'); ?>><?php esc_html_e('Winning price: Low to High', 'antiques-marketplace'); ?></option>
						<option value="bids_high" <?php selected($filters['sort'], 'bids_high'); ?>><?php esc_html_e('Bid count: Most to Fewest', 'antiques-marketplace'); ?></option>
						<option value="bids_low" <?php selected($filters['sort'], 'bids_low'); ?>><?php esc_html_e('Bid count: Fewest to Most', 'antiques-marketplace'); ?></option>
						<option value="end_asc" <?php selected($filters['sort'], 'end_asc'); ?>><?php esc_html_e('Auction end: Soonest first', 'antiques-marketplace'); ?></option>
						<option value="end_desc" <?php selected($filters['sort'], 'end_desc'); ?>><?php esc_html_e('Auction end: Latest first', 'antiques-marketplace'); ?></option>
					</select>
				</div>
				<div class="filter-field filter-field--last">
					<label for="per-page"><?php esc_html_e('Per page', 'antiques-marketplace'); ?></label>
					<select id="per-page" name="per_page">
						<option value="20" <?php selected((int) $filters['limit'], 20); ?>>20</option>
						<option value="12" <?php selected((int) $filters['limit'], 12); ?>>12</option>
						<option value="24" <?php selected((int) $filters['limit'], 24); ?>>24</option>
						<option value="48" <?php selected((int) $filters['limit'], 48); ?>>48</option>
					</select>
				</div>
			</div>

			<div class="filter-actions">
				<button class="btn btn-filter-submit" type="submit"><?php esc_html_e('Apply filters', 'antiques-marketplace'); ?></button>
				<a class="filter-clear" href="<?php echo esc_url(antiques_marketplace_listings_url()); ?>"><?php esc_html_e('Clear filters', 'antiques-marketplace'); ?></a>
			</div>
		</form>

		<?php if (is_active_sidebar('marketplace-filters')) : ?>
			<div style="margin-top: 1rem;">
				<?php dynamic_sidebar('marketplace-filters'); ?>
			</div>
		<?php endif; ?>
	</aside>

	<section class="panel">
		<?php
		$external_listing_result = antiques_marketplace_get_external_products($filters);

		$products      = isset($external_listing_result['products']) ? $external_listing_result['products'] : array();
		$listing_error = isset($external_listing_result['error']) ? $external_listing_result['error'] : null;
		$product_table = isset($external_listing_result['table']) ? $external_listing_result['table'] : null;
		$total_results = isset($external_listing_result['total']) ? (int) $external_listing_result['total'] : count($products);
		$per_page      = (int) $filters['limit'];
		$current_page  = (int) $filters['page'];
		$total_pages   = max(1, (int) ceil($total_results / $per_page));
		$query_args    = antiques_marketplace_listing_filter_query_args($filters);
		?>
		<?php $sold_view = antiques_marketplace_is_sold_listing_view($filters); ?>
		<p class="fx-caption"><?php echo esc_html($fx_caption); ?></p>
		<nav class="category-hub" aria-label="<?php esc_attr_e('Antique categories', 'antiques-marketplace'); ?>">
			<h3 class="category-hub__title"><?php esc_html_e('Browse by category', 'antiques-marketplace'); ?></h3>
			<ul class="category-hub__list">
				<?php foreach ($categories as $category_slug) : ?>
					<li>
						<a class="category-chip <?php echo $filters['category'] === $category_slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(antiques_marketplace_category_url($category_slug)); ?>">
							<span class="category-chip__label">#<?php echo esc_html(antiques_marketplace_category_label($category_slug)); ?></span>
							<?php if (!empty($category_counts[ $category_slug ])) : ?>
								<span class="category-chip__count"><?php echo esc_html(number_format_i18n((int) $category_counts[ $category_slug ])); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="panel-heading">
			<h3>
				<?php
				if ($sold_view) {
					esc_html_e('Past sales', 'antiques-marketplace');
				} else {
					esc_html_e('Featured listings', 'antiques-marketplace');
				}
				?>
			</h3>
			<?php if (!empty($products)) : ?>
				<p class="results-meta">
					<?php
					echo esc_html(
						sprintf(
							__('Showing %1$d-%2$d of %3$d products', 'antiques-marketplace'),
							(($current_page - 1) * $per_page) + 1,
							min($current_page * $per_page, $total_results),
							$total_results
						)
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<div class="listing-grid">
			<?php
			if (!empty($products)) :
				$listing_index = 0;
				foreach ($products as $product) :
					$product_title   = isset($product['product_title']) ? (string) $product['product_title'] : '';
					$product_id      = isset($product['product_id']) ? (int) $product['product_id'] : 0;
					$product_url     = $product_id > 0 ? antiques_marketplace_product_permalink($product_id) : '#';
					$has_product_image = !empty($product['product_image']);
					$product_image   = $has_product_image ? antiques_marketplace_listing_image_url((string) $product['product_image']) : antiques_marketplace_no_image_url();
					$fallback_image  = antiques_marketplace_no_image_url();
					$image_alt       = $has_product_image
						? $product_title
						: sprintf(__('Image unavailable for %s', 'antiques-marketplace'), $product_title);
					$product_created = !empty($product['product_created']) ? (string) $product['product_created'] : '';
					$product_end     = !empty($product['product_end_time']) ? (string) $product['product_end_time'] : '';
					$product_bids    = isset($product['product_bid_count']) && is_numeric($product['product_bid_count']) ? (int) $product['product_bid_count'] : null;
					$product_price   = antiques_marketplace_format_display_price($product['product_price'] ?? null);
					$time_left       = antiques_marketplace_format_time_left($product_end);
					$category_slug   = antiques_marketplace_classify_listing($product_title);
					$display_date    = $sold_view && $product_end ? $product_end : $product_created;
					?>
					<article class="listing-card">
						<a href="<?php echo esc_url($product_url); ?>">
							<img
								class="listing-card__image<?php echo $has_product_image ? '' : ' is-fallback-image'; ?>"
								src="<?php echo esc_url($product_image); ?>"
								alt="<?php echo esc_attr($image_alt); ?>"
								data-fallback-src="<?php echo esc_url($fallback_image); ?>"
								data-fallback-alt="<?php echo esc_attr(sprintf(__('Image unavailable for %s', 'antiques-marketplace'), $product_title)); ?>"
								width="320"
								height="155"
								decoding="async"
								<?php echo $listing_index < 5 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
							>
						</a>
						<div class="card-content">
							<h4 class="card-title">
								<a href="<?php echo esc_url($product_url); ?>">
									<?php echo esc_html($product_title); ?>
								</a>
							</h4>
							<div class="price-label">
								<?php
								if ($sold_view) {
									esc_html_e('Sold price', 'antiques-marketplace');
								} else {
									esc_html_e('CURRENT BID', 'antiques-marketplace');
								}
								?>
							</div>
							<div class="price">
								<?php
								if ($product_price) {
									echo esc_html($product_price);
								} else {
									esc_html_e('Bid not available', 'antiques-marketplace');
								}
								?>
							</div>
							<a class="card-hashtag" href="<?php echo esc_url(antiques_marketplace_category_url($category_slug)); ?>">#<?php echo esc_html(antiques_marketplace_category_label($category_slug)); ?></a>
							<div class="meta"><?php echo esc_html($time_left); ?></div>
							<?php if (null !== $product_bids) : ?>
								<div class="meta meta-secondary">
									<?php echo esc_html(sprintf(__('Bids: %d', 'antiques-marketplace'), $product_bids)); ?>
								</div>
							<?php endif; ?>
							<?php if ($display_date) : ?>
								<div class="meta meta-secondary"><?php echo esc_html(wp_date(get_option('date_format'), strtotime($display_date))); ?></div>
							<?php endif; ?>
						</div>
					</article>
					<?php
					$listing_index++;
				endforeach;
			else :
				?>
				<div class="panel-empty">
					<p><?php esc_html_e('No external products found.', 'antiques-marketplace'); ?></p>
					<?php if ($listing_error) : ?>
						<p><?php echo esc_html(sprintf(__('Database error: %s', 'antiques-marketplace'), antiques_marketplace_translate_for_locale($listing_error))); ?></p>
					<?php elseif ($product_table) : ?>
						<p><?php echo esc_html(sprintf(__('Connected to table: %s', 'antiques-marketplace'), $product_table)); ?></p>
					<?php endif; ?>
				</div>
				<?php
			endif;
			?>
		</div>
		<?php if ($total_pages > 1) : ?>
			<nav class="results-pagination" aria-label="<?php esc_attr_e('Product pagination', 'antiques-marketplace'); ?>">
				<?php
				$build_page_url = function( $page_number ) use ( $query_args ) {
					return add_query_arg(
						array_merge($query_args, array('pg' => (string) $page_number)),
						antiques_marketplace_listings_url()
					);
				};

				if ($current_page > 1) :
					?>
					<a class="pagination-nav" href="<?php echo esc_url($build_page_url($current_page - 1)); ?>">
						<?php esc_html_e('Prev', 'antiques-marketplace'); ?>
					</a>
				<?php endif; ?>

				<?php
				$visible_pages = array(1, $total_pages, $current_page - 1, $current_page, $current_page + 1);
				for ($i = 2; $i <= 3; $i++) {
					$visible_pages[] = $i;
					$visible_pages[] = $total_pages - ($i - 1);
				}
				$visible_pages = array_unique(array_filter($visible_pages, function( $page_number ) use ( $total_pages ) {
					return $page_number >= 1 && $page_number <= $total_pages;
				}));
				sort($visible_pages);

				$last_rendered = 0;
				foreach ($visible_pages as $page_number) :
					if ($last_rendered > 0 && ($page_number - $last_rendered) > 1) :
						?>
						<span class="pagination-ellipsis" aria-hidden="true">...</span>
						<?php
					endif;
					?>
					<a class="<?php echo $page_number === $current_page ? 'is-active' : ''; ?>" href="<?php echo esc_url($build_page_url($page_number)); ?>">
						<?php echo esc_html((string) $page_number); ?>
					</a>
					<?php
					$last_rendered = $page_number;
				endforeach;

				if ($current_page < $total_pages) :
					?>
					<a class="pagination-nav" href="<?php echo esc_url($build_page_url($current_page + 1)); ?>">
						<?php esc_html_e('Next', 'antiques-marketplace'); ?>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</section>
</div>

<?php