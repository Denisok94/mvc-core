<?php

declare(strict_types=1);

namespace LiteMvc\Core\Component\HttpClient;

use stdClass;
use Psr\Log\LoggerInterface;

/**
 * Guzzle-based HttpClientProxy implementing HttpClientInterface.
 *
 * Поддерживает:
 * - SOCKS5 прокси с авторизацией
 * - базовую авторизацию (Basic)
 * - Bearer токен
 * - опциональный PSR-3 Logger
 * 
 * @example 
 * ```php
 * // API‑авторизация
 * $client = new HttpClientProxy(
 *  $apiUrl,
 *  'api_user',      // Basic user
 *  'api_pass',      // Basic pass
 *  null             // без токена
 * );
 * // Bearer 
 * $client = new HttpClientProxy(
 *  $apiUrl,
 *  null,            // без Basic
 *  null,
 *  'my_bearer_token'
 * );
 * ```
 */
class HttpClientProxy extends HttpClient
{
    private ?string $user;
    private ?string $password;
    private ?string $token;

    /**
     * @param string $apiUrl Базовый URL API (например, 'https://api.example.com')
     * @param ?string $user
     * @param ?string $password
     * @param ?string $token
     * @param ?LoggerInterface $logger PSR-3 Logger (опционально)
     */
    public function __construct(
        string $apiUrl,
        ?string $user = null,
        ?string $password = null,
        ?string $token = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($apiUrl, $logger);

        $this->user = $user;
        $this->password = $password;
        $this->token = $token;
    }

    protected function getHeaders(array $headers = []): array
    {
        if ($this->user && $this->password) {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->user . ':' . $this->password);
        }
        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        return $headers;
    }

    // Все методы просто делегируют в httpClient, добавляя заголовки
    public function post(string $endpoint, $data, array $headers = []): stdClass
    {
        return $this->post($endpoint, $data, $this->getHeaders($headers));
    }

    public function postJson(string $endpoint, string $json, array $headers = []): stdClass
    {
        return $this->postJson($endpoint, $json, $this->getHeaders($headers));
    }

    public function get(string $endpoint, array $parameters = [], array $headers = []): stdClass
    {
        return $this->get($endpoint, $parameters, $this->getHeaders($headers));
    }

    public function getJson(string $endpoint, array $parameters = [], array $headers = []): stdClass
    {
        return $this->getJson($endpoint, $parameters, $this->getHeaders($headers));
    }

    public function delete(string $endpoint, $data = null, array $headers = []): stdClass
    {
        return $this->delete($endpoint, $data, $this->getHeaders($headers));
    }

    public function deleteJson(string $endpoint, ?string $json = null, array $headers = []): stdClass
    {
        return $this->deleteJson($endpoint, $json, $this->getHeaders($headers));
    }

    public function patch(string $endpoint, $data = null, array $headers = []): stdClass
    {
        return $this->patch($endpoint, $data, $this->getHeaders($headers));
    }

    public function patchJson(string $endpoint, ?string $json = null, array $headers = []): stdClass
    {
        return $this->patchJson($endpoint, $json, $this->getHeaders($headers));
    }
}
