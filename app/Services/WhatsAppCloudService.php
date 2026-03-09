<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppCloudService
{
    public function sendTemplateMessage(
        string $to,
        string $templateName,
        array $bodyParameters = [],
        string $languageCode = 'en_US',
        ?string $buttonUrlParameter = null,
        int $buttonIndex = 0,
    ): array {
        $to = $this->normalizeTo($to);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        $components = [];

        if (!empty($bodyParameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(static fn ($text) => [
                    'type' => 'text',
                    'text' => (string) $text,
                ], $bodyParameters),
            ];
        }

        if (!empty($buttonUrlParameter)) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => (string) $buttonIndex,
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => (string) $buttonUrlParameter,
                    ],
                ],
            ];
        }

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = $this->client()->post($this->messagesUrl(), $payload);

        if (!$response->successful()) {
            Log::warning('WhatsApp Cloud API request failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            $response->throw();
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        $token = config('services.whatsapp.token');
        if (empty($token)) {
            throw new RuntimeException('Missing WHATSAPP_CLOUD_API_TOKEN');
        }

        return Http::asJson()
            ->acceptJson()
            ->withToken($token)
            ->timeout(30);
    }

    private function messagesUrl(): string
    {
        $version = config('services.whatsapp.api_version', 'v21.0');
        $phoneNumberId = config('services.whatsapp.phone_number_id');

        if (empty($phoneNumberId)) {
            throw new RuntimeException('Missing WHATSAPP_PHONE_NUMBER_ID');
        }

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }

    private function normalizeTo(string $phone): string
    {
        // WhatsApp Cloud API expects the phone number in international format.
        // Commonly this is digits only (country code + national number).
        $phone = trim($phone);
        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }
}
