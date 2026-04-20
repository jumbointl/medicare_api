<?php

namespace App\Payments\Contracts;

use App\Models\PreOrderModel;

interface PaymentGatewayInterface
{
    public function initiate(PreOrderModel $preOrder): array;
}
