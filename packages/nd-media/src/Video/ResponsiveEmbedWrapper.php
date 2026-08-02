<?php

declare(strict_types=1);

namespace NDMedia\Video;

/**
 * Envuelve los `<iframe>` de oEmbed (YouTube, Vimeo, ...) en un contenedor
 * con proporción de aspecto fija, para que sean responsive sin depender de
 * los atributos `width`/`height` fijos que devuelve el proveedor. El estilo
 * de `.nd-video-embed` vive en nd-theme (presentación).
 */
final class ResponsiveEmbedWrapper {

	public function wrap( string $html ): string {
		if ( $html === '' || ! str_contains( $html, '<iframe' ) ) {
			return $html;
		}

		return '<div class="nd-video-embed">' . $html . '</div>';
	}
}
