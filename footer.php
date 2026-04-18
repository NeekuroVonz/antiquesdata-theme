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

<footer class="site-footer">
	<div class="container">
		<?php
		$footer_links = array(
			'terms-of-service'    => __('Terms of Service', 'antiques-marketplace'),
			'privacy-policy'      => __('Privacy Policy', 'antiques-marketplace'),
			'commerce-disclosure' => __('Commerce disclosure', 'antiques-marketplace'),
		);
		?>
		<nav class="footer-links" aria-label="<?php esc_attr_e('Legal links', 'antiques-marketplace'); ?>">
			<?php foreach ($footer_links as $slug => $label) : ?>
				<?php $page = get_page_by_path($slug); ?>
				<?php if ($page instanceof WP_Post) : ?>
					<a href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html($label); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url(home_url('/' . $slug . '/')); ?>"><?php echo esc_html($label); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
