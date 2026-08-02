<?php
/**
 * Cuadrícula reutilizable de artículos, compartida por archive.php,
 * search.php, home.php e index.php para no duplicar el mismo bucle y
 * paginación en cada una.
 *
 * @package NDTheme
 */

?>
<div class="nd-post-grid">
	<?php if (have_posts()) : ?>
		<?php
		while (have_posts()) :
			the_post();
			get_template_part('template-parts/content/entry-summary');
		endwhile;
		?>
	<?php else : ?>
		<p class="nd-post-grid__empty"><?php esc_html_e('No se encontraron artículos.', 'nd-theme'); ?></p>
	<?php endif; ?>
</div>

<?php
the_posts_pagination([
	'prev_text' => esc_html__('Anterior', 'nd-theme'),
	'next_text' => esc_html__('Siguiente', 'nd-theme'),
]);
?>
