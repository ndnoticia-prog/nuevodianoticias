<?php
/**
 * Resultados de búsqueda.
 *
 * @package NDTheme
 */

get_header();
?>
<header class="nd-archive__header">
	<h1 class="nd-archive__title">
		<?php
		printf(
			/* translators: %s: término buscado, ya envuelto en <span> */
			esc_html__('Resultados de búsqueda para: %s', 'nd-theme'),
			'<span>' . esc_html(get_search_query()) . '</span>'
		);
		?>
	</h1>
</header>

<?php get_template_part('template-parts/content/post-grid'); ?>

<?php get_footer(); ?>
