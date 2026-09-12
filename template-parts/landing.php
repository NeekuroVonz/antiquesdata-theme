<?php
/**
 * Marketing landing sections for the public homepage.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

$trial_url = antiques_marketplace_trial_url();
$hero_img  = antiques_marketplace_theme_asset('images/hero-ceramics.png');
$why_img   = antiques_marketplace_theme_asset('images/why-ceramics.png');
$price_img = antiques_marketplace_theme_asset('images/pricing-ceramics.png');

$genres = array(
	__('Tea ware', 'antiques-marketplace'),
	__('Ceramics', 'antiques-marketplace'),
	__('Imari ware', 'antiques-marketplace'),
	__('Kutani ware', 'antiques-marketplace'),
	__('Bizen ware', 'antiques-marketplace'),
	__('Hanging scrolls', 'antiques-marketplace'),
	__('Ukiyo-e', 'antiques-marketplace'),
	__('Nihonga', 'antiques-marketplace'),
	__('Swords', 'antiques-marketplace'),
	__('Netsuke', 'antiques-marketplace'),
	__('Lacquerware', 'antiques-marketplace'),
	__('Buddhist sculpture', 'antiques-marketplace'),
);

$faqs = array(
	array(
		__('Can I look up how much Japanese antiques sold for overseas?', 'antiques-marketplace'),
		__('Yes. Search hammer prices from overseas auctions in Japanese, then compare them with domestic results.', 'antiques-marketplace'),
	),
	array(
		__('Can I use this if I do not read English?', 'antiques-marketplace'),
		__('Yes. Search, filters, and listing titles are presented in Japanese. Switch to English from the header if you prefer.', 'antiques-marketplace'),
	),
	array(
		__('What genres are covered?', 'antiques-marketplace'),
		__('Tea ware, ceramics, Imari, Kutani, Bizen, hanging scrolls, ukiyo-e, Nihonga, swords, netsuke, lacquer, Buddhist sculpture, and related Japanese antiques.', 'antiques-marketplace'),
	),
	array(
		__('Is this an appraisal or valuation service?', 'antiques-marketplace'),
		__('No. This is a market-data service. It shows what comparable lots actually sold for; it does not issue certificates or formal appraisals.', 'antiques-marketplace'),
	),
	array(
		__('Are overseas auction prices shown in Japanese yen?', 'antiques-marketplace'),
		__('Yes. Source hammers are stored in euros and converted for display. Japanese uses yen; English uses US dollars, with the live rate shown on catalog pages.', 'antiques-marketplace'),
	),
	array(
		__('How do I start?', 'antiques-marketplace'),
		__('Create an account and start the trial. After the trial, continue with a paid membership to keep searching the catalog.', 'antiques-marketplace'),
	),
);
?>

<section class="land-hero">
	<div class="land-inner land-hero__grid">
		<div class="land-hero__copy">
			<p class="land-kicker"><?php esc_html_e('Overseas market data for Japanese antiques', 'antiques-marketplace'); ?></p>
			<h1><?php echo wp_kses_post(__('See overseas prices<br>for Japanese antiques', 'antiques-marketplace')); ?></h1>
			<p class="land-lead"><?php esc_html_e('Overseas auction hammer prices and market data, for Japanese antique dealers.', 'antiques-marketplace'); ?></p>
			<p class="land-body"><?php esc_html_e('Antique Data Japan is a market-data service for Japanese dealers: search, compare, and analyze what Japanese antiques actually sold for at overseas auctions. Look up Sotheby’s, Christie’s, Bonhams, and other houses in Japanese, then price against the world market.', 'antiques-marketplace'); ?></p>
			<ul class="land-hero__points">
				<li>
					<span class="land-mini-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M15.5 15.5 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
					</span>
					<?php esc_html_e('Search overseas hammer prices in Japanese', 'antiques-marketplace'); ?>
				</li>
				<li>
					<span class="land-mini-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M5 18V11M10 18V7M15 18v-5M20 18V9" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
					</span>
					<?php esc_html_e('Compare domestic and overseas prices', 'antiques-marketplace'); ?>
				</li>
				<li>
					<span class="land-mini-icon" aria-hidden="true">
						<svg viewBox="0 0 24 24"><path d="M12 3v18M5 8h14M7 16h10" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
					</span>
					<?php esc_html_e('Support buying and selling decisions', 'antiques-marketplace'); ?>
				</li>
			</ul>
			<a class="btn btn-gold land-hero__cta" href="<?php echo esc_url($trial_url); ?>"><?php esc_html_e('Start a free trial', 'antiques-marketplace'); ?></a>
			<p class="land-note"><?php esc_html_e('¥5,000/month (tax included) · No credit card · 7-day free trial', 'antiques-marketplace'); ?></p>
		</div>

		<div class="land-hero__stage" aria-hidden="true">
			<img class="land-float land-float--jar" src="<?php echo esc_url($hero_img); ?>" alt="" width="420" height="315" decoding="async">
			<div class="land-laptop">
				<div class="land-dash">
					<div class="land-dash__brand">Antique Data Japan</div>
					<div class="land-dash__kpis">
						<div><span>データ件数</span><strong>25,854</strong></div>
						<div><span>平均落札</span><strong>¥482,000</strong></div>
						<div><span>累計流通額</span><strong>¥12,500,000,000</strong></div>
						<div><span>今月</span><strong>1,245</strong></div>
					</div>
					<div class="land-dash__chart">
						<span style="height:38%"></span>
						<span style="height:52%"></span>
						<span style="height:44%"></span>
						<span style="height:61%"></span>
						<span style="height:48%"></span>
						<span style="height:72%"></span>
						<span style="height:58%"></span>
						<span style="height:81%"></span>
						<span style="height:64%"></span>
						<span style="height:90%"></span>
					</div>
					<div class="land-dash__rows">
						<div><i></i><b>Sotheby’s New York</b><em>¥2,180,000</em></div>
						<div><i></i><b>Christie’s London</b><em>¥1,640,000</em></div>
						<div><i></i><b>Bonhams Hong Kong</b><em>¥980,000</em></div>
					</div>
				</div>
			</div>
			<aside class="land-phone">
				<img src="<?php echo esc_url($hero_img); ?>" alt="" width="420" height="315" decoding="async">
				<p>抹茶茶碗 / Raku chawan</p>
				<strong>¥1,280,000</strong>
			</aside>
			<img class="land-float land-float--bowls" src="<?php echo esc_url($hero_img); ?>" alt="" width="420" height="315" decoding="async" fetchpriority="high">
		</div>
	</div>
</section>

<section class="land-pain">
	<div class="land-inner">
		<h2><?php esc_html_e('Have you run into this?', 'antiques-marketplace'); ?></h2>
		<div class="land-pain__grid">
			<article>
				<span class="land-icon-disc" aria-hidden="true">
					<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M15.5 15.5 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</span>
				<p><?php esc_html_e('Overseas sites are hard to search in English', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-disc" aria-hidden="true">
					<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4.2L15 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</span>
				<p><?php esc_html_e('You cannot see the real overseas hammer price', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-disc" aria-hidden="true">
					<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3.8 12h16.4M12 3.8c2.4 2.6 3.6 5.4 3.6 8.2S14.4 17.6 12 20.2C9.6 17.6 8.4 14.8 8.4 12S9.6 6.4 12 3.8z" fill="none" stroke="currentColor" stroke-width="1.35"/></svg>
				</span>
				<p><?php esc_html_e('You cannot tell which market or country is strong', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-disc" aria-hidden="true">
					<svg viewBox="0 0 24 24"><path d="M12 4 3.8 19.2h16.4L12 4z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M12 10v4.2M12 16.8v.4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				</span>
				<p><?php esc_html_e('Selling too cheap in Japan is a real risk', 'antiques-marketplace'); ?></p>
			</article>
		</div>
		<p class="land-closing"><?php esc_html_e('Antique Data Japan closes that information gap so Japanese dealers can price against the world market.', 'antiques-marketplace'); ?></p>
	</div>
</section>

<section class="land-features" id="features">
	<div class="land-inner">
		<h2><?php esc_html_e('What you can do with Antique Data Japan', 'antiques-marketplace'); ?></h2>
		<div class="land-features__grid">
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M15.5 15.5 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
				<h3><?php esc_html_e('Search overseas auction hammer prices', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Look up artists, kilns, periods, and categories across Sotheby’s, Christie’s, Bonhams, and other houses.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 18V11M10 18V7M15 18v-5M20 18V9" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
				<h3><?php esc_html_e('Compare and analyze overseas markets', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Place a Japanese asking price next to the overseas hammer and see the gap before you buy or sell.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.25" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3.8 12h16.4M12 3.8c2.4 2.6 3.6 5.4 3.6 8.2S14.4 17.6 12 20.2C9.6 17.6 8.4 14.8 8.4 12S9.6 6.4 12 3.8z" fill="none" stroke="currentColor" stroke-width="1.35"/></svg></span>
				<h3><?php esc_html_e('See results by country and auction house', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Filter the United States, the United Kingdom, France, Hong Kong, and other rooms where Japanese art trades.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M8 9h8M8 12h5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
				<h3><?php esc_html_e('Japanese yen conversion and CSV export', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Read overseas hammers in yen, then export rows for your own books, reports, or inventory notes.', 'antiques-marketplace'); ?></p>
			</article>
		</div>
	</div>
</section>

<section class="land-split">
	<div class="land-inner land-split__grid">
		<div>
			<h2><?php esc_html_e('Example genres and search keywords', 'antiques-marketplace'); ?></h2>
			<ul class="land-genres">
				<?php foreach ($genres as $genre_label) : ?>
					<li><?php echo esc_html($genre_label); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="land-why">
			<h2><?php esc_html_e('Why overseas prices matter', 'antiques-marketplace'); ?></h2>
			<p><?php esc_html_e('The same Japanese object can trade at a different level once it reaches an international room. Dealers who only watch the domestic hammer miss that spread.', 'antiques-marketplace'); ?></p>
			<div class="land-why__stats">
				<div>
					<span><?php esc_html_e('Domestic average hammer', 'antiques-marketplace'); ?></span>
					<strong>¥300,000</strong>
				</div>
				<div class="land-why__arrow" aria-hidden="true">→</div>
				<div class="land-why__gold">
					<span><?php esc_html_e('Overseas average hammer', 'antiques-marketplace'); ?></span>
					<strong>¥2,800,000</strong>
					<em><?php esc_html_e('About 9.3× the domestic average', 'antiques-marketplace'); ?></em>
				</div>
			</div>
			<img src="<?php echo esc_url($why_img); ?>" alt="<?php esc_attr_e('Japanese ceramic jar and porcelain vase', 'antiques-marketplace'); ?>" width="900" height="675" loading="lazy" decoding="async">
		</div>
	</div>
</section>

<section class="land-audience" id="audience">
	<div class="land-inner">
		<h2><?php esc_html_e('Who this is for', 'antiques-marketplace'); ?></h2>
		<div class="land-audience__grid">
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 10h16l-1.2 10H5.2L4 10zM8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
				<h3><?php esc_html_e('Antique shops and fine-art dealers', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Price inventory against overseas rooms, not only the local auction.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5.5 19.2c.8-3.3 3.3-5 6.5-5s5.7 1.7 6.5 5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
				<h3><?php esc_html_e('Licensed dealers and buyers', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Check what a lot is worth abroad before you bid or take it in.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 14h9l2.2-3.2L19 14M8 14v6M16 20H6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="13.2" y="4" width="6.2" height="2.2" rx="0.6" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></span>
				<h3><?php esc_html_e('Auction businesses', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Set estimates with a view of comparable overseas hammers.', 'antiques-marketplace'); ?></p>
			</article>
			<article>
				<span class="land-icon-line" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19h16M6 19V8l6-3 6 3v11" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
				<h3><?php esc_html_e('Art dealers and specialist shops', 'antiques-marketplace'); ?></h3>
				<p><?php esc_html_e('Support client conversations with sold prices, not guesses.', 'antiques-marketplace'); ?></p>
			</article>
		</div>
	</div>
</section>

<section class="land-pricing" id="pricing">
	<div class="land-inner land-pricing__grid">
		<div class="land-pricing__intro">
			<h2><?php esc_html_e('A simple pricing plan', 'antiques-marketplace'); ?></h2>
			<p><?php esc_html_e('One plan. Search overseas hammers, compare them in yen, and export what you need.', 'antiques-marketplace'); ?></p>
			<img src="<?php echo esc_url($price_img); ?>" alt="<?php esc_attr_e('Japanese tea caddy, jar, and tea bowl', 'antiques-marketplace'); ?>" width="900" height="675" loading="lazy" decoding="async">
		</div>
		<div class="land-price-card">
			<p class="land-kicker"><?php esc_html_e('Standard plan', 'antiques-marketplace'); ?></p>
			<p class="land-price-card__amount">
				<?php esc_html_e('¥5,000 / month', 'antiques-marketplace'); ?>
				<small><?php esc_html_e('(tax included)', 'antiques-marketplace'); ?></small>
			</p>
			<p class="land-note"><?php esc_html_e('Cancel anytime · 7-day free trial', 'antiques-marketplace'); ?></p>
			<a class="btn btn-gold" href="<?php echo esc_url($trial_url); ?>"><?php esc_html_e('Try 7 days free', 'antiques-marketplace'); ?></a>
		</div>
		<ul class="land-checks">
			<li><?php esc_html_e('Search overseas auction hammer data', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Filter by country and auction house', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Japanese painting and antique sold prices', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Market reports', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Search by artist, period, and category', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('CSV export', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Overseas price history', 'antiques-marketplace'); ?></li>
			<li><?php esc_html_e('Domestic market data links', 'antiques-marketplace'); ?></li>
		</ul>
	</div>
</section>

<section class="land-faq" id="faq">
	<div class="land-inner">
		<h2><?php esc_html_e('FAQ', 'antiques-marketplace'); ?></h2>
		<div class="land-faq__grid">
			<?php foreach ($faqs as $faq) : ?>
				<details class="land-faq__item">
					<summary><?php echo esc_html($faq[0]); ?></summary>
					<p><?php echo esc_html($faq[1]); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
