<?php

declare(strict_types=1);

namespace NDWorkflow\Admin;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

/**
 * Calendario editorial arrastrable: la única página de admin de
 * nd-workflow. Ancla el slug del menú compartido "ND Platform" (ver
 * AdminMenuServiceProvider) porque es la página de uso diario más
 * frecuente para un equipo editorial.
 */
final class CalendarAdminPage implements RegistersAdminPages {

	public const string SLUG = 'nd-platform';

	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-workflow';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Calendario editorial', 'nd-workflow' ),
				menuTitle: __( 'Calendario', 'nd-workflow' ),
				capability: Capability::EDIT_ND_WORKFLOW,
				render: $this->render( ... ),
				position: 10,
			),
		);
	}

	public function render(): void {
		$jsUrl  = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/calendar.js' );
		$cssUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/calendar.css' );

		if ( $cssUrl !== '' ) {
			wp_enqueue_style( 'nd-workflow-calendar', $cssUrl, array(), $this->assetVersion() );
		}

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-workflow-calendar', $jsUrl, array(), $this->assetVersion(), true );
		}

		$restBase = esc_url_raw( rest_url( 'nd/v1/workflow' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		echo '<div class="wrap nd-workflow-calendar-wrap">';
		echo '<h1>' . esc_html__( 'Calendario editorial', 'nd-workflow' ) . '</h1>';
		echo '<p class="description">' . esc_html__( 'Arrastra un artículo a otro día para reprogramarlo.', 'nd-workflow' ) . '</p>';
		printf(
			'<div id="nd-workflow-calendar" data-rest-base="%s" data-nonce="%s"><p>%s</p></div>',
			esc_attr( $restBase ),
			esc_attr( $nonce ),
			esc_html__( 'Cargando calendario…', 'nd-workflow' )
		);
		echo '</div>';
	}

	private function assetVersion(): string {
		return defined( 'NDCORE_VERSION' ) ? (string) NDCORE_VERSION : '0.0.0';
	}
}
