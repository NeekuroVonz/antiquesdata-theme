<?php
/**
 * Theme header.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
			Antiques<span>Data</span>
		</a>
		<nav class="header-account" aria-label="<?php esc_attr_e('Account', 'antiques-marketplace'); ?>">
			<?php if (is_user_logged_in()) : ?>
				<?php
				$logout_url = wp_nonce_url(
					admin_url('admin-post.php?action=antiques_theme_logout'),
					'antiques-logout',
					'antiques_logout_nonce'
				);
				?>
				<?php if (! antiques_marketplace_user_has_membership() && ! antiques_marketplace_bypass_membership()) : ?>
					<a class="header-account-link header-account-link--accent" href="<?php echo esc_url(antiques_marketplace_page_link('subscribe')); ?>"><?php esc_html_e('Subscribe', 'antiques-marketplace'); ?></a>
				<?php endif; ?>
				<?php
				$current     = wp_get_current_user();
				$label_name  = $current->display_name ? $current->display_name : $current->user_login;
				?>
				<span class="header-account-user"><?php echo esc_html($label_name); ?></span>
				<a class="header-account-link" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Log out', 'antiques-marketplace'); ?></a>
			<?php else : ?>
				<a class="header-account-link" href="<?php echo esc_url(antiques_marketplace_page_link('login')); ?>"><?php esc_html_e('Log in', 'antiques-marketplace'); ?></a>
				<a class="header-account-link header-account-link--accent" href="<?php echo esc_url(antiques_marketplace_page_link('register')); ?>"><?php esc_html_e('Register', 'antiques-marketplace'); ?></a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<nav class="category-nav">
	<div class="container">
		<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'antiques-marketplace'); ?></a>
	</div>
</nav>

<main class="container">
