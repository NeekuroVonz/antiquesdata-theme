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
	</div>
</header>

<nav class="category-nav">
	<div class="container">
		<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'antiques-marketplace'); ?></a>
	</div>
</nav>

<main class="container">
