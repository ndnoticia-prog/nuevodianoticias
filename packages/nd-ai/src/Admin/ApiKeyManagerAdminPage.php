<?php

declare(strict_types=1);

namespace NDAi\Admin;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

/**
 * Gestor de claves de API de los proveedores de IA (OpenAI, Claude, Gemini,
 * DeepSeek): guardar/borrar cada clave, sin exponer nunca el valor
 * completo ya guardado (ver ApiKeyController::describe()).
 */
final class ApiKeyManagerAdminPage implements RegistersAdminPages {

	private const string SLUG             = 'nd-ai-keys';
	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-ai';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Claves de IA', 'nd-ai' ),
				menuTitle: __( 'Inteligencia Artificial', 'nd-ai' ),
				capability: Capability::MANAGE_ND_SETTINGS,
				render: $this->render( ... ),
				position: 40,
			),
		);
	}

	public function render(): void {
		$jsUrl  = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/api-keys.js' );
		$cssUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/api-keys.css' );

		if ( $cssUrl !== '' ) {
			wp_enqueue_style( 'nd-ai-keys', $cssUrl, array(), $this->assetVersion() );
		}

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-ai-keys', $jsUrl, array(), $this->assetVersion(), true );
			wp_localize_script( 'nd-ai-keys', 'ndAiKeysL10n', $this->l10n() );
		}

		$restBase = esc_url_raw( rest_url( 'nd/v1/ai' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		echo '<div class="wrap nd-ai-keys-wrap">';
		echo '<h1>' . esc_html__( 'Claves de API de IA', 'nd-ai' ) . '</h1>';
		echo '<p>' . esc_html__( 'Las claves se guardan cifradas y nunca se muestran completas una vez guardadas.', 'nd-ai' ) . '</p>';
		printf(
			'<div id="nd-ai-keys" data-rest-base="%s" data-nonce="%s"><p>%s</p></div>',
			esc_attr( $restBase ),
			esc_attr( $nonce ),
			esc_html__( 'Cargando…', 'nd-ai' )
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
			'provider'     => __( 'Proveedor', 'nd-ai' ),
			'status'       => __( 'Estado', 'nd-ai' ),
			'newKey'       => __( 'Nueva clave', 'nd-ai' ),
			'actions'      => __( 'Acciones', 'nd-ai' ),
			'noKey'        => __( 'Sin clave', 'nd-ai' ),
			'placeholder'  => __( 'Pegar clave de API…', 'nd-ai' ),
			'save'         => __( 'Guardar', 'nd-ai' ),
			'clear'        => __( 'Borrar', 'nd-ai' ),
			'confirmClear' => __( '¿Borrar la clave guardada?', 'nd-ai' ),
		);
	}
}
