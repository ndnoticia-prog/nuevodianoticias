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

## Verificación real del toolchain (alpha.1–alpha.5)

Con PHP 8.3+/Composer/Node ya instalados, se ejecutó por primera vez `composer install` y `composer run check` (PHPCS/WPCS + PHPStan nivel máximo + PHPUnit) en los 13 paquetes, además de `npm install && npm run build` en `nd-theme`. Resultado: **0 errores/warnings de PHPCS, 0 errores de PHPStan y 157 pruebas PHPUnit en verde en los 13 paquetes**; el build de Vite genera `dist/app.css`/`dist/app.js` correctamente. Esto resuelve todas las notas "Pending verification: composer install && composer run check sigue pendiente" de alpha.1 a alpha.5.

### Fixed

Bugs reales encontrados y corregidos durante la verificación (no solo ruido de linter):

- **`nd-core`**: `Activator`/`Deactivator`/`Uninstaller` ignoraban por completo el flag `$networkWide` — en una red multisitio, activar/desactivar/desinstalar el plugin "en toda la red" solo afectaba al sitio actual, dejando el resto de sitios sin tablas propias o con cron huérfano. Ahora iteran `get_sites()` + `switch_to_blog()` cuando corresponde.
- **`nd-seo`, `nd-cache`**: `get_the_category()`/`get_the_category($post)` recibía un objeto `WP_Post` en vez del ID esperado (`OpenGraphBuilder`, `BreadcrumbBuilder`, `CacheInvalidator`); funcionaba por la tolerancia interna de WordPress pero no era el contrato documentado.
- **`nd-seo`**: `OpenGraphBuilder` podía filtrar `false` (fallo de `get_the_date()`/`get_the_modified_date()`) dentro de un array tipado como solo-strings, produciendo una etiqueta OpenGraph con valor `false` en vez de omitirla.
- **`nd-cache`, `nd-search`**: `wp_is_post_revision()`/`wp_is_post_autosave()` devuelven `int|false`, no `bool`; el `||` los trataba como booleanos, lo que en PHPStan nivel `max` señaló una comparación implícita imprecisa.
- **`nd-workflow`**: `CalendarRepository::postsForMonth()` indexaba por `get_the_date()`, que puede devolver `false`; un fallo silencioso habría mezclado artículos de fechas distintas bajo la misma clave `0`.
- **Tests (`nd-seo`, `nd-media`)**: varios tests usaban `Functions\expect('mismaFuncion')->with(A)->andReturn(x)` seguido de otra expectativa `->with(B)` para la misma función — Brain Monkey no enruta por argumento entre expectativas así definidas y siempre devuelve la primera, dando falsos positivos silenciosos en `RobotsTxtBuilderTest`, `WebSiteSchemaTest`, `OrganizationSchemaTest` y `PodcastFeedEnhancerTest`. Corregido con `andReturnUsing()`.
- **Test (`nd-ai`)**: `ApiKeyStoreTest::test_round_trips_a_key_through_encryption` fallaba porque el mock de `get_option` usaba una arrow function (`fn () => $storedValue ?? $default`), que captura `$storedValue` por valor en el momento de definirse (antes de que `update_option` lo mutara), no por referencia — el código de producción (`ApiKeyStore`, `Encryption`) era correcto; el test estaba mal escrito.
- **Configuración del monorepo**: los `composer.json` de los 11 paquetes que dependen de `nd-core` solo declaraban `repositories` hacia `nd-core`, pero `nd-core` a su vez requiere otros 10 paquetes empaquetados — `composer install` fallaba en cada paquete si se ejecutaba de forma aislada (fuera de la raíz). Se completó el grafo de `repositories` en los 13 `composer.json`.
- **`packages/nd-api`**: carpeta vacía sin `composer.json`, referenciada como repositorio `path` en la raíz desde el scaffold inicial pero nunca implementada (bloqueaba `composer install` de la raíz). Se retira la referencia; la decisión de no crear `nd-api` como paquete separado ya estaba documentada en alpha.5 (ver nota más abajo).
- **`phpstan.neon.dist` (todos los paquetes salvo `nd-core`)**: incluían manualmente `vendor/szepeviktor/phpstan-wordpress/extension.neon` y `vendor/phpstan/phpstan-strict-rules/rules.neon`, que `phpstan/extension-installer` ya registra automáticamente; el doble registro hacía abortar PHPStan sin ningún resultado.
- **`phpcs.xml.dist` (todos los paquetes)**: solo excluía `WordPress.NamingConventions.ValidVariableName`; con `WordPress-Extra` completo, WPCS asume el estilo snake_case procedural de WordPress core y marcaba como error el PSR-12 camelCase que exige la especificación del proyecto en todo el código orientado a objetos. Se afinó el ruleset (ver `docs/Architecture.md`) en vez de renombrar cientos de símbolos públicos.
- Faltaba `patchwork.json` (`{"redefinable-internals": [...]}`) en `nd-media` y `nd-theme`, necesario para que Brain Monkey pueda interceptar `function_exists()`/`file_exists()`/`filemtime()` en sus tests.

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

- ~~`composer install`, `composer run check`...~~ **Resuelto**: ver "Verificación real del toolchain (alpha.1–alpha.5)" más arriba — `composer run check` en verde (0 errores PHPCS/PHPStan, 52 pruebas PHPUnit).
- Pruebas de integración con WordPress real (`wp-env` + suite oficial de WP) para `DatabaseManager`, `Migrator` y `QueueManager`: siguen pendientes, requieren un `$wpdb`/MySQL reales y no son cubribles de forma fiable solo con Brain Monkey.

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
- ~~`composer install && composer run check` en `nd-builder` y `nd-theme`...~~ **Resuelto**: ver "Verificación real del toolchain (alpha.1–alpha.5)" más arriba. Además se corrigieron 4 errores reales de PHPStan en `HomeContentProvider` (accesos `WP_Post` sobre valores `int|WP_Post` de `WP_Query::$posts` sin comprobar, y una propiedad `->term_id` sobre un tipo sin verificar) y se ejecutó `npm install && npm run build` en `nd-theme` (genera `dist/app.css`/`dist/app.js` correctamente).

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
- ~~`composer install && composer run check` en `nd-seo`, `nd-media` y `nd-discover`...~~ **Resuelto**: ver "Verificación real del toolchain (alpha.1–alpha.5)" más arriba.

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
- ~~`composer install && composer run check` en `nd-workflow`, `nd-ads` y `nd-analytics`...~~ **Resuelto**: ver "Verificación real del toolchain (alpha.1–alpha.5)" más arriba.

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
- ~~`composer install && composer run check` en `nd-ai`, `nd-search` y `nd-cache`...~~ **Resuelto**: ver "Verificación real del toolchain (alpha.1–alpha.5)" más arriba.

### Note

Con esta versión quedan implementados los paquetes de la arquitectura original salvo `nd-api`, que deliberadamente no se creó como paquete separado: su responsabilidad (superficie REST pública) vive distribuida en cada paquete a través de `NDCore\RestApi\Contracts\RegistersRoutes` y el filtro `nd_core/rest_controllers`, centralizados por `RestApiServiceProvider` de nd-core desde alpha.1.

## [0.1.0-beta.1] - Unreleased

### Added

- Infraestructura de pruebas de integración con WordPress/MySQL reales, sin Docker/`wp-env` (no disponible en este entorno): `git sparse-checkout` de `WordPress/wordpress-develop` para el arnés de pruebas oficial, MariaDB instalada sin `sudo` vía Homebrew de usuario, y un proyecto PHPUnit 9 aislado y compartido por todos los paquetes (`tools/wp-tests/phpunit9/`), porque el arnés de pruebas de WordPress core todavía llama a métodos eliminados en PHPUnit 11 (el que usan las pruebas unitarias de todos los paquetes). Ver `tools/wp-tests/README.md`.
- `nd-core`: 14 pruebas de integración reales para `DatabaseManager`, `Migrator` y `QueueManager` (pendientes desde alpha.1).
- `nd-core`: menú de admin compartido "ND Platform" — `AdminMenuServiceProvider` + filtro `nd_core/admin_pages`, `AdminPage` (value object) y el contrato `RegistersAdminPages`, mismo patrón que `nd_core/rest_controllers`. La página con `position` más bajo fija el slug del menú de nivel superior, siguiendo la convención de WooCommerce/Yoast en vez de crear una página "índice" separada y duplicada.
- `nd-core`: `AssetUrl::forPackage()` (nuevo) — resuelve la URL de un asset de un paquete empaquetado a partir de su nombre de paquete Composer conocido, en vez de introspeccionar `__DIR__` (ver corrección más abajo).
- `nd-workflow`: calendario editorial arrastrable (drag-and-drop nativo HTML5, sin librería externa) como primera página del menú "ND Platform"; nuevo `PATCH /workflow/posts/{id}/schedule` sobre `wp_update_post()`.
- `nd-ads`: gestor de campañas — CRUD completo (crear, editar, activar/desactivar, borrar) con estadísticas de impresiones/clics/CTR visibles por campaña; nuevo `CampaignController` REST (el paquete no tenía ninguna capa REST hasta ahora) y `CampaignRepository::all()`/`update()` (faltaban).
- `nd-analytics`: panel de solo lectura (en vivo, más leídas, autores/categorías top) sobre los endpoints REST ya existentes desde alpha.4, sin cambios en el backend.
- `nd-ai`: gestor de claves de API — guardar/borrar por proveedor (OpenAI/Claude/Gemini/DeepSeek), protegido por `Capability::MANAGE_ND_SETTINGS` (distinto de `USE_ND_AI`, que es para generar contenido, no para administrar credenciales); nuevo `ApiKeyController` REST; la clave completa nunca se devuelve al cliente una vez guardada, solo un booleano y los últimos 4 caracteres.
- `nd-search`: panel del índice — estadísticas, contenido indexado reciente, consulta de prueba y reconstrucción manual bajo demanda; nuevo `SearchController` REST (el paquete no tenía ninguna capa REST hasta ahora) y `SearchIndexer::reindexAll()`/`SearchIndexRepository::count()`/`recent()` (no existían).
- `nd-cache`: botón de purga manual — nuevo `CachePurgeController` REST sobre `CacheManager::flush()`, ya existente desde alpha.5, para los casos que `CacheInvalidator` no cubre (cambios que no pasan por `save_post`: un widget, un menú, la configuración del tema).
- `nd-search`: 8 pruebas de integración reales para `SearchIndexRepository`, incluyendo el propio FULLTEXT (orden por relevancia real de MySQL). Documenta dos comportamientos reales de MySQL/InnoDB descubiertos escribiéndolas (no defectos de nd-search): `NATURAL LANGUAGE MODE` descarta como stopword cualquier término presente en más del 50% de las filas indexadas; y las filas insertadas sin `COMMIT` no son visibles para `MATCH/AGAINST`, a diferencia de un `SELECT` normal — rompe el aislamiento habitual por transacción de `WP_UnitTestCase`.
- `nd-workflow`: 8 pruebas de integración reales para `CalendarRepository` (`WP_Query` con `date_query` y los estados editoriales personalizados `nd_in_review`/`nd_needs_changes`) y `EditorialNoteRepository`.
- `nd-ads`: 14 pruebas de integración reales para `CampaignRepository` (round-trip de los campos JSON `zones`/`category_slugs`/`creative`), `StatsRepository`/`StatsRecorder` (agregación real de impresiones/clics/CTR) y `ClickRedirectController` (`registerRewriteRule`/`registerQueryVar`; `maybeRedirect()` queda fuera deliberadamente, termina en `exit()` tras `wp_safe_redirect()`).
- `nd-analytics`: 13 pruebas de integración reales para `AnalyticsRepository` (`topAuthors`/`topCategories` con joins reales contra `wp_posts` y `wp_term_relationships`/`wp_term_taxonomy`/`wp_terms`), `PageviewRecorder` y `ImpressionRecorder`. `PageviewRecorderTest` verifica con una petición WordPress real (`go_to()`) la misma regla ya comprobada a mano en el navegador esta sesión: un editor viendo su propio artículo no genera pageview, una visita anónima sí.
- `nd-search`: 9 pruebas de integración reales adicionales para `SearchIndexer` (indexado/desindexado automático vía los hooks reales `save_post`/`before_delete_post`, exclusión de revisiones, `reindexAll()`) y `SearchQueryOverride` (sustitución del `LIKE` nativo por el FULLTEXT, solo observable disparando una petición real vía `go_to()`, ya que exige que la consulta sea la principal).
- `nd-seo`: 20 pruebas de integración reales para `SeoContextResolver`, `BreadcrumbBuilder`, `NewsArticleSchema` y `NewsSitemapController` (esta última invoca `buildXml()` por Reflection, ya que `maybeRender()` termina en `exit()` tras `wp_safe_redirect()`, mismo caso que `ClickRedirectController` en nd-ads).
- `nd-theme`: 5 pruebas de integración reales para `HomeContentProvider`. A diferencia del resto de paquetes, nd-theme es un TEMA, no un plugin: su `ThemeServiceProvider` solo se registra cuando WordPress carga el `functions.php` del tema activo, algo que el arnés de pruebas no simula — pero `HomeContentProvider` resultó ser PHP puro sin dependencias del Container, así que su bootstrap de integración no necesita simular la activación del tema, solo un autoloader PSR-4 mínimo propio.

Con esto quedan resueltas todas las notas "pendiente de pruebas de integración con WordPress real" documentadas desde alpha.1.

### Fixed

- **`nd-core`**: `TransientCacheDriver` perdía valores `null` cacheados — `wp_options.option_value` es `NOT NULL`, así que `set_transient($key, null, $ttl)` se guardaba como `''` en la base de datos, no como `null`; al leerlo de vuelta, `CacheManager::remember()` lo trataba como un valor cacheado válido en vez de invocar de nuevo al callback, y ese `''` no coincidía con el tipo declarado por el callback. Provocó un `TypeError` fatal real en `UpdateChecker::latestRelease()` que tumbaba todo el admin — encontrado en vivo verificando una página de admin en el navegador, no por ningún test. Corregido envolviendo `null` con un centinela antes de guardarlo.
- **`nd-core`**: `AdminMenuServiceProvider` estaba completamente implementado pero nunca se había añadido a `Application::resolveProviderClasses()` — el menú "ND Platform" nunca aparecía en `wp-admin`. Solo detectado por verificación real en navegador; ningún test unitario o de integración ejercitaba la cadena completa `Application::resolveProviderClasses()` → menú registrado.
- **`nd-core`**: `AssetUrl::for()` devolvía `''` para paquetes empaquetados en entorno de desarrollo (repositorios `path` de Composer, symlinked) — `__DIR__` dentro de un paquete hermano symlinked resuelve a su ruta canónica real, no a la ruta relativa al plugin alcanzada a través del symlink de `vendor/`, así que el CSS/JS de las páginas de admin nunca se encolaba. Corregido con el nuevo `AssetUrl::forPackage()`.
- **`nd-workflow` (JS)**: `calendar.js` construía las URLs de la REST API con concatenación de cadenas ingenua, rota en instalaciones de WordPress con enlaces permanentes "Simple" (la forma por defecto de una instalación nueva), donde `rest_url()` devuelve `?rest_route=...` en vez de la forma "bonita" `/wp-json/...`. Corregido con un helper `apiUrl()` basado en la API `URL`/`URLSearchParams`, reutilizado en todos los paneles de admin nuevos.
