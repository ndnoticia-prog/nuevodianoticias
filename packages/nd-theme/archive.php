<?php
/**
 * Archivo genérico. WordPress usa esta plantilla como respaldo para
 * categorías, etiquetas, autores y fechas cuando no existen category.php /
 * tag.php / author.php dedicados. `the_archive_title()` y
 * `the_archive_description()` ya adaptan su salida automáticamente a cada
 * uno de esos contextos, así que crear cuatro archivos casi idénticos solo
 * duplicaría código sin aportar nada.
 *
 * @package NDTheme
 */

use NDSeo\Breadcrumbs\BreadcrumbRenderer;

get_header();
?>
<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BreadcrumbRenderer ya escapa cada valor internamente.
echo nd_app(BreadcrumbRenderer::class)->render();
?>
<header class="nd-archive__header">
	<h1 class="nd-archive__title"><?php the_archive_title(); ?></h1>

	<?php if (is_author()) : ?>
		<div class="nd-archive__author">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() ya devuelve HTML escapado por WordPress core.
			echo get_avatar(get_queried_object_id(), 96);
			?>
		</div>
	<?php endif; ?>

	<?php the_archive_description('<div class="nd-archive__description">', '</div>'); ?>
</header>

<?php get_template_part('template-parts/content/post-grid'); ?>

<?php get_footer(); ?>
