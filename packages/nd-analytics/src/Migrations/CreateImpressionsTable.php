<?php

declare(strict_types=1);

namespace NDAnalytics\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreateImpressionsTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('analytics_impressions');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            context VARCHAR(32) NOT NULL DEFAULT '',
            viewed_at DATETIME NOT NULL,
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
        $table = $db->table('analytics_impressions');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
