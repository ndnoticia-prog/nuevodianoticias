<?php

declare(strict_types=1);

namespace NDAds\Rendering;

use NDAds\Selection\CampaignSelector;
use NDAds\Stats\StatsRecorder;

/**
 * "Seleccionar + renderizar + registrar impresión" para una zona, en un
 * único lugar: lo usa tanto el shortcode [nd_ad] como las plantillas de
 * nd-theme que colocan una zona directamente (p. ej. la cabecera).
 */
final class AdZoneRenderer {

	public function __construct(
		private readonly CampaignSelector $selector,
		private readonly AdRenderer $renderer,
		private readonly StatsRecorder $stats,
	) {
	}

	/**
	 * @param list<string> $categorySlugs
	 */
	public function render( string $zone, array $categorySlugs = array() ): string {
		$campaign = $this->selector->selectForZone( $zone, $categorySlugs );

		if ( $campaign === null ) {
			return '';
		}

		$html = $this->renderer->render( $campaign );

		if ( $html === '' ) {
			return '';
		}

		$this->stats->recordImpression( $campaign->id, $zone );

		return $html;
	}
}
