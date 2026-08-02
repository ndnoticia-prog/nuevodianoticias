<?php

declare(strict_types=1);

namespace NDAds\Stats;

use NDCore\Database\DatabaseManager;

final class StatsRepository
{
    public function __construct(private readonly DatabaseManager $db)
    {
    }

    /**
     * @return array{impressions: int, clicks: int, ctr: float}
     */
    public function summaryForCampaign(int $campaignId): array
    {
        $table = $this->db->table('ad_events');
        $impressions = $this->count($table, $campaignId, 'impression');
        $clicks = $this->count($table, $campaignId, 'click');

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round($clicks / $impressions * 100, 2) : 0.0,
        ];
    }

    private function count(string $table, int $campaignId, string $eventType): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM {$table} WHERE campaign_id = %d AND event_type = %s",
            [$campaignId, $eventType]
        );

        return $row !== null ? (int) $row['total'] : 0;
    }
}
