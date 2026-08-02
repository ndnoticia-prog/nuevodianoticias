<?php

declare(strict_types=1);

namespace NDCore\Filesystem;

use RuntimeException;
use WP_Filesystem_Base;

/**
 * Única puerta de acceso de ND Platform al sistema de archivos, apoyada en
 * la abstracción `WP_Filesystem` de WordPress (compatible con FTP/SSH2
 * cuando el hosting no permite escritura directa).
 */
final class Filesystem {

	private ?WP_Filesystem_Base $filesystem = null;

	public function exists( string $path ): bool {
		return $this->fs()->exists( $path );
	}

	public function get( string $path ): string {
		$contents = $this->fs()->get_contents( $path );

		if ( $contents === false ) {
			throw new RuntimeException( sprintf( 'No se pudo leer el archivo "%s".', $path ) );
		}

		return $contents;
	}

	public function put( string $path, string $contents ): bool {
		return $this->fs()->put_contents( $path, $contents, FS_CHMOD_FILE );
	}

	public function delete( string $path ): bool {
		return $this->fs()->delete( $path );
	}

	public function makeDirectory( string $path ): bool {
		return $this->fs()->is_dir( $path ) || $this->fs()->mkdir( $path, FS_CHMOD_DIR );
	}

	public function isDirectory( string $path ): bool {
		return $this->fs()->is_dir( $path );
	}

	public function copy( string $from, string $to, bool $overwrite = true ): bool {
		return $this->fs()->copy( $from, $to, $overwrite, FS_CHMOD_FILE );
	}

	public function move( string $from, string $to, bool $overwrite = true ): bool {
		return $this->fs()->move( $from, $to, $overwrite );
	}

	public function size( string $path ): int {
		$size = $this->fs()->size( $path );

		return $size === false ? 0 : (int) $size;
	}

	/**
	 * @return list<string>
	 */
	public function filesIn( string $directory ): array {
		$listing = $this->fs()->dirlist( $directory );

		if ( ! is_array( $listing ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_keys( $listing ),
				fn ( string $name ): bool => $listing[ $name ]['type'] === 'f'
			)
		);
	}

	private function fs(): WP_Filesystem_Base {
		if ( $this->filesystem instanceof WP_Filesystem_Base ) {
			return $this->filesystem;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			throw new RuntimeException( 'No se pudo inicializar WP_Filesystem: credenciales de acceso no disponibles.' );
		}

		$this->filesystem = $wp_filesystem;

		return $this->filesystem;
	}
}
