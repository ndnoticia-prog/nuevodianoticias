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

	/**
	 * Igual que {@see self::for()}, pero para los paquetes empaquetados
	 * dentro de `vendor/` (nd-workflow, nd-ads, ...): construye la URL a
	 * partir del nombre de paquete de Composer conocido en vez de la ruta
	 * absoluta de archivo. `for()` falla para estos paquetes en un entorno
	 * de desarrollo con `repositories` de tipo `path` (los symlinks que crea
	 * Composer hacen que `__DIR__` resuelva a la ubicación real del paquete
	 * hermano, fuera del árbol de nd-core) aunque en producción (build con
	 * archivos copiados de verdad, no symlinks) sí habría funcionado igual.
	 *
	 * @param string $composerPackageName P. ej. "ndnoticia/nd-workflow".
	 * @param string $relativePath P. ej. "assets/admin/calendar.js".
	 */
	public static function forPackage( string $composerPackageName, string $relativePath ): string {
		if ( ! defined( 'NDCORE_PLUGIN_URL' ) ) {
			return '';
		}

		/** @var string $pluginUrl */
		$pluginUrl = NDCORE_PLUGIN_URL;

		return rtrim( $pluginUrl, '/' ) . '/vendor/' . trim( $composerPackageName, '/' ) . '/' . ltrim( $relativePath, '/' );
	}
}
