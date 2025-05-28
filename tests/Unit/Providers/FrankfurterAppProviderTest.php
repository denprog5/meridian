<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use Denprog\Meridian\Providers\FrankfurterAppProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const API_BASE_URL_FRANKFURTER_TEST = 'https://api.frankfurter.dev';

covers(FrankfurterAppProvider::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
});

describe('Successful Rate Fetching', function (): void {
    it('fetches rates for specific target currencies for the latest date', function (): void {
        $baseCurrency = 'USD';
        $targetCurrencies = ['EUR', 'GBP'];
        $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8];
        $dateString = Carbon::now()->toDateString(); // Used in the faked response

        $endpointUrl = API_BASE_URL_FRANKFURTER_TEST.'/v1/latest';
        $queryParams = ['from' => $baseCurrency, 'to' => implode(',', $targetCurrencies)];
        $expectedBuiltUrl = $endpointUrl.'?'.http_build_query($queryParams);

        Http::fake([
            $expectedBuiltUrl => Http::response(['base' => $baseCurrency, 'date' => $dateString, 'rates' => $expectedRates]),
        ]);

        $provider = new FrankfurterAppProvider();
        $rates = $provider->getRates($baseCurrency, $targetCurrencies);

        expect($rates)->toBe($expectedRates);

        Http::assertSent(fn (Request $request): bool => $request->url() === $expectedBuiltUrl);
    });

    it('fetches rates for all available target currencies if none specified for the latest date', function (): void {
        $baseCurrency = 'USD';
        $expectedRates = ['EUR' => 0.9, 'GBP' => 0.8, 'JPY' => 110];
        $dateString = Carbon::now()->toDateString();

        $endpointUrl = API_BASE_URL_FRANKFURTER_TEST.'/v1/latest';
        $queryParams = ['from' => $baseCurrency];
        $expectedUrl = $endpointUrl.'?'.http_build_query($queryParams);

        Http::fake([
            $expectedUrl => Http::response(['base' => $baseCurrency, 'date' => $dateString, 'rates' => $expectedRates]),
        ]);

        $provider = new FrankfurterAppProvider();
        $rates = $provider->getRates($baseCurrency);

        expect($rates)->toBe($expectedRates);

        Http::assertSent(fn (Request $request): bool => $request->url() === $expectedUrl &&
            $request->data() === ['from' => $baseCurrency]);
    });

    it('fetches rates for a specific date and target currencies successfully', function (): void {
        $baseCurrency = 'USD';
        $targetCurrencies = ['EUR'];
        $date = Carbon::parse('2023-01-15');
        $expectedRates = ['EUR' => 0.85];

        $endpointUrl = API_BASE_URL_FRANKFURTER_TEST.'/v1/'.$date->toDateString();
        $queryParams = ['from' => $baseCurrency, 'to' => implode(',', $targetCurrencies)];
        $expectedUrl = $endpointUrl.'?'.http_build_query($queryParams);

        Http::fake([
            $expectedUrl => Http::response(['base' => $baseCurrency, 'date' => $date->toDateString(), 'rates' => $expectedRates]),
        ]);

        $provider = new FrankfurterAppProvider();
        $rates = $provider->getRates($baseCurrency, $targetCurrencies, $date);

        expect($rates)->toBe($expectedRates);

        Http::assertSent(function (Request $request) use ($baseCurrency, $targetCurrencies, $expectedUrl): bool {
            $expectedParams = ['from' => $baseCurrency, 'to' => implode(',', $targetCurrencies)];

            return $request->url() === $expectedUrl &&
                $request->data() === $expectedParams;
        });
    });
});

describe('Error and Failure Handling', function (): void {
    test('getRates returns null and logs error for various API issues', function (callable $fakeResponseFactory, string $expectedUrlPattern): void {
        $baseCurrency = 'USD';
        $targetCurrencies = ['EUR'];

        Http::fake([
            $expectedUrlPattern => $fakeResponseFactory(),
        ]);

        Log::shouldReceive('error')->once();

        $provider = new FrankfurterAppProvider();
        $rates = $provider->getRates($baseCurrency, $targetCurrencies);

        expect($rates)->toBeNull();

        Http::assertSent(fn (Request $request): bool => $request->url() === $expectedUrlPattern);
    })->with([
        'API request failure (500)' => [
            fn () => Http::response(null, 500),
            API_BASE_URL_FRANKFURTER_TEST.'/v1/latest?from=USD&to=EUR',
        ],
        'API non-success (300)' => [
            fn () => Http::response(['message' => 'Some other issue'], 300),
            API_BASE_URL_FRANKFURTER_TEST.'/v1/latest?from=USD&to=EUR',
        ],
        'API response is invalid JSON string' => [
            fn () => Http::response('invalid json string'),
            API_BASE_URL_FRANKFURTER_TEST.'/v1/latest?from=USD&to=EUR',
        ],
        'API response is valid JSON but missing rates key' => [
            fn () => Http::response(['base' => 'USD', 'date' => Carbon::now()->toDateString()]),
            API_BASE_URL_FRANKFURTER_TEST.'/v1/latest?from=USD&to=EUR',
        ],
    ]);

    it('getRates returns null and logs error on client exception (e.g., timeout)', function (): void {
        $baseCurrency = 'USD';
        $targetCurrencies = ['EUR'];

        Http::shouldReceive('timeout->get')->once()->andThrow(new ConnectionException('Simulated connection timeout'));

        Log::shouldReceive('error')->once()
            ->withArgs(fn ($message, $context): bool => str_contains($message, 'Exception while fetching rates') &&
                isset($context['exception']) && str_contains((string) $context['exception'], 'Simulated connection timeout'));

        $provider = new FrankfurterAppProvider();
        $rates = $provider->getRates($baseCurrency, $targetCurrencies);

        expect($rates)->toBeNull();
    });
});
