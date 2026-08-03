<?php

declare(strict_types=1);

namespace NDSeo\Tests\Integration\Sitemap;

use NDCore\Config\Config;
use NDSeo\Sitemap\NewsSitemapController;
use ReflectionMethod;
use WP_UnitTestCase;

/**
 * Prueba NewsSitemapController contra WordPress real: buildXml() consulta
 * wp_posts a través de WP_Query con un date_query relativo ("N horas
 * atrás"), no simulable de forma fiable con Brain Monkey.
 *
 * maybeRender() (el único método público que genera y envía la respuesta
 * completa) termina siempre con exit() tras el echo — igual que
 * NDAds\Http\ClickRedirectController::maybeRedirect() (ver su test de
 * integración en nd-ads), no hay forma de interceptar esa salida sin matar
 * el proceso de PHPUnit, así que se deja fuera deliberadamente.
 *
 * A diferencia de ese caso, buildXml() sí es un método separable sin
 * exit() ni efectos secundarios propios (solo construye una cadena XML a
 * partir de una WP_Query real): se invoca aquí vía Reflection para probar
 * justamente la lógica que el resto de la clase no puede exponer de otro
 * modo — qué posts entran en el sitemap según su fecha.
 */
final class NewsSitemapControllerTest extends WP_UnitTestCase {

	/**
	 * @param array<string, mixed> $newsSitemapConfig
	 */
	private function controller( array $newsSitemapConfig = array() ): NewsSitemapController {
		return new NewsSitemapController( new Config( array( 'seo' => array( 'news_sitemap' => $newsSitemapConfig ) ) ) );
	}

	private function buildXml( NewsSitemapController $controller ): string {
		// No hace falta setAccessible(true): desde PHP 8.1,
		// ReflectionMethod::invoke() ya puede llamar a métodos privados sin
		// él (declararlo solo genera un aviso de obsolescencia en PHP 8.5+).
		$method = new ReflectionMethod( $controller, 'buildXml' );

		/** @var string $xml */
		$xml = $method->invoke( $controller );

		return $xml;
	}

	public function test_register_rewrite_rule_maps_the_sitemap_url_to_the_query_var(): void {
		global $wp_rewrite;

		$this->set_permalink_structure( '/%postname%/' );

		$this->controller()->registerRewriteRule();
		flush_rewrite_rules();

		$rules = $wp_rewrite->rewrite_rules();

		self::assertArrayHasKey( '^sitemap-news\.xml$', $rules );
		self::assertSame(
			'index.php?' . NewsSitemapController::QUERY_VAR . '=1',
			$rules['^sitemap-news\.xml$']
		);
	}

	public function test_register_query_var_appends_the_sitemap_var(): void {
		$result = $this->controller()->registerQueryVar( array( 'existing_var' ) );

		self::assertSame( array( 'existing_var', NewsSitemapController::QUERY_VAR ), $result );
	}

	public function test_build_xml_includes_recent_posts_and_excludes_posts_older_than_the_window(): void {
		$recent = self::factory()->post->create_and_get(
			array(
				'post_title' => 'Noticia reciente',
				'post_date'  => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		$old = self::factory()->post->create_and_get(
			array(
				'post_title' => 'Noticia antigua',
				'post_date'  => gmdate( 'Y-m-d H:i:s', time() - ( 72 * HOUR_IN_SECONDS ) ),
			)
		);

		$xml = $this->buildXml( $this->controller( array( 'max_age_hours' => 48 ) ) );

		self::assertStringContainsString( esc_url( (string) get_permalink( $recent ) ), $xml );
		self::assertStringContainsString( 'Noticia reciente', $xml );
		self::assertStringNotContainsString( 'Noticia antigua', $xml );
		self::assertStringNotContainsString( esc_url( (string) get_permalink( $old ) ), $xml );
	}

	public function test_build_xml_respects_a_custom_max_age(): void {
		self::factory()->post->create_and_get(
			array(
				'post_title' => 'Hace 10 horas',
				'post_date'  => gmdate( 'Y-m-d H:i:s', time() - ( 10 * HOUR_IN_SECONDS ) ),
			)
		);

		$xmlWithNarrowWindow = $this->buildXml( $this->controller( array( 'max_age_hours' => 5 ) ) );

		self::assertStringNotContainsString( 'Hace 10 horas', $xmlWithNarrowWindow );

		$xmlWithWideWindow = $this->buildXml( $this->controller( array( 'max_age_hours' => 24 ) ) );

		self::assertStringContainsString( 'Hace 10 horas', $xmlWithWideWindow );
	}

	public function test_build_xml_uses_the_configured_publication_name_and_language(): void {
		self::factory()->post->create( array( 'post_date' => gmdate( 'Y-m-d H:i:s' ) ) );

		$xml = $this->buildXml(
			$this->controller(
				array(
					'publication_name'     => 'ND Noticias',
					'publication_language' => 'es',
				)
			)
		);

		self::assertStringContainsString( '<news:name>ND Noticias</news:name>', $xml );
		self::assertStringContainsString( '<news:language>es</news:language>', $xml );
	}
}
