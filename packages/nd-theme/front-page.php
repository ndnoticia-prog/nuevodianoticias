<?php
/**
 * Portada: compone los bloques del homepage (breaking, hero, noticias) a
 * través del motor de renderizado de nd-builder.
 *
 * @package NDTheme
 */

use NDBuilder\Renderer;
use NDTheme\Content\HomeContentProvider;

get_header();

/** @var HomeContentProvider $homeContent */
$homeContent = nd_app(HomeContentProvider::class);

/** @var Renderer $renderer */
$renderer = nd_app(Renderer::class);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML ya escapado por cada template-part de bloque.
echo $renderer->renderMany($homeContent->blocksForHomepage());

get_footer();
