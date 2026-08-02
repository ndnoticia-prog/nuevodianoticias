<?php

declare(strict_types=1);

namespace NDAds\Rendering;

use NDAds\Domain\Campaign;
use NDAds\Domain\CampaignType;

/**
 * Genera el HTML de cada tipo de campaña. A diferencia de nd-builder (cuyo
 * HTML vive siempre en el tema), aquí el marcado sí vive en el paquete:
 * los formatos de anuncio (snippet de AdSense, slot de GAM, ...) son
 * prácticamente estándar entre proveedores y no son "presentación editorial"
 * del tema, así que nd-ads puede servir anuncios de forma consistente sin
 * depender de que cada tema los reimplemente.
 *
 * Deliberadamente puro (sin efectos secundarios ni dependencia de base de
 * datos): registrar la impresión es responsabilidad de quien lo invoca
 * (ver AdShortcode), no de este renderer.
 */
final class AdRenderer
{
    public function render(Campaign $campaign): string
    {
        $html = match ($campaign->type) {
            CampaignType::AdSense => $this->renderAdSense($campaign),
            CampaignType::GoogleAdManager => $this->renderGam($campaign),
            CampaignType::Html => $this->renderHtml($campaign),
            CampaignType::Image => $this->renderImage($campaign),
            CampaignType::Video => $this->renderVideo($campaign),
            CampaignType::Sponsored => $this->renderSponsored($campaign),
        };

        if ($html === '') {
            return '';
        }

        return sprintf(
            '<div class="nd-ad nd-ad--%s" data-nd-ad-campaign="%d">%s</div>',
            esc_attr($campaign->type->value),
            $campaign->id,
            $html
        );
    }

    private function renderAdSense(Campaign $campaign): string
    {
        $client = $campaign->creative['adsense_client'] ?? null;
        $slot = $campaign->creative['adsense_slot'] ?? null;

        if (! is_string($client) || ! is_string($slot) || $client === '' || $slot === '') {
            return '';
        }

        return sprintf(
            '<ins class="adsbygoogle" style="display:block" data-ad-client="%s" data-ad-slot="%s" ' .
            'data-ad-format="auto" data-full-width-responsive="true"></ins>' .
            '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>',
            esc_attr($client),
            esc_attr($slot)
        );
    }

    private function renderGam(Campaign $campaign): string
    {
        $unitPath = $campaign->creative['gam_unit_path'] ?? null;
        $sizes = $campaign->creative['gam_sizes'] ?? [[300, 250]];

        if (! is_string($unitPath) || $unitPath === '') {
            return '';
        }

        $divId = 'nd-gam-slot-' . $campaign->id;
        $divIdJson = (string) wp_json_encode($divId);
        $unitPathJson = (string) wp_json_encode($unitPath);
        $sizesJson = (string) wp_json_encode(is_array($sizes) ? $sizes : [[300, 250]]);

        return sprintf(
            '<div id="%1$s"></div><script>' .
            'window.googletag=window.googletag||{cmd:[]};' .
            'googletag.cmd.push(function(){' .
            'googletag.defineSlot(%2$s,%3$s,%4$s).addService(googletag.pubads());' .
            'googletag.pubads().enableSingleRequest();' .
            'googletag.enableServices();' .
            'googletag.display(%4$s);' .
            '});</script>',
            esc_attr($divId),
            $unitPathJson,
            $sizesJson,
            $divIdJson
        );
    }

    private function renderHtml(Campaign $campaign): string
    {
        $html = $campaign->creative['html'] ?? null;

        return is_string($html) ? $html : '';
    }

    private function renderImage(Campaign $campaign): string
    {
        $imageUrl = $campaign->creative['image_url'] ?? null;
        $altText = $campaign->creative['alt_text'] ?? $campaign->name;

        if (! is_string($imageUrl) || $imageUrl === '') {
            return '';
        }

        $img = sprintf(
            '<img src="%s" alt="%s" loading="lazy">',
            esc_url($imageUrl),
            esc_attr((string) $altText)
        );

        $linkUrl = $campaign->creative['link_url'] ?? null;

        if (! is_string($linkUrl) || $linkUrl === '') {
            return $img;
        }

        return sprintf(
            '<a href="%s" rel="sponsored noopener" target="_blank">%s</a>',
            esc_url($this->clickUrl($campaign)),
            $img
        );
    }

    private function renderVideo(Campaign $campaign): string
    {
        $videoUrl = $campaign->creative['video_url'] ?? null;

        if (! is_string($videoUrl) || $videoUrl === '') {
            return '';
        }

        return sprintf(
            '<video controls preload="metadata" playsinline style="max-width:100%%"><source src="%s"></video>',
            esc_url($videoUrl)
        );
    }

    private function renderSponsored(Campaign $campaign): string
    {
        $body = $campaign->creative['html'] ?? null;
        $label = $campaign->creative['sponsor_label'] ?? __('Contenido patrocinado', 'nd-ads');

        if (! is_string($body) || $body === '') {
            return '';
        }

        return sprintf(
            '<span class="nd-ad__sponsor-label">%s</span>%s',
            esc_html((string) $label),
            $body
        );
    }

    private function clickUrl(Campaign $campaign): string
    {
        return (string) home_url('/nd-ads/click/' . $campaign->id);
    }
}
