<?php

declare(strict_types=1);

namespace NDAnalytics\Tests\Unit\Providers;

use NDAnalytics\Migrations\CreateImpressionsTable;
use NDAnalytics\Migrations\CreatePageviewsTable;
use NDAnalytics\Providers\AnalyticsServiceProvider;
use NDAnalytics\Tracking\VisitorHasher;
use NDCore\Container\Container;
use PHPUnit\Framework\TestCase;

final class AnalyticsServiceProviderTest extends TestCase {

	/**
	 * Nota: solo se resuelve VisitorHasher aquí (no depende de la base de
	 * datos). PageviewRecorder/ImpressionRecorder/AnalyticsRepository
	 * arrastran DatabaseManager, que requiere un $wpdb real (WordPress);
	 * misma limitación ya documentada para DatabaseManager/Migrator.
	 */
	public function test_register_binds_the_database_independent_service(): void {
		$container = new Container();
		( new AnalyticsServiceProvider( $container ) )->register();

		self::assertInstanceOf( VisitorHasher::class, $container->make( VisitorHasher::class ) );
	}

	public function test_migrations_include_pageviews_and_impressions_tables(): void {
		$provider = new AnalyticsServiceProvider( new Container() );

		self::assertSame(
			array( CreatePageviewsTable::class, CreateImpressionsTable::class ),
			$provider->migrations()
		);
	}
}
