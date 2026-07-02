<?php

namespace App\Services\ExternalApiService;

use App\Models\ExternalApi\ExternalApi;
use App\Utils\PlaceholderReplacer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;


class ExternalApiService
{
    /**
     * Template fields that must contain either null or valid JSON.
     */
    protected const TEMPLATE_FIELDS = [
        'headers_template',
        'param_template',
        'request_template',
        'response_template',
    ];

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

    /**
     * Create a new external API definition.
     *
     * @throws InvalidArgumentException When a template field is not valid JSON
     */
    public function createExternalApi(array $data): ExternalApi
    {
        $this->assertValidTemplates($data);

        if (!empty($data['api_code']) && ExternalApi::where('api_code', $data['api_code'])->exists()) {
            throw new InvalidArgumentException("An external API with code '{$data['api_code']}' already exists.");
        }

        return DB::transaction(fn () => ExternalApi::create($data));
    }

    /**
     * Update an existing external API definition, identified by its api_code.
     *
     * @throws InvalidArgumentException When a template field is not valid JSON
     */
    public function updateExternalApi(string $apiCode, array $data): ExternalApi
    {
        $this->assertValidTemplates($data);

        $externalApi = ExternalApi::where('api_code', $apiCode)->firstOrFail();

        if (
            !empty($data['api_code'])
            && $data['api_code'] !== $externalApi->api_code
            && ExternalApi::where('api_code', $data['api_code'])->exists()
        ) {
            throw new InvalidArgumentException("An external API with code '{$data['api_code']}' already exists.");
        }

        DB::transaction(function () use ($externalApi, $data) {
            $externalApi->fill($data);
            $externalApi->save();
        });

        return $externalApi->refresh();
    }

    /**
     * Update an existing external API definition, identified by its primary key.
     *
     * @throws InvalidArgumentException When a template field is not valid JSON
     */
    public function updateExternalApiById(int $id, array $data): ExternalApi
    {
        $this->assertValidTemplates($data);

        $externalApi = ExternalApi::findOrFail($id);

        if (
            !empty($data['api_code'])
            && $data['api_code'] !== $externalApi->api_code
            && ExternalApi::where('api_code', $data['api_code'])->exists()
        ) {
            throw new InvalidArgumentException("An external API with code '{$data['api_code']}' already exists.");
        }

        DB::transaction(function () use ($externalApi, $data) {
            $externalApi->fill($data);
            $externalApi->save();
        });

        return $externalApi->refresh();
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

    /**
     * Ensure any provided template fields are either omitted, null, or valid JSON.
     *
     * @throws InvalidArgumentException
     */
    protected function assertValidTemplates(array $data): void
    {
        foreach (self::TEMPLATE_FIELDS as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                continue;
            }

            if (!is_string($data[$field])) {
                throw new InvalidArgumentException("The '{$field}' field must be a JSON string.");
            }

            json_decode($data[$field], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException(
                    "The '{$field}' field must contain valid JSON: " . json_last_error_msg()
                );
            }
        }
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


    /**
     * List external API definitions, with optional filtering and pagination.
     */
    public function listExternalApis(Request $request): LengthAwarePaginator
    {
        $query = ExternalApi::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('api_name', 'like', "%{$search}%")
                    ->orWhere('api_code', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->integer('per_page', 15), 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
