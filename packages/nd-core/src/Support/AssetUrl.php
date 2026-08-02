<?php

declare(strict_types=1);

namespace NDCore\Support;

/**
 * Traduce una ruta absoluta de archivo dentro del árbol del plugin a su URL
 * pública. Necesario porque nd-workflow, nd-ads, nd-analytics, etc. no son
 * plugins independientes con su propia `plugin_dir_url()`: se empaquetan
 * dentro de `vendor/` de nd-core (ver Application::resolveProviderClasses()),
 * así que sus propios assets (JS/CSS de admin) también viven ahí y solo
 * nd-core conoce el prefijo real de URL pública (`NDCORE_PLUGIN_URL`).
 */
final class AssetUrl {

	private function __construct() {
	}

	/**
	 * @param string $absolutePath Ruta absoluta de archivo, dentro de `NDCORE_PLUGIN_DIR`.
	 *
	 * @return string Cadena vacía si el plugin no está cargado como tal (p. ej. en tests fuera de contexto) o si la ruta no está dentro del árbol del plugin.
	 */
	public static function for( string $absolutePath ): string {
		if ( ! defined( 'NDCORE_PLUGIN_DIR' ) || ! defined( 'NDCORE_PLUGIN_URL' ) ) {
			return '';
		}

		/** @var string $pluginDir */
		$pluginDir = NDCORE_PLUGIN_DIR;

		/** @var string $pluginUrl */
		$pluginUrl = NDCORE_PLUGIN_URL;

		if ( ! str_starts_with( $absolutePath, $pluginDir ) ) {
			return '';
		}

		$relative = ltrim( substr( $absolutePath, strlen( $pluginDir ) ), '/' );

		return rtrim( $pluginUrl, '/' ) . '/' . $relative;
	}
}
