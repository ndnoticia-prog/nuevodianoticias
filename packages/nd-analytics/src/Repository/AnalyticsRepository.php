<?php

declare(strict_types=1);

namespace NDAnalytics\Repository;

use NDCore\Database\DatabaseManager;

/**
 * Consultas de analítica editorial. "Tiempo real" se resuelve consultando
 * directamente los últimos N minutos de `analytics_pageviews` en el
 * momento de la petición: no hay websockets ni un proceso en segundo plano,
 * pero el dato que se muestra es, en efecto, el más reciente posible.
 */
final class AnalyticsRepository
{
    private const PAGEVIEWS_TABLE = 'analytics_pageviews';
    private const IMPRESSIONS_TABLE = 'analytics_impressions';

    public function __construct(private readonly DatabaseManager $db)
    {
    }

    /**
     * @return list<array{post_id: int, views: int}>
     */
    public function topPosts(int $days = 7, int $limit = 10): array
    {
        $table = $this->db->table(self::PAGEVIEWS_TABLE);

        $rows = $this->db->select(
            "SELECT post_id, COUNT(*) AS views FROM {$table} " .
            'WHERE viewed_at >= %s GROUP BY post_id ORDER BY views DESC LIMIT %d',
            [$this->daysAgo($days), $limit]
        );

        return $this->mapPostViews($rows);
    }

    /**
     * @return array{visitors: int, posts: list<array{post_id: int, views: int}>}
     */
    public function activeNow(int $minutesWindow = 5): array
    {
        $table = $this->db->table(self::PAGEVIEWS_TABLE);
        $since = gmdate('Y-m-d H:i:s', time() - $minutesWindow * MINUTE_IN_SECONDS);

        $visitorRow = $this->db->selectOne(
            "SELECT COUNT(DISTINCT visitor_hash) AS total FROM {$table} WHERE viewed_at >= %s",
            [$since]
        );

        $postsRows = $this->db->select(
            "SELECT post_id, COUNT(*) AS views FROM {$table} " .
            'WHERE viewed_at >= %s GROUP BY post_id ORDER BY views DESC LIMIT 10',
            [$since]
        );

        return [
            'visitors' => $visitorRow !== null ? (int) $visitorRow['total'] : 0,
            'posts' => $this->mapPostViews($postsRows),
        ];
    }

    /**
     * @return list<array{author_id: int, views: int}>
     */
    public function topAuthors(int $days = 30, int $limit = 10): array
    {
        $pageviews = $this->db->table(self::PAGEVIEWS_TABLE);
        $posts = $this->db->wpTable('posts');

        $rows = $this->db->select(
            'SELECT p.post_author AS author_id, COUNT(*) AS views ' .
            "FROM {$pageviews} pv INNER JOIN {$posts} p ON p.ID = pv.post_id " .
            'WHERE pv.viewed_at >= %s GROUP BY p.post_author ORDER BY views DESC LIMIT %d',
            [$this->daysAgo($days), $limit]
        );

        return array_map(
            static fn (array $row): array => [
                'author_id' => (int) $row['author_id'],
                'views' => (int) $row['views'],
            ],
            $rows
        );
    }

    /**
     * @return list<array{term_id: int, name: string, views: int}>
     */
    public function topCategories(int $days = 30, int $limit = 10): array
    {
        $pageviews = $this->db->table(self::PAGEVIEWS_TABLE);
        $termRelationships = $this->db->wpTable('term_relationships');
        $termTaxonomy = $this->db->wpTable('term_taxonomy');
        $terms = $this->db->wpTable('terms');

        $rows = $this->db->select(
            'SELECT t.term_id, t.name, COUNT(*) AS views ' .
            "FROM {$pageviews} pv " .
            "INNER JOIN {$termRelationships} tr ON tr.object_id = pv.post_id " .
            "INNER JOIN {$termTaxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'category' " .
            "INNER JOIN {$terms} t ON t.term_id = tt.term_id " .
            'WHERE pv.viewed_at >= %s GROUP BY t.term_id ORDER BY views DESC LIMIT %d',
            [$this->daysAgo($days), $limit]
        );

        return array_map(
            static fn (array $row): array => [
                'term_id' => (int) $row['term_id'],
                'name' => (string) $row['name'],
                'views' => (int) $row['views'],
            ],
            $rows
        );
    }

    /**
     * @return array{post_id: int, views: int, impressions: int, ctr: float}
     */
    public function ctrForPost(int $postId, int $days = 30): array
    {
        $pageviews = $this->db->table(self::PAGEVIEWS_TABLE);
        $impressions = $this->db->table(self::IMPRESSIONS_TABLE);
        $since = $this->daysAgo($days);

        $viewsRow = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM {$pageviews} WHERE post_id = %d AND viewed_at >= %s",
            [$postId, $since]
        );

        $impressionsRow = $this->db->selectOne(
            "SELECT COUNT(*) AS total FROM {$impressions} WHERE post_id = %d AND viewed_at >= %s",
            [$postId, $since]
        );

        $views = $viewsRow !== null ? (int) $viewsRow['total'] : 0;
        $impressionsCount = $impressionsRow !== null ? (int) $impressionsRow['total'] : 0;

        return [
            'post_id' => $postId,
            'views' => $views,
            'impressions' => $impressionsCount,
            'ctr' => $impressionsCount > 0 ? round($views / $impressionsCount * 100, 2) : 0.0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{post_id: int, views: int}>
     */
    private function mapPostViews(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'post_id' => (int) $row['post_id'],
                'views' => (int) $row['views'],
            ],
            $rows
        );
    }

    private function daysAgo(int $days): string
    {
        return gmdate('Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS);
    }
}
