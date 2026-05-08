<?php
/**
 * Login page.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();

$redirect_to = isset($_GET['redirect_to']) ? wp_validate_redirect(wp_unslash($_GET['redirect_to']), home_url('/')) : home_url('/');
?>
<section class="panel auth-page static-page" style="max-width: 480px; margin: 1.5rem auto;">
	<h1 class="static-page-title"><?php esc_html_e('Log in', 'antiques-marketplace'); ?></h1>

	<?php if (!empty($_GET['login'])) : ?>
		<p class="auth-notice auth-notice--error" role="alert">
			<?php
			$code = sanitize_text_field(wp_unslash($_GET['login']));
			if ('failed' === $code) {
				esc_html_e('Invalid username or password.', 'antiques-marketplace');
			} elseif ('empty' === $code) {
				esc_html_e('Please enter your username and password.', 'antiques-marketplace');
			} else {
				esc_html_e('Could not log you in.', 'antiques-marketplace');
			}
			?>
		</p>
	<?php endif; ?>

	<form class="auth-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="antiques_theme_login">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
		<?php wp_nonce_field('antiques-login', 'antiques_login_nonce'); ?>

		<label for="user_login"><?php esc_html_e('Username or email', 'antiques-marketplace'); ?></label>
		<input type="text" name="log" id="user_login" required autocomplete="username" value="">

		<label for="user_pass"><?php esc_html_e('Password', 'antiques-marketplace'); ?></label>
		<input type="password" name="pwd" id="user_pass" required autocomplete="current-password" value="">

		<label class="auth-inline">
			<input type="checkbox" name="rememberme" value="forever">
			<?php esc_html_e('Remember me', 'antiques-marketplace'); ?>
		</label>

		<button class="btn" type="submit"><?php esc_html_e('Log in', 'antiques-marketplace'); ?></button>
	</form>

	<p class="auth-meta">
		<a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'antiques-marketplace'); ?></a>
		&middot;
		<a href="<?php echo esc_url(antiques_marketplace_page_link('register')); ?>"><?php esc_html_e('Create an account', 'antiques-marketplace'); ?></a>
	</p>
</section>
<?php
get_footer();
