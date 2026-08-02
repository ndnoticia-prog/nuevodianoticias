<?php

declare(strict_types=1);

namespace NDWorkflow\Notes;

final class EditorialNote {

	public const string TYPE_NOTE               = 'note';
	public const string TYPE_CORRECTION_REQUEST = 'correction_request';

	public function __construct(
		public readonly int $id,
		public readonly int $postId,
		public readonly int $authorId,
		public readonly string $type,
		public readonly string $body,
		public readonly string $createdAt,
	) {
	}
}
