<?php
/**
 * Theme header.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

$current_lang = antiques_marketplace_get_lang();
$trial_url    = antiques_marketplace_trial_url();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#content"><?php esc_html_e('Skip to content', 'antiques-marketplace'); ?></a>

<header class="site-header">
	<div class="header-bar">
		<a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
			<span class="brand-mark" aria-hidden="true">
				<svg viewBox="0 0 36 36" width="32" height="32">
					<circle cx="18" cy="18" r="16.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
					<circle cx="18" cy="18" r="11.5" fill="none" stroke="currentColor" stroke-width="0.7" opacity="0.55"/>
					<path d="M11 22.5c2.2-6.4 5-10.6 7-13.2 2 2.6 4.8 6.8 7 13.2" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
					<path d="M13.2 19.2h9.6" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>
				</svg>
			</span>
			<span class="brand-name">Antique Data Japan</span>
		</a>

		<nav id="site-nav" class="header-nav" aria-label="<?php esc_attr_e('Primary', 'antiques-marketplace'); ?>">
			<a class="<?php echo is_front_page() ? 'is-current' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'antiques-marketplace'); ?></a>
			<a href="<?php echo esc_url(antiques_marketplace_landing_hash('features')); ?>"><?php esc_html_e('Service features', 'antiques-marketplace'); ?></a>
			<a class="<?php echo is_page('data') ? 'is-current' : ''; ?>" href="<?php echo esc_url(antiques_marketplace_listings_url()); ?>"><?php esc_html_e('Data coverage', 'antiques-marketplace'); ?></a>
			<a class="<?php echo is_page('market-trends') ? 'is-current' : ''; ?>" href="<?php echo esc_url(antiques_marketplace_page_link('market-trends')); ?>"><?php esc_html_e('Trend Dashboard', 'antiques-marketplace'); ?></a>
			<a href="<?php echo esc_url(antiques_marketplace_landing_hash('audience')); ?>"><?php esc_html_e('Use cases', 'antiques-marketplace'); ?></a>
			<a href="<?php echo esc_url(antiques_marketplace_landing_hash('pricing')); ?>"><?php esc_html_e('Pricing', 'antiques-marketplace'); ?></a>
			<a href="<?php echo esc_url(antiques_marketplace_landing_hash('faq')); ?>"><?php esc_html_e('FAQ', 'antiques-marketplace'); ?></a>
			<a href="<?php echo esc_url(antiques_marketplace_landing_hash('contact')); ?>"><?php esc_html_e('Contact', 'antiques-marketplace'); ?></a>
		</nav>

		<div class="header-tools">
			<div class="lang-switcher" role="navigation" aria-label="<?php esc_attr_e('Language', 'antiques-marketplace'); ?>">
				<a class="lang-switcher-link <?php echo 'en' === $current_lang ? 'is-active' : ''; ?>" href="<?php echo esc_url(antiques_marketplace_lang_switch_url('en')); ?>"><?php esc_html_e('EN', 'antiques-marketplace'); ?></a>
				<a class="lang-switcher-link <?php echo 'ja' === $current_lang ? 'is-active' : ''; ?>" href="<?php echo esc_url(antiques_marketplace_lang_switch_url('ja')); ?>"><?php esc_html_e('JA', 'antiques-marketplace'); ?></a>
			</div>
			<nav class="header-account" aria-label="<?php esc_attr_e('Account', 'antiques-marketplace'); ?>">
				<?php if (is_user_logged_in()) : ?>
					<?php
					$logout_url = wp_nonce_url(
						admin_url('admin-post.php?action=antiques_theme_logout'),
						'antiques-logout',
						'antiques_logout_nonce'
					);
					$current    = wp_get_current_user();
					$label_name = $current->display_name ? $current->display_name : $current->user_login;
					?>
					<?php if (! antiques_marketplace_user_has_membership() && ! antiques_marketplace_bypass_membership()) : ?>
						<a class="header-account-link" href="<?php echo esc_url(antiques_marketplace_page_link('subscribe')); ?>"><?php esc_html_e('Subscribe', 'antiques-marketplace'); ?></a>
					<?php endif; ?>
					<span class="header-account-user"><?php echo esc_html($label_name); ?></span>
					<a class="header-account-link" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Log out', 'antiques-marketplace'); ?></a>
				<?php else : ?>
					<a class="header-account-link" href="<?php echo esc_url(antiques_marketplace_page_link('login')); ?>"><?php esc_html_e('Log in', 'antiques-marketplace'); ?></a>
				<?php endif; ?>
			</nav>
			<a class="btn btn-gold header-cta" href="<?php echo esc_url($trial_url); ?>"><?php esc_html_e('7-day free trial', 'antiques-marketplace'); ?></a>
			<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
				<span class="nav-toggle__bar"></span>
				<span class="sr-only"><?php esc_html_e('Open menu', 'antiques-marketplace'); ?></span>
			</button>
		</div>
	</div>
</header>

<main id="content" class="site-main<?php echo is_front_page() ? '' : ' site-main--contained'; ?>">
