<?php

declare(strict_types=1);

namespace NDAi\Tests\Unit\Providers;

use Brain\Monkey\Functions;
use NDAi\Exceptions\AiProviderException;
use NDAi\Providers\OpenAiProvider;
use NDAi\Tests\BrainMonkeyTestCase;
use NDCore\Http\Client;

final class OpenAiProviderTest extends BrainMonkeyTestCase {

	public function test_throws_without_an_api_key(): void {
		$this->expectException( AiProviderException::class );

		( new OpenAiProvider( new Client(), '' ) )->complete( 'hola' );
	}

	public function test_returns_the_completion_text_on_success(): void {
		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn(
			(string) json_encode(
				array(
					'choices' => array(
						array( 'message' => array( 'content' => '  Titular generado  ' ) ),
					),
				)
			)
		);
		Functions\expect( 'wp_remote_retrieve_headers' )->once()->andReturn( array() );
		Functions\expect( 'wp_remote_request' )->once()->andReturn( array() );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$result = ( new OpenAiProvider( new Client(), 'sk-test' ) )->complete( 'genera un titular' );

		self::assertSame( 'Titular generado', $result );
	}

	public function test_throws_when_the_http_call_fails(): void {
		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 500 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( 'Internal Server Error' );
		Functions\expect( 'wp_remote_retrieve_headers' )->once()->andReturn( array() );
		Functions\expect( 'wp_remote_request' )->once()->andReturn( array() );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$this->expectException( AiProviderException::class );

		( new OpenAiProvider( new Client(), 'sk-test' ) )->complete( 'genera un titular' );
	}

	public function test_throws_when_the_response_has_no_text_content(): void {
		Functions\expect( 'is_wp_error' )->once()->andReturn( false );
		Functions\expect( 'wp_remote_retrieve_response_code' )->once()->andReturn( 200 );
		Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( (string) json_encode( array( 'choices' => array() ) ) );
		Functions\expect( 'wp_remote_retrieve_headers' )->once()->andReturn( array() );
		Functions\expect( 'wp_remote_request' )->once()->andReturn( array() );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$this->expectException( AiProviderException::class );

		( new OpenAiProvider( new Client(), 'sk-test' ) )->complete( 'genera un titular' );
	}
}
