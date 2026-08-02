<?php

declare(strict_types=1);

namespace NDAds\Tests\Unit\Providers;

use NDAds\Migrations\CreateAdCampaignsTable;
use NDAds\Migrations\CreateAdEventsTable;
use NDAds\Providers\AdsServiceProvider;
use NDAds\Rendering\AdRenderer;
use NDCore\Container\Container;
use PHPUnit\Framework\TestCase;

final class AdsServiceProviderTest extends TestCase {

	/**
	 * Nota: solo se resuelve AdRenderer aquí (es puro, sin dependencias).
	 * CampaignRepository/StatsRecorder/ClickRedirectController/AdShortcode
	 * arrastran DatabaseManager, que requiere un $wpdb real (WordPress);
	 * misma limitación ya documentada para DatabaseManager/Migrator.
	 */
	public function test_register_binds_the_pure_renderer(): void {
		$container = new Container();
		( new AdsServiceProvider( $container ) )->register();

		self::assertInstanceOf( AdRenderer::class, $container->make( AdRenderer::class ) );
	}

	public function test_migrations_include_campaigns_and_events_tables(): void {
		$provider = new AdsServiceProvider( new Container() );

		self::assertSame(
			array( CreateAdCampaignsTable::class, CreateAdEventsTable::class ),
			$provider->migrations()
		);
	}
}
