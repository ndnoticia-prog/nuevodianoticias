<?php

declare(strict_types=1);

namespace NDCore\Permissions;

/**
 * Gestiona las capacidades personalizadas de ND Platform sobre los roles
 * nativos de WordPress (administrator, editor, author).
 */
final class PermissionManager {

	/**
	 * @return array<string, list<string>>
	 */
	private function roleCapabilityMap(): array {
		return array(
			'administrator' => array(
				Capability::MANAGE_ND_SETTINGS,
				Capability::EDIT_ND_WORKFLOW,
				Capability::PUBLISH_ND_ARTICLES,
				Capability::MANAGE_ND_ADS,
				Capability::VIEW_ND_ANALYTICS,
				Capability::USE_ND_AI,
			),
			'editor'        => array(
				Capability::EDIT_ND_WORKFLOW,
				Capability::PUBLISH_ND_ARTICLES,
				Capability::VIEW_ND_ANALYTICS,
				Capability::USE_ND_AI,
			),
			'author'        => array(
				Capability::EDIT_ND_WORKFLOW,
				Capability::USE_ND_AI,
			),
		);
	}

	/**
	 * Se ejecuta en la activación del plugin: añade las capacidades a los
	 * roles existentes sin eliminar las capacidades nativas de WordPress.
	 */
	public function registerCapabilities(): void {
		foreach ( $this->roleCapabilityMap() as $roleName => $capabilities ) {
			$role = get_role( $roleName );

			if ( $role === null ) {
				continue;
			}

			foreach ( $capabilities as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}

	/**
	 * Se ejecuta en la desinstalación del plugin: revierte exactamente las
	 * capacidades que se añadieron, sin tocar nada más del rol.
	 */
	public function removeCapabilities(): void {
		foreach ( $this->roleCapabilityMap() as $roleName => $capabilities ) {
			$role = get_role( $roleName );

			if ( $role === null ) {
				continue;
			}

			foreach ( $capabilities as $capability ) {
				$role->remove_cap( $capability );
			}
		}
	}

	public function currentUserCan( string $capability ): bool {
		return current_user_can( $capability );
	}

	public function userCan( int $userId, string $capability ): bool {
		return user_can( $userId, $capability );
	}
}
