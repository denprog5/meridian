<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Denprog\Meridian\Providers\FrankfurterAppProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

const API_BASE_URL_TEST = 'https://api.frankfurter.dev/v1'; // Match provider's constant

test('getRates fetches rates for specific target currencies successfully', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR', 'GBP'];
    $date = Carbon::now();
    $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8];

    Http::fake([
        API_BASE_URL_TEST.'/latest?symbols=EUR,GBP&base=USD' => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toEqual($targetCurrencies);
});

test('getRates fetches rates for all available target currencies if none specified', function (): void {
    $baseCurrency = 'USD';
    $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8, 'JPY' => 110];
    $date = Carbon::now();

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency);

    expect($rates)->toBe($expectedRates);

    Http::assertSent(function ($request) use ($baseCurrency): bool {
        $expectedUrl = API_BASE_URL_TEST.'/latest?from='.$baseCurrency;

        return $request->url() === $expectedUrl &&
               $request['from'] === $baseCurrency &&
               ! isset($request['to']);
    });
});

test('getRates fetches rates for a specific date successfully', function (): void {
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

    Http::assertSent(function (Request $request) use ($baseCurrency, $targetCurrencies, $date): bool {
        $expectedUrl = API_BASE_URL_TEST.'/'.$date->toDateString().'?from='.$baseCurrency.'&to='.implode(',', $targetCurrencies);

        return $request->url() === $expectedUrl &&
               $request['from'] === $baseCurrency &&
               $request['to'] === implode(',', $targetCurrencies);
    });

});

test('getRates handles API request failure and logs error', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(null, 500),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();
});

test('getRates returns null and logs error if API response is not successful but not a client/server error', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response(['message' => 'Some other issue'], 300),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();
});

test('getRates returns null and logs error if API response is successful but rates key is missing', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR'];

    Http::fake([
        API_BASE_URL_TEST.'/latest*' => Http::response('invalid json string'),
    ]);

    $provider = new FrankfurterAppProvider();
    $rates = $provider->getRates($baseCurrency, $targetCurrencies);

    expect($rates)->toBeNull();
});
