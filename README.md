# ND Platform

CMS editorial profesional construido sobre WordPress, diseñado para operar medios digitales de alto tráfico (noticias, opinión, multimedia) con estándares de ingeniería de nivel producción.

## Qué es

ND Platform es un monorepo de paquetes Composer/npm independientes que juntos conforman un CMS editorial completo:

| Paquete | Responsabilidad |
|---|---|
| [`nd-core`](packages/nd-core) | Núcleo de la plataforma: contenedor DI, aplicación, configuración, hooks, eventos, enrutamiento, REST API, base de datos, migraciones, caché, colas, scheduler, seguridad, permisos, activación/actualización. |
| [`nd-theme`](packages/nd-theme) | Tema de presentación (sin lógica de negocio): layouts, bloques de front-end, modo oscuro, responsive. |
| [`nd-builder`](packages/nd-builder) | Constructor visual propio de páginas y módulos editoriales. |
| [`nd-api`](packages/nd-api) | Superficie REST/GraphQL pública y de integración. |
| [`nd-seo`](packages/nd-seo) | SEO técnico automático: Schema.org, OpenGraph, sitemaps, Discover, Google News. |
| [`nd-ads`](packages/nd-ads) | Motor de publicidad propio (AdSense, GAM, patrocinados, segmentación). |
| [`nd-media`](packages/nd-media) | Optimización y entrega de multimedia (imagen, video, podcast). |
| [`nd-workflow`](packages/nd-workflow) | Flujo editorial: estados, roles, asignaciones, calendario. |
| [`nd-analytics`](packages/nd-analytics) | Analítica editorial propia (tiempo real, más leídas, CTR). |
| [`nd-ai`](packages/nd-ai) | Proveedor de IA desacoplado (OpenAI, Claude, Gemini, DeepSeek, LLM local). |
| [`nd-cache`](packages/nd-cache) | Capa de caché de objetos/página (Redis, transients). |
| [`nd-discover`](packages/nd-discover) | Optimización para Google Discover. |
| [`nd-search`](packages/nd-search) | Motor de búsqueda interno. |

Los entregables instalables en WordPress son **`nd-core`** y **`nd-theme`**; el resto de paquetes son dependencias que `nd-core` orquesta.

## Requisitos

- PHP >= 8.3
- WordPress >= 6.5
- MySQL >= 8.0 / MariaDB >= 10.6
- Composer >= 2.7
- Node.js >= 20 / npm >= 10
- Redis (opcional, recomendado en producción)

## Estructura del monorepo

```
nd-platform/
├── apps/            # Puntos de entrada ejecutables (ej. workers, CLIs)
├── packages/        # Paquetes independientes (ver tabla arriba)
├── bootstrap/       # Bootstrap compartido de la aplicación
├── config/          # Configuración por defecto (PHP arrays)
├── docs/            # Documentación técnica
├── resources/       # Recursos compartidos (idiomas, plantillas)
├── routes/          # Definición de rutas REST/web compartidas
├── storage/         # Almacenamiento en tiempo de ejecución (logs, caché, uploads temporales)
├── tests/           # Pruebas de integración cross-paquete
└── tools/           # Scripts de build, empaquetado y CI
```

## Desarrollo

```bash
composer install
npm install

composer run check   # phpcs + phpstan + phpunit sobre todos los paquetes
npm run build         # build de assets (Vite) de nd-theme y nd-builder
```

## Versionado y releases

El proyecto avanza por versiones pre-release controladas (`v0.1.0-alpha.N` → `v0.1.0-beta` → `v0.1.0`). Ninguna versión avanza hasta que la anterior compila, pasa PHPStan nivel máximo y su suite de pruebas en verde. Ver [`CHANGELOG.md`](CHANGELOG.md) y [`ROADMAP.md`](ROADMAP.md).

## Empaquetado

Los paquetes instalables se generan con:

```bash
tools/build/package.sh nd-core
tools/build/package.sh nd-theme
```

Esto produce `dist/nd-core-<version>.zip` y `dist/nd-theme-<version>.zip`, listos para instalar en WordPress vía **Plugins → Añadir nuevo → Subir plugin** (nd-core) y **Apariencia → Temas → Añadir nuevo → Subir tema** (nd-theme).

## Documentación

- [Architecture.md](docs/Architecture.md) — decisiones de arquitectura del sistema.
- [API.md](docs/API.md) — referencia de la REST API de `nd-core`.
- [ROADMAP.md](ROADMAP.md) — plan de versiones.
- [CHANGELOG.md](CHANGELOG.md) — historial de cambios.

## Licencia

GPL-2.0-or-later. Ver [LICENSE](LICENSE).
