<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Denprog\Meridian\Providers\FrankfurterAppProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const API_BASE_URL_TEST = 'https://api.frankfurter.dev/v1'; // Match provider's constant

beforeEach(function () {
    Log::spy(); // Use spy for more granular assertions per test
    // Convert all PHP errors (including notices/warnings) to exceptions for this test suite
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return false;
        }
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
});

afterEach(function () {
    restore_error_handler();
});

test('getRates fetches rates for specific target currencies successfully', function () {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR', 'GBP'];
    $date = Carbon::now();
    $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8];

    $expectedPath = API_BASE_URL_TEST.'/latest';
    $expectedQueryString = 'from='.$baseCurrency.'&to='.implode(',', $targetCurrencies);

    Http::fake([
        API_BASE_URL_TEST.'/latest?symbols=EUR,GBP&base=USD' => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect(array_keys($rates))->toEqual($targetCurrencies);
});

test('getRates fetches rates for all available target currencies if none specified', function () {
    $baseCurrency = 'USD';
    $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8, 'JPY' => 110];
    $date = Carbon::now();

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency);

    expect($rates)->toBe($expectedRates);

    Http::assertSent(function ($request) use ($baseCurrency) {
        $expectedUrl = API_BASE_URL_TEST.'/latest?from='.$baseCurrency;
        return $request->url() === $expectedUrl &&
               $request['from'] === $baseCurrency &&
               !isset($request['to']);
    });

    Log::shouldNotHaveReceived('error');
});

test('getRates fetches rates for a specific date successfully', function () {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];
    $date = Carbon::parse('2023-01-15');
    $expectedRates = ['EUR' => 0.85];

    Http::fake([
        API_BASE_URL_TEST.'/'.$date->toDateString().'*' => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies, $date);

    expect($rates)->toBe($expectedRates);

    Http::assertSent(function ($request) use ($baseCurrency, $targetCurrencies, $date) {
        $expectedUrl = API_BASE_URL_TEST.'/'.$date->toDateString().'?from='.$baseCurrency.'&to='.implode(',', $targetCurrencies);
        return $request->url() === $expectedUrl &&
               $request['from'] === $baseCurrency &&
               $request['to'] === implode(',', $targetCurrencies);
    });

    Log::shouldNotHaveReceived('error');
});

test('getRates handles API request failure and logs error', function () {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(null, 500),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context = []) {
            return str_contains($message, 'Frankfurter.app API request failed.') &&
                   isset($context['status']) && $context['status'] === 500 &&
                   isset($context['url']) && str_contains($context['url'], API_BASE_URL_TEST.'/latest') &&
                   isset($context['params']['from']) && $context['params']['from'] === 'USD';
        });
});

//test('getRates handles connection exception and logs error', function () {
//    $baseCurrency = 'USD';
//    $targetCurrencies = ['EUR'];
//
//    Http::fake([
//        API_BASE_URL_TEST.'/*' => function ($request) {
//            throw new \Illuminate\Http\Client\ConnectionException("Connection failed for testing purposes: ".$request->url());
//        },
//    ]);
//
//    $provider = new FrankfurterAppProvider();
//    $rates = $provider->getRates($baseCurrency, $targetCurrencies);
//
//    expect($rates)->toBeNull();
//
//    Log::shouldHaveReceived('error')
//        ->once()
//        ->withArgs(function (string $message, array $context = []) use ($baseCurrency, $targetCurrencies) {
//            return str_contains($message, 'Exception while fetching rates from Frankfurter.app.') &&
//                   isset($context['exception']) &&
//                   isset($context['base_currency']) && $context['base_currency'] === $baseCurrency &&
//                   isset($context['target_currencies']) && $context['target_currencies'] === $targetCurrencies;
//        });
//});

test('getRates returns null and logs error if API response is not successful but not a client/server error', function () {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(['message' => 'Some other issue'], 300),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context = []) use ($baseCurrency, $targetCurrencies) {
            return str_contains($message, 'Exception while fetching rates from Frankfurter.app.') &&
                   isset($context['exception']) &&
                   isset($context['base_currency']) && $context['base_currency'] === $baseCurrency &&
                   isset($context['target_currencies']) && $context['target_currencies'] === $targetCurrencies;
        });
});

test('getRates returns null and logs error if API response is successful but rates key is missing', function () {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    // Force a situation where $response->json() might fail or return non-array, leading to Throwable
    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response('invalid json string', 200),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context = []) use ($baseCurrency, $targetCurrencies) {
            // The specific exception message might change (e.g., JSON decode error or access on null)
            // So, we check that an exception was logged and it's for the correct provider operation.
            return str_contains($message, 'Exception while fetching rates from Frankfurter.app.') &&
                   isset($context['exception']) && // Ensure some exception message is present
                   isset($context['base_currency']) && $context['base_currency'] === $baseCurrency &&
                   isset($context['target_currencies']) && $context['target_currencies'] === $targetCurrencies;
        });
});
