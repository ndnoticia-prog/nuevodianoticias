<?php
/**
 * Comentarios de lectores (nativos de WordPress, no confundir con los
 * comentarios internos editoriales de nd-workflow — ver docs/Architecture.md,
 * "nd-workflow: qué es nuevo y qué reutiliza de WordPress core"). Sin este
 * archivo, `comments_template()` (llamado desde single.php cuando los
 * comentarios están abiertos) recurre al renderizado heredado de WordPress
 * y emite un aviso de obsolescencia en cada artículo.
 *
 * @package NDTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>

<div class="nd-comments" id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="nd-comments__title">
			<?php
			printf(
				/* translators: %s: número de comentarios */
				esc_html( _n( '%s comentario', '%s comentarios', get_comments_number(), 'nd-theme' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="nd-comments__list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( 'Anteriores', 'nd-theme' ),
				'next_text' => esc_html__( 'Siguientes', 'nd-theme' ),
			)
		);
		?>
	<?php endif; ?>

	<?php if ( comments_open() ) : ?>
		<?php
		// `title_reply_before`/`title_reply_after` (no un argumento de
		// etiqueta aparte) es la única forma que ofrece comment_form() de
		// cambiar el <h3> que trae por defecto — aquí a <h2>, para no saltar
		// de <h1> directo a <h3> cuando el artículo todavía no tiene
		// comentarios (el único <h2> anterior, el de la lista, no se
		// imprime en ese caso).
		comment_form(
			array(
				'title_reply_before' => '<h2 id="reply-title" class="nd-comments__reply-title">',
				'title_reply_after'  => '</h2>',
			)
		);
		?>
	<?php elseif ( have_comments() ) : ?>
		<p class="nd-comments__closed"><?php esc_html_e( 'Los comentarios están cerrados.', 'nd-theme' ); ?></p>
	<?php endif; ?>
</div>
