<?php
/**
 * Índice de entradas cuando la portada es una página estática y hay una
 * "página de entradas" separada configurada en Ajustes > Lectura.
 *
 * @package NDTheme
 */

get_header();
?>
<header class="nd-archive__header">
	<h1 class="nd-archive__title"><?php single_post_title(); ?></h1>
</header>

<?php get_template_part('template-parts/content/post-grid'); ?>

<?php get_footer(); ?>
