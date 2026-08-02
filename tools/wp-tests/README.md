# Entorno de pruebas de integración con WordPress real

Los paquetes de ND Platform tienen dos suites de PHPUnit distintas:

- **Unitarias** (`composer test`, `tests/Unit/`): usan Brain Monkey para interceptar funciones de WordPress. Rápidas, sin base de datos, pero no pueden cubrir código que depende de `WP_Post`/`WP_Query`/`$wpdb` reales.
- **Integración** (`composer test:integration`, `tests/Integration/`): cargan un núcleo real de WordPress contra una base de datos MySQL/MariaDB de pruebas, siguiendo el flujo oficial de [`tests/phpunit/includes/bootstrap.php`](https://github.com/WordPress/wordpress-develop/blob/trunk/tests/phpunit/includes/bootstrap.php) de `wordpress-develop` para "Plugin/theme integration tests" (instalación parcial, sin Docker/`wp-env`).

Este directorio contiene el checkout de WordPress **compartido por los 13 paquetes**, para no duplicar los ~180MB once per package.

## Preparar el entorno (una sola vez)

1. **MySQL/MariaDB en ejecución**, con una base de datos vacía y un usuario con permisos sobre ella. En este entorno de desarrollo:

   ```bash
   /Users/delvisiban/.nd-toolchain/homebrew/Cellar/mariadb/12.3.2/bin/mariadbd-safe \
     --datadir='/Users/delvisiban/.nd-toolchain/homebrew/var/mysql' &
   ```

   Usuario/base ya creados: `wp` / `wp_local_dev_pw` sobre `wordpress_test` (host `127.0.0.1`). Ver `wp-tests-config.php` para las credenciales exactas.

2. **Checkout de `wordpress-develop`** (núcleo de WordPress + el arnés de pruebas PHPUnit, en sparse-checkout para no bajar el repo completo):

   ```bash
   mkdir -p tools/wp-tests && cd tools/wp-tests
   git clone --filter=blob:none --sparse --depth=1 https://github.com/WordPress/wordpress-develop.git
   cd wordpress-develop
   git sparse-checkout set --skip-checks src tests/phpunit wp-tests-config-sample.php
   ```

3. **`wp-tests-config.php`**: copiar `wp-tests-config-sample.php` a `wp-tests-config.php` dentro de `wordpress-develop/` y rellenar `DB_NAME`/`DB_USER`/`DB_PASSWORD`/`DB_HOST` con las credenciales del paso 1, y generar salts únicos (`php -r '...random_bytes...'` o el [generador de WordPress.org](https://api.wordpress.org/secret-key/1.1/salt/)).

4. **`yoast/phpunit-polyfills`** como dependencia de desarrollo de cada paquete que tenga `tests/Integration/` (ya añadido a `nd-core/composer.json`; replicar en el resto según se les añadan pruebas de integración) — `composer install` lo resuelve solo.

## Ejecutar las pruebas de integración de un paquete

```bash
cd packages/nd-core
composer test:integration
```

Cada paquete localiza el checkout compartido automáticamente (dos niveles por encima de su propio directorio: `packages/<paquete>/../../tools/wp-tests/wordpress-develop`). Para usar una ubicación distinta, exporta `WP_TESTS_DIR` antes de ejecutar:

```bash
WP_TESTS_DIR=/ruta/a/otro/checkout composer test:integration
```

## Por qué no `wp-env` (Docker)

`wp-env` es la forma recomendada por WordPress.org para pruebas de integración, pero requiere Docker Desktop. Este entorno de desarrollo no tiene Docker disponible, así que se usa el flujo "clásico" (antes basado en `svn`, aquí con `git sparse-checkout` porque `svn` tampoco está disponible) documentado directamente por el propio arnés de pruebas de WordPress. El resultado es equivalente: mismo núcleo de WordPress, mismo `WP_UnitTestCase`, misma base de datos real.
