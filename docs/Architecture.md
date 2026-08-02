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
