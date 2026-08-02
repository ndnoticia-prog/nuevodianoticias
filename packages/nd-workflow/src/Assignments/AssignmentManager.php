<?php

declare(strict_types=1);

namespace NDWorkflow\Assignments;

/**
 * Asignación de un artículo a un usuario responsable de revisarlo o
 * completarlo. Se apoya en post meta en lugar de una tabla propia: es una
 * relación 1:1 simple y post meta ya está indexado por WordPress.
 */
final class AssignmentManager {

	private const META_KEY = '_nd_assigned_to';

	public function assign( int $postId, int $userId ): bool {
		return (bool) update_post_meta( $postId, self::META_KEY, $userId );
	}

	public function unassign( int $postId ): bool {
		return delete_post_meta( $postId, self::META_KEY );
	}

	public function assignedTo( int $postId ): ?int {
		$userId = get_post_meta( $postId, self::META_KEY, true );

		return is_numeric( $userId ) && (int) $userId > 0 ? (int) $userId : null;
	}
}
