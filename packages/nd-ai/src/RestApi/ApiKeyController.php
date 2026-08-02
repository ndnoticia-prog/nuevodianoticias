<?php

declare(strict_types=1);

namespace NDAi\RestApi;

use NDAi\Settings\ApiKeyStore;
use NDCore\Permissions\Capability;
use NDCore\RestApi\Contracts\RegistersRoutes;
use NDCore\RestApi\RestController;
use NDCore\Routing\Router;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Gestión de claves de API de proveedores de IA. Las claves nunca se
 * devuelven en texto plano: solo se informa si hay una guardada y sus
 * últimos 4 caracteres, para que el admin pueda confirmar cuál está activa
 * sin volver a exponer el secreto completo (ApiKeyStore ya la cifra en
 * reposo, ver su docblock).
 */
final class ApiKeyController extends RestController implements RegistersRoutes {

	/**
	 * @var array<string, string>
	 */
	private const PROVIDERS = array(
		'openai'   => 'OpenAI',
		'claude'   => 'Claude',
		'gemini'   => 'Gemini',
		'deepseek' => 'DeepSeek',
	);

	public function __construct( private readonly ApiKeyStore $keys ) {
	}

	public function registerRoutes( Router $router ): void {
		$permission  = static fn (): bool => current_user_can( Capability::MANAGE_ND_SETTINGS );
		$providerArg = array(
			'provider' => array(
				'type'     => 'string',
				'required' => true,
				'enum'     => array_keys( self::PROVIDERS ),
			),
		);

		$router->get( 'nd/v1', '/ai/keys', array( $this, 'index' ), $permission );
		$router->put(
			'nd/v1',
			'/ai/keys/(?P<provider>[a-z]+)',
			array( $this, 'update' ),
			$permission,
			$providerArg + array(
				'api_key' => array(
					'type'     => 'string',
					'required' => true,
				),
			)
		);
		$router->delete( 'nd/v1', '/ai/keys/(?P<provider>[a-z]+)', array( $this, 'destroy' ), $permission, $providerArg );
	}

	public function index(): WP_REST_Response {
		$data = array();

		foreach ( self::PROVIDERS as $provider => $label ) {
			$data[] = $this->describe( $provider, $label );
		}

		return $this->success( array( 'data' => $data ) );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$provider = (string) $request->get_param( 'provider' );

		if ( ! array_key_exists( $provider, self::PROVIDERS ) ) {
			return $this->error( 'nd_ai_unknown_provider', __( 'Proveedor de IA desconocido.', 'nd-ai' ), 404 );
		}

		$apiKey = trim( (string) $request->get_param( 'api_key' ) );

		if ( $apiKey === '' ) {
			return $this->error( 'nd_ai_missing_key', __( 'La clave de API no puede estar vacía.', 'nd-ai' ), 422 );
		}

		$this->keys->set( $provider, $apiKey );

		return $this->success( array( 'data' => $this->describe( $provider, self::PROVIDERS[ $provider ] ) ) );
	}

	public function destroy( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$provider = (string) $request->get_param( 'provider' );

		if ( ! array_key_exists( $provider, self::PROVIDERS ) ) {
			return $this->error( 'nd_ai_unknown_provider', __( 'Proveedor de IA desconocido.', 'nd-ai' ), 404 );
		}

		$this->keys->set( $provider, '' );

		return $this->success( array(), 204 );
	}

	/**
	 * @return array{provider: string, label: string, has_key: bool, key_preview: string}
	 */
	private function describe( string $provider, string $label ): array {
		$key = $this->keys->get( $provider );

		return array(
			'provider'    => $provider,
			'label'       => $label,
			'has_key'     => $key !== '',
			'key_preview' => $key !== '' ? '••••' . substr( $key, -4 ) : '',
		);
	}
}
