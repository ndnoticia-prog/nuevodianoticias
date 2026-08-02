<?php
/**
 * Tarjeta resumen de un artículo, usada en listados (archivo, búsqueda,
 * índice de entradas).
 *
 * @package NDTheme
 */

?>
<article <?php post_class('nd-entry-summary'); ?>>
	<?php if (has_post_thumbnail()) : ?>
		<a class="nd-entry-summary__media" href="<?php the_permalink(); ?>">
			<?php the_post_thumbnail('medium_large'); ?>
		</a>
	<?php endif; ?>

	<div class="nd-entry-summary__body">
		<h2 class="nd-entry-summary__title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="nd-entry-summary__meta">
			<?php
			printf(
				/* translators: 1: nombre del autor, 2: fecha de publicación */
				esc_html__('Por %1$s · %2$s', 'nd-theme'),
				esc_html(get_the_author()),
				esc_html(get_the_date())
			);
			?>
		</p>

		<div class="nd-entry-summary__excerpt">
			<?php the_excerpt(); ?>
		</div>
	</div>
</article>
