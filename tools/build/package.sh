#!/usr/bin/env bash
#
# Genera nd-core-<version>.zip y nd-theme-<version>.zip, listos para
# instalar en un WordPress real (Plugins > Añadir nuevo > Subir plugin /
# Apariencia > Temas > Subir tema).
#
# Por qué no basta con "zip -r packages/nd-core": nd-core empaqueta 10
# paquetes hermanos vía repositorios `path` de Composer
# (ver docs/Architecture.md, "Dependencias entre paquetes"), que en este
# monorepo se resuelven como SYMLINKS a la carpeta hermana completa
# (vendor/ndnoticia/nd-search -> ../../../nd-search/), no solo a su src/.
# Comprimir eso tal cual produciría un zip con enlaces simbólicos rotos
# (apuntan fuera del zip) y arrastraría los propios tests/vendor/config de
# cada paquete hermano. Este script:
#   1. Reinstala las dependencias de producción (--no-dev) de nd-core y
#      nd-theme.
#   2. Copia cada carpeta DEREFERENCIANDO symlinks (cp -RL), para que el
#      contenido de cada paquete hermano quede copiado de verdad dentro
#      del zip.
#   3. Elimina los archivos de desarrollo (tests/, phpunit*/phpcs/phpstan,
#      vendor/ propio, composer.lock, ...) de cada paquete hermano
#      empaquetado dentro de vendor/ndnoticia/.
#
# Uso: tools/build/package.sh
# Requiere: composer y npm en el PATH (ver tools/wp-tests/README.md para
# cómo se instalaron en este entorno de desarrollo).

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PACKAGES_DIR="$ROOT_DIR/packages"
DIST_DIR="$ROOT_DIR/tools/build/dist"

VERSION="$(php -r '$d = json_decode(file_get_contents($argv[1]), true); echo $d["version"];' "$PACKAGES_DIR/nd-core/composer.json")"

echo "==> Empaquetando ND Platform v${VERSION}"

# -- 1. Dependencias de producción -------------------------------------

echo "==> composer install --no-dev en nd-core"
(cd "$PACKAGES_DIR/nd-core" && composer install --no-dev --optimize-autoloader --quiet)

echo "==> composer install --no-dev en nd-theme"
(cd "$PACKAGES_DIR/nd-theme" && composer install --no-dev --optimize-autoloader --quiet)

echo "==> npm install && npm run build en nd-theme (assets Vite)"
(cd "$PACKAGES_DIR/nd-theme" && npm install --no-fund --no-audit --silent && npm run build --silent)

if [ ! -f "$PACKAGES_DIR/nd-theme/dist/app.css" ] || [ ! -f "$PACKAGES_DIR/nd-theme/dist/app.js" ]; then
	echo "ERROR: npm run build no generó dist/app.css / dist/app.js" >&2
	exit 1
fi

# -- 2. Directorio de build limpio --------------------------------------

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR/nd-core" "$DIST_DIR/nd-theme"

# -- 3. Copia de nd-core (sin vendor/ todavía) ---------------------------

echo "==> Copiando nd-core"
rsync -a \
	--exclude='vendor/' \
	--exclude='.phpunit.cache' \
	--exclude='.phpunit.result.cache' \
	--exclude='composer.lock' \
	--exclude='phpcs.xml.dist' \
	--exclude='phpstan.neon.dist' \
	--exclude='phpstan-bootstrap.php' \
	--exclude='phpunit.xml.dist' \
	--exclude='phpunit-integration.xml.dist' \
	--exclude='tests/' \
	"$PACKAGES_DIR/nd-core/" "$DIST_DIR/nd-core/"

# -- 3b. vendor/ de nd-core, con cuidado con los paquetes empaquetados ----
#
# vendor/ndnoticia/<paquete> es un symlink a la carpeta hermana COMPLETA
# (ver cabecera de este script), y CADA paquete hermano declara en su
# propio composer.json repositorios `path` hacia TODOS los demás (para
# que IDEs/PHPStan/PHPUnit los vean en desarrollo) — es un grafo
# completamente conectado, no un árbol. Un `cp -RL`/`rsync -L` que
# dereferencie symlinks de forma recursiva cae en una explosión
# combinatoria de ciclos (nd-core -> nd-search -> nd-core -> ...) antes de
# que la detección de ciclos lo frene, y el proceso puede acabar sin
# memoria. La solución: NUNCA dereferenciar de forma recursiva. Se copian
# literalmente vendor/autoload.php y vendor/composer/ (generados por
# Composer, no symlinks) tal cual, y para cada paquete hermano se resuelve
# el symlink UN solo nivel (readlink) y se copian a mano, desde esa ruta
# ya resuelta, solo sus subcarpetas de producción conocidas (src/,
# assets/, config/) — nunca su propio vendor/ ni tests/, que es
# precisamente lo que no queremos arrastrar.
echo "==> Copiando vendor/ de nd-core (sin dereferenciar recursivamente)"
mkdir -p "$DIST_DIR/nd-core/vendor"
cp -R "$PACKAGES_DIR/nd-core/vendor/composer" "$DIST_DIR/nd-core/vendor/composer"
cp "$PACKAGES_DIR/nd-core/vendor/autoload.php" "$DIST_DIR/nd-core/vendor/autoload.php"

for real_vendor in psr; do
	if [ -d "$PACKAGES_DIR/nd-core/vendor/$real_vendor" ]; then
		cp -R "$PACKAGES_DIR/nd-core/vendor/$real_vendor" "$DIST_DIR/nd-core/vendor/$real_vendor"
	fi
done

mkdir -p "$DIST_DIR/nd-core/vendor/ndnoticia"
for link in "$PACKAGES_DIR/nd-core/vendor/ndnoticia"/*; do
	pkg_name="$(basename "$link")"
	target="$(cd "$link" && pwd -P)"
	dest="$DIST_DIR/nd-core/vendor/ndnoticia/$pkg_name"
	mkdir -p "$dest"

	for sub in src assets config; do
		if [ -e "$target/$sub" ]; then
			cp -R "$target/$sub" "$dest/$sub"
		fi
	done

	if [ -f "$target/composer.json" ]; then
		cp "$target/composer.json" "$dest/composer.json"
	fi
done

# -- 4. Copia de nd-theme -------------------------------------------------

echo "==> Copiando nd-theme"
rsync -a \
	--exclude='vendor/' \
	--exclude='.phpunit.cache' \
	--exclude='.phpunit.result.cache' \
	--exclude='composer.lock' \
	--exclude='package-lock.json' \
	--exclude='node_modules/' \
	--exclude='patchwork.json' \
	--exclude='phpcs.xml.dist' \
	--exclude='phpstan.neon.dist' \
	--exclude='phpstan-bootstrap.php' \
	--exclude='phpunit.xml.dist' \
	--exclude='phpunit-integration.xml.dist' \
	--exclude='resources/' \
	--exclude='tests/' \
	--exclude='vite.config.js' \
	"$PACKAGES_DIR/nd-theme/" "$DIST_DIR/nd-theme/"

# nd-theme no tiene NINGUNA dependencia de producción (su composer.json
# declara solo "php": ">=8.3") — los paquetes hermanos que referencia son
# de require-dev (nd-core/nd-builder/nd-seo/nd-ads, para IDEs/PHPStan/
# PHPUnit en desarrollo), ausentes tras `composer install --no-dev`. No se
# usa `-L` aquí (a diferencia de nd-core) precisamente para no arriesgarse
# a la misma explosión de symlinks si algo dejara vendor/ndnoticia/
# presente; vendor/autoload.php y vendor/composer/ (generados por
# Composer, nunca symlinks) se copian tal cual.
mkdir -p "$DIST_DIR/nd-theme/vendor"
cp -R "$PACKAGES_DIR/nd-theme/vendor/composer" "$DIST_DIR/nd-theme/vendor/composer"
cp "$PACKAGES_DIR/nd-theme/vendor/autoload.php" "$DIST_DIR/nd-theme/vendor/autoload.php"
rm -rf "$DIST_DIR/nd-theme/vendor/ndnoticia"

# -- 5. Zips ---------------------------------------------------------------

echo "==> Generando zips"
(cd "$DIST_DIR" && rm -f "nd-core-${VERSION}.zip" "nd-theme-${VERSION}.zip" \
	&& zip -rq "nd-core-${VERSION}.zip" nd-core -x '.DS_Store' -x '__MACOSX/*' \
	&& zip -rq "nd-theme-${VERSION}.zip" nd-theme -x '.DS_Store' -x '__MACOSX/*')

echo "==> Listo:"
echo "    $DIST_DIR/nd-core-${VERSION}.zip"
echo "    $DIST_DIR/nd-theme-${VERSION}.zip"

# -- 6. Restaurar dependencias de desarrollo -------------------------------
#
# El paso 1 dejó packages/nd-core y packages/nd-theme con solo las
# dependencias de producción instaladas (composer install --no-dev), lo
# que rompería `composer run check` (phpunit/phpstan ya no estarían) si
# alguien siguiera trabajando en el repo después de empaquetar. Se
# restaura el árbol de dependencias de desarrollo completo antes de
# terminar.

echo "==> Restaurando dependencias de desarrollo en nd-core y nd-theme"
(cd "$PACKAGES_DIR/nd-core" && composer install --quiet)
(cd "$PACKAGES_DIR/nd-theme" && composer install --quiet)
