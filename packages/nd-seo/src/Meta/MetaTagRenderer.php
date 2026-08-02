<?php

declare(strict_types=1);

namespace NDSeo\Meta;

use NDSeo\Context\SeoContextResolver;

/**
 * Orquesta la salida de `<meta>`/`<link>` en `wp_head`: robots, canonical,
 * OpenGraph y Twitter Cards, a partir de un único {@see \NDSeo\Context\SeoContext}.
 */
final class MetaTagRenderer {

	public function __construct(
		private readonly SeoContextResolver $contextResolver,
		private readonly RobotsMetaBuilder $robots,
		private readonly OpenGraphBuilder $openGraph,
		private readonly TwitterCardBuilder $twitterCard,
	) {
	}

	public function render(): void {
		$context = $this->contextResolver->resolve();

		echo '<meta name="robots" content="' . esc_attr( $this->robots->build( $context ) ) . '">' . "\n";

		if ( $context->canonicalUrl !== '' ) {
			echo '<link rel="canonical" href="' . esc_url( $context->canonicalUrl ) . '">' . "\n";
		}

		foreach ( $this->openGraph->build( $context ) as $property => $content ) {
			echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
		}

		foreach ( $this->twitterCard->build( $context ) as $name => $content ) {
			echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $content ) . '">' . "\n";
		}
	}
}
