<?php
/**
 * Default page template.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

<?php if (have_posts()) : ?>
	<?php while (have_posts()) : the_post(); ?>
		<article class="panel static-page" style="margin-top: 1rem;">
			<h1 class="static-page-title"><?php the_title(); ?></h1>
			<div class="static-page-content">
				<?php the_content(); ?>
			</div>
		</article>
	<?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
