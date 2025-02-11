<?php

namespace kennethormandy\marketplace\providers\clients;

use verbb\auth\clients\stripe\provider\StripeResourceOwner;

class StripeExpressResourceOwner extends StripeResourceOwner
{
    /**
     * @inheritdoc
     * @return bool
     */
    public function getTransfersEnabled(): bool
    {
        return $this->response['transfers_enabled'] ?? false;
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function getCurrenciesSupported(): array
    {
        return $this->response['currencies_supported'] ?? [];
    }

    /**
     * @inheritdoc
     * @return ?bool
     */
    public function getManaged(): bool
    {
        return $this->response['managed'] ?? false;
    }
}
