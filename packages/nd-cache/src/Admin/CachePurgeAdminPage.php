<?php

declare(strict_types=1);

namespace NDCache\Admin;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

final class CachePurgeAdminPage implements RegistersAdminPages {

	private const string SLUG             = 'nd-cache-purge';
	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-cache';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Caché', 'nd-cache' ),
				menuTitle: __( 'Caché', 'nd-cache' ),
				capability: Capability::MANAGE_ND_SETTINGS,
				render: $this->render( ... ),
				position: 60,
			),
		);
	}

	public function render(): void {
		$jsUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/cache-purge.js' );

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-cache-purge', $jsUrl, array(), $this->assetVersion(), true );
			wp_localize_script( 'nd-cache-purge', 'ndCachePurgeL10n', $this->l10n() );
		}

		$restBase = esc_url_raw( rest_url( 'nd/v1/cache' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		echo '<div class="wrap nd-cache-purge-wrap">';
		echo '<h1>' . esc_html__( 'Caché de página', 'nd-cache' ) . '</h1>';
		echo '<p>' . esc_html__( 'La caché editorial (artículos, portada, categorías) ya se purga automáticamente al publicar o editar. Usa esto solo si algo más (un widget, un menú, la configuración del tema) dejó HTML desactualizado.', 'nd-cache' ) . '</p>';
		printf(
			'<div id="nd-cache-purge" data-rest-base="%s" data-nonce="%s"></div>',
			esc_attr( $restBase ),
			esc_attr( $nonce )
		);
		echo '</div>';
	}

	private function assetVersion(): string {
		return defined( 'NDCORE_VERSION' ) ? (string) NDCORE_VERSION : '0.0.0';
	}

	/**
	 * @return array<string, string>
	 */
	private function l10n(): array {
		return array(
			'purge'   => __( 'Purgar toda la caché', 'nd-cache' ),
			'purging' => __( 'Purgando…', 'nd-cache' ),
			'done'    => __( 'Caché purgada.', 'nd-cache' ),
		);
	}
}
