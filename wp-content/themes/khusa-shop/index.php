<?php
/**
 * Main template fallback.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}

get_header();
?>

<div class="container py-5">
	<?php if (have_posts()) : ?>
		<?php while (have_posts()) : ?>
			<?php the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('mb-5'); ?>>
				<h1 class="h2"><?php the_title(); ?></h1>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e('No content found.', 'khusa-shop'); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
