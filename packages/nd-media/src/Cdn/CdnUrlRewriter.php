<?php

declare(strict_types=1);

namespace NDMedia\Cdn;

use NDCore\Config\Config;

/**
 * Reescribe las URLs de `wp-content/uploads` hacia un dominio de CDN
 * configurado, tanto para llamadas directas a la librería de medios
 * (`wp_get_attachment_url`) como para imágenes ya insertadas en el
 * contenido de las entradas.
 */
final class CdnUrlRewriter
{
    public function __construct(private readonly Config $config)
    {
    }

    public function filterAttachmentUrl(string $url): string
    {
        return $this->rewrite($url);
    }

    public function filterContent(string $content): string
    {
        return $this->rewrite($content);
    }

    private function rewrite(string $subject): string
    {
        $cdnUrl = $this->cdnUrl();

        if ($cdnUrl === null) {
            return $subject;
        }

        return str_replace($this->uploadsBaseUrl(), $cdnUrl, $subject);
    }

    private function cdnUrl(): ?string
    {
        $url = $this->config->get('media.cdn_url');

        return is_string($url) && $url !== '' ? rtrim($url, '/') : null;
    }

    private function uploadsBaseUrl(): string
    {
        $uploads = wp_get_upload_dir();
        $baseUrl = $uploads['baseurl'] ?? '';

        return rtrim((string) $baseUrl, '/');
    }
}
