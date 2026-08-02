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

`nd-ads` no expone endpoints REST: el clic en un anuncio se registra y redirige vía la ruta reescrita `/nd-ads/click/{id}` (no JSON, ver `docs/Architecture.md`).

## Endpoints — v0.1.0-alpha.5

| Método | Ruta | Descripción | Permiso |
|---|---|---|---|
| `POST` | `/wp-json/nd/v1/ai/posts/{id}/generate` | Genera contenido asistido por IA para un artículo. `task` (obligatorio): `headline`, `seo_title`, `meta_description`, `tags`, `categories`, `summary`, `excerpt`, `social_facebook`, `social_instagram`, `social_x`, `social_linkedin`, `newsletter`, `video_script`. | `use_nd_ai` |

`nd-search` no expone endpoints REST propios en esta versión: sustituye la búsqueda nativa de WordPress directamente en la consulta principal (`pre_get_posts`/`posts_search`), así que `search.php` de nd-theme ya usa resultados ordenados por relevancia sin ningún cambio en el tema. `nd-cache` tampoco expone endpoints: opera de forma transparente en `template_redirect`.

## Endpoints planificados (versiones posteriores)

Estos endpoints se documentarán aquí en el momento en que se implementen (no antes), siguiendo la regla de "nunca documentar código que no existe":

- `nd/v1/ads/campaigns/*` — CRUD de campañas publicitarias (nd-ads), para un futuro panel de administración.

## Extender la API desde un paquete

Cada paquete registra sus rutas implementando `NDCore\RestApi\Contracts\RegistersRoutes` en su `ServiceProvider` y devolviendo instancias de `NDCore\Routing\Route`. `nd-core` centraliza el registro real en `RestApiServiceProvider`, que se subscribe al hook `rest_api_init` una única vez.
