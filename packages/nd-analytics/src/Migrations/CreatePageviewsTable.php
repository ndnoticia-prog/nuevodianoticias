<?php

declare(strict_types=1);

namespace NDAnalytics\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreatePageviewsTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('analytics_pageviews');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            url VARCHAR(500) NOT NULL DEFAULT '',
            referrer VARCHAR(500) NOT NULL DEFAULT '',
            visitor_hash CHAR(64) NOT NULL,
            viewed_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY viewed_at (viewed_at)
        ) {$charsetCollate};";

        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    public function down(DatabaseManager $db): void
    {
        $table = $db->table('analytics_pageviews');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
