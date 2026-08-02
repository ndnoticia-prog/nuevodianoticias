<?php
/**
 * Bloque "Noticias": cuadrícula de últimas noticias.
 *
 * @package NDTheme
 * @var NDBuilder\Block $block
 */

$items = $block->attribute('items', []);

if (! is_array($items) || $items === []) {
	return;
}
?>
<section class="nd-block nd-block--noticias" aria-label="<?php esc_attr_e('Últimas noticias', 'nd-theme'); ?>">
	<h2 class="nd-block-noticias__title"><?php esc_html_e('Últimas noticias', 'nd-theme'); ?></h2>

	<div class="nd-block-noticias__grid">
		<?php foreach ($items as $item) : ?>
			<article class="nd-block-noticias__item">
				<?php if (! empty($item['thumbnail'])) : ?>
					<a class="nd-block-noticias__media" href="<?php echo esc_url((string) $item['permalink']); ?>">
						<img
							src="<?php echo esc_url((string) $item['thumbnail']); ?>"
							alt="<?php echo esc_attr((string) $item['title']); ?>"
							loading="lazy"
						>
					</a>
				<?php endif; ?>

				<h3 class="nd-block-noticias__item-title">
					<a href="<?php echo esc_url((string) $item['permalink']); ?>">
						<?php echo esc_html((string) $item['title']); ?>
					</a>
				</h3>

				<p class="nd-block-noticias__item-meta">
					<?php echo esc_html((string) $item['author']); ?> · <?php echo esc_html((string) $item['date']); ?>
				</p>
			</article>
		<?php endforeach; ?>
	</div>
</section>
