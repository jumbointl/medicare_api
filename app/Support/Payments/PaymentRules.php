<?php

namespace App\Support\Payments;

final class PaymentType
{
    public const PAY_AT_CLINIC = 1100;
    public const BANK_TRANSFER = 1200;
    public const PAY_ONE_HOUR_BEFORE = 1500;
    public const MEDICAL_AGREEMENT_PRESENTIAL = 2100;
    public const DEBIT_CARD = 7000;
    public const CREDIT_CARD = 7500;
    public const QR = 7900;
    public const PREAPPROVED_CREDIT = 8000;
    public const CASH = 9001;
    public const HOSPITAL_WALLET = 9100;
}

final class PaymentProvider
{
    public const BANCARD = 1001;
    public const BANCARD_TEXT = 'bancard';
}

final class PaymentRules
{
    public static function isManualValidation(int $paymentTypeId): bool
    {
        return $paymentTypeId < 5000;
    }

    public static function requiresOnlineValidation(int $paymentTypeId): bool
    {
        return $paymentTypeId >= 5001 && $paymentTypeId <= 7999;
    }

    public static function isAutoPaid(int $paymentTypeId): bool
    {
        return $paymentTypeId >= 8000;
    }

    public static function resolveName(int $paymentTypeId): string
    {
        return match ($paymentTypeId) {
            PaymentType::PAY_AT_CLINIC => 'PAGO EN CLINICA',
            PaymentType::BANK_TRANSFER => 'TRANSFERENCIA BANCARIA',
            PaymentType::PAY_ONE_HOUR_BEFORE => 'PAGO 1 HORA ANTES DE CONSULTA',
            PaymentType::MEDICAL_AGREEMENT_PRESENTIAL => 'CONVENIO MEDICO PRESENCIAL',
            PaymentType::DEBIT_CARD => 'TARJETA DE DEBITO',
            PaymentType::CREDIT_CARD => 'TARJETA DE CREDITO',
            PaymentType::QR => 'QR',
            PaymentType::PREAPPROVED_CREDIT => 'CREDITO PREAPROBADO',
            PaymentType::CASH => 'EFECTIVO',
            PaymentType::HOSPITAL_WALLET => 'BILLETERA HOSPITAL PROPIO',
            default => 'NO DEFINIDO',
        };
    }

    public static function resolveMethodCode(int $paymentTypeId): string
    {
        return match ($paymentTypeId) {
            PaymentType::DEBIT_CARD => 'debit_card',
            PaymentType::CREDIT_CARD => 'credit_card',
            PaymentType::QR => 'qr',
            PaymentType::BANK_TRANSFER => 'bank_transfer',
            PaymentType::PAY_AT_CLINIC => 'pay_at_clinic',
            PaymentType::PAY_ONE_HOUR_BEFORE => 'pay_before_consultation',
            PaymentType::MEDICAL_AGREEMENT_PRESENTIAL => 'medical_agreement_presential',
            PaymentType::PREAPPROVED_CREDIT => 'preapproved_credit',
            PaymentType::CASH => 'cash',
            PaymentType::HOSPITAL_WALLET => 'hospital_wallet',
            default => (string) $paymentTypeId,
        };
    }

    public static function appointmentPaymentStatusOnCreation(int $paymentTypeId): string
    {
        return self::isAutoPaid($paymentTypeId) ? 'Paid' : 'Unpaid';
    }
}