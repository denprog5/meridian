<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Service for updating exchange rates.
 */
final readonly class UpdateExchangeRateService implements UpdateExchangeRateContract
{
    /**
     * UpdateExchangeRateService constructor.
     */
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
        private CurrencyServiceContract $currencyService,
        private ExchangeRate $exchangeRateModel
    ) {}

    /**
     * {@inheritdoc}
     */
    public function updateRates(
        ?string $baseCurrencyCode = null,
        ?array $targetCurrencyCodes = null,
        ?Carbon $date = null
    ): bool {
        $actualBaseCurrencyCode = mb_strtoupper($baseCurrencyCode ?? Config::string('meridian.system_base_currency_code', 'USD'));
        $actualDate = ($date ?? Carbon::today())->startOfDay();

        $baseCurrency = $this->currencyService->findByCode($actualBaseCurrencyCode);
        if (! $baseCurrency instanceof Currency) {
            Log::warning("[Meridian] UpdateExchangeRateService: Invalid base currency code '$actualBaseCurrencyCode'.");

            return false;
        }

        $configuredTargetCodes = Config::get('meridian.target_currency_codes', []);
        $actualTargetCurrencyCodes = $targetCurrencyCodes ?? $configuredTargetCodes;

        if (empty($actualTargetCurrencyCodes) || ! is_array($actualTargetCurrencyCodes)) {
            Log::info('[Meridian] UpdateExchangeRateService: No target currency codes specified or configured to update.');

            return false;
        }

        $actualTargetCurrencyCodes = array_filter(
            /* @phpstan-ignore-next-line */
            array_map('strtoupper', $actualTargetCurrencyCodes),
            fn ($code): bool => $code !== $actualBaseCurrencyCode
        );

        if ($actualTargetCurrencyCodes === []) {
            Log::info("[Meridian] UpdateExchangeRateService: No valid target currency codes remaining after filtering base currency '$actualBaseCurrencyCode'.");

            return false;
        }

        $validTargetCurrencies = [];
        foreach ($actualTargetCurrencyCodes as $targetCode) {
            if ($this->currencyService->findByCode($targetCode) instanceof Currency) {
                $validTargetCurrencies[] = $targetCode;
            } else {
                Log::warning("[Meridian] UpdateExchangeRateService: Invalid target currency code '$targetCode' skipped.");
            }
        }

        if ($validTargetCurrencies === []) {
            Log::info("[Meridian] UpdateExchangeRateService: No valid target currencies found to update rates for against base '$actualBaseCurrencyCode'.");

            return false;
        }

        $fetchedRates = $this->exchangeRateProvider->getRates($actualBaseCurrencyCode, $validTargetCurrencies, $actualDate);

        if ($fetchedRates === null || $fetchedRates === []) {
            Log::info("[Meridian] UpdateExchangeRateService: No rates returned from provider for base '$actualBaseCurrencyCode' and targets on {$actualDate->toDateString()}.");

            return false;
        }

        foreach ($fetchedRates as $targetCode => $rate) {
            $targetCode = mb_strtoupper($targetCode);
            $this->exchangeRateModel->query()->updateOrCreate(
                [
                    'base_currency_code' => $actualBaseCurrencyCode,
                    'target_currency_code' => $targetCode,
                    'rate_date' => $actualDate,
                ],
                [
                    'rate' => $rate,
                ]
            );
        }

        return true;
    }
}
