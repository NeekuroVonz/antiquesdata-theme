<?php
/**
 * Theme footer.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}
?>
</main>

<footer class="site-footer" id="contact">
	<div class="footer-inner">
		<div class="footer-top">
			<div class="footer-brand">
				<a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
					<span class="brand-mark" aria-hidden="true">
						<svg viewBox="0 0 36 36" width="28" height="28">
							<circle cx="18" cy="18" r="16.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
							<circle cx="18" cy="18" r="11.5" fill="none" stroke="currentColor" stroke-width="0.7" opacity="0.55"/>
							<path d="M11 22.5c2.2-6.4 5-10.6 7-13.2 2 2.6 4.8 6.8 7 13.2" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
							<path d="M13.2 19.2h9.6" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>
						</svg>
					</span>
					<span class="brand-name">Antique Data Japan</span>
				</a>
				<p><?php esc_html_e('Japanese antiques, priced against the world market.', 'antiques-marketplace'); ?></p>
				<a class="btn btn-gold" href="mailto:<?php echo esc_attr(get_bloginfo('admin_email')); ?>"><?php esc_html_e('Contact us here', 'antiques-marketplace'); ?></a>
			</div>
			<nav class="footer-nav" aria-label="<?php esc_attr_e('Footer', 'antiques-marketplace'); ?>">
				<div>
					<h2><?php esc_html_e('Service', 'antiques-marketplace'); ?></h2>
					<a href="<?php echo esc_url(antiques_marketplace_landing_hash('features')); ?>"><?php esc_html_e('Service features', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_listings_url()); ?>"><?php esc_html_e('Data coverage', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_landing_hash('audience')); ?>"><?php esc_html_e('Use cases', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_landing_hash('pricing')); ?>"><?php esc_html_e('Pricing', 'antiques-marketplace'); ?></a>
				</div>
				<div>
					<h2><?php esc_html_e('Support', 'antiques-marketplace'); ?></h2>
					<a href="<?php echo esc_url(antiques_marketplace_landing_hash('faq')); ?>"><?php esc_html_e('FAQ', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_landing_hash('contact')); ?>"><?php esc_html_e('Contact', 'antiques-marketplace'); ?></a>
				</div>
				<div>
					<h2><?php esc_html_e('Company', 'antiques-marketplace'); ?></h2>
					<a href="<?php echo esc_url(antiques_marketplace_page_link('privacy-policy')); ?>"><?php esc_html_e('Privacy Policy', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_page_link('commerce-disclosure')); ?>"><?php esc_html_e('Commerce disclosure', 'antiques-marketplace'); ?></a>
					<a href="<?php echo esc_url(antiques_marketplace_page_link('terms-of-service')); ?>"><?php esc_html_e('Terms of Service', 'antiques-marketplace'); ?></a>
				</div>
			</nav>
		</div>
		<div class="footer-bottom">
			<p class="footer-copy">&copy; <?php echo esc_html(gmdate('Y')); ?> Antique Data Japan Co., Ltd.</p>
			<a class="footer-top-link" href="#"><?php esc_html_e('Back to top', 'antiques-marketplace'); ?> <span aria-hidden="true">↑</span></a>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
