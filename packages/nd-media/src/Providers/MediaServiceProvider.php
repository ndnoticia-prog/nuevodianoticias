<?php

declare(strict_types=1);

namespace NDMedia\Providers;

use NDCore\Config\Config;
use NDCore\Hooks\HookManager;
use NDCore\Providers\ServiceProvider;
use NDMedia\Cdn\CdnUrlRewriter;
use NDMedia\Optimization\ModernFormatConverter;
use NDMedia\Optimization\ResponsiveImageSizer;
use NDMedia\Podcast\PodcastFeedEnhancer;
use NDMedia\Video\ResponsiveEmbedWrapper;

final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var Config $config */
        $config = $this->container->make(Config::class);
        $config->loadDirectory(dirname(__DIR__, 2) . '/config');

        $this->container->singleton(ModernFormatConverter::class);
        $this->container->singleton(ResponsiveImageSizer::class);
        $this->container->singleton(CdnUrlRewriter::class);
        $this->container->singleton(ResponsiveEmbedWrapper::class);
        $this->container->singleton(PodcastFeedEnhancer::class);
    }

    public function boot(): void
    {
        /** @var HookManager $hooks */
        $hooks = $this->container->make(HookManager::class);

        /** @var ModernFormatConverter $formatConverter */
        $formatConverter = $this->container->make(ModernFormatConverter::class);
        $hooks->addFilter('image_editor_output_format', $formatConverter->filterOutputFormat(...));

        /** @var ResponsiveImageSizer $imageSizer */
        $imageSizer = $this->container->make(ResponsiveImageSizer::class);
        $hooks->addFilter('wp_calculate_image_sizes', $imageSizer->filterSizes(...));

        /** @var CdnUrlRewriter $cdn */
        $cdn = $this->container->make(CdnUrlRewriter::class);
        $hooks->addFilter('wp_get_attachment_url', $cdn->filterAttachmentUrl(...));
        $hooks->addFilter('the_content', $cdn->filterContent(...));

        /** @var ResponsiveEmbedWrapper $embedWrapper */
        $embedWrapper = $this->container->make(ResponsiveEmbedWrapper::class);
        $hooks->addFilter('embed_oembed_html', $embedWrapper->wrap(...));

        /** @var PodcastFeedEnhancer $podcast */
        $podcast = $this->container->make(PodcastFeedEnhancer::class);
        $hooks->addAction('rss2_ns', $podcast->addNamespace(...));
        $hooks->addAction('rss2_item', $podcast->addEnclosure(...));
    }
}
