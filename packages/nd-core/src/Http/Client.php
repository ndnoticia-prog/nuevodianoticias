<?php

declare(strict_types=1);

namespace NDCore\Http;

/**
 * Cliente HTTP saliente de ND Platform, apoyado en `wp_remote_request` para
 * heredar los timeouts, proxies y filtros que el hosting ya configura sobre
 * la capa HTTP de WordPress.
 */
final class Client
{
    public function send(Request $request): Response
    {
        $args = [
            'method' => $request->method,
            'headers' => $request->headers,
            'timeout' => $request->timeoutSeconds,
        ];

        if ($request->body !== '') {
            $args['body'] = $request->body;
        }

        $response = wp_remote_request($request->fullUrl(), $args);

        if (is_wp_error($response)) {
            return new Response(0, [], '', true, $response->get_error_message());
        }

        return new Response(
            status: (int) wp_remote_retrieve_response_code($response),
            headers: $this->normalizeHeaders(wp_remote_retrieve_headers($response)),
            body: (string) wp_remote_retrieve_body($response),
            isError: false,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    public function get(string $url, array $query = [], array $headers = []): Response
    {
        return $this->send(new Request('GET', $url, $headers, $query));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function postJson(string $url, array $data = [], array $headers = []): Response
    {
        return $this->send(Request::json('POST', $url, $data, $headers));
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function putJson(string $url, array $data = [], array $headers = []): Response
    {
        return $this->send(Request::json('PUT', $url, $data, $headers));
    }

    /**
     * @param array<string, string> $headers
     */
    public function delete(string $url, array $headers = []): Response
    {
        return $this->send(new Request('DELETE', $url, $headers));
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHeaders(mixed $rawHeaders): array
    {
        $headers = [];

        if (! is_iterable($rawHeaders)) {
            return $headers;
        }

        foreach ($rawHeaders as $name => $value) {
            $headers[strtolower((string) $name)] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        return $headers;
    }
}
