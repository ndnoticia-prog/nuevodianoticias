<?php

declare(strict_types=1);

namespace NDCore\Migrator;

use NDCore\Database\DatabaseManager;

/**
 * Aplica migraciones de esquema de forma idempotente y registra cuáles ya se
 * ejecutaron en la tabla `{prefix}nd_migrations`.
 */
final class Migrator {

	private const TABLE = 'migrations';

	public function __construct( private readonly DatabaseManager $db ) {
	}

	public function ensureMigrationsTableExists(): void {
		$table          = $this->db->table( self::TABLE );
		$charsetCollate = $this->db->charsetCollate();

		$sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(191) NOT NULL,
            applied_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY migration (migration)
        ) {$charsetCollate};";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );
	}

	/**
	 * @param list<class-string<Migration>> $migrationClasses
	 */
	public function run( array $migrationClasses ): void {
		$this->ensureMigrationsTableExists();

		$applied = $this->appliedMigrations();

		foreach ( $migrationClasses as $migrationClass ) {
			if ( in_array( $migrationClass, $applied, true ) ) {
				continue;
			}

			$migration = new $migrationClass();
			$migration->up( $this->db );

			$this->db->insert(
				$this->db->table( self::TABLE ),
				array(
					'migration'  => $migrationClass,
					'applied_at' => current_time( 'mysql' ),
				),
				array(
					'migration'  => '%s',
					'applied_at' => '%s',
				)
			);
		}
	}

	/**
	 * @param list<class-string<Migration>> $migrationClasses Se revierten en orden inverso al indicado.
	 */
	public function rollback( array $migrationClasses ): void {
		$applied = $this->appliedMigrations();

		foreach ( array_reverse( $migrationClasses ) as $migrationClass ) {
			if ( ! in_array( $migrationClass, $applied, true ) ) {
				continue;
			}

			$migration = new $migrationClass();
			$migration->down( $this->db );

			$this->db->delete( $this->db->table( self::TABLE ), array( 'migration' => $migrationClass ) );
		}
	}

	/**
	 * @return list<string>
	 */
	public function appliedMigrations(): array {
		$table = $this->db->table( self::TABLE );
		$rows  = $this->db->select( "SELECT migration FROM {$table}" );

		return array_values(
			array_map(
				static fn ( array $row ): string => (string) $row['migration'],
				$rows
			)
		);
	}
}
