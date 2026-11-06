<?php
/**
 * Template Name: Trend Dashboard
 *
 * @package AntiquesMarketplace
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();

$metrics     = antiques_marketplace_get_dashboard_metrics();
$error       = isset($metrics['error']) ? $metrics['error'] : null;
$default_key = '12m';
$front       = antiques_marketplace_dashboard_front_data(is_array($metrics) ? $metrics : array());
$period      = $front['periods'][ $default_key ];
$summary     = antiques_marketplace_dashboard_summary(is_array($metrics) ? $metrics : array());

$range_labels = array(
	'1m'  => __('1 month', 'antiques-marketplace'),
	'6m'  => __('6 months', 'antiques-marketplace'),
	'12m' => __('12 months', 'antiques-marketplace'),
);
?>

<article class="trends" data-dashboard data-dashboard-range="<?php echo esc_attr($default_key); ?>">
	<header class="trends-head">
		<div class="trends-inner trends-head__top">
			<div class="trends-head__title">
				<p class="trends-kicker"><?php esc_html_e('Catawiki hammer prices, converted from EUR', 'antiques-marketplace'); ?></p>
				<h1><?php esc_html_e('Trend Dashboard', 'antiques-marketplace'); ?></h1>
				<p class="trends-fx"><?php echo esc_html(antiques_marketplace_exchange_rate_caption()); ?></p>
			</div>
			<?php if (!$error) : ?>
				<div class="trends-range" role="tablist" aria-label="<?php esc_attr_e('Time range', 'antiques-marketplace'); ?>">
					<?php foreach ($range_labels as $key => $label) : ?>
						<button
							type="button"
							id="dashboard-range-<?php echo esc_attr($key); ?>"
							class="trends-range__btn<?php echo $key === $default_key ? ' is-active' : ''; ?>"
							role="tab"
							aria-selected="<?php echo $key === $default_key ? 'true' : 'false'; ?>"
							aria-controls="dashboard-results"
							tabindex="<?php echo $key === $default_key ? '0' : '-1'; ?>"
							data-dashboard-range="<?php echo esc_attr($key); ?>"
						><?php echo esc_html($label); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<?php if ($error) : ?>
		<div class="trends-inner trends-body">
			<p class="trends-error"><?php echo esc_html(sprintf(__('Database error: %s', 'antiques-marketplace'), antiques_marketplace_translate_for_locale($error))); ?></p>
		</div>
	<?php else : ?>
		<div id="dashboard-results" role="tabpanel" aria-labelledby="dashboard-range-<?php echo esc_attr($default_key); ?>">
			<div class="trends-kpis-band">
				<dl class="trends-inner trends-kpis">
					<div class="trends-kpi trends-kpi--main">
						<dt><?php esc_html_e('Median sold price', 'antiques-marketplace'); ?></dt>
						<dd class="trends-kpi__value" data-dashboard-median><?php echo esc_html($period['median']); ?></dd>
						<dd class="trends-kpi__note"><?php esc_html_e('P50 hammer price', 'antiques-marketplace'); ?></dd>
					</div>
					<div class="trends-kpi">
						<dt><?php esc_html_e('Lots sold', 'antiques-marketplace'); ?></dt>
						<dd class="trends-kpi__value" data-dashboard-lots><?php echo esc_html($period['lots']); ?></dd>
						<dd class="trends-kpi__note" data-dashboard-span><?php echo esc_html($period['span']); ?></dd>
					</div>
					<div class="trends-kpi">
						<dt><?php esc_html_e('Top buyer country', 'antiques-marketplace'); ?></dt>
						<dd class="trends-kpi__value" data-dashboard-top><?php echo esc_html($period['top']); ?></dd>
						<dd class="trends-kpi__note" data-dashboard-top-note><?php echo esc_html($period['top_note']); ?></dd>
					</div>
				</dl>
			</div>

			<div class="trends-inner trends-body">
				<section class="trends-summary" aria-labelledby="trends-summary-heading">
					<div class="trends-summary__head">
						<h2 id="trends-summary-heading"><?php esc_html_e('Market summary', 'antiques-marketplace'); ?></h2>
						<p class="trends-summary__meta">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: date and time */
									__('Updated %s · refreshed daily', 'antiques-marketplace'),
									wp_date('ja' === antiques_marketplace_get_lang() ? 'Y年n月j日 H:i' : 'M j, Y H:i', (int) $summary['generated_at'])
								)
							);
							?>
						</p>
					</div>
					<div class="trends-summary__body" data-dashboard-summary>
						<?php echo antiques_marketplace_dashboard_summary_html($summary['items'][ $default_key ] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped per line. ?>
					</div>
				</section>

				<div class="trends-charts">
					<section class="trends-chart" aria-labelledby="trends-price-heading">
						<h2 id="trends-price-heading"><?php esc_html_e('Price trend', 'antiques-marketplace'); ?></h2>
						<div class="trends-chart__plot" role="img" data-dashboard-price></div>
					</section>
					<section class="trends-chart" aria-labelledby="trends-volume-heading">
						<h2 id="trends-volume-heading"><?php esc_html_e('Lots sold', 'antiques-marketplace'); ?></h2>
						<div class="trends-chart__plot" role="img" data-dashboard-volume></div>
					</section>
				</div>

				<section class="trends-who" aria-labelledby="trends-who-heading">
					<h2 id="trends-who-heading"><?php esc_html_e('Who is buying', 'antiques-marketplace'); ?></h2>
					<ol class="trends-who__list" data-dashboard-countries>
						<?php if (empty($period['countries'])) : ?>
							<li class="trends-who__empty"><?php echo esc_html($front['empty']); ?></li>
						<?php else : ?>
							<?php foreach ($period['countries'] as $i => $country) : ?>
								<li class="trends-who__row">
									<span class="trends-who__rank"><?php echo esc_html(str_pad((string) ( $i + 1 ), 2, '0', STR_PAD_LEFT)); ?></span>
									<span class="trends-who__name"><?php echo esc_html($country['name']); ?></span>
									<span class="trends-who__bar" aria-hidden="true"><i style="--w: <?php echo esc_attr((string) $country['bar']); ?>%"></i></span>
									<span class="trends-who__lots"><?php echo esc_html($country['lots']); ?></span>
									<span class="trends-who__cats">
										<?php foreach ((array) $country['categories'] as $category) : ?>
											<a href="<?php echo esc_url((string) $category['url']); ?>">#<?php echo esc_html((string) $category['label']); ?></a>
										<?php endforeach; ?>
									</span>
									<span class="trends-who__price"><?php echo esc_html($country['median']); ?></span>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ol>
					<p class="trends-who__note"><?php esc_html_e('Winning bidder country from bid history. Categories from lot titles.', 'antiques-marketplace'); ?></p>
				</section>
			</div>
		</div>
	<?php endif; ?>
</article>

<?php
get_footer();
