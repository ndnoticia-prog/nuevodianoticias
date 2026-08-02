<?php

declare(strict_types=1);

namespace NDSearch\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

/**
 * Índice propio con FULLTEXT, en lugar de alterar el esquema de `wp_posts`
 * (una tabla core de WordPress) para añadirle un índice — más seguro y
 * completamente bajo control de este paquete.
 */
final class CreateSearchIndexTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('search_index');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            post_id BIGINT UNSIGNED NOT NULL,
            title TEXT NOT NULL,
            content_text LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (post_id),
            FULLTEXT KEY nd_search_fulltext (title, content_text)
        ) {$charsetCollate};";

        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    public function down(DatabaseManager $db): void
    {
        $table = $db->table('search_index');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
