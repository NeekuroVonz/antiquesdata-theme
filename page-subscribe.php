<?php
/**
 * Subscription checkout page ($20 one-time via Stripe Checkout).
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();

$stripe        = antiques_marketplace_stripe_config();
$price_cents   = antiques_marketplace_subscription_amount_cents();
$price_display = '$' . number_format_i18n($price_cents / 100, 2);
$has_access    = antiques_marketplace_user_has_membership();
$bypass_sub    = antiques_marketplace_bypass_subscription_only();
$full_bypass   = antiques_marketplace_bypass_membership();
?>
<section class="panel auth-page static-page" style="max-width: 520px; margin: 1.5rem auto;">
	<h1 class="static-page-title"><?php esc_html_e('Subscribe', 'antiques-marketplace'); ?></h1>

	<?php if ($full_bypass || $bypass_sub) : ?>
		<p class="legal-muted"><?php esc_html_e('Membership payment is bypassed in this environment (see .env).', 'antiques-marketplace'); ?></p>
		<p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Continue to site', 'antiques-marketplace'); ?></a></p>
	<?php elseif (!is_user_logged_in()) : ?>
		<p><?php esc_html_e('Log in or create an account to complete payment and unlock all listings.', 'antiques-marketplace'); ?></p>
		<p>
			<a class="btn" href="<?php echo esc_url(antiques_marketplace_page_link('login')); ?>"><?php esc_html_e('Log in', 'antiques-marketplace'); ?></a>
			<a class="btn" href="<?php echo esc_url(antiques_marketplace_page_link('register')); ?>" style="margin-left: 0.5rem; background: #64748b;"><?php esc_html_e('Register', 'antiques-marketplace'); ?></a>
		</p>
	<?php elseif ($has_access) : ?>
		<p><?php esc_html_e('Your subscription is active. You have full access.', 'antiques-marketplace'); ?></p>
		<?php if (!empty($_GET['welcome'])) : ?>
			<p class="auth-notice auth-notice--ok"><?php esc_html_e('Thank you! Your payment was successful.', 'antiques-marketplace'); ?></p>
		<?php endif; ?>
		<p><a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Browse listings', 'antiques-marketplace'); ?></a></p>
	<?php else : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: formatted price e.g. $20.00 */
					__('Unlock every listing on the site with a one-time payment of %s.', 'antiques-marketplace'),
					$price_display
				)
			);
			?>
		</p>

		<?php if (!empty($_GET['checkout'])) : ?>
			<?php
			$st = sanitize_text_field(wp_unslash($_GET['checkout']));
			if ('cancel' === $st) :
				?>
				<p class="auth-notice" role="status"><?php esc_html_e('Checkout was cancelled.', 'antiques-marketplace'); ?></p>
			<?php elseif ('invalid_request' === $st) : ?>
				<p class="auth-notice auth-notice--error" role="alert"><?php esc_html_e('Checkout session expired. Please try again.', 'antiques-marketplace'); ?></p>
			<?php elseif ('stripe_not_configured' === $st) : ?>
				<p class="auth-notice auth-notice--error" role="alert"><?php esc_html_e('Stripe is not configured. Add STRIPE_SECRET_KEY to .env.', 'antiques-marketplace'); ?></p>
			<?php elseif ('stripe_error' === $st) : ?>
				<p class="auth-notice auth-notice--error" role="alert"><?php esc_html_e('Payment could not be started. Check Stripe keys in .env.', 'antiques-marketplace'); ?></p>
				<?php
				$checkout_error = antiques_marketplace_pop_checkout_error();
				if ('' !== $checkout_error) :
					?>
					<p class="auth-notice auth-notice--error" role="alert"><?php echo esc_html($checkout_error); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ('' === $stripe['secret_key']) : ?>
			<p class="auth-notice auth-notice--error"><?php esc_html_e('Stripe secret key is missing. Add STRIPE_SECRET_KEY to your .env file.', 'antiques-marketplace'); ?></p>
		<?php else : ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="antiques_start_checkout">
				<?php wp_nonce_field('antiques-checkout', 'antiques_checkout_nonce'); ?>
				<button class="btn" type="submit"><?php echo esc_html(sprintf(__('Pay %s with Stripe', 'antiques-marketplace'), $price_display)); ?></button>
			</form>
		<?php endif; ?>
	<?php endif; ?>
</section>
<?php
get_footer();
