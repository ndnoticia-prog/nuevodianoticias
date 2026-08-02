<?php

declare(strict_types=1);

namespace NDSeo\Schema;

use NDSeo\Context\SeoContextResolver;
use NDSeo\Schema\Contracts\SchemaProvider;

/**
 * Recoge todos los {@see SchemaProvider} aplicables al contexto actual y los
 * imprime como un único bloque `<script type="application/ld+json">` con un
 * `@graph`, en lugar de un `<script>` por cada tipo de dato estructurado.
 */
final class SchemaOutput {

	/**
	 * `JSON_HEX_TAG` es obligatorio aquí: sin él, un título de artículo que
	 * contuviera literalmente "</script>" cerraría el bloque JSON-LD e
	 * inyectaría HTML/JS arbitrario en la página.
	 */
	private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;

	/**
	 * @param list<SchemaProvider> $providers
	 */
	public function __construct(
		private readonly SeoContextResolver $contextResolver,
		private readonly array $providers,
	) {
	}

	public function render(): void {
		$context = $this->contextResolver->resolve();
		$graph   = array();

		foreach ( $this->providers as $provider ) {
			if ( ! $provider->supports( $context ) ) {
				continue;
			}

			$graph[] = $provider->build( $context );
		}

		if ( $graph === array() ) {
			return;
		}

		$document = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);

		$json = wp_json_encode( $document, self::JSON_FLAGS );

		if ( ! is_string( $json ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD ya va codificado con JSON_HEX_TAG|JSON_HEX_AMP (ver docblock de self::JSON_FLAGS); esc_html() rompería el JSON.
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}
}
