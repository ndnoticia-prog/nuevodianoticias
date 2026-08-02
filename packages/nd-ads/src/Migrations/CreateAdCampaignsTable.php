<?php

declare(strict_types=1);

namespace NDAds\Migrations;

use NDCore\Database\DatabaseManager;
use NDCore\Migrator\Migration;

final class CreateAdCampaignsTable extends Migration
{
    public function up(DatabaseManager $db): void
    {
        $table = $db->table('ad_campaigns');
        $charsetCollate = $db->charsetCollate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            advertiser VARCHAR(191) NOT NULL DEFAULT '',
            type VARCHAR(32) NOT NULL,
            active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            priority SMALLINT NOT NULL DEFAULT 10,
            zones TEXT NOT NULL,
            category_slugs TEXT NOT NULL,
            creative LONGTEXT NOT NULL,
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY active (active),
            KEY priority (priority)
        ) {$charsetCollate};";

        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta($sql);
    }

    public function down(DatabaseManager $db): void
    {
        $table = $db->table('ad_campaigns');
        $db->statement("DROP TABLE IF EXISTS {$table}");
    }
}
