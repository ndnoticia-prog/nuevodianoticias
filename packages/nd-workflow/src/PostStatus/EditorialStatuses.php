<?php

declare(strict_types=1);

namespace NDWorkflow\PostStatus;

/**
 * Estados editoriales intermedios entre "borrador" y "pendiente de revisión"
 * nativos de WordPress: permiten distinguir un artículo que un editor ya
 * está revisando de uno que devolvió con cambios pendientes.
 */
final class EditorialStatuses {

	public const string IN_REVIEW     = 'nd_in_review';
	public const string NEEDS_CHANGES = 'nd_needs_changes';

	public function register(): void {
		register_post_status(
			self::IN_REVIEW,
			array(
				'label'                     => _x( 'En revisión', 'post status', 'nd-workflow' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				/* translators: %s: número de artículos en este estado. */
				'label_count'               => _n_noop(
					'En revisión <span class="count">(%s)</span>',
					'En revisión <span class="count">(%s)</span>',
					'nd-workflow'
				),
			)
		);

		register_post_status(
			self::NEEDS_CHANGES,
			array(
				'label'                     => _x( 'Necesita cambios', 'post status', 'nd-workflow' ),
				'public'                    => false,
				'internal'                  => true,
				'protected'                 => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				/* translators: %s: número de artículos en este estado. */
				'label_count'               => _n_noop(
					'Necesita cambios <span class="count">(%s)</span>',
					'Necesita cambios <span class="count">(%s)</span>',
					'nd-workflow'
				),
			)
		);
	}
}
