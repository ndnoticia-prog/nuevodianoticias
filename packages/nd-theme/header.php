<?php
/**
 * Cabecera del documento HTML, común a todas las plantillas.
 *
 * @package NDTheme
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
		(function () {
			try {
				var stored = localStorage.getItem('nd-theme-color-scheme');
				var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
				if (stored === 'dark' || (stored !== 'light' && prefersDark)) {
					document.documentElement.setAttribute('data-theme', 'dark');
				}
			} catch (error) {
				// localStorage puede estar bloqueado (modo privado); degradar sin romper el render.
			}
		})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="nd-skip-link screen-reader-text" href="#nd-main-content"><?php esc_html_e('Saltar al contenido', 'nd-theme'); ?></a>
<?php get_template_part('template-parts/header/site-header'); ?>
<main id="nd-main-content" class="nd-site-main">
