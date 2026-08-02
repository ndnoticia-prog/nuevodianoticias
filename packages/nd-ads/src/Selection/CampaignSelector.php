<?php

declare(strict_types=1);

namespace NDAds\Selection;

use DateTimeImmutable;
use NDAds\Domain\Campaign;
use NDAds\Repository\CampaignRepository;

/**
 * Elige, para una zona y un contexto de categorías dado, la campaña activa
 * de mayor prioridad que además esté programada para el momento actual y
 * coincida con la segmentación configurada.
 */
final class CampaignSelector {

	public function __construct( private readonly CampaignRepository $campaigns ) {
	}

	/**
	 * @param list<string> $categorySlugs Categorías del contenido donde se va a mostrar el anuncio.
	 */
	public function selectForZone( string $zone, array $categorySlugs = array() ): ?Campaign {
		$now = new DateTimeImmutable( 'now', wp_timezone() );

		foreach ( $this->campaigns->active() as $campaign ) {
			if ( ! $campaign->matchesZone( $zone ) ) {
				continue;
			}

			if ( ! $campaign->isScheduledAt( $now ) ) {
				continue;
			}

			if ( ! $campaign->matchesCategories( $categorySlugs ) ) {
				continue;
			}

			return $campaign;
		}

		return null;
	}
}
