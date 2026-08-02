<?php

declare(strict_types=1);

namespace NDAnalytics\Tracking;

use NDCore\Database\DatabaseManager;
use WP_Post;

/**
 * Registra una visita del lado del servidor (sin necesidad de JS ni de
 * depender de que el visitante no tenga bloqueadores de anuncios/rastreo
 * activos). Se ejecuta en `wp` (una vez resuelta la consulta principal),
 * no en `wp_head`, para tener ya disponible el objeto consultado.
 */
final class PageviewRecorder {

	private const TABLE = 'analytics_pageviews';

	public function __construct(
		private readonly DatabaseManager $db,
		private readonly VisitorHasher $hasher,
	) {
	}

	public function recordForCurrentRequest(): void {
		if ( ! is_singular( 'post' ) || current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post = get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$ip        = $this->clientIp();
		$userAgent = $this->requestHeader( 'HTTP_USER_AGENT' );
		$referrer  = $this->requestHeader( 'HTTP_REFERER' );

		$this->db->insert(
			$this->db->table( self::TABLE ),
			array(
				'post_id'      => $post->ID,
				'url'          => (string) get_permalink( $post ),
				'referrer'     => $referrer !== '' ? esc_url_raw( $referrer ) : '',
				'visitor_hash' => $this->hasher->hash( $ip, $userAgent ),
				'viewed_at'    => current_time( 'mysql', true ),
			),
			array(
				'post_id'      => '%d',
				'url'          => '%s',
				'referrer'     => '%s',
				'visitor_hash' => '%s',
				'viewed_at'    => '%s',
			)
		);
	}

	private function clientIp(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		return is_string( $ip ) ? sanitize_text_field( wp_unslash( $ip ) ) : '';
	}

	private function requestHeader( string $key ): string {
		$value = $_SERVER[ $key ] ?? '';

		return is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : '';
	}
}
