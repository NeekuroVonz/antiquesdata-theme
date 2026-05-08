<?php
/**
 * Privacy Policy page content (structure aligned with justaskakira theme).
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
			<h1 class="legal-page-title">Privacy Policy</h1>
			<p class="legal-muted">Last updated: April 18, 2026</p>

			<h2>Introduction</h2>
			<p>This Privacy Policy describes how <strong><?php echo esc_html(get_bloginfo('name')); ?></strong> (&ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;) collects, uses, discloses, and protects personal information when you visit our website at
				<?php echo esc_html(wp_parse_url(home_url(), PHP_URL_HOST) ?: 'our website'); ?> (the &ldquo;Site&rdquo;), browse listings, use contact or inquiry features, or purchase services through the Site. By using the Site, you agree to this Privacy Policy.</p>
			<p>This policy follows common practices for digital marketplaces: clear purposes, sharing, security, retention, and your rights.</p>

			<h2>Other policies</h2>
			<p>This Privacy Policy works together with our
				<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>">Terms of Service</a> and
				<a href="<?php echo esc_url(antiques_marketplace_page_link('commerce-disclosure')); ?>">Commerce disclosure</a>. If there is a conflict, we will resolve it reasonably; specific written contracts for a transaction may control how we handle certain data under that contract.</p>

			<h2>Information we collect</h2>
			<p>We may collect:</p>
			<ul>
				<li><strong>Contact and inquiry data:</strong> Name, email address, phone number, and message content when you submit a contact form or otherwise reach out.</li>
				<li><strong>Account and activity data:</strong> Information you provide in connection with bids, purchases, or seller interactions when those features are available.</li>
				<li><strong>Payment-related data:</strong> When you pay online, our payment processor (e.g. Stripe) may collect payment method details. We typically receive limited information such as confirmation of payment, amount, and metadata needed for accounting and support—not your full card number.</li>
				<li><strong>Technical and usage data:</strong> IP address, browser type, device identifiers, general location derived from IP, and Site usage information collected via cookies or similar technologies.</li>
				<li><strong>Listing catalog data:</strong> Product and auction information may be displayed from external data sources to power the marketplace experience.</li>
			</ul>

			<h2>How we use your information</h2>
			<p>We use personal information to:</p>
			<ul>
				<li>Operate and improve the Site and marketplace features;</li>
				<li>Respond to inquiries and provide customer support;</li>
				<li>Process payments and prevent fraud;</li>
				<li>Send service-related messages and, where permitted, information about the Site (you may opt out of marketing where applicable);</li>
				<li>Comply with law, enforce our terms, and protect rights, safety, and security.</li>
			</ul>

			<h2>How we share information</h2>
			<p>We do not sell your personal information. We may share information with:</p>
			<ul>
				<li><strong>Service providers</strong> who assist us (e.g. hosting, email delivery, analytics, payment processing such as Stripe), subject to appropriate confidentiality and processing obligations;</li>
				<li><strong>Professional advisers</strong> (e.g. lawyers, accountants) when required;</li>
				<li><strong>Authorities or third parties</strong> when required by law or to protect us, our users, or others.</li>
			</ul>
			<p>Payment processors handle card transactions under their own privacy policies and terms.</p>

			<h2>Cookies and similar technologies</h2>
			<p>We may use cookies and similar technologies to operate the Site, remember preferences, measure traffic, and improve performance. You can control cookies through your browser settings; disabling some cookies may affect Site functionality.</p>

			<h2>Security</h2>
			<p>We use reasonable technical and organizational measures to protect personal information. No method of transmission over the Internet is completely secure; we cannot guarantee absolute security.</p>

			<h2>Retention</h2>
			<p>We retain information for as long as needed to provide the Site and services, comply with legal, tax, and accounting obligations, resolve disputes, and enforce agreements. Retention periods vary depending on the nature of the data and our legal duties.</p>

			<h2>International transfers</h2>
			<p>Your information may be processed in the country where we operate or in other countries where we or our service providers operate (for example, payment or hosting services). Where required by law, we use appropriate safeguards for cross-border transfers.</p>

			<h2>Your rights</h2>
			<p>Depending on where you live, you may have rights to access, correct, delete, or restrict processing of your personal data, or to object to certain processing. You may also have the right to lodge a complaint with a supervisory authority. To exercise rights, contact us using the information below. We may need to verify your request.</p>

			<h2>Children</h2>
			<p>The Site is not directed to children under 16. We do not knowingly collect personal information from children under 16. If you believe we have collected such information, contact us and we will take steps to delete it.</p>

			<h2>Changes to this Privacy Policy</h2>
			<p>We may update this Privacy Policy from time to time. We will post the revised version on this page and update the &ldquo;Last updated&rdquo; date. For material changes, we may provide additional notice (e.g. a notice on the Site or email where appropriate).</p>

			<h2>Contact us</h2>
			<p><strong>Legal Name:</strong> Akira Washiya | 鷲谷彬<br>
				<strong>Company Name:</strong> 有限会社鳥海メディカルサービス<br>
				<strong>Head of Operations:</strong> Akira Washiya<br>
				<strong>Company Number (法人番号):</strong> 2060002006021<br>
				<strong>Address:</strong> 栃木県宇都宮市一条３丁目２番２９号レオパレス２１－１０２号室<br>
				<strong>Phone:</strong> <a href="tel:08012292520">080-1229-2520</a><br>
				<strong>Email:</strong> <a href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php echo esc_html(get_bloginfo('admin_email')); ?></a></p>
		</div>
	</div>
</section>
<?php get_footer(); ?>
