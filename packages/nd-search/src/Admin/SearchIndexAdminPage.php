<?php

declare(strict_types=1);

namespace NDSearch\Admin;

use NDCore\Admin\AdminPage;
use NDCore\Admin\Contracts\RegistersAdminPages;
use NDCore\Permissions\Capability;
use NDCore\Support\AssetUrl;

/**
 * Panel del índice de búsqueda propio (no el de WordPress): estadísticas,
 * contenido indexado reciente, consulta de prueba y botón de
 * reconstrucción manual.
 */
final class SearchIndexAdminPage implements RegistersAdminPages {

	private const string SLUG             = 'nd-search-index';
	private const string COMPOSER_PACKAGE = 'ndnoticia/nd-search';

	/**
	 * @return list<AdminPage>
	 */
	public function adminPages(): array {
		return array(
			new AdminPage(
				slug: self::SLUG,
				pageTitle: __( 'Índice de búsqueda', 'nd-search' ),
				menuTitle: __( 'Búsqueda', 'nd-search' ),
				capability: Capability::MANAGE_ND_SETTINGS,
				render: $this->render( ... ),
				position: 50,
			),
		);
	}

	public function render(): void {
		$jsUrl  = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/search-index.js' );
		$cssUrl = AssetUrl::forPackage( self::COMPOSER_PACKAGE, 'assets/admin/search-index.css' );

		if ( $cssUrl !== '' ) {
			wp_enqueue_style( 'nd-search-index', $cssUrl, array(), $this->assetVersion() );
		}

		if ( $jsUrl !== '' ) {
			wp_enqueue_script( 'nd-search-index', $jsUrl, array(), $this->assetVersion(), true );
			wp_localize_script( 'nd-search-index', 'ndSearchIndexL10n', $this->l10n() );
		}

		$restBase = esc_url_raw( rest_url( 'nd/v1/search' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );

		echo '<div class="wrap nd-search-index-wrap">';
		echo '<h1>' . esc_html__( 'Índice de búsqueda', 'nd-search' ) . '</h1>';
		printf(
			'<div id="nd-search-index" data-rest-base="%s" data-nonce="%s"><p>%s</p></div>',
			esc_attr( $restBase ),
			esc_attr( $nonce ),
			esc_html__( 'Cargando…', 'nd-search' )
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
			'loading'         => __( 'Cargando…', 'nd-search' ),
			'indexed'         => __( 'Artículos indexados', 'nd-search' ),
			'reindex'         => __( 'Reconstruir índice', 'nd-search' ),
			'reindexing'      => __( 'Reconstruyendo…', 'nd-search' ),
			/* translators: %d: number of reindexed posts. */
			'reindexDone'     => __( 'Índice reconstruido: %d artículos.', 'nd-search' ),
			'recentTitle'     => __( 'Indexado recientemente', 'nd-search' ),
			'noRecent'        => __( 'Todavía no hay contenido indexado.', 'nd-search' ),
			'testTitle'       => __( 'Probar una búsqueda', 'nd-search' ),
			'testPlaceholder' => __( 'Escribe una consulta…', 'nd-search' ),
			'testButton'      => __( 'Buscar', 'nd-search' ),
			'noResults'       => __( 'Sin resultados.', 'nd-search' ),
			'article'         => __( 'Artículo', 'nd-search' ),
			'updatedAt'       => __( 'Actualizado', 'nd-search' ),
		);
	}
}
