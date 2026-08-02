<?php

declare(strict_types=1);

namespace NDMedia\Podcast;

use NDCore\Config\Config;

/**
 * Añade soporte de podcast al feed RSS nativo de WordPress (`rss2_ns` /
 * `rss2_item`): namespace de iTunes y `<enclosure>` para las entradas que
 * tengan un audio asociado (meta `_nd_podcast_audio_url`), sin necesidad de
 * un generador de feeds propio.
 */
final class PodcastFeedEnhancer
{
    public function __construct(private readonly Config $config)
    {
    }

    public function addNamespace(): void
    {
        echo ' xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"' . "\n";
    }

    public function addEnclosure(): void
    {
        $postId = get_the_ID();

        if ($postId === false) {
            return;
        }

        $audioUrl = get_post_meta($postId, $this->audioMetaKey(), true);

        if (! is_string($audioUrl) || $audioUrl === '') {
            return;
        }

        $length = (int) get_post_meta($postId, $this->audioLengthMetaKey(), true);

        printf(
            '<enclosure url="%s" length="%d" type="audio/mpeg" />' . "\n",
            esc_url($audioUrl),
            $length
        );

        printf('<itunes:title>%s</itunes:title>' . "\n", esc_html(get_the_title($postId)));
    }

    private function audioMetaKey(): string
    {
        return (string) $this->config->get('media.podcast.audio_meta_key', '_nd_podcast_audio_url');
    }

    private function audioLengthMetaKey(): string
    {
        return (string) $this->config->get('media.podcast.audio_length_meta_key', '_nd_podcast_audio_length');
    }
}
