<?php

namespace App\Contracts;

/**
 * Adapter for the city's upstream billing/ERP system.
 * The platform is a channel on top of it — never the system of record.
 */
interface MunicipalBillingProviderInterface
{
    public function key(): string;

    /** @return array<int,array> raw account records from the upstream system */
    public function fetchAccounts(): array;

    /** @return array<int,array> raw bill records for an upstream account */
    public function fetchBills(string $accountNumber): array;

    /** @return array<int,array> raw payment records posted in the upstream system */
    public function fetchPayments(string $accountNumber): array;

    /**
     * Push a locally-received payment up to the billing system for posting.
     * Returns upstream reference or null when queued.
     */
    public function postPayment(array $paymentData): ?string;
}
