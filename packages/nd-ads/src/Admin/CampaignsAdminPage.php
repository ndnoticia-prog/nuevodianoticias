<?php

declare(strict_types=1);

namespace NDAds\Admin;

use NDAds\Domain\CampaignType;
use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

/**
 * Gestor de campañas publicitarias: listar, crear, editar, activar/
 * desactivar y borrar, con las estadísticas (impresiones/clics/CTR) de
 * cada una a la vista.
 */
final class CampaignsAdminPage implements RegistersAdminPages {

	private const string SLUG             = 'nd-ads-campaigns';
	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-ads';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Campañas publicitarias', 'nd-ads' ),
				menuTitle: __( 'Publicidad', 'nd-ads' ),
				capability: Capability::MANAGE_ND_ADS,
				render: $this->render( ... ),
				position: 20,
			),
		);
	}

	public function render(): void {
		$jsUrl  = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/campaigns.js' );
		$cssUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/campaigns.css' );

		if ( $cssUrl !== '' ) {
			wp_enqueue_style( 'nd-ads-campaigns', $cssUrl, array(), $this->assetVersion() );
		}

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-ads-campaigns', $jsUrl, array(), $this->assetVersion(), true );
		}

		$restBase = esc_url_raw( rest_url( 'nd/v1/ads' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );
		$types    = wp_json_encode( array_map( static fn ( CampaignType $type ): string => $type->value, CampaignType::cases() ) );

		echo '<div class="wrap nd-ads-campaigns-wrap">';
		echo '<h1>' . esc_html__( 'Campañas publicitarias', 'nd-ads' ) . '</h1>';
		printf(
			'<div id="nd-ads-campaigns" data-rest-base="%s" data-nonce="%s" data-types="%s"><p>%s</p></div>',
			esc_attr( $restBase ),
			esc_attr( $nonce ),
			esc_attr( is_string( $types ) ? $types : '[]' ),
			esc_html__( 'Cargando campañas…', 'nd-ads' )
		);
		echo '</div>';
	}

	private function assetVersion(): string {
		return defined( 'NDCORE_VERSION' ) ? (string) NDCORE_VERSION : '0.0.0';
	}
}
