<?php

declare(strict_types=1);

namespace NDSearch\Repository;

use NDCore\Database\DatabaseManager;

final class SearchIndexRepository
{
    private const TABLE = 'search_index';

    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function upsert(int $postId, string $title, string $contentText): void
    {
        $table = $this->db->table(self::TABLE);

        if ($this->exists($postId)) {
            $this->db->update(
                $table,
                [
                    'title' => $title,
                    'content_text' => $contentText,
                    'updated_at' => current_time('mysql', true),
                ],
                ['post_id' => $postId]
            );

            return;
        }

        $this->db->insert(
            $table,
            [
                'post_id' => $postId,
                'title' => $title,
                'content_text' => $contentText,
                'updated_at' => current_time('mysql', true),
            ],
            [
                'post_id' => '%d',
                'title' => '%s',
                'content_text' => '%s',
                'updated_at' => '%s',
            ]
        );
    }

    public function delete(int $postId): bool
    {
        return (bool) $this->db->delete($this->db->table(self::TABLE), ['post_id' => $postId]);
    }

    /**
     * @return list<int> IDs de artículo ordenados por relevancia descendente.
     */
    public function search(string $query, int $limit = 20): array
    {
        if (trim($query) === '') {
            return [];
        }

        $table = $this->db->table(self::TABLE);

        $rows = $this->db->select(
            "SELECT post_id FROM {$table} " .
            'WHERE MATCH(title, content_text) AGAINST (%s IN NATURAL LANGUAGE MODE) ' .
            'ORDER BY MATCH(title, content_text) AGAINST (%s IN NATURAL LANGUAGE MODE) DESC ' .
            'LIMIT %d',
            [$query, $query, $limit]
        );

        return array_map(static fn (array $row): int => (int) $row['post_id'], $rows);
    }

    private function exists(int $postId): bool
    {
        $table = $this->db->table(self::TABLE);

        return $this->db->selectOne("SELECT post_id FROM {$table} WHERE post_id = %d", [$postId]) !== null;
    }
}
