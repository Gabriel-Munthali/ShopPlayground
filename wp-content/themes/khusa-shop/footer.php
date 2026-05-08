<?php
/**
 * Theme footer.
 *
 * @package Khusa_Shop
 */

if (! defined('ABSPATH')) {
	exit;
}
?>

</main>

<footer class="khusa-site-footer">
	<div class="container khusa-footer-grid">
		<section class="khusa-footer-signup">
			<h2><?php esc_html_e('Receive an exclusive 20% discount code when you signup.', 'khusa-shop'); ?></h2>
			<form class="khusa-signup-form" action="#" method="post">
				<input type="email" placeholder="<?php esc_attr_e('Enter your email', 'khusa-shop'); ?>" aria-label="<?php esc_attr_e('Email address', 'khusa-shop'); ?>">
				<button type="submit"><?php esc_html_e('Subscribe', 'khusa-shop'); ?></button>
			</form>
		</section>
		<section>
			<h3><?php esc_html_e('Company', 'khusa-shop'); ?></h3>
			<ul>
				<li><a href="#"><?php esc_html_e('About Us', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Blog', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Careers', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Locations', 'khusa-shop'); ?></a></li>
			</ul>
		</section>
		<section>
			<h3><?php esc_html_e('Customer Care', 'khusa-shop'); ?></h3>
			<ul>
				<li><a href="#"><?php esc_html_e('Size Guide', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Help & FAQs', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Return My Order', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Refer a Friend', 'khusa-shop'); ?></a></li>
			</ul>
		</section>
		<section>
			<h3><?php esc_html_e('Terms & Policies', 'khusa-shop'); ?></h3>
			<ul>
				<li><a href="#"><?php esc_html_e('Duties & Taxes', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Shipping Info', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Privacy Policy', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Terms and Conditions', 'khusa-shop'); ?></a></li>
			</ul>
		</section>
		<section>
			<h3><?php esc_html_e('Follow Us', 'khusa-shop'); ?></h3>
			<ul>
				<li><a href="#"><?php esc_html_e('Instagram', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Facebook', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('Pinterest', 'khusa-shop'); ?></a></li>
				<li><a href="#"><?php esc_html_e('TikTok', 'khusa-shop'); ?></a></li>
			</ul>
		</section>
	</div>
	<div class="container khusa-footer-bottom">
		<p><?php bloginfo('name'); ?> <span><?php echo esc_html(gmdate('Y')); ?>. <?php esc_html_e('All rights reserved', 'khusa-shop'); ?></span></p>
		<div class="khusa-footer-meta">
			<span>VISA</span>
			<span>MC</span>
			<span>AMEX</span>
			<span>PAY</span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
