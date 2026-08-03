<?php

declare(strict_types=1);

namespace NDAds\Tests\Integration\Http;

use NDAds\Http\ClickRedirectController;
use NDAds\Repository\CampaignRepository;
use NDAds\Stats\StatsRecorder;
use NDCore\Database\DatabaseManager;
use WP_UnitTestCase;

/**
 * Prueba de integración de ClickRedirectController limitada a
 * registerRewriteRule() y registerQueryVar(): son las únicas piezas
 * observables sin matar el proceso de PHPUnit. maybeRedirect() termina
 * siempre con exit() (tras wp_safe_redirect()/wp_redirect(), ver su
 * código), y ni el arnés de pruebas de wordpress-develop en uso
 * (tools/wp-tests/wordpress-develop) ni el resto de este codebase ofrecen
 * un mecanismo para interceptar esa salida sin terminar el proceso de
 * pruebas, así que maybeRedirect() se deja fuera deliberadamente.
 */
final class ClickRedirectControllerTest extends WP_UnitTestCase {

	private function controller(): ClickRedirectController {
		$db = new DatabaseManager();

		return new ClickRedirectController( new CampaignRepository( $db ), new StatsRecorder( $db ) );
	}

	public function test_register_rewrite_rule_maps_the_click_url_to_the_query_var(): void {
		global $wp_rewrite;

		$this->set_permalink_structure( '/%postname%/' );

		$this->controller()->registerRewriteRule();
		flush_rewrite_rules();

		$rules = $wp_rewrite->rewrite_rules();

		self::assertArrayHasKey( '^nd-ads/click/([0-9]+)/?$', $rules );
		self::assertSame(
			'index.php?' . ClickRedirectController::QUERY_VAR . '=$matches[1]',
			$rules['^nd-ads/click/([0-9]+)/?$']
		);
	}

	public function test_register_query_var_appends_the_click_campaign_var(): void {
		$result = $this->controller()->registerQueryVar( array( 'existing_var' ) );

		self::assertSame( array( 'existing_var', ClickRedirectController::QUERY_VAR ), $result );
	}
}
