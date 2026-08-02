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

- [x] `nd-seo`: `SeoContext`/`SeoContextResolver` (única fuente de verdad por página: singular, home, archivo, búsqueda, 404).
- [x] `nd-seo`: meta tags en `wp_head` — robots (con `max-image-preview:large` para elegibilidad en Discover), canonical, OpenGraph, Twitter Cards.
- [x] `nd-seo`: Schema.org JSON-LD como un único `@graph` — `Organization`, `WebSite` (con `SearchAction`), `NewsArticle`, `BreadcrumbList`.
- [x] `nd-seo`: breadcrumbs (`BreadcrumbBuilder`/`BreadcrumbRenderer`) integradas en `nd-theme` (`single.php`, `archive.php`, `search.php`).
- [x] `nd-seo`: sitemap de Google News (`/sitemap-news.xml`, artículos de las últimas 48h) — el sitemap general se reutiliza deliberadamente de WordPress core (`wp-sitemap.xml`) en lugar de reimplementarlo.
- [x] `nd-seo`: `robots.txt` con directivas `Sitemap:`.
- [x] `nd-core`: `nd-seo` añadido a `require` (empaquetado) y `SeoServiceProvider` registrado automáticamente en `Application`.
- [x] Suite de pruebas PHPUnit (Brain Monkey) para las piezas comprobables sin `WP_Post`/`WP_Query` reales (`RobotsMetaBuilder`, `TwitterCardBuilder`, `OrganizationSchema`, `WebSiteSchema`, `RobotsTxtBuilder`, `SchemaOutput`, `SeoServiceProvider`).
- [ ] Pruebas de integración con WordPress real para `SeoContextResolver`, `BreadcrumbBuilder`, `NewsArticleSchema` y `NewsSitemapController`: dependen de `WP_Post`/`WP_Query` reales (mismo caso que `HomeContentProvider` en alpha.2).
- [x] `nd-media`: WebP/AVIF vía el filtro nativo `image_editor_output_format` (con detección real de soporte GD); `sizes` responsive alineado a los breakpoints de nd-theme; CDN (`wp_get_attachment_url` + `the_content`); video embebido responsive (oEmbed); podcast (RSS2 `<enclosure>` + namespace de iTunes). Deliberadamente **no** reimplementa `srcset` ni lazy load: ya son nativos de WordPress core desde 4.4/5.5.
- [x] `nd-discover`: tamaño de imagen destacada `nd-discover-featured` (1200×675, ≥1200px de ancho requerido por Discover), registrado en `after_setup_theme` y consumido por `nd-seo`/`nd-theme` como contrato de nombre de tamaño (sin acoplar esos paquetes a nd-discover).
- [x] `nd-core`: `nd-media` y `nd-discover` añadidos a `require` (empaquetados) y sus providers registrados automáticamente en `Application`.
- [x] Suite de pruebas PHPUnit (Brain Monkey) para `nd-media` (incluyendo `function_exists('imagewebp'/'imageavif')` interceptado para no depender del GD real de la máquina de CI) y `nd-discover`.
- [ ] Verificación real con el toolchain instalado: `composer install && composer run check` en verde en `nd-seo`, `nd-media` y `nd-discover` (pendiente del mismo entorno de desarrollo que el resto de paquetes).

## v0.1.0-alpha.4 — Editorial y publicidad

- [x] `nd-workflow`: estados editoriales adicionales (`nd_in_review`, `nd_needs_changes`) vía `register_post_status()`; comentarios internos (tabla propia `nd_editorial_notes`, deliberadamente separada de los comentarios públicos de WP); asignaciones (post meta); datos del calendario editorial (`CalendarRepository`, sin interfaz visual todavía); REST (`/workflow/posts/{id}/notes`, `/workflow/posts/{id}/assignment`, `/workflow/calendar`). Roles/capacidades ya existían desde `nd-core` (`Capability::EDIT_ND_WORKFLOW`). Versionado/correcciones reutilizan las revisiones nativas de WordPress en vez de reimplementarlas.
- [x] `nd-ads`: motor de campañas (`nd_ad_campaigns`/`nd_ad_events`) — AdSense, Google Ad Manager, HTML, imagen, video y patrocinados; segmentación por categoría y zona; prioridad; programación (`starts_at`/`ends_at`); estadísticas (impresiones/clics/CTR); clic con redirección resuelta en servidor (`/nd-ads/click/{id}`, sin riesgo de open-redirect); shortcode `[nd_ad zone="..."]`; zonas fijas en nd-theme (cabecera, tras el contenido del artículo).
- [x] `nd-analytics`: pageviews del lado del servidor sin JS (hook `wp`, excluye personal editorial), hash de visitante sin PII en crudo (`wp_hash` + fecha del día, rota diariamente); "tiempo real" por consulta directa de los últimos N minutos; más leídas, autores, categorías; CTR (impresiones de bloques de portada vs. pageviews) mediante un evento interno `NDBuilder\Events\BlockRendered` que desacopla nd-analytics de nd-builder/nd-theme; panel REST (`/analytics/*`) protegido con `Capability::VIEW_ND_ANALYTICS`. No depende de Google Analytics para nada de esto.
- [x] `nd-core`: `DatabaseManager::wpTable()` (nuevo) para referenciar tablas nativas de WordPress desde consultas propias; `nd-workflow`, `nd-ads` y `nd-analytics` añadidos a `require` (empaquetados) y sus providers registrados automáticamente en `Application`.
- [x] Suite de pruebas PHPUnit (Brain Monkey) para las piezas comprobables sin `WP_Post`/`WP_Query`/`$wpdb` reales.
- [ ] Sin interfaz visual de administración (calendario arrastrable, gestor de campañas, panel de analítica): esta versión entrega la capa de datos y REST; la UI queda para una versión posterior.
- [ ] Pruebas de integración con WordPress real para todo lo que depende de `DatabaseManager`/`WP_Query`/`WP_Post` (`EditorialNoteRepository`, `CalendarRepository`, `CampaignRepository`, `StatsRecorder`/`StatsRepository`, `ClickRedirectController`, `PageviewRecorder`, `ImpressionRecorder`, `AnalyticsRepository`): mismo caso ya documentado para `DatabaseManager`/`Migrator` en alpha.1.
- [ ] Verificación real con el toolchain instalado: `composer install && composer run check` en verde en `nd-workflow`, `nd-ads` y `nd-analytics` (pendiente del mismo entorno de desarrollo que el resto de paquetes).

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
