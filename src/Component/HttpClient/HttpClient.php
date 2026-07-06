<?php

declare(strict_types=1);

namespace LiteMvc\Core\Component\HttpClient;

use stdClass;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Guzzle-based HttpClient implementing HttpClientInterface.
 *
 * Поддерживает:
 * - SOCKS5 прокси с авторизацией
 * - опциональный PSR-3 Logger
 * 
 * @example 
 * ```php
 * $client = new HttpClient($apiUrl);
 * // SOCKS5
 * $client = (new HttpClient($apiUrl, $logger))
 *  ->withProxy('10.10.10.10', 1080, 'proxy_user', 'proxy_pass')
 *  ->withTimeout(10.0, 5.0);
 * // guzzle options
 * $client = (new HttpClient($apiUrl, $logger))
 *  ->withOptions(
 *  [
 *   'timeout'      => 10.0,          // таймаут запроса
 *   'connect_timeout' => 5.0,       // таймаут соединения
 *   'verify'       => true,          // SSL проверка
 *   'http_errors'  => false,         // не выбрасывать исключение на 4xx/5xx (если нужно)
 *  ]);
 * ```
 */
class HttpClient implements HttpClientInterface
{
    private Client $client;
    private ?LoggerInterface $logger;
    private string $baseUri;
    private array $options = [];

    /**
     * @param string $apiUrl Базовый URL API (например, 'https://api.example.com')
     * @param ?LoggerInterface $logger PSR-3 Logger (опционально)
     */
    public function __construct(
        string $apiUrl,
        ?LoggerInterface $logger = null
    ) {
        $this->baseUri = rtrim($apiUrl, '/');
        $this->logger = $logger;
        $this->options = ['base_uri' => $this->baseUri];
    }

    /**
     * SOCKS5 Proxy
     * @param string $host Хост SOCKS5 прокси (например, '10.10.10.10')
     * @param int $port Порт SOCKS5 прокси (например, 1080)
     * @param ?string $user Пользователь прокси
     * @param ?string $pass Пароль прокси
     * 
     * @return HttpClient
     */
    public function withProxy(string $host, int $port, ?string $user = null, ?string $pass = null): self
    {
        $proxyUrl = sprintf('socks5://%s:%d', $host, $port);
        if ($user && $pass) {
            $proxyUrl = sprintf(
                'socks5h://%s:%s@%s:%d',
                urlencode($user),
                urlencode($pass),
                $host,
                $port
            );
        }
        $this->options['proxy'] = $proxyUrl;
        return $this;
    }

    /**
     * @param float $timeout
     * @param ?float $connectTimeout
     * @return HttpClient
     */
    public function withTimeout(float $timeout, ?float $connectTimeout = null): self
    {
        $this->options['timeout'] = $timeout;
        if ($connectTimeout !== null) {
            $this->options['connect_timeout'] = $connectTimeout;
        }
        return $this;
    }

    /**
     * @param array $guzzleOptions
     * @return HttpClient
     */
    public function withOptions(array $guzzleOptions = []): self
    {
        $this->options = array_merge($this->options, $guzzleOptions);
        return $this;
    }

    private function ensureClient(): void
    {
        if (!isset($this->client)) {
            $this->client = new Client($this->options);
        }
    }


    //-----------------------------------

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info($message, $context);
        }
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return stdClass
     */
    private function makeRequest(string $method, string $endpoint, array $options = []): stdClass
    {
        $this->ensureClient();

        $fullUrl = $this->baseUri . '/' . ltrim($endpoint, '/');

        $this->log("HTTP {$method} {$fullUrl}", $options);

        try {
            $response = $this->client->request($method, $endpoint, $options);
        } catch (RequestException $e) {
            // Логируем ошибку и пробрасываем дальше, чтобы клиент мог обработать
            $this->log('HTTP request failed', [
                'url' => $fullUrl,
                'method' => $method,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }

        $body = $response->getBody()->getContents();

        $result = new stdClass();
        $result->code = $response->getStatusCode();
        $result->headers = $response->getHeaders();
        $result->raw_body = $body;

        // Пытаемся распарсить JSON, если возможно
        if (strpos($response->getHeaderLine('Content-Type'), 'application/json') !== false) {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            $result->body = $decoded;
        } else {
            $result->body = $body;
        }

        return $result;
    }

    //-----------------------------------

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param array $headers
     * @return stdClass
     */
    public function post(string $endpoint, $data, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);

        if (is_array($data)) {
            $options['json'] = $data;
        } elseif (is_string($data)) {
            $options['body'] = $data;
            $headers['Content-Type'] = 'text/plain';
        } else {
            $options['body'] = (string)$data;
        }

        return $this->makeRequest('POST', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param string $json
     * @param array $headers
     * @return stdClass
     */
    public function postJson(string $endpoint, string $json, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);
        $options['body'] = $json;
        $options['headers']['Content-Type'] = 'application/json';

        return $this->makeRequest('POST', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return stdClass
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);
        if (!empty($parameters)) {
            $options['query'] = $parameters;
        }

        return $this->makeRequest('GET', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return stdClass
     */
    public function getJson(string $endpoint, array $parameters = [], array $headers = []): stdClass
    {
        return $this->get($endpoint, $parameters, $headers);
    }

    /**
     * @param string $endpoint
     * @param mixed|null $data
     * @param array $headers
     * @return stdClass
     */
    public function delete(string $endpoint, $data = null, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);

        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data;
            } else {
                $options['body'] = (string)$data;
            }
        }

        return $this->makeRequest('DELETE', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return stdClass
     */
    public function deleteJson(string $endpoint, ?string $json = null, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);
        if ($json !== null) {
            $options['body'] = $json;
            $options['headers']['Content-Type'] = 'application/json';
        }

        return $this->makeRequest('DELETE', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param mixed|null $data
     * @param array $headers
     * @return stdClass
     */
    public function patch(string $endpoint, $data = null, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);

        if ($data !== null) {
            if (is_array($data)) {
                $options['json'] = $data;
            } else {
                $options['body'] = (string)$data;
            }
        }

        return $this->makeRequest('PATCH', $endpoint, $options);
    }

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return stdClass
     */
    public function patchJson(string $endpoint, ?string $json = null, array $headers = []): stdClass
    {
        $options = $this->prepareOptions($headers);
        if ($json !== null) {
            $options['body'] = $json;
            $options['headers']['Content-Type'] = 'application/json';
        }

        return $this->makeRequest('PATCH', $endpoint, $options);
    }

    private function prepareOptions(array $headers): array
    {
        $options = [];
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }
        return $options;
    }

    public function isSuccess(stdClass $response): bool
    {
        return in_array($response->code, self::SUCCESS_RESPONSE_CODES, true);
    }

    public function throwFailure(stdClass $response): void
    {
        throw new \RuntimeException($response->raw_body, $response->code);
    }

    public function checkResponse(stdClass $response): void
    {
        if (!$this->isSuccess($response)) {
            $this->throwFailure($response);
        }
    }
}
