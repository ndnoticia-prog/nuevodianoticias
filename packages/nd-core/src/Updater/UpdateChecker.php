<?php

declare(strict_types=1);

namespace NDCore\Updater;

use NDCore\Cache\CacheManager;
use NDCore\Hooks\HookManager;
use NDCore\Http\Client;

/**
 * Comprobador de actualizaciones autoalojado: ND Core no se distribuye por
 * WordPress.org, así que consulta directamente los "Releases" de un
 * repositorio de GitHub para notificar nuevas versiones en el admin.
 */
final class UpdateChecker {

	private const CACHE_KEY         = 'updater/latest_release';
	private const CACHE_TTL_SECONDS = 21600;

	public function __construct(
		private readonly Client $http,
		private readonly CacheManager $cache,
		private readonly string $pluginFile,
		private readonly string $currentVersion,
		private readonly string $repository,
		private readonly string $releaseAssetName = 'nd-core.zip',
	) {
	}

	public function register( HookManager $hooks ): void {
		$hooks->addFilter( 'pre_set_site_transient_update_plugins', array( $this, 'injectUpdate' ) );
		$hooks->addFilter( 'plugins_api', array( $this, 'pluginInformation' ), 10, 3 );
	}

	public function injectUpdate( mixed $transient ): mixed {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->latestRelease();

		if ( $release === null || version_compare( $release['version'], $this->currentVersion, '<=' ) ) {
			return $transient;
		}

		$pluginBasename = plugin_basename( $this->pluginFile );

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			// $transient es el objeto dinámico (stdClass) que WordPress usa
			// para el transient "update_plugins": no tiene una clase propia
			// con propiedades declaradas, así que asignarle ->response es el
			// patrón esperado, no un error.
			// @phpstan-ignore property.notFound
			$transient->response = array();
		}

		$transient->response[ $pluginBasename ] = (object) array(
			'slug'        => dirname( $pluginBasename ),
			'plugin'      => $pluginBasename,
			'new_version' => $release['version'],
			'url'         => $release['url'],
			'package'     => $release['package'],
		);

		return $transient;
	}

	public function pluginInformation( mixed $result, string $action, object $args ): mixed {
		$slug = dirname( plugin_basename( $this->pluginFile ) );

		if ( $action !== 'plugin_information' || ( $args->slug ?? null ) !== $slug ) {
			return $result;
		}

		$release = $this->latestRelease();

		if ( $release === null ) {
			return $result;
		}

		return (object) array(
			'name'          => 'ND Core',
			'slug'          => $slug,
			'version'       => $release['version'],
			'download_link' => $release['package'],
			'homepage'      => $release['url'],
		);
	}

	/**
	 * @return array{version: string, url: string, package: string}|null
	 */
	private function latestRelease(): ?array {
		/** @var array{version: string, url: string, package: string}|null $release */
		$release = $this->cache->remember(
			self::CACHE_KEY,
			fn (): ?array => $this->fetchLatestRelease(),
			self::CACHE_TTL_SECONDS
		);

		return $release;
	}

	/**
	 * @return array{version: string, url: string, package: string}|null
	 */
	private function fetchLatestRelease(): ?array {
		$response = $this->http->get(
			sprintf( 'https://api.github.com/repos/%s/releases/latest', $this->repository ),
			array(),
			array(
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'ND-Platform-Update-Checker',
			)
		);

		if ( $response->failed() ) {
			return null;
		}

		$data    = $response->json();
		$tagName = $data['tag_name'] ?? null;
		$version = is_string( $tagName ) ? ltrim( $tagName, 'v' ) : null;

		if ( $version === null ) {
			return null;
		}

		$assets  = is_array( $data['assets'] ?? null ) ? $data['assets'] : array();
		$package = null;

		foreach ( $assets as $asset ) {
			if ( is_array( $asset ) && ( $asset['name'] ?? null ) === $this->releaseAssetName ) {
				$package = $asset['browser_download_url'] ?? null;

				break;
			}
		}

		if ( ! is_string( $package ) ) {
			return null;
		}

		return array(
			'version' => $version,
			'url'     => is_string( $data['html_url'] ?? null ) ? $data['html_url'] : '',
			'package' => $package,
		);
	}
}
