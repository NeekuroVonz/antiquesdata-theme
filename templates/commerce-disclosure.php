<?php
/**
 * Commerce disclosure page content (structure aligned with justaskakira theme).
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}
?>
<?php get_header(); ?>
<section class="panel legal-page" id="legal-page">
	<div class="container legal-prose">
		<div class="legal-prose-inner">
			<h1 class="legal-page-title">Commerce disclosure</h1>
			<p class="legal-muted">Last updated: April 18, 2026</p>

			<p class="legal-lead">This page gives the information we show customers before and when they pay online. It follows the same kind of layout used on professional services and platform terms (clear identification of the seller, how and when charges occur, and how refunds work), consistent with
				<a href="https://support.stripe.com/questions/how-to-create-and-display-a-commerce-disclosure-page" target="_blank" rel="noopener noreferrer">Stripe&rsquo;s guidance</a> on commerce disclosures. Please read it together with our
				<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>">Terms of Service</a> and
				<a href="<?php echo esc_url(antiques_marketplace_page_link('privacy-policy')); ?>">Privacy Policy</a>.</p>

			<h2>Seller / operator</h2>
			<ul>
				<li><strong>Site / brand name:</strong> <?php echo esc_html(get_bloginfo('name')); ?></li>
				<li><strong>Description:</strong> Online antiques marketplace: listings, bids, and related information may be displayed from catalog data.</li>
				<li><strong>Website:</strong> <?php echo esc_html(home_url('/')); ?></li>
				<li><strong>Email:</strong> <a href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php echo esc_html(get_bloginfo('admin_email')); ?></a></li>
			</ul>
			<p>The <strong>legal entity name</strong> and <strong>address</strong> used for tax and payment registration should match the information you maintain with your payment processor and on official records.</p>

			<h2>What you are buying</h2>
			<p>Depending on the feature, you may purchase <strong>access to services</strong> or <strong>goods</strong> as described at checkout, on an invoice, or on the listing. The scope and price for each transaction are described in the checkout flow, invoice, or written agreement.</p>

			<h2>Prices and additional charges</h2>
			<p>Prices are shown in the currency displayed at checkout or on your invoice. The total is the fee plus any taxes we are required to collect. We do not add recurring charges unless a subscription or installment plan is offered and you agree to it before payment.</p>

			<h2>Payment methods and timing</h2>
			<p>Card and other payment methods made available at checkout are processed by <strong>Stripe</strong> (or another processor we name at checkout). When you confirm payment, you authorize us and our payment processor to charge your selected method for the amount shown.</p>
			<p>For amounts billed by invoice, payment is due on the date stated on the invoice unless we agree otherwise in writing.</p>

			<h2>Delivery / performance</h2>
			<p>Digital services and information are delivered as described at checkout. Physical goods, if any, ship according to the listing or agreement. Consulting or custom services follow the timeline in your proposal or statement of work where applicable.</p>

			<h2>Cancellations, changes, and refunds</h2>
			<p><strong>Charges are tied to confirmed purchases</strong>, and <strong>refunds depend on what was promised and what has already been delivered</strong>:</p>
			<ul>
				<li><strong>After you pay:</strong> Fees cover what is described in the checkout description, invoice, or signed agreement. If you prepaid for work that <strong>has not started</strong> and you cancel in writing, we may refund the unused portion to your original payment method where allowed by law and our agreement with you.</li>
				<li><strong>After work has started or deliverables are provided:</strong> Fees for completed or allocated work are generally <strong>earned and non-refundable</strong>, except where we agree otherwise in writing or the law requires a refund.</li>
				<li><strong>Billing questions or disputes:</strong> Contact
					<a href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php echo esc_html(get_bloginfo('admin_email')); ?></a> before initiating a chargeback where possible so we can try to resolve the issue.</li>
			</ul>

			<h2>Privacy</h2>
			<p>Our <a href="<?php echo esc_url(antiques_marketplace_page_link('privacy-policy')); ?>">Privacy Policy</a> explains how we handle personal data. Card data is processed by Stripe under its terms and security practices.</p>

			<h2>Terms of Service</h2>
			<p>Purchases are also governed by our
				<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>">Terms of Service</a>, including limitations of liability and governing law.</p>

			<h2>Stripe</h2>
			<p>Stripe is a third-party payment processor and is not the seller of third-party listings. See the
				<a href="https://stripe.com/legal/consumer" target="_blank" rel="noopener noreferrer">Stripe consumer terms</a>.</p>
		</div>
	</div>
</section>
<?php get_footer(); ?>
