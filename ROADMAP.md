# Roadmap

Metodología: se avanza de versión únicamente cuando la anterior compila, pasa `composer run check` (PHPCS/WPCS + PHPStan nivel máximo + PHPUnit) y queda documentada en `CHANGELOG.md`.

## v0.1.0-alpha.1 — Núcleo mínimo viable

- [x] Scaffold del monorepo (estructura de carpetas, composer.json/package.json raíz, licencia, docs base).
- [x] `nd-core`: `Application`, `Container`, `Config`.
- [x] `nd-core`: `ServiceProvider` base y providers concretos (`CoreServiceProvider`, `RoutingServiceProvider`, `RestApiServiceProvider`).
- [x] `nd-core`: `Events` (dispatcher, evento base) y `Hooks` (wrapper tipado de `add_action`/`add_filter`).
- [x] `nd-core`: `Routing` y `RestApi` (registro de rutas REST de WordPress; `GET /wp-json/nd/v1/system/status`).
- [x] `nd-core`: `Database`, `Migrator`, `Installer`, `Activation` (activación/desactivación/desinstalación).
- [x] `nd-core`: `Cache`, `Filesystem`, `Http`, `Queue`, `Scheduler`.
- [x] `nd-core`: `Security`, `Permissions`, `Settings`, `Support`, `Updater`.
- [x] Suite de pruebas unitarias PHPUnit (Brain Monkey) para `Container`, `Config`, `Support\{Str,Arr,Collection}`, `EventDispatcher`, `HookManager`, `SettingsRepository`, `Security\Encryption`.
- [ ] Pruebas de integración con WordPress real (`wp-env` + suite oficial de WP) para `DatabaseManager`, `Migrator` y `QueueManager`: requieren un `$wpdb`/MySQL reales y no son cubribles de forma fiable solo con Brain Monkey.
- [ ] `nd-core-0.1.0-alpha.1.zip` generado por `tools/build/package.sh` e instalable en WordPress sin errores fatales.
- [ ] Verificación real con el toolchain instalado: `composer install && composer run check` en verde (pendiente de que PHP/Composer estén disponibles en el entorno de desarrollo).

## v0.1.0-alpha.2 — Tema base y builder mínimo

- [x] `nd-builder`: modelo de datos de bloques (`Block`), `BlockRegistry`, `Renderer`, `TemplateBlockRenderer` (puente hacia las plantillas del tema activo vía `locate_template()`), `BuilderServiceProvider`.
- [x] `nd-core`: `nd-builder` añadido a `require` (se empaqueta dentro del plugin) y registrado automáticamente en la lista de providers por defecto de `Application`.
- [x] `nd-theme`: bootstrap del tema (`style.css`, `functions.php` con comprobación de que `nd-core` esté activo, `ThemeServiceProvider`).
- [x] `nd-theme`: layouts — `front-page.php` (portada basada en bloques), `single.php`, `archive.php` (cubre categoría/etiqueta/autor/fecha), `search.php`, `home.php`, `index.php`, `header.php`/`footer.php`.
- [x] Bloques: Hero, Noticias, Breaking (plantillas en `template-parts/blocks/`, poblados con contenido real vía `HomeContentProvider` + `WP_Query`).
- [x] Modo oscuro (atributo `data-theme` + `prefers-color-scheme`, sin parpadeo gracias al script inline en `header.php`) y diseño responsive (SCSS con breakpoints mobile-first).
- [x] Suite de pruebas unitarias PHPUnit (Brain Monkey) para `nd-builder` (`Block`, `BlockRegistry`, `Renderer`, `TemplateBlockRenderer`, `BuilderServiceProvider`) y para `nd-theme` (`ThemeServiceProvider`).
- [ ] Pruebas de integración con WordPress real para `HomeContentProvider`: depende de `WP_Query` contra una base de datos real: no es cubrible de forma fiable solo con Brain Monkey (mismo caso que `DatabaseManager`/`Migrator` en alpha.1).
- [ ] `nd-theme-0.1.0-alpha.1.zip` instalable, generado por `tools/build/package.sh`.
- [ ] Verificación real con el toolchain instalado: `composer install && composer run check` en verde en `nd-builder` y `nd-theme` (pendiente del mismo entorno de desarrollo que alpha.1).

## v0.1.0-alpha.3 — SEO y multimedia

- [ ] `nd-seo`: Schema.org, OpenGraph, Twitter Cards, canonical, breadcrumbs, sitemap XML, robots.
- [ ] `nd-media`: optimización WebP/AVIF, responsive images, lazy load.
- [ ] `nd-discover`: requisitos técnicos de Google Discover (imágenes destacadas grandes, AMP-free, Core Web Vitals).

## v0.1.0-alpha.4 — Editorial y publicidad

- [ ] `nd-workflow`: estados editoriales, roles, comentarios internos, calendario, asignaciones.
- [ ] `nd-ads`: motor de anuncios (AdSense, GAM, HTML/imagen/video, patrocinados, segmentación, prioridad).
- [ ] `nd-analytics`: analítica editorial propia (tiempo real, más leídas, CTR, panel).

## v0.1.0-alpha.5 — IA y búsqueda

- [ ] `nd-ai`: proveedor desacoplado (OpenAI, Claude, Gemini, DeepSeek, LLM local) y generación de titulares/SEO/resúmenes/redes sociales.
- [ ] `nd-search`: motor de búsqueda interno.
- [ ] `nd-cache`: capa de caché de objetos/página con soporte Redis.

## v0.1.0-beta

- [ ] Integración completa entre los 13 paquetes vía `nd-core`.
- [ ] Auditoría de accesibilidad y Core Web Vitals.
- [ ] Hardening de seguridad (sanitización, nonces, capacidades) en toda la superficie REST/admin.
- [ ] Documentación completa (`Architecture.md`, `API.md`) actualizada.

## v0.1.0

- [ ] `nd-core-0.1.0.zip` y `nd-theme-0.1.0.zip` generados, instalados en una instancia WordPress limpia sin errores, con `composer run check` en verde en los 13 paquetes.
