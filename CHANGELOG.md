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

## [0.1.0-alpha.2] - Unreleased

### Added

Paquete `nd-builder` (nuevo) — constructor visual propio, capa de lógica sin HTML:

- `Block`: value object inmutable (tipo, id, atributos).
- `BlockRegistry` y `Renderer`: registro de tipos de bloque y motor de renderizado server-side.
- `TemplateBlockRenderer`: puente hacia el tema activo vía `locate_template()`, sin `extract()` (WPCS) — el bloque llega íntegro como `$block` al scope de la plantilla.
- `BuilderServiceProvider`: registra los tipos `hero`, `noticias` y `breaking` apuntando a `template-parts/blocks/` del tema activo.

Paquete `nd-theme` (nuevo) — presentación, sin lógica de negocio:

- Bootstrap (`style.css`, `functions.php`) con comprobación de que el plugin `nd-core` esté activo antes de registrar nada.
- `ThemeServiceProvider`: theme supports, menús (`primary`, `footer`) y encolado de los assets compilados por Vite — todo a través de `HookManager`, nunca con `add_action` directo.
- `HomeContentProvider`: traduce `WP_Query` a instancias de `NDBuilder\Block` para componer la portada (breaking → hero → noticias).
- Plantillas: `front-page.php`, `single.php`, `archive.php` (fallback genérico y adaptativo para categoría/etiqueta/autor/fecha), `search.php`, `home.php`, `index.php`, `header.php`/`footer.php`, `template-parts/blocks/{hero,noticias,breaking}.php`.
- SCSS (Vite): variables con modo oscuro (`prefers-color-scheme` + `data-theme`, sin parpadeo gracias a un script inline en `<head>`), diseño responsive mobile-first.
- JS (Vite, ES modules): toggle de modo oscuro persistido en `localStorage`.
- Suite de pruebas PHPUnit (Brain Monkey) para `nd-builder` y para `ThemeServiceProvider`.

### Changed

- `nd-core`: `nd-builder` se añade a `require` (se empaqueta dentro del plugin) y `NDBuilder\Providers\BuilderServiceProvider` se registra automáticamente en la lista de providers por defecto de `Application` (protegido con `class_exists()`).

### Fixed

- Corregido: `nd-builder`/`nd-theme` declaraban `ndnoticia/nd-core` en `require` en un borrador inicial, lo que habría duplicado sus clases (y provocado un fatal error "class already declared") si el plugin y el tema estaban activos a la vez. Se movió a `require-dev` (resuelto por un repositorio `path` local, solo para desarrollo/análisis estático) — ver "Dependencias entre paquetes" en `docs/Architecture.md`.

### Pending verification

- `HomeContentProvider` depende de `WP_Query` real: no es cubrible de forma fiable con Brain Monkey; necesita pruebas de integración con WordPress real, igual que `DatabaseManager`/`Migrator` de `nd-core`.
- `composer install && composer run check` en `nd-builder` y `nd-theme` sigue pendiente del mismo entorno de desarrollo (PHP/Composer) que `nd-core`.
