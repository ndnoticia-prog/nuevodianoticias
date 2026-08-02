<?php

declare(strict_types=1);

namespace NDCore\Installer;

use NDCore\Application;
use NDCore\Migrator\Migrator;
use NDCore\Permissions\PermissionManager;
use NDCore\Settings\SettingsRepository;

/**
 * Orquesta lo que debe ocurrir al activar/desinstalar el plugin: migraciones
 * de todos los paquetes registrados, capacidades y ajustes por defecto.
 */
final class Installer {

	public function __construct(
		private readonly Application $app,
		private readonly Migrator $migrator,
		private readonly PermissionManager $permissions,
		private readonly SettingsRepository $settings,
	) {
	}

	public function install(): void {
		$this->migrator->run( $this->allMigrationClasses() );
		$this->permissions->registerCapabilities();
		$this->seedDefaultSettings();

		$this->settings->set( 'installed_version', defined( 'NDCORE_VERSION' ) ? NDCORE_VERSION : null );
		$this->settings->set( 'installed_at', current_time( 'mysql', true ), false );

		flush_rewrite_rules();
	}

	public function uninstall(): void {
		$this->permissions->removeCapabilities();
		$this->migrator->rollback( $this->allMigrationClasses() );
		$this->purgeSettings();
	}

	/**
	 * @return list<class-string<\NDCore\Migrator\Migration>>
	 */
	private function allMigrationClasses(): array {
		$classes = array();

		foreach ( $this->app->providers() as $provider ) {
			array_push( $classes, ...$provider->migrations() );
		}

		return array_values( array_unique( $classes ) );
	}

	private function seedDefaultSettings(): void {
		if ( ! $this->settings->has( 'cache_driver' ) ) {
			$this->settings->set( 'cache_driver', 'transient' );
		}
	}

	private function purgeSettings(): void {
		foreach ( array( 'installed_version', 'installed_at', 'cache_driver' ) as $key ) {
			$this->settings->forget( $key );
		}
	}
}
