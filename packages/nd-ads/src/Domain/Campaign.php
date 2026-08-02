<?php

declare(strict_types=1);

namespace NDAds\Domain;

use DateTimeImmutable;

final class Campaign
{
    /**
     * @param list<string> $zones Slots donde puede aparecer (p. ej. "header", "in-article").
     * @param list<string> $categorySlugs Vacío = sin segmentación por categoría (elegible en cualquiera).
     * @param array<string, mixed> $creative Datos propios del tipo: html, image_url/link_url, adsense_slot, gam_unit_path, video_url, sponsor_label...
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $advertiser,
        public readonly CampaignType $type,
        public readonly bool $active,
        public readonly int $priority,
        public readonly array $zones,
        public readonly array $categorySlugs,
        public readonly array $creative,
        public readonly ?string $startsAt,
        public readonly ?string $endsAt,
    ) {
    }

    public function isScheduledAt(DateTimeImmutable $now): bool
    {
        if ($this->startsAt !== null && $now < new DateTimeImmutable($this->startsAt)) {
            return false;
        }

        if ($this->endsAt !== null && $now > new DateTimeImmutable($this->endsAt)) {
            return false;
        }

        return true;
    }

    public function matchesZone(string $zone): bool
    {
        return in_array($zone, $this->zones, true);
    }

    /**
     * @param list<string> $categorySlugs Categorías del contenido donde se va a mostrar el anuncio.
     */
    public function matchesCategories(array $categorySlugs): bool
    {
        if ($this->categorySlugs === []) {
            return true;
        }

        return array_intersect($this->categorySlugs, $categorySlugs) !== [];
    }
}
