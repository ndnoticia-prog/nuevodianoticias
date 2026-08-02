<?php

declare(strict_types=1);

namespace NDSearch\Tests\Unit\Providers;

use NDCore\Container\Container;
use NDSearch\Migrations\CreateSearchIndexTable;
use NDSearch\Providers\SearchServiceProvider;
use PHPUnit\Framework\TestCase;

final class SearchServiceProviderTest extends TestCase {

	/**
	 * Nota: a diferencia del resto de paquetes, nd-search no tiene ninguna
	 * pieza comprobable sin WordPress real: SearchIndexRepository necesita
	 * DatabaseManager ($wpdb), y SearchIndexer/SearchQueryOverride operan
	 * sobre WP_Post/WP_Query, clases del propio core de WordPress que no
	 * existen fuera de una instalación real. Todo el paquete necesita
	 * pruebas de integración con WordPress real; aquí solo se comprueba lo
	 * que no depende de ninguna de esas dos cosas.
	 */
	public function test_migrations_include_the_search_index_table(): void {
		$provider = new SearchServiceProvider( new Container() );

		self::assertSame( array( CreateSearchIndexTable::class ), $provider->migrations() );
	}
}
