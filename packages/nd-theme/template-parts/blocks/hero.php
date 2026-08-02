<?php
/**
 * Bloque "Hero": destaca el artículo principal de portada.
 *
 * @package NDTheme
 * @var NDBuilder\Block $block
 */

if (! $block->attribute('post_id')) {
	return;
}

$thumbnail = $block->attribute('thumbnail');
$permalink = (string) $block->attribute('permalink');
$title = (string) $block->attribute('title');
?>
<section class="nd-block nd-block--hero" aria-label="<?php esc_attr_e('Titular principal', 'nd-theme'); ?>">
	<?php if ($thumbnail) : ?>
		<a class="nd-block-hero__media" href="<?php echo esc_url($permalink); ?>">
			<img
				src="<?php echo esc_url((string) $thumbnail); ?>"
				alt="<?php echo esc_attr($title); ?>"
				loading="eager"
			>
		</a>
	<?php endif; ?>

	<div class="nd-block-hero__body">
		<h2 class="nd-block-hero__title">
			<a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
		</h2>

		<?php if ($block->attribute('excerpt')) : ?>
			<p class="nd-block-hero__excerpt"><?php echo esc_html((string) $block->attribute('excerpt')); ?></p>
		<?php endif; ?>

		<p class="nd-block-hero__meta">
			<?php
			printf(
				/* translators: 1: nombre del autor, 2: fecha de publicación */
				esc_html__('Por %1$s · %2$s', 'nd-theme'),
				esc_html((string) $block->attribute('author')),
				esc_html((string) $block->attribute('date'))
			);
			?>
		</p>
	</div>
</section>
