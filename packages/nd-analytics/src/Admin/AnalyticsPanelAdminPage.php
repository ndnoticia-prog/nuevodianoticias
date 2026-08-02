<?php

declare(strict_types=1);

namespace NDAnalytics\Admin;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

/**
 * Panel de lectura de analítica editorial: más leídos, en vivo, autores y
 * categorías top. No hay acciones de escritura aquí, solo los mismos
 * endpoints de AnalyticsController ya expuestos desde alpha.4.
 */
final class AnalyticsPanelAdminPage implements RegistersAdminPages {

	private const string SLUG             = 'nd-analytics-panel';
	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-analytics';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Analítica', 'nd-analytics' ),
				menuTitle: __( 'Analítica', 'nd-analytics' ),
				capability: Capability::VIEW_ND_ANALYTICS,
				render: $this->render( ... ),
				position: 30,
			),
		);
	}

	public function render(): void {
		$jsUrl  = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/analytics.js' );
		$cssUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/analytics.css' );

		if ( $cssUrl !== '' ) {
			wp_enqueue_style( 'nd-analytics-panel', $cssUrl, array(), $this->assetVersion() );
		}

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-analytics-panel', $jsUrl, array(), $this->assetVersion(), true );
			wp_localize_script( 'nd-analytics-panel', 'ndAnalyticsPanelL10n', $this->l10n() );
		}

		$restBase  = esc_url_raw( rest_url( 'nd/v1/analytics' ) );
		$postsBase = esc_url_raw( rest_url( 'wp/v2/posts' ) );
		$usersBase = esc_url_raw( rest_url( 'wp/v2/users' ) );
		$nonce     = wp_create_nonce( 'wp_rest' );

		echo '<div class="wrap nd-analytics-panel-wrap">';
		echo '<h1>' . esc_html__( 'Analítica editorial', 'nd-analytics' ) . '</h1>';
		printf(
			'<div id="nd-analytics-panel" data-rest-base="%s" data-posts-base="%s" data-users-base="%s" data-nonce="%s"><p>%s</p></div>',
			esc_attr( $restBase ),
			esc_attr( $postsBase ),
			esc_attr( $usersBase ),
			esc_attr( $nonce ),
			esc_html__( 'Cargando datos…', 'nd-analytics' )
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
			'loading'       => __( 'Cargando datos…', 'nd-analytics' ),
			'noData'        => __( 'Sin datos en este período.', 'nd-analytics' ),
			'rangeLabel'    => __( 'Período:', 'nd-analytics' ),
			/* translators: %d: number of days. */
			'days'          => __( 'Últimos %d días', 'nd-analytics' ),
			'activeNow'     => __( 'En vivo (últimos 5 minutos)', 'nd-analytics' ),
			'topPosts'      => __( 'Más leídos', 'nd-analytics' ),
			'topAuthors'    => __( 'Autores con más lecturas', 'nd-analytics' ),
			'topCategories' => __( 'Categorías con más lecturas', 'nd-analytics' ),
			'post'          => __( 'Artículo', 'nd-analytics' ),
			'author'        => __( 'Autor', 'nd-analytics' ),
			'category'      => __( 'Categoría', 'nd-analytics' ),
			'views'         => __( 'Vistas', 'nd-analytics' ),
		);
	}
}
