<?php

namespace App\Services\ExternalApiService;

use App\Models\ExternalApi\ExternalApi;
use App\Utils\PlaceholderReplacer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExternalApiService
{
    public function __construct(
        protected PlaceholderReplacer $placeholderReplacer
    ) {}

    public function getExternalApiData(string $apiCode, Request $request): array
    {
        $externalApi = ExternalApi::where('api_code', $apiCode)
            ->where('is_active', true)
            ->firstOrFail();

        $requestData = $request->all();

        $headers = $this->buildFromTemplate($externalApi->headers_template, $requestData);
        $params  = $this->buildFromTemplate($externalApi->param_template, $requestData);
        $body    = $this->buildFromTemplate($externalApi->request_template, $requestData, $requestData);

        $rawResponse = $this->callExternalApi($externalApi, $headers, $params, $body);

        return $this->buildFromTemplate($externalApi->response_template, $rawResponse, $rawResponse);
    }

    protected function buildFromTemplate(?string $jsonTemplate, array $rootObject, array $fallback = []): array
    {
        if (empty($jsonTemplate)) {
            return $fallback;
        }

        $template = json_decode($jsonTemplate, true);

        if (!is_array($template)) {
            return $fallback;
        }

        return $this->placeholderReplacer->replaceInTemplate($template, $rootObject);
    }

    protected function callExternalApi(ExternalApi $externalApi, array $headers, array $params, array $body): array
    {
        $method = strtolower($externalApi->request_method ?? 'post');

        $client = Http::withHeaders($headers)->timeout(30);

        // Attach query params to ANY verb (GET/POST/PUT/etc.) without
        // clobbering the body. Requires Laravel 9+ (withQueryParameters).
        if (!empty($params)) {
            $client = $client->withQueryParameters($params);
        }

        try {
            $response = match ($method) {
                'get'    => $client->get($externalApi->api_base_url, $body),
                'put'    => $client->put($externalApi->api_base_url, $body),
                'patch'  => $client->patch($externalApi->api_base_url, $body),
                'delete' => $client->delete($externalApi->api_base_url, $body),
                default  => $client->post($externalApi->api_base_url, $body),
            };
        } catch (ConnectionException $e) {
            Log::error('External API connection error', [
                'api_code' => $externalApi->api_code,
                'message'  => $e->getMessage(),
            ]);

            throw new RuntimeException("Unable to connect to external API '{$externalApi->api_code}'.");
        }

        if ($response->failed()) {
            Log::error('External API call failed', [
                'api_code' => $externalApi->api_code,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            throw new RuntimeException(
                "External API '{$externalApi->api_code}' returned an error (status {$response->status()})."
            );
        }

        return $response->json() ?? [];
    }
}
