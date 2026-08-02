<?php

declare(strict_types=1);

namespace NDAnalytics\Tracking;

/**
 * Deriva un identificador de visitante sin almacenar IP ni user agent en
 * crudo (privacidad): usa `wp_hash()` (HMAC con las claves secretas de esta
 * instalación de WordPress) sobre IP + user agent + fecha del día, de forma
 * que el hash rota cada día y no permite reconstruir la IP original ni
 * correlacionar a una persona más allá de una jornada.
 */
final class VisitorHasher {

	public function hash( string $ipAddress, string $userAgent ): string {
		return wp_hash( $ipAddress . '|' . $userAgent . '|' . gmdate( 'Y-m-d' ) );
	}
}
