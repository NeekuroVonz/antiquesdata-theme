<?php
/**
 * Template Name: Past sales catalog
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
<div class="container page-shell">
	<?php get_template_part('template-parts/catalog-listings'); ?>
</div>
<?php
get_footer();
