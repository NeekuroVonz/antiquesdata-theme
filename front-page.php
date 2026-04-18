<?php
/**
 * Front page template.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

<section class="hero">
	<h1><?php esc_html_e('Bid, buy, and discover rare antiques', 'antiques-marketplace'); ?></h1>
	<p><?php esc_html_e('A marketplace-style homepage inspired by eBay. Publish listings as posts and start selling.', 'antiques-marketplace'); ?></p>
</section>

<div class="page-layout">
	<aside class="filters">
		<h3><?php esc_html_e('Filter listings', 'antiques-marketplace'); ?></h3>
		<form method="get" action="<?php echo esc_url(home_url('/')); ?>">
			<label for="q"><?php esc_html_e('Keywords', 'antiques-marketplace'); ?></label>
			<input id="q" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>">

			<label for="min-price"><?php esc_html_e('Min price', 'antiques-marketplace'); ?></label>
			<input id="min-price" type="number" min="0" step="1" name="min_price" value="<?php echo isset($_GET['min_price']) ? esc_attr(wp_unslash($_GET['min_price'])) : ''; ?>">

			<label for="max-price"><?php esc_html_e('Max price', 'antiques-marketplace'); ?></label>
			<input id="max-price" type="number" min="0" step="1" name="max_price" value="<?php echo isset($_GET['max_price']) ? esc_attr(wp_unslash($_GET['max_price'])) : ''; ?>">

			<label for="sort"><?php esc_html_e('Sort by', 'antiques-marketplace'); ?></label>
			<select id="sort" name="sort">
				<option value="latest"><?php esc_html_e('Newest', 'antiques-marketplace'); ?></option>
				<option value="low"><?php esc_html_e('Price: Low to High', 'antiques-marketplace'); ?></option>
				<option value="high"><?php esc_html_e('Price: High to Low', 'antiques-marketplace'); ?></option>
			</select>

			<button class="btn" type="submit"><?php esc_html_e('Apply filters', 'antiques-marketplace'); ?></button>
		</form>

		<?php if (is_active_sidebar('marketplace-filters')) : ?>
			<div style="margin-top: 1rem;">
				<?php dynamic_sidebar('marketplace-filters'); ?>
			</div>
		<?php endif; ?>
	</aside>

	<section class="panel">
		<?php
		$sort         = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'latest';
		$current_page = isset($_GET['pg']) ? max(1, (int) wp_unslash($_GET['pg'])) : 1;
		$per_page     = 12;

		$external_listing_result = antiques_marketplace_get_external_products(
			array(
				'search'    => get_search_query(),
				'min_price' => isset($_GET['min_price']) ? (float) wp_unslash($_GET['min_price']) : null,
				'max_price' => isset($_GET['max_price']) ? (float) wp_unslash($_GET['max_price']) : null,
				'sort'      => in_array($sort, array('latest', 'low', 'high'), true) ? $sort : 'latest',
				'limit'     => $per_page,
				'page'      => $current_page,
			)
		);

		$products      = isset($external_listing_result['products']) ? $external_listing_result['products'] : array();
		$listing_error = isset($external_listing_result['error']) ? $external_listing_result['error'] : null;
		$product_table = isset($external_listing_result['table']) ? $external_listing_result['table'] : null;
		$total_results = isset($external_listing_result['total']) ? (int) $external_listing_result['total'] : count($products);
		$total_pages   = max(1, (int) ceil($total_results / $per_page));
		?>
		<div class="panel-heading">
			<h3><?php esc_html_e('Featured listings', 'antiques-marketplace'); ?></h3>
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
				foreach ($products as $product) :
					$product_title   = isset($product['product_title']) ? (string) $product['product_title'] : '';
					$product_id      = isset($product['product_id']) ? (int) $product['product_id'] : 0;
					$product_url     = $product_id > 0 ? antiques_marketplace_product_permalink($product_id) : '#';
					$product_image   = !empty($product['product_image']) ? esc_url((string) $product['product_image']) : 'https://via.placeholder.com/640x420?text=No+Image';
					$product_created = !empty($product['product_created']) ? (string) $product['product_created'] : '';
					$product_end     = !empty($product['product_end_time']) ? (string) $product['product_end_time'] : '';
					$product_price   = isset($product['product_price']) && is_numeric($product['product_price']) ? number_format_i18n((float) $product['product_price'], 2) : null;
					$time_left       = antiques_marketplace_format_time_left($product_end);
					?>
					<article class="listing-card">
						<a href="<?php echo esc_url($product_url); ?>">
							<img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_title); ?>">
						</a>
						<div class="card-content">
							<h4 class="card-title">
								<a href="<?php echo esc_url($product_url); ?>">
									<?php echo esc_html($product_title); ?>
								</a>
							</h4>
							<div class="price-label"><?php esc_html_e('CURRENT BID', 'antiques-marketplace'); ?></div>
							<div class="price">
								<?php
								if ($product_price) {
									echo esc_html('$' . $product_price);
								} else {
									esc_html_e('Bid not available', 'antiques-marketplace');
								}
								?>
							</div>
							<div class="meta"><?php echo esc_html($time_left); ?></div>
							<?php if ($product_created) : ?>
								<div class="meta meta-secondary"><?php echo esc_html(wp_date(get_option('date_format'), strtotime($product_created))); ?></div>
							<?php endif; ?>
						</div>
					</article>
					<?php
				endforeach;
			else :
				?>
				<div class="panel-empty">
					<p><?php esc_html_e('No external products found.', 'antiques-marketplace'); ?></p>
					<?php if ($listing_error) : ?>
						<p><?php echo esc_html(sprintf(__('Database error: %s', 'antiques-marketplace'), $listing_error)); ?></p>
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
				$query_args = array();
				if (isset($_GET['s']) && '' !== $_GET['s']) {
					$query_args['s'] = sanitize_text_field(wp_unslash($_GET['s']));
				}
				if (isset($_GET['min_price']) && '' !== $_GET['min_price']) {
					$query_args['min_price'] = (string) wp_unslash($_GET['min_price']);
				}
				if (isset($_GET['max_price']) && '' !== $_GET['max_price']) {
					$query_args['max_price'] = (string) wp_unslash($_GET['max_price']);
				}
				if (isset($_GET['sort']) && '' !== $_GET['sort']) {
					$query_args['sort'] = sanitize_text_field(wp_unslash($_GET['sort']));
				}

				$build_page_url = function( $page_number ) use ( $query_args ) {
					return add_query_arg(
						array_merge($query_args, array('pg' => (string) $page_number)),
						home_url('/')
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
get_footer();
