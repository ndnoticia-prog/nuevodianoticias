<?php

declare(strict_types=1);

namespace NDWorkflow\Notes;

use NDCore\Database\DatabaseManager;

/**
 * Comentarios internos entre editores sobre un artículo. Deliberadamente
 * NO usa la tabla nativa de comentarios de WordPress (pensada para
 * comentarios públicos de lectores): una tabla propia evita mezclar ambos
 * conceptos y sus permisos.
 */
final class EditorialNoteRepository
{
    private const TABLE = 'editorial_notes';

    public function __construct(private readonly DatabaseManager $db)
    {
    }

    public function create(
        int $postId,
        int $authorId,
        string $body,
        string $type = EditorialNote::TYPE_NOTE
    ): EditorialNote {
        $createdAt = current_time('mysql', true);

        $id = $this->db->insert(
            $this->db->table(self::TABLE),
            [
                'post_id' => $postId,
                'author_id' => $authorId,
                'type' => $type,
                'body' => $body,
                'created_at' => $createdAt,
            ],
            [
                'post_id' => '%d',
                'author_id' => '%d',
                'type' => '%s',
                'body' => '%s',
                'created_at' => '%s',
            ]
        );

        return new EditorialNote($id, $postId, $authorId, $type, $body, $createdAt);
    }

    /**
     * @return list<EditorialNote>
     */
    public function forPost(int $postId): array
    {
        $table = $this->db->table(self::TABLE);
        $rows = $this->db->select("SELECT * FROM {$table} WHERE post_id = %d ORDER BY created_at DESC", [$postId]);

        return array_map($this->hydrate(...), $rows);
    }

    public function delete(int $noteId): bool
    {
        return (bool) $this->db->delete($this->db->table(self::TABLE), ['id' => $noteId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): EditorialNote
    {
        return new EditorialNote(
            id: (int) $row['id'],
            postId: (int) $row['post_id'],
            authorId: (int) $row['author_id'],
            type: (string) $row['type'],
            body: (string) $row['body'],
            createdAt: (string) $row['created_at'],
        );
    }
}
