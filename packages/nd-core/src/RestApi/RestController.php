<?php

declare(strict_types=1);

namespace NDCore\RestApi;

use WP_Error;
use WP_REST_Response;

abstract class RestController {

	/**
	 * @param array<mixed> $data
	 */
	protected function success( array $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	/**
	 * @param array<string, mixed> $additionalErrorData
	 */
	protected function error( string $code, string $message, int $status = 400, array $additionalErrorData = array() ): WP_Error {
		return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $additionalErrorData ) );
	}
}
