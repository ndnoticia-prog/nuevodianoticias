<?php

declare(strict_types=1);

namespace NDWorkflow\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreateEditorialNotesTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('editorial_notes');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(32) NOT NULL DEFAULT 'note',
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) {$charsetCollate};";

        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    public function down(DatabaseManager $db): void
    {
        $table = $db->table('editorial_notes');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
