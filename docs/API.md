# API.md — REST API de ND Platform

## Convenciones

- **Namespace base:** `nd/v1` (registrado vía `register_rest_route('nd/v1', ...)`). Cada versión mayor incompatible incrementa el número (`nd/v2`).
- **Formato:** JSON (`application/json`) en petición y respuesta.
- **Autenticación:** cookies + nonce de WordPress (`X-WP-Nonce`) para peticiones desde el admin/front autenticado; `Application Passwords` de WordPress para integraciones externas server-to-server.
- **Autorización:** cada ruta declara un `permission_callback` explícito respaldado por `NDCore\Permissions\PermissionManager`. Nunca se usa `__return_true` en producción.
- **Errores:** se devuelven como `WP_Error` serializados por WordPress con forma `{"code": string, "message": string, "data": {"status": int}}`.
- **Paginación:** parámetros `page` (1-indexado) y `per_page` (máx. 100), con cabeceras de respuesta `X-WP-Total` y `X-WP-TotalPages`.

## Endpoints — v0.1.0-alpha.1

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/wp-json/nd/v1/system/status` | Health-check de la plataforma: versión de `nd-core`, paquetes activos, estado de caché/cola. | Público (sin datos sensibles) |

## Endpoints — v0.1.0-alpha.4

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| `GET` | `/wp-json/nd/v1/workflow/posts/{id}/notes` | Lista los comentarios internos de un artículo. | `edit_nd_workflow` |
| `POST` | `/wp-json/nd/v1/workflow/posts/{id}/notes` | Crea un comentario interno (`body`, `type` opcional: `note`/`correction_request`). | `edit_nd_workflow` |
| `DELETE` | `/wp-json/nd/v1/workflow/notes/{id}` | Elimina un comentario interno. | `edit_nd_workflow` |
| `POST` | `/wp-json/nd/v1/workflow/posts/{id}/assignment` | Asigna un artículo a un usuario (`user_id`). | `edit_nd_workflow` |
| `DELETE` | `/wp-json/nd/v1/workflow/posts/{id}/assignment` | Quita la asignación de un artículo. | `edit_nd_workflow` |
| `GET` | `/wp-json/nd/v1/workflow/calendar?year=&month=` | Artículos agrupados por día para el calendario editorial (solo datos; sin interfaz visual en esta versión). | `edit_nd_workflow` |
| `GET` | `/wp-json/nd/v1/analytics/top-posts?days=&limit=` | Más leídos en los últimos N días. | `view_nd_analytics` |
| `GET` | `/wp-json/nd/v1/analytics/active-now?minutes=` | Visitantes únicos y artículos más vistos en los últimos N minutos ("tiempo real" por consulta directa, sin websockets). | `view_nd_analytics` |
| `GET` | `/wp-json/nd/v1/analytics/top-authors?days=` | Vistas agregadas por autor. | `view_nd_analytics` |
| `GET` | `/wp-json/nd/v1/analytics/top-categories?days=` | Vistas agregadas por categoría. | `view_nd_analytics` |
| `GET` | `/wp-json/nd/v1/analytics/posts/{id}/ctr?days=` | CTR de un artículo: pageviews vs. impresiones en bloques de portada. | `view_nd_analytics` |

El clic en un anuncio se registra y redirige vía la ruta reescrita `/nd-ads/click/{id}` (no JSON, ver `docs/Architecture.md`) — independiente del CRUD de campañas de v0.1.0-beta.1, más abajo.

## Endpoints — v0.1.0-alpha.5

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| `POST` | `/wp-json/nd/v1/ai/posts/{id}/generate` | Genera contenido asistido por IA para un artículo. `task` (obligatorio): `headline`, `seo_title`, `meta_description`, `tags`, `categories`, `summary`, `excerpt`, `social_facebook`, `social_instagram`, `social_x`, `social_linkedin`, `newsletter`, `video_script`. | `use_nd_ai` |

En esta versión, `nd-search` sustituye la búsqueda nativa de WordPress directamente en la consulta principal (`pre_get_posts`/`posts_search`, sin endpoints propios) y `nd-cache` opera de forma transparente en `template_redirect` — ambos ganan endpoints REST propios en v0.1.0-beta.1 (paneles de admin), ver más abajo.

## Endpoints — v0.1.0-beta.1

Añadidos junto con las páginas de administración del menú compartido "ND Platform" (ver `docs/Architecture.md`, "Menú de admin compartido").

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| `PATCH` | `/wp-json/nd/v1/workflow/posts/{id}/schedule` | Reprograma la fecha de publicación de un artículo (`date`, formato `AAAA-MM-DD`; conserva la hora original) — usado por el arrastre del calendario editorial. | `edit_nd_workflow` |
| `GET` | `/wp-json/nd/v1/ads/campaigns` | Lista todas las campañas (activas e inactivas). | `manage_nd_ads` |
| `POST` | `/wp-json/nd/v1/ads/campaigns` | Crea una campaña (`name`, `advertiser`, `type`, `active`, `priority`, `zones`, `category_slugs`, `creative`, `starts_at`, `ends_at`). | `manage_nd_ads` |
| `PUT` | `/wp-json/nd/v1/ads/campaigns/{id}` | Actualiza una campaña (mismos campos que `POST`). | `manage_nd_ads` |
| `PATCH` | `/wp-json/nd/v1/ads/campaigns/{id}/active` | Activa/desactiva una campaña (`active`, booleano). | `manage_nd_ads` |
| `DELETE` | `/wp-json/nd/v1/ads/campaigns/{id}` | Borra una campaña. | `manage_nd_ads` |
| `GET` | `/wp-json/nd/v1/ai/keys` | Lista los proveedores de IA con su estado (¿tiene clave guardada?) y una vista previa de 4 caracteres — nunca la clave completa. | `manage_nd_settings` |
| `PUT` | `/wp-json/nd/v1/ai/keys/{provider}` | Guarda/reemplaza la clave de API de un proveedor (`api_key`). | `manage_nd_settings` |
| `DELETE` | `/wp-json/nd/v1/ai/keys/{provider}` | Borra la clave guardada de un proveedor. | `manage_nd_settings` |
| `GET` | `/wp-json/nd/v1/search/stats` | Número de artículos indexados. | `manage_nd_settings` |
| `GET` | `/wp-json/nd/v1/search/recent?limit=` | Contenido indexado más recientemente. | `manage_nd_settings` |
| `GET` | `/wp-json/nd/v1/search/query?q=&limit=` | Ejecuta una consulta de prueba contra el índice FULLTEXT. | `manage_nd_settings` |
| `POST` | `/wp-json/nd/v1/search/reindex` | Reconstruye el índice completo bajo demanda (`SearchIndexer::reindexAll()`). | `manage_nd_settings` |
| `POST` | `/wp-json/nd/v1/cache/purge` | Purga toda la caché de página (`CacheManager::flush()`) — para cambios que no pasan por `save_post`. | `manage_nd_settings` |

Los endpoints de `nd-ai`/`nd-search`/`nd-cache` usan `manage_nd_settings` (no una capacidad propia del paquete) porque son mantenimiento administrativo — gestionar credenciales, reconstruir un índice, purgar una caché — no una acción editorial del día a día; distinto de `use_nd_ai` (generar contenido, alpha.5) o `edit_nd_workflow`/`manage_nd_ads` (acciones editoriales/publicitarias habituales).

## Extender la API desde un paquete

Cada paquete registra sus rutas implementando `NDCore\RestApi\Contracts\RegistersRoutes` en su `ServiceProvider` y devolviendo instancias de `NDCore\Routing\Route`. `nd-core` centraliza el registro real en `RestApiServiceProvider`, que se subscribe al hook `rest_api_init` una única vez.
