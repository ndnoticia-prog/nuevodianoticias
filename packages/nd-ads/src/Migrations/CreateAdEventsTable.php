<?php

declare(strict_types=1);

namespace NDAds\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreateAdEventsTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('ad_events');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(16) NOT NULL,
            zone VARCHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY event_type (event_type)
        ) {$charsetCollate};";

        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    public function down(DatabaseManager $db): void
    {
        $table = $db->table('ad_events');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
