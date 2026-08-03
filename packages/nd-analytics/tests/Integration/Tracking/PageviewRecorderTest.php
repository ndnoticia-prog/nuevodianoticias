<?php

declare(strict_types=1);

namespace NDAnalytics\Tests\Integration\Tracking;

use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba PageviewRecorder contra un $wpdb/MySQL reales y una petición
 * WordPress real (`$this->go_to()` + `wp_set_current_user()`): la regla de
 * negocio de que un editor viendo su propio artículo no genera pageview
 * (para no inflar las métricas con el propio tráfico de redacción) depende
 * de `current_user_can()` e `is_singular()`, ninguna simulable de forma
 * fiable con Brain Monkey. Verificado manualmente en navegador durante esta
 * sesión: un administrador visitando su propio post no generó fila, una
 * petición anónima sí.
 *
 * No se instancia PageviewRecorder ni se llama a
 * recordForCurrentRequest() a mano: `$this->go_to()` dispara la acción
 * `wp` de WordPress a través de `WP::main()`, y
 * AnalyticsServiceProvider::boot() ya engancha
 * `recordForCurrentRequest()` a esa misma acción sobre el contenedor real
 * (nd-core está cargado como plugin activo en este bootstrap). Llamar al
 * método también a mano duplicaría la fila insertada; dejar que sea el
 * propio hook quien la registre es, además, exactamente el flujo real que
 * se verificó a mano en el navegador.
 */
final class PageviewRecorderTest extends WP_UnitTestCase {

	private function pageviewsForPost( int $postId ): array {
		$db = new DatabaseManager();

		return $db->select(
			'SELECT * FROM ' . $db->table( 'analytics_pageviews' ) . ' WHERE post_id = %d',
			array( $postId )
		);
	}

	public function test_visiting_a_post_as_an_editor_does_not_record_a_pageview(): void {
		$post  = self::factory()->post->create();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin );
		$this->go_to( (string) get_permalink( $post ) );

		self::assertSame( array(), $this->pageviewsForPost( $post ) );
	}

	public function test_visiting_a_post_as_an_anonymous_visitor_records_a_pageview(): void {
		$post = self::factory()->post->create();

		wp_set_current_user( 0 );
		$this->go_to( (string) get_permalink( $post ) );

		$rows = $this->pageviewsForPost( $post );

		self::assertCount( 1, $rows );
		self::assertSame( (string) $post, $rows[0]['post_id'] );
	}

	public function test_visiting_a_non_singular_page_does_not_record_a_pageview(): void {
		$db = new DatabaseManager();

		wp_set_current_user( 0 );

		$before = $db->selectOne( 'SELECT COUNT(*) AS total FROM ' . $db->table( 'analytics_pageviews' ) );

		$this->go_to( home_url( '/' ) );

		$after = $db->selectOne( 'SELECT COUNT(*) AS total FROM ' . $db->table( 'analytics_pageviews' ) );

		self::assertSame( $before['total'], $after['total'] );
	}
}
