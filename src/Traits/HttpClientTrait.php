<?php
namespace MesaSDK\PhpMpesa\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

trait HttpClientTrait
{
    protected function createHttpClient(Config $config): Client
    {
        return new Client([
            'verify' => $config->getVerifySSL(),
            'timeout' => $config->getRequestTimeout(),
            'connect_timeout' => $config->getRequestTimeout()
        ]);
    }

    /**
     * Execute an HTTP request with retry logic
     *
     * @param Client $client The HTTP client
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $options Request options
     * @param Config $config Configuration instance
     * @return array Response data
     * @throws MpesaException
     */
    protected function executeWithRetry(
        Client $client,
        string $method,
        string $url,
        array $options,
        Config $config
    ): array {
        $retryConfig = $config->getRetryConfig();
        $maxRetries = $retryConfig['max_retries'];
        $retryDelay = $retryConfig['retry_delay'];
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $maxRetries) {
            try {
                $response = $client->request($method, $url, $options);
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new MpesaException(
                        "Invalid JSON response",
                        0,
                        ['error' => json_last_error_msg(), 'response' => $body]
                    );
                }

                return $data ?? [];

            } catch (RequestException $e) {
                $lastException = $e;
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

                // Don't retry on client errors (4xx) except for 429 (Too Many Requests)
                if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                    throw new MpesaException(
                        "Request failed: " . $e->getMessage(),
                        $statusCode,
                        [
                            'error' => $e->getMessage(),
                            'response' => $responseBody,
                            'url' => $url,
                            'method' => $method
                        ]
                    );
                }

                $attempt++;
                if ($attempt <= $maxRetries) {
                    $sleep = min($retryDelay * pow(2, $attempt - 1), 10000);
                    $jitter = rand(0, 1000);
                    usleep(($sleep + $jitter) * 1000);
                    continue;
                }
            } catch (\Exception $e) {
                throw new MpesaException(
                    "Request failed: " . $e->getMessage(),
                    0,
                    [
                        'error' => $e->getMessage(),
                        'url' => $url,
                        'method' => $method
                    ]
                );
            }
        }

        throw new MpesaException(
            "Max retries exceeded. Last error: " . $lastException->getMessage(),
            0,
            [
                'error' => $lastException->getMessage(),
                'attempts' => $attempt,
                'url' => $url,
                'method' => $method
            ]
        );
    }
}