<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Position;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ExternalApiService
{
    /**
     * Placeholders available in the body template
     */
    public const PLACEHOLDERS = [
        'date',
        'started_at',
        'finished_at',
        'hours',
        'pause',
        'description',
        'remote',
        'invoice_title',
        'project_reference',
    ];

    /**
     * Send all unsent positions of an invoice to the external API configured for its client
     *
     * @param Invoice $invoice Record whose positions are sent
     * @return array{sent: int, errors: list<string>}
     */
    public static function pushInvoice(Invoice $invoice): array
    {
        $config = $invoice->project?->client?->api_config;
        if (!$config) {
            return ['sent' => 0, 'errors' => [__('apiNotConfigured')]];
        }

        try {
            $request = self::request($config);
        } catch (Throwable $e) {
            return ['sent' => 0, 'errors' => [$e->getMessage()]];
        }

        $sent = 0;
        $errors = [];
        $positions = $invoice->positions()->whereNull('external_id')->orderBy('started_at')->get();
        foreach ($positions as $position) {
            try {
                $position->update(['external_id' => self::push($position, $request, $config)]);
                $sent++;
            } catch (Throwable $e) {
                $errors[] = Str::limit($position->description, 40) . ': ' . $e->getMessage();
            }
        }

        return ['sent' => $sent, 'errors' => $errors];
    }

    /**
     * Send a single position and return the id the external API assigned to it
     */
    public static function push(Position $position, PendingRequest $request, array $config): string
    {
        $response = self::throwIfFailed(
            $request->send($config['method'] ?? 'POST', $config['url'], ['json' => self::renderBody($position, $config['body'])]),
        );

        $id = $response->json($config['id_field'] ?? 'id');
        if ($id === null || $id === '') {
            throw new RuntimeException(__('apiResponseWithoutId'));
        }

        return (string) $id;
    }

    /**
     * Replace placeholders in the body template with JSON encoded position data and decode the result
     */
    public static function renderBody(Position $position, string $template): array
    {
        $values = [
            'date' => $position->started_at->format('Y-m-d'),
            'started_at' => $position->started_at->toIso8601String(),
            'finished_at' => $position->finished_at->toIso8601String(),
            'hours' => round($position->duration, 2),
            'pause' => $position->pause_duration,
            'description' => $position->description,
            'remote' => $position->remote,
            'invoice_title' => $position->invoice?->title,
            'project_reference' => $position->invoice?->project?->api_reference,
        ];

        $replacements = [];
        foreach ($values as $key => $value) {
            $replacements['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return json_decode(strtr($template, $replacements), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Build a request with the configured authentication applied
     */
    private static function request(array $config): PendingRequest
    {
        $request = Http::acceptJson();

        return match ($config['auth'] ?? 'none') {
            'token' => $request->withToken($config['token']),
            'basic' => $request->withBasicAuth($config['username'], $config['password']),
            'login' => $request->withToken(
                self::throwIfFailed(
                    Http::acceptJson()->post($config['login_url'], ['username' => $config['username'], 'password' => $config['password']]),
                )
                    ->json($config['token_field'] ?? 'token')
                    ?? throw new RuntimeException(__('apiLoginWithoutToken')),
            ),
            default => $request,
        };
    }

    /**
     * Throw a readable error for a failed response, e.g. "401 Unauthorized: Bad credentials"
     */
    private static function throwIfFailed(Response $response): Response
    {
        if (!$response->failed()) {
            return $response;
        }

        $json = $response->json();
        $message = is_array($json)
            ? collect(['detail', 'message', 'error_description', 'error', 'title'])
                ->map(fn(string $field) => data_get($json, $field))
                ->first(fn($value) => is_string($value) && $value !== '' && $value !== $response->reason())
            : Str::limit(trim(strip_tags($response->body())), 200);

        throw new RuntimeException(trim("{$response->status()} {$response->reason()}") . ($message ? ": $message" : ''));
    }
}
