<?php

namespace App\Services\Bancard\Card;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BancardCardClient
{
    protected string $baseUrl;
    protected string $publicKey;
    protected int $timeout;
    protected int $connectTimeout;

    public function __construct(
        protected BancardCardTokenService $tokenService,
    ) {
        $this->baseUrl = rtrim((string) config('bancard_card.base_url'), '/');
        $this->publicKey = (string) config('bancard_card.public_key');
        $this->timeout = (int) config('bancard_card.timeout', 20);
        $this->connectTimeout = (int) config('bancard_card.connect_timeout', 10);
    }

    protected function validateConfig(): void
    {
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('BANCARD_CARD_BASE_URL no configurado.');
        }

        if (empty($this->publicKey)) {
            throw new \RuntimeException('BANCARD_CARD_PUBLIC_KEY no configurado.');
        }

        if (empty(config('bancard_card.private_key'))) {
            throw new \RuntimeException('BANCARD_CARD_PRIVATE_KEY no configurado.');
        }
    }

    protected function endpointUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->acceptJson()
            ->asJson();
    }

    protected function send(
        string $method,
        string $endpoint,
        array $payload,
    ): array {
        $this->validateConfig();

        /** @var Response $response */
        $response = $this->http()->send(
            strtoupper($method),
            $this->endpointUrl($endpoint),
            [
                'json' => $payload,
            ]
        );

        $json = $response->json();

        return [
            'ok' => $response->successful(),
            'status_code' => $response->status(),
            'raw' => is_array($json) ? $json : [
                'raw_body' => $response->body(),
            ],
            'body' => $response->body(),
        ];
    }

    protected function formatAmount(float|int|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public function singleBuy(array $data): array
    {
        $shopProcessId = (string) $data['shop_process_id'];
        $amount = $this->formatAmount($data['amount']);
        $currency = (string) ($data['currency'] ?? 'PYG');
        $description = (string) ($data['description'] ?? '');
        $returnUrl = (string) $data['return_url'];
        $cancelUrl = (string) $data['cancel_url'];
        $additionalData = $data['additional_data'] ?? null;

        $operation = [
            'token' => $this->tokenService->singleBuyToken(
                $shopProcessId,
                $amount,
                $currency,
            ),
            'shop_process_id' => $shopProcessId,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
        ];

        if (!is_null($additionalData) && $additionalData !== '') {
            $operation['additional_data'] = $additionalData;
        }

        $payload = [
            'public_key' => $this->publicKey,
            'operation' => $operation,
        ];

        return $this->send('POST', 'single_buy', $payload);
    }

    public function confirmation(string|int $shopProcessId): array
    {
        $shopProcessId = (string) $shopProcessId;

        $payload = [
            'public_key' => $this->publicKey,
            'operation' => [
                'token' => $this->tokenService->confirmationToken($shopProcessId),
                'shop_process_id' => $shopProcessId,
            ],
        ];

        return $this->send('POST', 'single_buy/confirmations', $payload);
    }

    public function rollback(string|int $shopProcessId): array
    {
        $shopProcessId = (string) $shopProcessId;

        $payload = [
            'public_key' => $this->publicKey,
            'operation' => [
                'token' => $this->tokenService->rollbackToken($shopProcessId),
                'shop_process_id' => $shopProcessId,
            ],
        ];

        return $this->send('POST', 'single_buy/rollback', $payload);
    }

    public function chargeToken(array $data): array
    {
        $shopProcessId = (string) $data['shop_process_id'];
        $aliasToken = (string) $data['alias_token'];
        $amount = $this->formatAmount($data['amount']);
        $currency = (string) ($data['currency'] ?? 'PYG');
        $description = (string) ($data['description'] ?? '');
        $additionalData = (string) ($data['additional_data'] ?? '');
        $numberOfPayments = (string) ($data['number_of_payments'] ?? '1');

        $payload = [
            'public_key' => $this->publicKey,
            'operation' => [
                'token' => $this->tokenService->chargeTokenToken(
                    $shopProcessId,
                    $amount,
                    $currency,
                    $aliasToken,
                ),
                'shop_process_id' => $shopProcessId,
                'number_of_payments' => $numberOfPayments,
                'additional_data' => $additionalData,
                'description' => $description,
                'alias_token' => $aliasToken,
                'amount' => $amount,
                'currency' => $currency,
            ],
        ];

        return $this->send('POST', 'charge', $payload);
    }
}