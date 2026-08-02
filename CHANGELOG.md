# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

### Added

- Estructura inicial del monorepo (`apps/`, `packages/`, `bootstrap/`, `config/`, `docs/`, `resources/`, `routes/`, `storage/`, `tests/`, `tools/`).
- `composer.json` raíz con repositorios `path` hacia los 13 paquetes y toolchain de calidad (PHPCS/WPCS, PHPStan nivel máximo, PHPUnit, Brain Monkey).
- `package.json` raíz con npm workspaces para `nd-theme` y `nd-builder`, y Vite como bundler.
- Licencia GPL-2.0-or-later.

## [0.1.0-alpha.1] - Unreleased

### Added

Paquete `nd-core` — núcleo mínimo viable de ND Platform:

- **Bootstrap del plugin**: `nd-core.php` con cabecera de plugin, comprobación de requisitos mínimos (PHP 8.3, WordPress 6.5), autodesactivación segura si no se cumplen, y `uninstall.php`.
- **Contenedor**: `Container` compatible con PSR-11, con autowiring vía reflexión, detección de dependencias circulares y fallback a valores por defecto cuando una dependencia de clase no es resoluble.
- **Aplicación**: `Application` (singleton gestionado, extiende `Container`) orquesta configuración, `ServiceProvider` y arranque.
- **Configuración**: `Config` con notación de puntos y carga de directorios de archivos PHP.
- **Providers**: `ServiceProvider` base, `CoreServiceProvider`, `RoutingServiceProvider`, `RestApiServiceProvider`.
- **Eventos y hooks**: `EventDispatcher` interno (con prioridades y `stopPropagation`) y `HookManager` como única puerta hacia `add_action`/`add_filter` de WordPress.
- **Enrutamiento y REST API**: `Router`/`Route`/`RouteCollection`, `RestController` base y `SystemController` (`GET /wp-json/nd/v1/system/status`).
- **Base de datos y migraciones**: `DatabaseManager` (envoltorio de `$wpdb` con sentencias preparadas), `Migrator` idempotente con tabla `{prefix}nd_migrations`.
- **Instalación**: `Installer`, `Activator`, `Deactivator`, `Uninstaller`.
- **Caché**: `CacheManager` con drivers `transient`, `object-cache` y `redis` (phpredis).
- **Filesystem, HTTP, colas y scheduler**: `Filesystem` (sobre `WP_Filesystem`), `Http\Client`/`Request`/`Response` (sobre `wp_remote_request`), `QueueManager` respaldado por tabla propia y procesado vía WP-Cron, `Scheduler` sobre `wp_schedule_event`.
- **Seguridad y permisos**: `Nonce`, `Sanitizer`, `Encryption` (cifrado autenticado con libsodium), `PermissionManager` con capacidades personalizadas (`Capability`).
- **Ajustes**: `SettingsRepository` sobre `wp_options` con prefijo propio.
- **Soporte**: `Str`, `Arr`, `Collection`, funciones globales de conveniencia (`nd_app()`, `nd_config()`, `nd_cache()`, `nd_settings()`).
- **Actualizador**: `UpdateChecker` autoalojado contra GitHub Releases (el plugin no se distribuye por WordPress.org).
- **Pruebas**: suite PHPUnit (Brain Monkey) para `Container`, `Config`, `Support\{Str,Arr,Collection}`, `EventDispatcher`, `HookManager`, `SettingsRepository` y `Security\Encryption`.

### Pending verification

- `composer install`, `composer run check` (PHPCS/WPCS + PHPStan nivel máximo + PHPUnit) aún no se han ejecutado en este entorno de desarrollo por falta de PHP/Composer instalados; el código se ha revisado manualmente pero no se considera "verde" hasta correr el toolchain real.
