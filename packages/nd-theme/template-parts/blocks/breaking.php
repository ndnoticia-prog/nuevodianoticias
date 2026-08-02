<?php
/**
 * Bloque "Breaking": barra de última hora.
 *
 * @package NDTheme
 * @var NDBuilder\Block $block
 */

$items = $block->attribute('items', []);

if (! is_array($items) || $items === []) {
	return;
}
?>
<div class="nd-block nd-block--breaking" role="region" aria-label="<?php esc_attr_e('Última hora', 'nd-theme'); ?>">
	<span class="nd-block-breaking__label"><?php esc_html_e('Última hora', 'nd-theme'); ?></span>
	<ul class="nd-block-breaking__list">
		<?php foreach ($items as $item) : ?>
			<li class="nd-block-breaking__item">
				<a href="<?php echo esc_url((string) $item['permalink']); ?>">
					<?php echo esc_html((string) $item['title']); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
