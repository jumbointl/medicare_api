<?php

namespace App\Services\Bancard\Card;

class BancardCardTokenService
{
    public function __construct(
        protected ?string $privateKey = null,
    ) {
        $this->privateKey = $this->privateKey ?: config('bancard_card.private_key');
    }

    protected function getPrivateKey(): string
    {
        if (empty($this->privateKey)) {
            throw new \RuntimeException('BANCARD_CARD_PRIVATE_KEY no configurado.');
        }

        return $this->privateKey;
    }

    public function singleBuyToken(
        string|int $shopProcessId,
        string $amount,
        string $currency,
    ): string {
        return md5($this->getPrivateKey() . $shopProcessId . $amount . $currency);
    }

    public function confirmationToken(
        string|int $shopProcessId,
    ): string {
        return md5($this->getPrivateKey() . $shopProcessId . 'get_confirmation');
    }

    public function rollbackToken(
        string|int $shopProcessId,
    ): string {
        return md5($this->getPrivateKey() . $shopProcessId . 'rollback' . '0.00');
    }

    public function chargeTokenToken(
        string|int $shopProcessId,
        string $amount,
        string $currency,
        string $aliasToken,
    ): string {
        return md5(
            $this->getPrivateKey()
            . $shopProcessId
            . 'charge'
            . $amount
            . $currency
            . $aliasToken
        );
    }
}