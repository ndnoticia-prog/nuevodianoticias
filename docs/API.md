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

## Endpoints planificados (versiones posteriores)

Estos endpoints se documentarán aquí en el momento en que se implementen (no antes), siguiendo la regla de "nunca documentar código que no existe":

- `nd/v1/content/*` — CRUD editorial (nd-workflow).
- `nd/v1/ads/*` — gestión de campañas (nd-ads).
- `nd/v1/analytics/*` — métricas editoriales (nd-analytics).
- `nd/v1/ai/*` — generación asistida de contenido (nd-ai).
- `nd/v1/search/*` — búsqueda interna (nd-search).

## Extender la API desde un paquete

Cada paquete registra sus rutas implementando `NDCore\RestApi\Contracts\RegistersRoutes` en su `ServiceProvider` y devolviendo instancias de `NDCore\Routing\Route`. `nd-core` centraliza el registro real en `RestApiServiceProvider`, que se subscribe al hook `rest_api_init` una única vez.
