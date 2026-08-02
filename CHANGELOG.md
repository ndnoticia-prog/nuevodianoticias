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

## [0.1.0-alpha.3] - Unreleased

### Added

Paquete `nd-seo` (nuevo) — SEO automático:

- `SeoContext`/`SeoContextResolver`: resuelve una única vez, por petición, el título/descripción/URL canónica/imagen/tipo/`noindex` de la página actual (singular, home, archivo, búsqueda, 404), consumido por el resto del paquete para no repetir esa lógica.
- Meta tags en `wp_head`: `robots` (con `max-image-preview:large`, requisito de elegibilidad para Google Discover), `<link rel="canonical">`, OpenGraph y Twitter Cards.
- Schema.org: JSON-LD como un único `<script type="application/ld+json">` con `@graph` — `Organization`, `WebSite` (con `SearchAction` para el buscador interno), `NewsArticle` (en vez de `Article`, para elegibilidad en Google News/Discover) y `BreadcrumbList`. Codificado con `JSON_HEX_TAG | JSON_HEX_AMP` para evitar inyección de HTML si un título contuviera literalmente `</script>`.
- Breadcrumbs: `BreadcrumbBuilder` (misma lógica para el HTML y el `BreadcrumbList` de Schema.org) y `BreadcrumbRenderer`, integradas en `nd-theme` (`single.php`, `archive.php`, `search.php`).
- Sitemap de Google News (`/sitemap-news.xml`, artículos publicados en las últimas 48h configurable). El sitemap general **no** se reimplementa: se reutiliza `wp-sitemap.xml` de WordPress core.
- `robots.txt`: directivas `Sitemap:` hacia el sitemap general de core y el de noticias.
- Suite de pruebas PHPUnit (Brain Monkey) para las piezas comprobables sin `WP_Post`/`WP_Query` reales.

Paquete `nd-media` (nuevo) — optimización multimedia:

- WebP/AVIF: `ModernFormatConverter` usa el filtro nativo `image_editor_output_format` (WordPress 5.8+), detectando en tiempo real si el GD del servidor soporta `imagewebp()`/`imageavif()` (degrada de AVIF a WebP, o desactiva la conversión, si no hay soporte).
- Responsive: `ResponsiveImageSizer` sustituye el `sizes` por defecto de WordPress (basado solo en el ancho intrínseco de la imagen) por uno alineado a los breakpoints reales de nd-theme. `srcset` y lazy load (`loading="lazy"`) **no** se reimplementan: son nativos de WordPress core desde 4.4/5.5.
- CDN: `CdnUrlRewriter` reescribe URLs de `wp-content/uploads` a un dominio configurado (`wp_get_attachment_url` + `the_content`).
- Video: `ResponsiveEmbedWrapper` envuelve los `<iframe>` de oEmbed en un contenedor con proporción de aspecto fija (estilo en nd-theme, `.nd-video-embed`).
- Podcast: `PodcastFeedEnhancer` extiende el feed RSS2 nativo de WordPress con el namespace de iTunes y `<enclosure>` para entradas con audio asociado, en vez de un generador de feeds propio.
- Suite de pruebas PHPUnit (Brain Monkey), incluyendo `function_exists()` interceptado para no depender del GD real de la máquina donde corran los tests.

Paquete `nd-discover` (nuevo) — requisitos técnicos de Google Discover:

- `ImageSizes::FEATURED` (`nd-discover-featured`, 1200×675): WordPress core no tiene un tamaño ≥1200px de ancho por defecto (`large` se queda en 1024px), y Google exige ese mínimo para el carrusel visual grande de Discover. Se registra en `after_setup_theme`.
- `nd-seo` (`SeoContextResolver`) y `nd-theme` (`HomeContentProvider`, solo para el hero) referencian ese nombre de tamaño como cadena literal — no como dependencia de Composer hacia nd-discover — con fallback explícito a `large` si el tamaño no existe.

### Changed

- `nd-core`: `nd-seo`, `nd-media` y `nd-discover` se añaden a `require` (se empaquetan dentro del plugin) y sus `ServiceProvider` se registran automáticamente en la lista de providers por defecto de `Application` (protegidos con `class_exists()`), siguiendo el mismo patrón que `nd-builder`.
- `nd-theme`: `single.php`, `archive.php` y `search.php` ahora imprimen la ruta de navegación (`NDSeo\Breadcrumbs\BreadcrumbRenderer`); `nd-seo` se añade a `require-dev` (repositorio `path` local) para desarrollo/análisis estático; la imagen del bloque hero usa el tamaño de nd-discover cuando está disponible.

### Pending verification

- `SeoContextResolver`, `BreadcrumbBuilder`, `NewsArticleSchema` y `NewsSitemapController` dependen de `WP_Post`/`WP_Query` reales: necesitan pruebas de integración con WordPress real, mismo caso que `HomeContentProvider` en alpha.2.
- Si `/sitemap-news.xml` devuelve 404 justo tras activar `nd-core`, es la limitación conocida de WordPress con rewrite rules añadidas durante la propia activación (ver "SEO" en `docs/Architecture.md`) — se resuelve guardando los enlaces permanentes una vez desde el admin.
- `composer install && composer run check` en `nd-seo`, `nd-media` y `nd-discover` sigue pendiente del mismo entorno de desarrollo (PHP/Composer) que el resto de paquetes.

## [0.1.0-alpha.4] - Unreleased

### Added

Paquete `nd-workflow` (nuevo) — flujo editorial:

- Estados editoriales adicionales `nd_in_review`/`nd_needs_changes` (`register_post_status()`), complementarios a los nativos de WordPress, no sustitutos.
- Comentarios internos (`EditorialNoteRepository`, tabla propia `nd_editorial_notes`) — deliberadamente separados de los comentarios públicos nativos de WordPress.
- Asignaciones (`AssignmentManager`, post meta `_nd_assigned_to`).
- Datos del calendario editorial (`CalendarRepository`): artículos agrupados por día para un mes dado. Sin interfaz visual en esta versión.
- REST: `/workflow/posts/{id}/notes` (GET/POST), `/workflow/notes/{id}` (DELETE), `/workflow/posts/{id}/assignment` (POST/DELETE), `/workflow/calendar` (GET) — todos protegidos con `Capability::EDIT_ND_WORKFLOW`.
- Versionado y correcciones reutilizan las revisiones nativas de WordPress: no se reimplementa un sistema de versiones propio.

Paquete `nd-ads` (nuevo) — motor de publicidad propio:

- Campañas (`nd_ad_campaigns`) con tipo (AdSense, Google Ad Manager, HTML, imagen, video, patrocinado), prioridad, programación (`starts_at`/`ends_at`), segmentación por categoría y zona.
- `AdRenderer`: genera el HTML de cada tipo de campaña, deliberadamente puro (sin efectos secundarios ni acceso a base de datos).
- `AdZoneRenderer`: "seleccionar + renderizar + registrar impresión" en un único lugar, usado tanto por el shortcode `[nd_ad zone="..."]` como por las zonas fijas de nd-theme (cabecera, tras el contenido del artículo).
- Estadísticas (`nd_ad_events`: impresiones/clics, `StatsRepository` con CTR agregado).
- Clic con redirección resuelta en el servidor (`/nd-ads/click/{id}`): el destino se busca por ID, nunca se acepta una URL de destino por parámetro — sin riesgo de open-redirect.

Paquete `nd-analytics` (nuevo) — analítica editorial propia, sin depender de Google Analytics:

- `PageviewRecorder`: registro de visitas del lado del servidor (hook `wp`, sin JavaScript), excluyendo explícitamente al personal editorial (`edit_posts`) de las estadísticas.
- `VisitorHasher`: identificador de visitante sin almacenar IP/user-agent en crudo — `wp_hash()` sobre IP+UA+fecha del día, rota diariamente.
- `ImpressionRecorder`: impresiones de artículos mostrados en los bloques `hero`/`noticias` de portada, mediante un nuevo evento interno `NDBuilder\Events\BlockRendered` (no un hook de WordPress) que desacopla nd-analytics de nd-builder/nd-theme.
- `AnalyticsRepository`: más leídas, "tiempo real" (consulta directa de los últimos N minutos, sin websockets), autores y categorías más vistos, CTR por artículo (pageviews vs. impresiones).
- REST (`/analytics/top-posts`, `/analytics/active-now`, `/analytics/top-authors`, `/analytics/top-categories`, `/analytics/posts/{id}/ctr`), protegido con `Capability::VIEW_ND_ANALYTICS`.

Suite de pruebas PHPUnit (Brain Monkey) en los tres paquetes para las piezas comprobables sin `WP_Post`/`WP_Query`/`$wpdb` reales.

### Changed

- `nd-core`: nuevo `DatabaseManager::wpTable()` para referenciar tablas nativas de WordPress (`wp_posts`, `wp_terms`, ...) desde consultas propias, distinto de `table()` (siempre con infijo `nd_`, para tablas propias de la plataforma). `nd-workflow`, `nd-ads` y `nd-analytics` se añaden a `require` (empaquetados) y sus `ServiceProvider` se registran automáticamente en `Application`.
- `nd-builder`: `Renderer::render()` ahora despacha `NDBuilder\Events\BlockRendered` (vía `NDCore\Events\EventDispatcher`) cuando un bloque produce HTML no vacío.
- `nd-theme`: nueva zona de anuncios en la cabecera (`template-parts/header/site-header.php`) y tras el contenido del artículo (`single.php`), usando `NDAds\Rendering\AdZoneRenderer`.

### Fixed

- `AdRenderer` inicialmente dependía de `StatsRecorder` (acceso a base de datos) para registrar impresiones, lo que además de mezclar renderizado con un efecto secundario lo hacía imposible de probar de forma aislada. Se separó: `AdRenderer` quedó puro y `AdZoneRenderer` (nuevo) asumió la responsabilidad de registrar la impresión.

### Pending verification

- Sin interfaz visual de administración (calendario arrastrable, gestor de campañas, panel de analítica): esta versión entrega la capa de datos y REST; la UI queda para una versión posterior.
- `EditorialNoteRepository`, `CalendarRepository`, `CampaignRepository`, `StatsRecorder`/`StatsRepository`, `ClickRedirectController`, `PageviewRecorder`, `ImpressionRecorder` y `AnalyticsRepository` dependen de `DatabaseManager`/`WP_Query`/`WP_Post` reales: necesitan pruebas de integración con WordPress real, mismo caso ya documentado para `DatabaseManager`/`Migrator` en alpha.1.
- `composer install && composer run check` en `nd-workflow`, `nd-ads` y `nd-analytics` sigue pendiente del mismo entorno de desarrollo (PHP/Composer) que el resto de paquetes.

## [0.1.0-alpha.5] - Unreleased

### Added

Paquete `nd-ai` (nuevo) — proveedor de IA desacoplado:

- `AiProvider` (contrato) con cinco implementaciones sobre `NDCore\Http\Client`: `OpenAiProvider`, `ClaudeProvider`, `GeminiProvider`, `DeepSeekProvider`, `LocalLlmProvider` (cualquier servidor compatible con la API de OpenAI: Ollama, LM Studio, vLLM, ...).
- `AiManager`: resuelve el proveedor activo (configurable) sin que el código que lo consume conozca cuál es.
- `ApiKeyStore`: primer consumidor real de `NDCore\Security\Encryption` — las claves de API de cada proveedor se cifran con `sodium_crypto_secretbox` antes de guardarse en `wp_options`, nunca en texto plano.
- `ContentAssistant`: titulares, título SEO, meta description, etiquetas, categorías sugeridas, resumen, extracto, publicaciones para Facebook/Instagram/X/LinkedIn, bloque de newsletter y guion de video — un método por tarea, cada uno con su propio prompt.
- REST: `POST /ai/posts/{id}/generate` (`task` como parámetro), protegido con `Capability::USE_ND_AI`.
- Suite de pruebas PHPUnit (Brain Monkey) más completa que en paquetes anteriores: al no depender de base de datos, `AiManager`, `ContentAssistant`, `OpenAiProvider` y el wiring completo de `AiServiceProvider` sí son comprobables sin WordPress real.

Paquete `nd-search` (nuevo) — motor de búsqueda interno:

- Índice `FULLTEXT` propio (`nd_search_index`), deliberadamente **sin** alterar el esquema de `wp_posts` (una tabla core de WordPress).
- `SearchIndexer`: mantiene el índice sincronizado (`save_post`/`before_delete_post`).
- `SearchQueryOverride`: sustituye el `LIKE` por defecto de WordPress en la consulta principal de búsqueda por resultados del índice FULLTEXT ordenados por relevancia (`pre_get_posts` + `posts_search`), sin tocar `get_search_query()`/`is_search()` — `search.php` de nd-theme sigue funcionando sin cambios.

Paquete `nd-cache` (nuevo) — caché de página completa:

- `PageCacheStore`/`PageCacheMiddleware`: HTML servido directamente en `template_redirect` (sin ejecutar el resto de WordPress) cuando hay caché; captura y almacena la salida vía output buffering cuando no la hay. Reutiliza `NDCore\Cache\CacheManager` como backend en lugar de duplicar la caché de objetos que nd-core ya tiene.
- Exclusiones deliberadamente conservadoras: nunca cachea usuarios autenticados, admin, AJAX/REST/cron ni búsquedas.
- `CacheInvalidator`: purga la página del artículo, la portada y sus categorías en `save_post`.

### Changed

- `nd-core`: `nd-ai`, `nd-search` y `nd-cache` se añaden a `require` (se empaquetan dentro del plugin) y sus `ServiceProvider` se registran automáticamente en la lista de providers por defecto de `Application` (protegidos con `class_exists()`).

### Pending verification

- Sin interfaz visual (gestor de claves de IA, panel de resultados de búsqueda, purga manual de caché): esta versión entrega la capa de datos/lógica y REST donde aplica.
- `nd-search` no tiene ninguna pieza comprobable sin WordPress real (`SearchIndexRepository`/`SearchIndexer`/`SearchQueryOverride` dependen de `WP_Post`/`WP_Query`/`$wpdb`): necesita pruebas de integración completas.
- `composer install && composer run check` en `nd-ai`, `nd-search` y `nd-cache` sigue pendiente del mismo entorno de desarrollo (PHP/Composer) que el resto de paquetes.

### Note

Con esta versión quedan implementados los paquetes de la arquitectura original salvo `nd-api`, que deliberadamente no se creó como paquete separado: su responsabilidad (superficie REST pública) vive distribuida en cada paquete a través de `NDCore\RestApi\Contracts\RegistersRoutes` y el filtro `nd_core/rest_controllers`, centralizados por `RestApiServiceProvider` de nd-core desde alpha.1.
