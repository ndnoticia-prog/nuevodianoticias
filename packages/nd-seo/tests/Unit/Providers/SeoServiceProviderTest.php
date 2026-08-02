<?php

declare(strict_types=1);

namespace NDSeo\Tests\Unit\Providers;

use NDCore\Config\Config;
use NDCore\Container\Container;
use NDSeo\Breadcrumbs\BreadcrumbRenderer;
use NDSeo\Meta\MetaTagRenderer;
use NDSeo\Providers\SeoServiceProvider;
use NDSeo\Schema\SchemaOutput;
use NDSeo\Sitemap\NewsSitemapController;
use PHPUnit\Framework\TestCase;

final class SeoServiceProviderTest extends TestCase {

	public function test_register_binds_all_core_services(): void {
		$container = new Container();
		$container->instance( Config::class, new Config() );

		( new SeoServiceProvider( $container ) )->register();

		self::assertInstanceOf( MetaTagRenderer::class, $container->make( MetaTagRenderer::class ) );
		self::assertInstanceOf( BreadcrumbRenderer::class, $container->make( BreadcrumbRenderer::class ) );
		self::assertInstanceOf( SchemaOutput::class, $container->make( SchemaOutput::class ) );
		self::assertInstanceOf( NewsSitemapController::class, $container->make( NewsSitemapController::class ) );
	}

	public function test_register_loads_the_seo_config_file(): void {
		$container = new Container();
		$config    = new Config();
		$container->instance( Config::class, $config );

		( new SeoServiceProvider( $container ) )->register();

		self::assertTrue( $config->has( 'seo.news_sitemap.enabled' ) );
		self::assertSame( 48, $config->get( 'seo.news_sitemap.max_age_hours' ) );
	}
}
