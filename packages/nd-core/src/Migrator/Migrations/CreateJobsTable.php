<?php

declare(strict_types=1);

namespace NDCore\Migrator\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreateJobsTable extends Migration {

	public function up( DatabaseManager $db ): void {
		$table          = $db->table( 'jobs' );
		$charsetCollate = $db->charsetCollate();

		$sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            job_class VARCHAR(191) NOT NULL,
            payload LONGTEXT NOT NULL,
            attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            available_at DATETIME NOT NULL,
            reserved_at DATETIME NULL,
            failed_at DATETIME NULL,
            error TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY available_at (available_at),
            KEY reserved_at (reserved_at)
        ) {$charsetCollate};";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $sql );
	}

	public function down( DatabaseManager $db ): void {
		$table = $db->table( 'jobs' );
		$db->statement( "DROP TABLE IF EXISTS {$table}" );
	}
}
