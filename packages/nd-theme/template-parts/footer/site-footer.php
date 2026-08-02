<?php
/**
 * Pie visual del sitio: navegación secundaria y aviso de copyright.
 *
 * @package NDTheme
 */

?>
<footer class="nd-site-footer">
	<nav class="nd-site-footer__nav" aria-label="<?php esc_attr_e('Menú de pie de página', 'nd-theme'); ?>">
		<?php
		wp_nav_menu([
			'theme_location' => 'footer',
			'container' => false,
			'menu_class' => 'nd-site-footer__menu',
			'fallback_cb' => false,
		]);
		?>
	</nav>
	<p class="nd-site-footer__copy">
		&copy; <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?>.
		<?php esc_html_e('Todos los derechos reservados.', 'nd-theme'); ?>
	</p>
</footer>
