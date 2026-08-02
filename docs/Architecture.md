# Arquitectura de ND Platform

## Principios

1. **`nd-core` es el único punto de acceso a WordPress.** Ningún otro paquete llama directamente a `add_action`, `add_filter`, `$wpdb` o funciones nativas de WP fuera de una capa de abstracción de `nd-core`. Esto permite probar unitariamente el resto de paquetes sin un WordPress real (usando `Brain\Monkey`).
2. **`nd-theme` no contiene lógica de negocio.** Solo composición visual: recibe datos ya resueltos por `nd-core`/`nd-builder` y los renderiza.
3. **Cada paquete es una unidad Composer independiente** con su propio `composer.json`, namespace PSR-4 y `ServiceProvider`. Un paquete puede probarse y versionarse sin instalar el resto.
4. **Inyección de dependencias explícita.** Nada se instancia con `new` fuera de un `ServiceProvider` o una factory; todo se resuelve a través del `Container` de `nd-core`.
5. **Sin duplicación.** Una capacidad (caché, HTTP, logging) vive en un único paquete y el resto la consume vía su interfaz pública, nunca reimplementándola.

## Ciclo de vida de la aplicación

```
WordPress carga nd-core.php (bootstrap del plugin)
  → Application::boot()
      → Container: registro de bindings base (Config, EventDispatcher, HookManager, ...)
      → Carga config/*.php → Config
      → Resuelve ServiceProviders registrados (core + cada paquete instalado)
          → register(): bindings del paquete en el Container
          → boot(): registro de hooks/rutas/comandos del paquete
      → HookManager::flush(): traduce los listeners registrados a add_action/add_filter reales
```

`Application` es un singleton gestionado por el propio `Container` (no un patrón Singleton clásico con estado estático fuera del contenedor), de forma que sigue siendo sustituible en pruebas.

## Namespaces

| Paquete | Namespace raíz |
|---|---|
| nd-core | `NDCore\` |
| nd-theme | `NDTheme\` |
| nd-builder | `NDBuilder\` |
| nd-api | `NDApi\` |
| nd-seo | `NDSeo\` |
| nd-ads | `NDAds\` |
| nd-media | `NDMedia\` |
| nd-workflow | `NDWorkflow\` |
| nd-analytics | `NDAnalytics\` |
| nd-ai | `NDAi\` |
| nd-cache | `NDCache\` |
| nd-discover | `NDDiscover\` |
| nd-search | `NDSearch\` |

## Contenedor de inyección de dependencias

`NDCore\Container\Container` implementa `Psr\Container\ContainerInterface`. Soporta:

- `bind(string $abstract, callable|string $concrete, bool $shared = false)`
- `singleton(string $abstract, callable|string $concrete)`
- `instance(string $abstract, object $instance)`
- `make(string $abstract, array $parameters = [])` con **autowiring** vía `ReflectionClass` cuando no hay binding explícito y la clase es instanciable.
- `get(string $id)` / `has(string $id)` (contrato PSR-11).

El autowiring resuelve type-hints de clases/interfaces recursivamente; parámetros escalares sin valor por defecto y sin binding explícito lanzan `NDCore\Container\Exceptions\UnresolvableParameterException`.

## Hooks vs. Events

- **`HookManager`** (`NDCore\Hooks`) es la única puerta hacia el sistema de hooks de WordPress (`add_action`/`add_filter`/`do_action`/`apply_filters`). Permite registrar listeners de forma diferida (antes de que WordPress esté cargado, útil en pruebas) y desregistrarlos por token.
- **`EventDispatcher`** (`NDCore\Events`) es un bus de eventos interno de la plataforma (PSR-14-like, sin dependencia externa) para comunicación entre paquetes de ND Platform que **no** necesita ser un hook de WordPress (p. ej. `ArticlePublished`, `AiContentGenerated`). Un `ServiceProvider` puede optar por reenviar un evento interno como hook de WordPress si necesita compatibilidad con otros plugins.

## Base de datos y migraciones

`NDCore\Database\DatabaseManager` envuelve `$wpdb` y expone `select`, `insert`, `update`, `delete`, `statement` con prepared statements obligatorios (nunca interpolación directa de variables en SQL). `NDCore\Migrator\Migrator` versiona el esquema en una tabla `{prefix}nd_migrations` y ejecuta migraciones idempotentes definidas como clases con `up()`/`down()`, registradas por cada paquete en su `ServiceProvider::migrations()`.

## Seguridad

- Toda entrada de usuario se sanitiza en el borde (controlador REST/admin), nunca en capas internas.
- Toda escritura desde formularios admin exige verificación de nonce (`NDCore\Security\Nonce`) y capacidad (`NDCore\Permissions\PermissionManager`).
- Credenciales de terceros (claves de proveedores de IA, tokens de servicios de anuncios) se cifran en reposo con `NDCore\Security\Encryption` (`sodium_crypto_secretbox`), nunca en texto plano en `wp_options`.

## Rendimiento

- Cualquier consulta repetible pasa por `NDCore\Cache\CacheManager`, que resuelve el driver activo (`transient`, `object-cache`, `redis`) de forma transparente para el consumidor.
- Trabajo pesado (llamadas a proveedores de IA, procesamiento de medios, envío de notificaciones) se despacha a `NDCore\Queue\QueueManager`, ejecutado de forma asíncrona vía WP-Cron, nunca de forma síncrona en el hilo de una petición HTTP.

## Dependencias entre paquetes: "provisto en runtime" vs. "empaquetado"

`nd-core` y `nd-theme` son los dos únicos paquetes que se instalan directamente en WordPress (como plugin y como tema respectivamente). El resto de paquetes (`nd-builder`, `nd-seo`, `nd-ads`, `nd-ai`, ...) no se instalan por separado: sus clases viajan **dentro del `vendor/` de `nd-core`**, declaradas en su `require` de Composer, y quedan disponibles globalmente en el proceso de PHP en cuanto el plugin `nd-core` carga su autoloader (esto ocurre antes de que WordPress cargue el tema).

Por eso `nd-builder` y `nd-theme` declaran `ndnoticia/nd-core` (y, en el caso de `nd-theme`, también `ndnoticia/nd-builder`) únicamente en `require-dev`, resuelto mediante un repositorio `path` propio hacia el paquete hermano. Esto permite que localmente (`composer install` completo) las clases estén disponibles para IDEs, PHPStan y PHPUnit, pero que un `composer install --no-dev` —el que usa `tools/build/package.sh` para generar el zip instalable— **no** las vendorice. Si lo hiciera, un mismo namespace (p. ej. `NDCore\Providers\ServiceProvider`) quedaría declarado dos veces en el mismo proceso de PHP (una vez por el plugin `nd-core` activo, otra por la copia empaquetada dentro de `nd-theme`), lo que WordPress no tolera: produce un fatal error `Cannot declare class ..., already declared`.

Regla práctica: si un paquete B es consumido en tiempo de ejecución por un paquete A que WordPress activa por separado (plugin/tema), y ambos podrían estar activos a la vez, B se declara en el `require` de **quien lo empaqueta** (aquí, siempre `nd-core`) y en el `require-dev` de cualquier otro paquete que solo lo necesite para desarrollo/análisis estático.

`nd-core` registra automáticamente `NDBuilder\Providers\BuilderServiceProvider` en su lista de providers por defecto (protegido con `class_exists()`), y `nd-theme` se autoregistra a través del filtro público `nd_core/providers` desde `functions.php`, comprobando antes que `NDCore\Application` exista para degradar sin fatal error si el plugin no está activo.

## SEO: qué reimplementa nd-seo y qué reutiliza de WordPress core

- **Sitemap general**: nd-seo **no** reimplementa un sitemap XML genérico. WordPress core expone `wp-sitemap.xml` (y sus sub-sitemaps) desde la 5.5, mantenido y con el protocolo de sitemaps.org correctamente implementado; reescribirlo sería duplicar código sin necesidad. `NDSeo\Robots\RobotsTxtBuilder` simplemente le añade la directiva `Sitemap:` a `robots.txt`.
- **Sitemap de Google News**: este SÍ es nuevo (`NDSeo\Sitemap\NewsSitemapController`, sirviendo `/sitemap-news.xml`) porque WordPress core no lo provee: usa un espacio de nombres XML distinto (`news:`) y solo incluye artículos de las últimas horas (configurable en `config/seo.php`, por defecto 48h).
- **JSON-LD**: `SchemaOutput` imprime un único `<script type="application/ld+json">` con un `@graph` (en vez de un `<script>` por tipo) y codifica con `JSON_HEX_TAG | JSON_HEX_AMP`: sin esos flags, un título de artículo que contuviera literalmente `</script>` cerraría el bloque e inyectaría HTML/JS arbitrario en la página — es una clase de vulnerabilidad real y conocida en plugins de SEO para WordPress.
- **Rewrite rule del sitemap de noticias y activación**: como es una limitación conocida de WordPress (las reglas de rewrite añadidas dentro del propio hook de activación no llegan a tiempo para el `flush_rewrite_rules()` que corre en la misma petición, porque `init` ya se disparó antes de que se ejecute el hook de activación), si `/sitemap-news.xml` da 404 justo tras activar `nd-core`, basta con ir a Ajustes → Enlaces permanentes y pulsar "Guardar cambios" una vez.

## Multimedia: qué reimplementa nd-media y qué reutiliza de WordPress core

- **Responsive images (`srcset`/`sizes`)**: WordPress core ya genera `srcset` automáticamente desde la 4.4. nd-media **no** lo reimplementa; `NDMedia\Optimization\ResponsiveImageSizer` solo sustituye el `sizes` calculado por defecto (que WP basa únicamente en el ancho intrínseco de la imagen, casi siempre incorrecto en una cuadrícula) por uno alineado a los breakpoints reales de nd-theme.
- **Lazy load**: WordPress core añade `loading="lazy"` automáticamente desde la 5.5. nd-media no lo toca; nd-theme ya usa `loading="eager"` explícito solo en la imagen del hero (LCP) y deja el resto en el comportamiento por defecto de core.
- **WebP/AVIF**: sí es nuevo. `NDMedia\Optimization\ModernFormatConverter` usa el filtro nativo `image_editor_output_format` (WordPress 5.8+) para que los tamaños intermedios de JPEG/PNG subidos se generen en WebP o AVIF, comprobando en tiempo real si el GD del servidor soporta `imagewebp()`/`imageavif()` (PHP >= 8.1) antes de activarlo — degrada a no convertir si no hay soporte, nunca fuerza un formato no disponible.
- **CDN**: `NDMedia\Cdn\CdnUrlRewriter` reescribe URLs de `wp-content/uploads` a un dominio de CDN configurado (`wp_get_attachment_url` + `the_content`). No sincroniza archivos con el CDN (eso es infraestructura del hosting/CDN, fuera del alcance de un plugin).
- **Video**: se apoya en oEmbed (ya nativo de WordPress) y solo añade `NDMedia\Video\ResponsiveEmbedWrapper`, que envuelve el `<iframe>` resultante en un contenedor con proporción de aspecto fija — el estilo (`.nd-video-embed`) vive en nd-theme, no en nd-media, siguiendo la misma separación lógica/presentación del resto de la plataforma.
- **Podcast**: en vez de un generador de feeds propio, `NDMedia\Podcast\PodcastFeedEnhancer` extiende el feed RSS2 nativo de WordPress (`rss2_ns`/`rss2_item`) con el namespace de iTunes y `<enclosure>` para las entradas que tengan un audio asociado (meta `_nd_podcast_audio_url`, configurable).

## Google Discover: el tamaño de imagen destacada como contrato entre paquetes

`nd-seo` ya cubre el requisito de `max-image-preview:large` en la meta `robots`. El requisito que faltaba —imagen destacada de al menos 1200px de ancho— lo cubre `nd-discover`, que registra un tamaño de imagen propio (`NDDiscover\ImageSizes::FEATURED = 'nd-discover-featured'`, 1200×675) en `after_setup_theme`.

`NDSeo\Context\SeoContextResolver` (para `og:image`/Schema.org) y `NDTheme\Content\HomeContentProvider` (para la imagen del bloque hero) referencian ese nombre de tamaño como **cadena literal**, no como una dependencia de Composer hacia `nd-discover` — el mismo patrón de bajo acoplamiento que la convención `template-parts/blocks/{type}` entre nd-builder y nd-theme. Si nd-discover no está activo, `get_the_post_thumbnail_url()` devuelve `false` para ese tamaño inexistente y ambos hacen fallback explícito a `large`, así que nunca hay un fatal error ni una imagen rota por depender de un paquete opcional.

## Plantillas de tema: evitar duplicar la jerarquía de WordPress

`nd-theme` no define `category.php`, `tag.php` ni `author.php` por separado. `archive.php` es el *fallback* que WordPress usa automáticamente para esos tres contextos (además de archivos por fecha y por tipo de contenido), y las funciones nativas `the_archive_title()` / `the_archive_description()` ya adaptan su salida a cada uno (incluyendo la biografía del autor). Crear tres archivos casi idénticos a `archive.php` solo duplicaría la misma cuadrícula sin aportar nada; `archive.php` añade la única pieza que sí es distinta por contexto (el avatar cuando `is_author()`).

## Comunicación entre paquetes sin acoplarlos: eventos internos

`NDCore\Events\EventDispatcher` (ver "Hooks vs. Events" arriba) existe precisamente para casos como este: `nd-analytics` necesita saber qué artículos aparecieron en los bloques `hero`/`noticias` de la portada para calcular impresiones y CTR, pero `nd-builder` no debe conocer la existencia de `nd-analytics`.

La solución: `NDBuilder\Renderer::render()` despacha un evento interno `NDBuilder\Events\BlockRendered` (con el `Block` y el HTML resultante) cada vez que un bloque produce salida no vacía — **no** es un hook de WordPress, es puro bus de eventos de la plataforma. `NDAnalytics\Providers\AnalyticsServiceProvider` se suscribe a `BlockRendered` en su `boot()` y registra una impresión por cada `post_id` presente en el bloque. Ni nd-builder ni nd-theme importan una sola clase de nd-analytics; si nd-analytics no está instalado, `dispatch()` simplemente no tiene listeners y no ocurre nada.

## nd-workflow: qué es nuevo y qué reutiliza de WordPress core

- **Comentarios internos**: tabla propia (`nd_editorial_notes`), deliberadamente separada de la tabla nativa de comentarios de WordPress (pensada para comentarios públicos de lectores, con su propio flujo de moderación/spam que no aplica aquí).
- **Estados editoriales**: `nd_in_review`/`nd_needs_changes` se registran con `register_post_status()` como estados *adicionales* a los nativos de WordPress (`draft`, `pending`, `publish`, ...), no los sustituyen.
- **Correcciones y versionado**: nd-workflow **no** reimplementa el versionado de contenido — WordPress core ya guarda revisiones completas de cada edición (`wp_postmeta`/tabla de revisiones) de forma nativa. Una "solicitud de corrección" se modela como una nota editorial con `type = correction_request`, no como un sistema de versiones paralelo.
- **Calendario**: `NDWorkflow\Calendar\CalendarRepository` es solo la capa de datos (qué artículos caen en qué día de un mes, vía `WP_Query`); una interfaz visual de calendario queda fuera del alcance de esta versión — ver "Pendiente" en el CHANGELOG.
- **Asignaciones**: post meta (`_nd_assigned_to`) en lugar de una tabla propia: es una relación 1:1 simple que post meta ya resuelve e indexa.

## nd-ads: por qué el HTML del anuncio vive en el paquete, no en el tema

A diferencia de nd-builder (cuyo HTML de bloques editoriales vive siempre en `nd-theme`), `NDAds\Rendering\AdRenderer` genera su propio HTML. Los formatos de anuncio (snippet de AdSense, definición de slot de GAM, `<video>`, ...) son prácticamente estándar entre proveedores y no son "presentación editorial" específica de un tema: que nd-ads los genere garantiza que cualquier tema que use ND Platform sirva anuncios de forma correcta y consistente sin tener que reimplementar esos formatos.

`AdRenderer` es deliberadamente puro (Campaña → HTML, sin efectos secundarios ni acceso a base de datos): registrar la impresión es responsabilidad de `NDAds\Rendering\AdZoneRenderer`, que además es el único punto que usan tanto el shortcode `[nd_ad zone="..."]` como las zonas fijas de nd-theme (cabecera, tras el contenido del artículo), evitando duplicar la secuencia "seleccionar → renderizar → registrar impresión".

El clic (`/nd-ads/click/{id}`) resuelve el destino de la campaña **en el servidor**, a partir del ID — nunca acepta una URL de destino por parámetro de la petición — para que no exista ninguna forma de construir un enlace de open-redirect a través de este endpoint.

## nd-analytics: privacidad y "tiempo real" sin fabricar infraestructura que no existe

- **Sin PII en crudo**: `NDAnalytics\Tracking\VisitorHasher` nunca almacena la IP ni el user agent: los combina con la fecha del día y los pasa por `wp_hash()` (HMAC con las claves secretas de esta instalación). El hash resultante rota cada día, así que no permite reconstruir la IP original ni correlacionar a la misma persona más allá de una jornada.
- **Registro del lado del servidor**: `PageviewRecorder` se ejecuta en el hook `wp` (una vez resuelta la consulta principal) para cada artículo, sin necesidad de JavaScript ni depender de que el visitante no tenga bloqueadores activos. Excluye explícitamente a usuarios con `edit_posts` (personal editorial) para no contaminar las estadísticas con su propio tráfico.
- **"Tiempo real" honesto**: no hay websockets ni un proceso en segundo plano — `AnalyticsRepository::activeNow()` consulta directamente los últimos N minutos de la tabla de pageviews en el momento de la petición. El dato mostrado es, en efecto, el más reciente posible; sencillamente no se empuja al cliente sin que lo pida.
- **`DatabaseManager::wpTable()`** (nuevo en nd-core): distinto de `table()` (que siempre añade el infijo `nd_` para tablas propias), permite referenciar tablas *nativas* de WordPress (`wp_posts`, `wp_terms`, ...) para los `JOIN` que necesitan `topAuthors()`/`topCategories()`.
