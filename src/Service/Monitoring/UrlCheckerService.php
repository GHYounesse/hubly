<?php

namespace App\Service\Monitoring;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UrlCheckerService
{
    public function __construct(private readonly HttpClientInterface $client) {}

    /**
     * @return array{success: bool, status_code: ?int, url: string, error: ?string, response_time_ms: int}
     */
    public function checkUrl(string $url): array
    {
        if (!str_starts_with($url, 'http')) {
            $url = 'http://' . $url;
        }

        $start = hrtime(true);

        try {
            $response = $this->client->request('GET', $url, ['timeout' => 5]);
            $statusCode = $response->getStatusCode();

            return [
                'success' => $statusCode < 400,
                'status_code' => $statusCode,
                'url' => $url,
                'error' => null,
                'response_time_ms' => $this->elapsedMs($start),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => null,
                'url' => $url,
                'error' => $e->getMessage(),
                'response_time_ms' => $this->elapsedMs($start),
            ];
        }
    }

    private function elapsedMs(int $start): int
    {
        return (int) round((hrtime(true) - $start) / 1_000_000);
    }
}
