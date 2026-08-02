<?php

declare(strict_types=1);

namespace NDMedia\Optimization;

use NDCore\Config\Config;

/**
 * Hace que WordPress genere los tamaños intermedios de las imágenes subidas
 * (JPEG/PNG) en un formato moderno (WebP/AVIF) usando el filtro nativo
 * `image_editor_output_format`, sin depender de un servicio externo.
 */
final class ModernFormatConverter {

	public function __construct( private readonly Config $config ) {
	}

	/**
	 * @param array<string, string> $formats
	 *
	 * @return array<string, string>
	 */
	public function filterOutputFormat( array $formats ): array {
		$targetMime = $this->preferredMime();

		if ( $targetMime === null ) {
			return $formats;
		}

		$formats['image/jpeg'] = $targetMime;
		$formats['image/png']  = $targetMime;

		return $formats;
	}

	private function preferredMime(): ?string {
		$format = $this->config->get( 'media.modern_format' );

		if ( $format === 'avif' ) {
			return $this->supportsAvif() ? 'image/avif' : ( $this->supportsWebp() ? 'image/webp' : null );
		}

		if ( $format === 'webp' ) {
			return $this->supportsWebp() ? 'image/webp' : null;
		}

		return null;
	}

	private function supportsAvif(): bool {
		return function_exists( 'imageavif' );
	}

	private function supportsWebp(): bool {
		return function_exists( 'imagewebp' );
	}
}
