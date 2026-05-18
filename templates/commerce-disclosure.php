<?php
/**
 * Commerce disclosure page.
 *
 * @package AntiquesMarketplace
 */
if (!defined('ABSPATH')) {
	exit;
}
get_header();
?>
<section class="panel legal-page" id="legal-page">
	<div class="container legal-prose">
		<div class="legal-prose-inner">
			<?php antiques_marketplace_render_legal_body('commerce'); ?>
		</div>
	</div>
</section>
<?php
get_footer();
