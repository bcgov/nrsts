<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PdexService
{
    /**
     * Obtain a PDEX access token via the OAuth2 client_credentials grant.
     * Tokens are cached until shortly before they expire.
     */
    public function token(): ?string
    {
        if ($cached = Cache::get('pdex_access_token')) {
            return $cached;
        }

        $tokenEndpoint = (string) config('services.pdex.token_endpoint');
        $clientId = config('services.pdex.client_id');
        $clientSecret = config('services.pdex.client_secret');

        Log::info('PDEX token request starting', [
            'token_endpoint' => $tokenEndpoint !== '' ? $tokenEndpoint : '(empty)',
            'api_url' => (string) config('services.pdex.api_url'),
            'client_id' => $this->maskSecret($clientId),
            'client_secret_set' => ! empty($clientSecret),
        ]);

        if ($tokenEndpoint === '' || empty($clientId) || empty($clientSecret)) {
            Log::error('PDEX token request skipped: client credentials or token endpoint not configured.', [
                'token_endpoint_set' => $tokenEndpoint !== '',
                'client_id_set' => ! empty($clientId),
                'client_secret_set' => ! empty($clientSecret),
            ]);

            return null;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->post($tokenEndpoint, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
        } catch (\Throwable $e) {
            Log::error('PDEX token request failed: '.$e->getMessage());

            return null;
        }

        if ($response->failed()) {
            Log::error('PDEX token request returned HTTP '.$response->status().': '.$response->body());

            return null;
        }

        $accessToken = $response->json('access_token');
        $expiresIn = (int) ($response->json('expires_in') ?? 0);

        Log::info('PDEX token request succeeded', [
            'access_token_received' => ! empty($accessToken),
            'expires_in' => $expiresIn,
            'token_claims' => $accessToken ? $this->decodeJwtClaims($accessToken) : null,
        ]);

        if ($accessToken && $expiresIn > 60) {
            Cache::put('pdex_access_token', $accessToken, $expiresIn - 60);
        }

        return $accessToken;
    }

    /**
     * Mask a secret/id for safe logging, keeping only the last 4 characters.
     */
    private function maskSecret($value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '(empty)';
        }

        return str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4);
    }

    /**
     * Decode (without verifying) the payload claims of a JWT for diagnostics.
     * Returns only non-sensitive claims useful for confirming which realm
     * issued the token (issuer, audience, authorized party, expiry).
     */
    private function decodeJwtClaims(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            return ['error' => 'not-a-jwt'];
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

        if ($payload === false) {
            return ['error' => 'undecodable-payload'];
        }

        $claims = json_decode($payload, true);

        if (! is_array($claims)) {
            return ['error' => 'invalid-json-payload'];
        }

        return [
            'iss' => $claims['iss'] ?? null,
            'aud' => $claims['aud'] ?? null,
            'azp' => $claims['azp'] ?? null,
            'exp' => $claims['exp'] ?? null,
        ];
    }

    /**
     * Perform an authenticated GET against the PDEX API and return the
     * decoded "data" payload. Returns null on any error so callers can
     * render gracefully when PDEX is unavailable or access is denied.
     */
    public function get(string $path): ?array
    {
        $baseUrl = rtrim((string) config('services.pdex.api_url'), '/');

        if ($baseUrl === '') {
            return null;
        }

        $token = $this->token();

        if (empty($token)) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->get($baseUrl.$path);
        } catch (\Throwable $e) {
            Log::warning('PDEX GET '.$path.' failed: '.$e->getMessage());

            return null;
        }

        if ($response->failed()) {
            Log::warning('PDEX GET '.$path.' returned HTTP '.$response->status().': '.$response->body());

            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            Log::warning('PDEX GET '.$path.' returned a non-array body', [
                'status' => $response->status(),
                'body_type' => gettype($payload),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            return null;
        }

        $data = $payload['data'] ?? $payload;

        // Diagnostics (no PII): confirm the shape of the PDEX response so we can
        // tell whether option lists arrive empty from the API vs. a parsing gap.
        Log::info('PDEX GET '.$path.' succeeded', [
            'status' => $response->status(),
            'has_data_wrapper' => array_key_exists('data', $payload),
            'top_level_keys' => array_slice(array_keys($payload), 0, 20),
            'data_type' => gettype($data),
            'data_count' => is_array($data) ? count($data) : null,
            'first_item_keys' => (is_array($data) && isset($data[0]) && is_array($data[0]))
                ? array_keys($data[0])
                : null,
        ]);

        return $data;
    }

    /**
     * Fetch the list of countries from PDEX (cached).
     */
    public function countries(): array
    {
        return Cache::remember('countries', 380, function () {
            $countries = $this->get('/countries') ?? [];

            Log::info('PDEX countries fetched', [
                'count' => is_array($countries) ? count($countries) : 0,
            ]);

            return $countries;
        });
    }

    /**
     * Fetch the student profile field definitions from PDEX and return a
     * structured payload for the applicant form:
     *   - options:   selectable lists keyed by field_id ({ value, label }).
     *   - labels:    display labels for checkbox fields keyed by field_id.
     *   - countries: title-cased country names for the country dropdown.
     * Cached so the form stays fast and keeps working if PDEX is down.
     *
     * @return array{options: array<string, array<int, array{value: string, label: string}>>, labels: array<string, string>, countries: array<int, string>}
     */
    public function studentUtils(): array
    {
        return Cache::remember('pdex_student_utils', 380, function () {
            $fields = $this->get('/utils/student') ?? [];

            $options = [];
            $labels = [];
            $permissionLabels = [];

            // Diagnostics (no PII): capture the raw field definitions so we can see
            // the field_ids, their declared types, and whether options arrays exist.
            Log::info('All the PDEX studentUtils fields', [
                'fields' => is_array($fields) ? array_slice($fields, 0, 40) : $fields,
            ]);
            Log::info('PDEX studentUtils raw fields', [
                'field_count' => is_array($fields) ? count($fields) : 0,
                'fields_type' => gettype($fields),
                'field_summary' => is_array($fields) ? array_map(function ($field) {
                    if (! is_array($field)) {
                        return ['non_array' => gettype($field)];
                    }

                    return [
                        'field_id' => $field['field_id'] ?? null,
                        'type' => $field['type'] ?? null,
                        'option_count' => isset($field['options']) && is_array($field['options'])
                            ? count($field['options'])
                            : 0,
                    ];
                }, array_slice($fields, 0, 40)) : [],
            ]);

            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if (! is_array($field) || empty($field['field_id'])) {
                        continue;
                    }
                    $permissionLabels[$field['field_id']] = (string) ($field['permission_label'] ?? '');

                    if (($field['type'] ?? null) === 'select' && ! empty($field['options'])) {
                        $options[$field['field_id']] = array_values(array_map(function ($opt) {
                            return [
                                'value' => (string) ($opt['value'] ?? ($opt['label'] ?? '')),
                                'label' => (string) ($opt['label'] ?? ($opt['value'] ?? '')),
                            ];
                        }, $field['options']));
                    }

                    if (($field['type'] ?? null) === 'checkbox' && ! empty($field['label'])) {
                        $labels[$field['field_id']] = (string) $field['label'];
                    }
                }
            }

            $countries = array_values(array_map(
                fn ($country) => ucwords(strtolower((string) ($country['name'] ?? ''))),
                array_filter($this->countries(), fn ($c) => is_array($c) && ! empty($c['name']))
            ));

            // Diagnostics (no PII): final counts the applicant form will receive.
            Log::info('PDEX studentUtils built', [
                'option_field_ids' => array_keys($options),
                'option_list_sizes' => array_map('count', $options),
                'label_field_ids' => array_keys($labels),
                'countries_count' => count($countries),
            ]);

            return [
                'options' => $options,
                'labels' => $labels,
                'permission_labels' => $permissionLabels,
                'countries' => $countries,
                'fields' => $fields, // raw fields for diagnostics
            ];
        });
    }
}
