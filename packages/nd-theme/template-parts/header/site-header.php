<?php
/**
 * Cabecera visual del sitio: marca, navegación principal y selector de tema.
 *
 * @package NDTheme
 */

?>
<header class="nd-site-header">
	<div class="nd-site-header__bar">
		<div class="nd-site-header__brand">
			<?php if (has_custom_logo()) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="nd-site-header__title" href="<?php echo esc_url(home_url('/')); ?>">
					<?php bloginfo('name'); ?>
				</a>
			<?php endif; ?>
		</div>

		<nav class="nd-site-header__nav" aria-label="<?php esc_attr_e('Menú principal', 'nd-theme'); ?>">
			<?php
			wp_nav_menu([
				'theme_location' => 'primary',
				'container' => false,
				'menu_class' => 'nd-site-header__menu',
				'fallback_cb' => false,
			]);
			?>
		</nav>

		<button
			type="button"
			class="nd-theme-toggle"
			data-nd-theme-toggle
			aria-label="<?php esc_attr_e('Cambiar entre modo claro y oscuro', 'nd-theme'); ?>"
		>
			<span aria-hidden="true">🌓</span>
		</button>
	</div>
</header>
