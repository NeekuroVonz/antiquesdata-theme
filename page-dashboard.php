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
$currency    = antiques_marketplace_display_currency();
$error       = isset($metrics['error']) ? $metrics['error'] : null;
$default_key = '12m';
$period      = isset($metrics['periods'][ $default_key ]) && is_array($metrics['periods'][ $default_key ])
	? $metrics['periods'][ $default_key ]
	: array(
		'lots'       => 0,
		'median_eur' => 0,
		'first_end'  => '',
		'last_end'   => '',
		'countries'  => array(),
	);

$range_labels = array(
	'1m'  => __('1 month', 'antiques-marketplace'),
	'6m'  => __('6 months', 'antiques-marketplace'),
	'12m' => __('12 months', 'antiques-marketplace'),
);

$period_first_end = (string) ( $period['first_end'] ?? '' );
$period_last_end  = (string) ( $period['last_end'] ?? '' );
$period_date_span = $period_first_end && $period_last_end
	? sprintf(
		/* translators: 1: first date, 2: last date */
		__('%1$s to %2$s', 'antiques-marketplace'),
		substr($period_first_end, 0, 10),
		substr($period_last_end, 0, 10)
	)
	: __('No available dates', 'antiques-marketplace');

$sample_prompts = array(
	__('How did the median sold price change in this window?', 'antiques-marketplace'),
	__('Which buyer country is strongest right now?', 'antiques-marketplace'),
	__('Which category shows the most activity?', 'antiques-marketplace'),
);
?>

<article class="trends" data-dashboard data-dashboard-range="<?php echo esc_attr($default_key); ?>">
	<header class="trends__intro">
		<p class="trends__kicker"><?php esc_html_e('Catawiki hammer prices, converted from EUR', 'antiques-marketplace'); ?></p>
		<h1><?php esc_html_e('Trend Dashboard', 'antiques-marketplace'); ?></h1>
		<p class="fx-caption"><?php echo esc_html(antiques_marketplace_exchange_rate_caption()); ?></p>
	</header>

	<?php if ($error) : ?>
		<p><?php echo esc_html(sprintf(__('Database error: %s', 'antiques-marketplace'), antiques_marketplace_translate_for_locale($error))); ?></p>
	<?php else : ?>
		<div class="trends__results" id="dashboard-results" role="tabpanel" aria-labelledby="dashboard-range-<?php echo esc_attr($default_key); ?>">
		<div class="trends__hero">
			<div>
				<p class="trends__metric-label"><?php esc_html_e('Median sold price', 'antiques-marketplace'); ?></p>
				<p class="trends__metric" data-dashboard-median><?php echo esc_html(antiques_marketplace_format_currency_amount((float) $period['median_eur'], $currency)); ?></p>
				<p class="trends__metric-note" data-dashboard-caption aria-live="polite">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: period label, 2: available date span, 3: lot count */
							__('P50 hammer, %1$s view. Available data: %2$s. %3$s lots.', 'antiques-marketplace'),
							$range_labels[ $default_key ],
							$period_date_span,
							number_format_i18n((int) $period['lots'])
						)
					);
					?>
				</p>
			</div>
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
		</div>

		<div class="trends__charts">
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
			<ul class="trends-who__list" data-dashboard-countries>
				<?php if (empty($period['countries'])) : ?>
					<li class="trends-who__empty"><?php esc_html_e('No sold lots in this period.', 'antiques-marketplace'); ?></li>
				<?php else : ?>
					<?php foreach ((array) $period['countries'] as $country) : ?>
						<li class="trends-who__row">
							<span class="trends-who__name"><?php echo esc_html((string) $country['name']); ?></span>
							<span class="trends-who__lots"><?php echo esc_html(sprintf(__('%s lots', 'antiques-marketplace'), number_format_i18n((int) $country['lots']))); ?></span>
							<span class="trends-who__cats">
								<?php foreach ((array) $country['categories'] as $category) : ?>
									<a href="<?php echo esc_url((string) $category['url']); ?>">#<?php echo esc_html((string) $category['label']); ?></a>
								<?php endforeach; ?>
							</span>
							<span class="trends-who__price"><?php echo esc_html(antiques_marketplace_format_currency_amount((float) $country['median_eur'], $currency)); ?></span>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
			<p class="trends-who__note"><?php esc_html_e('Winning bidder country from bid history. Categories from lot titles.', 'antiques-marketplace'); ?></p>
		</section>
		</div>

		<section class="trends-ai" aria-labelledby="trends-ai-brief-heading" data-dashboard-ai>
			<div class="trends-ai__grid">
				<div class="trends-ai__brief">
					<h2 id="trends-ai-brief-heading"><?php esc_html_e('AI Market Brief', 'antiques-marketplace'); ?></h2>
					<p class="trends-ai__note"><?php esc_html_e('Synthesized from the charts above for the selected time range. Numbers come from cached dashboard metrics only.', 'antiques-marketplace'); ?></p>
					<div class="trends-ai__body" data-dashboard-ai-insight aria-live="polite">
						<p class="trends-ai__loading"><?php esc_html_e('Writing market brief…', 'antiques-marketplace'); ?></p>
					</div>
				</div>

				<div class="trends-ai__chat">
					<h2 id="trends-ai-chat-heading"><?php esc_html_e('Ask the data', 'antiques-marketplace'); ?></h2>
					<p class="trends-ai__note"><?php esc_html_e('Ask about median price, volume, or buyer countries in the selected window.', 'antiques-marketplace'); ?></p>
					<div class="trends-ai__prompts" data-dashboard-ai-prompts>
						<?php foreach ($sample_prompts as $prompt) : ?>
							<button type="button" class="trends-ai__prompt" data-dashboard-ai-prompt="<?php echo esc_attr($prompt); ?>">
								<?php echo esc_html($prompt); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="trends-ai__thread" data-dashboard-ai-thread role="log" aria-labelledby="trends-ai-chat-heading"></div>
					<form class="trends-ai__form" data-dashboard-ai-form>
						<label class="sr-only" for="trends-ai-question"><?php esc_html_e('Your question', 'antiques-marketplace'); ?></label>
						<input
							id="trends-ai-question"
							class="trends-ai__input"
							type="text"
							name="question"
							autocomplete="off"
							maxlength="500"
							placeholder="<?php esc_attr_e('Ask about this time range…', 'antiques-marketplace'); ?>"
							data-dashboard-ai-input
						>
						<button class="btn btn-gold trends-ai__send" type="submit" data-dashboard-ai-send>
							<?php esc_html_e('Ask', 'antiques-marketplace'); ?>
						</button>
					</form>
				</div>
			</div>
		</section>
	<?php endif; ?>
</article>

<?php
get_footer();
