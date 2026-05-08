<?php
/**
 * Registration page.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
<section class="panel auth-page static-page" style="max-width: 480px; margin: 1.5rem auto;">
	<h1 class="static-page-title"><?php esc_html_e('Create account', 'antiques-marketplace'); ?></h1>
	<p class="legal-muted"><?php esc_html_e('Registration is required. After signing up, complete a one-time payment to unlock all listings.', 'antiques-marketplace'); ?></p>

	<?php if (!empty($_GET['register'])) : ?>
		<p class="auth-notice auth-notice--error" role="alert">
			<?php
			$code = sanitize_text_field(wp_unslash($_GET['register']));
			switch ($code) {
				case 'empty':
					esc_html_e('Please fill in all fields.', 'antiques-marketplace');
					break;
				case 'invalid_email':
					esc_html_e('Enter a valid email address.', 'antiques-marketplace');
					break;
				case 'mismatch':
					esc_html_e('Passwords do not match.', 'antiques-marketplace');
					break;
				case 'weak':
					esc_html_e('Use at least 8 characters for your password.', 'antiques-marketplace');
					break;
				default:
					esc_html_e('Could not create your account. The username or email may already be in use.', 'antiques-marketplace');
			}
			?>
		</p>
	<?php endif; ?>

	<form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="antiques_theme_register">
		<?php wp_nonce_field('antiques-register', 'antiques_register_nonce'); ?>

		<label for="user_login"><?php esc_html_e('Username', 'antiques-marketplace'); ?></label>
		<input type="text" name="user_login" id="user_login" required autocomplete="username" maxlength="60" value="">

		<label for="user_email"><?php esc_html_e('Email', 'antiques-marketplace'); ?></label>
		<input type="email" name="user_email" id="user_email" required autocomplete="email" value="">

		<label for="user_pass"><?php esc_html_e('Password', 'antiques-marketplace'); ?></label>
		<input type="password" name="user_pass" id="user_pass" required autocomplete="new-password" minlength="8" value="">

		<label for="user_pass2"><?php esc_html_e('Confirm password', 'antiques-marketplace'); ?></label>
		<input type="password" name="user_pass2" id="user_pass2" required autocomplete="new-password" minlength="8" value="">

		<button class="btn" type="submit"><?php esc_html_e('Register', 'antiques-marketplace'); ?></button>
	</form>

	<p class="auth-meta">
		<a href="<?php echo esc_url(antiques_marketplace_page_link('login')); ?>"><?php esc_html_e('Already have an account? Log in', 'antiques-marketplace'); ?></a>
	</p>
</section>
<?php
get_footer();
