<?php

namespace App\Support;

use App\Models\company;

/**
 * Formats money in the display currency of the current client's company,
 * derived from its billing / registered country. Falls back to USD.
 */
class Money
{
    /** country (lower-cased, trimmed) => [code, symbol] */
    private const MAP = [
        'india' => ['INR', '₹'], 'in' => ['INR', '₹'], 'ind' => ['INR', '₹'],
        'united states' => ['USD', '$'], 'usa' => ['USD', '$'], 'us' => ['USD', '$'], 'united states of america' => ['USD', '$'],
        'united kingdom' => ['GBP', '£'], 'uk' => ['GBP', '£'], 'gb' => ['GBP', '£'], 'england' => ['GBP', '£'],
        'canada' => ['CAD', 'CA$'], 'ca' => ['CAD', 'CA$'],
        'australia' => ['AUD', 'A$'], 'au' => ['AUD', 'A$'],
        'new zealand' => ['NZD', 'NZ$'],
        'singapore' => ['SGD', 'S$'], 'sg' => ['SGD', 'S$'],
        'united arab emirates' => ['AED', 'AED '], 'uae' => ['AED', 'AED '], 'dubai' => ['AED', 'AED '],
        'saudi arabia' => ['SAR', 'SAR '],
        'japan' => ['JPY', '¥'], 'china' => ['CNY', '¥'],
        'switzerland' => ['CHF', 'CHF '],
        'south africa' => ['ZAR', 'R'],
        'brazil' => ['BRL', 'R$'],
        'pakistan' => ['PKR', '₨'], 'bangladesh' => ['BDT', '৳'], 'sri lanka' => ['LKR', 'Rs '],
        'nigeria' => ['NGN', '₦'], 'kenya' => ['KES', 'KSh '],
        // Eurozone
        'germany' => ['EUR', '€'], 'france' => ['EUR', '€'], 'spain' => ['EUR', '€'], 'italy' => ['EUR', '€'],
        'netherlands' => ['EUR', '€'], 'ireland' => ['EUR', '€'], 'portugal' => ['EUR', '€'], 'belgium' => ['EUR', '€'],
        'austria' => ['EUR', '€'], 'finland' => ['EUR', '€'], 'greece' => ['EUR', '€'], 'europe' => ['EUR', '€'],
    ];

    /** Per-request cache keyed by company id. */
    private static array $cache = [];

    public static function flush(): void
    {
        self::$cache = [];
    }

    public static function resolve(?company $company): array
    {
        if (! $company) {
            return ['USD', '$'];
        }
        if (isset(self::$cache[$company->id])) {
            return self::$cache[$company->id];
        }

        $country = strtolower(trim((string) ($company->billing_country ?: $company->company_country ?: '')));
        $result = self::MAP[$country] ?? ['USD', '$'];

        return self::$cache[$company->id] = $result;
    }

    public static function format(float $amount, ?company $company, bool $withCode = false): string
    {
        [$code, $symbol] = self::resolve($company);
        $out = $symbol . number_format($amount, 2);
        return $withCode ? $out . ' ' . $code : $out;
    }

    /** Format for the currently authenticated client. */
    public static function client($amount): string
    {
        $company = auth()->user()?->contact?->company;
        return self::format((float) $amount, $company);
    }

    public static function clientCode(): string
    {
        return self::resolve(auth()->user()?->contact?->company)[0];
    }

    public static function clientSymbol(): string
    {
        return self::resolve(auth()->user()?->contact?->company)[1];
    }
}
