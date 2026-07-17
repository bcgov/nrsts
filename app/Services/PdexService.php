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

        if ($tokenEndpoint === '' || empty($clientId) || empty($clientSecret)) {
            Log::error('PDEX token request skipped: client credentials or token endpoint not configured.');

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

        if ($accessToken && $expiresIn > 60) {
            Cache::put('pdex_access_token', $accessToken, $expiresIn - 60);
        }

        return $accessToken;
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
            return null;
        }

        return $payload['data'] ?? $payload;
    }

    /**
     * Fetch the list of countries from PDEX (cached).
     */
    public function countries(): array
    {
        return Cache::remember('countries', 380, function () {
            return $this->get('/countries') ?? [];
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

            if (is_array($fields)) {
                foreach ($fields as $field) {
                    if (! is_array($field) || empty($field['field_id'])) {
                        continue;
                    }

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

            return [
                'options' => $options,
                'labels' => $labels,
                'countries' => $countries,
            ];
        });
    }
}
