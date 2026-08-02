<?php

declare(strict_types=1);

namespace NDAds\Http;

use NDAds\Repository\CampaignRepository;
use NDAds\Stats\StatsRecorder;

/**
 * `/nd-ads/click/{id}` registra el clic y redirige al destino real de la
 * campaña. El destino se busca en el servidor a partir del ID, nunca se
 * acepta una URL de destino por parámetro: así no existe riesgo de
 * open-redirect.
 */
final class ClickRedirectController {

	public const QUERY_VAR = 'nd_ad_click_campaign';

	public function __construct(
		private readonly CampaignRepository $campaigns,
		private readonly StatsRecorder $stats,
	) {
	}

	public function registerRewriteRule(): void {
		add_rewrite_rule( '^nd-ads/click/([0-9]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * @param list<string> $vars
	 *
	 * @return list<string>
	 */
	public function registerQueryVar( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	public function maybeRedirect(): void {
		$campaignId = (int) get_query_var( self::QUERY_VAR );

		if ( $campaignId <= 0 ) {
			return;
		}

		$campaign    = $this->campaigns->find( $campaignId );
		$destination = null;

		if ( $campaign !== null ) {
			$creativeLink = $campaign->creative['link_url'] ?? null;
			$destination  = is_string( $creativeLink ) && $creativeLink !== '' ? $creativeLink : null;
		}

		if ( $destination === null ) {
			wp_safe_redirect( (string) home_url( '/' ) );
			exit;
		}

		$this->stats->recordClick( $campaignId );

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- $destination es la URL del anunciante, resuelta en servidor desde datos de campaña gestionados por un admin (no un parámetro de la petición); wp_safe_redirect() bloquearía destinos externos legítimos, que es justo el caso de uso.
		wp_redirect( esc_url_raw( $destination ) );
		exit;
	}
}
