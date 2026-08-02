<?php

declare(strict_types=1);

namespace NDSeo\Providers;

use NDCore\Config\Config;
use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDSeo\Breadcrumbs\BreadcrumbBuilder;
use NDSeo\Breadcrumbs\BreadcrumbRenderer;
use NDSeo\Context\SeoContextResolver;
use NDSeo\Meta\MetaTagRenderer;
use NDSeo\Meta\OpenGraphBuilder;
use NDSeo\Meta\RobotsMetaBuilder;
use NDSeo\Meta\TwitterCardBuilder;
use NDSeo\Robots\RobotsTxtBuilder;
use NDSeo\Schema\BreadcrumbListSchema;
use NDSeo\Schema\Contracts\SchemaProvider;
use NDSeo\Schema\NewsArticleSchema;
use NDSeo\Schema\OrganizationSchema;
use NDSeo\Schema\SchemaOutput;
use NDSeo\Schema\WebSiteSchema;
use NDSeo\Sitemap\NewsSitemapController;

final class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var Config $config */
        $config = $this->container->make(Config::class);
        $config->loadDirectory(dirname(__DIR__, 2) . '/config');

        $this->container->singleton(SeoContextResolver::class);
        $this->container->singleton(RobotsMetaBuilder::class);
        $this->container->singleton(OpenGraphBuilder::class);
        $this->container->singleton(TwitterCardBuilder::class);
        $this->container->singleton(MetaTagRenderer::class);

        $this->container->singleton(BreadcrumbBuilder::class);
        $this->container->singleton(BreadcrumbRenderer::class);

        $this->container->singleton(OrganizationSchema::class);
        $this->container->singleton(WebSiteSchema::class);
        $this->container->singleton(NewsArticleSchema::class);
        $this->container->singleton(BreadcrumbListSchema::class);

        $this->container->singleton(SchemaOutput::class, function (): SchemaOutput {
            /** @var list<SchemaProvider> $providers */
            $providers = [
                $this->container->make(OrganizationSchema::class),
                $this->container->make(WebSiteSchema::class),
                $this->container->make(NewsArticleSchema::class),
                $this->container->make(BreadcrumbListSchema::class),
            ];

            return new SchemaOutput($this->container->make(SeoContextResolver::class), $providers);
        });

        $this->container->singleton(RobotsTxtBuilder::class);
        $this->container->singleton(NewsSitemapController::class);
    }

    public function boot(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->container->make(HookManager::class);

        $hooks->addAction('wp_head', function (): void {
            $this->container->make(MetaTagRenderer::class)->render();
        });

        $hooks->addAction('wp_head', function (): void {
            $this->container->make(SchemaOutput::class)->render();
        });

        $hooks->addFilter('robots_txt', function (string $output, bool $public): string {
            return $this->container->make(RobotsTxtBuilder::class)->filter($output, $public);
        }, 10, 2);

        /** @var NewsSitemapController $newsSitemap */
        $newsSitemap = $this->container->make(NewsSitemapController::class);

        $hooks->addAction('init', $newsSitemap->registerRewriteRule(...));
        $hooks->addFilter('query_vars', $newsSitemap->registerQueryVar(...));
        $hooks->addAction('template_redirect', $newsSitemap->maybeRender(...));
    }
}
