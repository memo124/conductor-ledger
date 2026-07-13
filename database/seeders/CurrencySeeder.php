<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $fiat = [
            ['code' => 'USD', 'name' => 'US Dollar', 'name_es' => 'Dólar estadounidense', 'symbol' => '$', 'sort_order' => 1],
            ['code' => 'EUR', 'name' => 'Euro', 'name_es' => 'Euro', 'symbol' => '€', 'sort_order' => 2],
            ['code' => 'GBP', 'name' => 'British Pound', 'name_es' => 'Libra esterlina', 'symbol' => '£', 'sort_order' => 3],
            ['code' => 'MXN', 'name' => 'Mexican Peso', 'name_es' => 'Peso mexicano', 'symbol' => '$', 'sort_order' => 10],
            ['code' => 'GTQ', 'name' => 'Guatemalan Quetzal', 'name_es' => 'Quetzal guatemalteco', 'symbol' => 'Q', 'sort_order' => 11],
            ['code' => 'HNL', 'name' => 'Honduran Lempira', 'name_es' => 'Lempira hondureño', 'symbol' => 'L', 'sort_order' => 12],
            ['code' => 'NIO', 'name' => 'Nicaraguan Córdoba', 'name_es' => 'Córdoba nicaragüense', 'symbol' => 'C$', 'sort_order' => 13],
            ['code' => 'CRC', 'name' => 'Costa Rican Colón', 'name_es' => 'Colón costarricense', 'symbol' => '₡', 'sort_order' => 14],
            ['code' => 'PAB', 'name' => 'Panamanian Balboa', 'name_es' => 'Balboa panameño', 'symbol' => 'B/.', 'sort_order' => 15],
            ['code' => 'DOP', 'name' => 'Dominican Peso', 'name_es' => 'Peso dominicano', 'symbol' => 'RD$', 'sort_order' => 16],
            ['code' => 'COP', 'name' => 'Colombian Peso', 'name_es' => 'Peso colombiano', 'symbol' => '$', 'sort_order' => 20],
            ['code' => 'PEN', 'name' => 'Peruvian Sol', 'name_es' => 'Sol peruano', 'symbol' => 'S/', 'sort_order' => 21],
            ['code' => 'CLP', 'name' => 'Chilean Peso', 'name_es' => 'Peso chileno', 'symbol' => '$', 'sort_order' => 22],
            ['code' => 'ARS', 'name' => 'Argentine Peso', 'name_es' => 'Peso argentino', 'symbol' => '$', 'sort_order' => 23],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'name_es' => 'Real brasileño', 'symbol' => 'R$', 'sort_order' => 24],
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'name_es' => 'Dólar canadiense', 'symbol' => 'CA$', 'sort_order' => 30],
        ];

        $crypto = [
            ['code' => 'BTC', 'name' => 'Bitcoin', 'name_es' => 'Bitcoin', 'symbol' => '₿', 'sort_order' => 100, 'decimal_places' => 8],
            ['code' => 'ETH', 'name' => 'Ethereum', 'name_es' => 'Ethereum', 'symbol' => 'Ξ', 'sort_order' => 101, 'decimal_places' => 8],
            ['code' => 'USDT', 'name' => 'Tether', 'name_es' => 'Tether', 'symbol' => '₮', 'sort_order' => 102, 'decimal_places' => 2],
            ['code' => 'BNB', 'name' => 'BNB', 'name_es' => 'BNB', 'symbol' => 'BNB', 'sort_order' => 103, 'decimal_places' => 8],
            ['code' => 'SOL', 'name' => 'Solana', 'name_es' => 'Solana', 'symbol' => 'SOL', 'sort_order' => 104, 'decimal_places' => 8],
            ['code' => 'XRP', 'name' => 'XRP', 'name_es' => 'XRP', 'symbol' => 'XRP', 'sort_order' => 105, 'decimal_places' => 6],
            ['code' => 'ADA', 'name' => 'Cardano', 'name_es' => 'Cardano', 'symbol' => 'ADA', 'sort_order' => 106, 'decimal_places' => 6],
            ['code' => 'DOGE', 'name' => 'Dogecoin', 'name_es' => 'Dogecoin', 'symbol' => 'Ð', 'sort_order' => 107, 'decimal_places' => 8],
        ];

        foreach ($fiat as $currency) {
            $this->upsertCurrency($currency, 'fiat', 2);
        }

        foreach ($crypto as $currency) {
            $this->upsertCurrency($currency, 'crypto', $currency['decimal_places'] ?? 8);
        }
    }

    private function upsertCurrency(array $currency, string $type, int $decimalPlaces): void
    {
        Currency::query()->updateOrCreate(
            ['code' => $currency['code']],
            [
                'name' => $currency['name'],
                'name_es' => $currency['name_es'],
                'symbol' => $currency['symbol'],
                'currency_type' => $type,
                'decimal_places' => $decimalPlaces,
                'is_active' => true,
                'sort_order' => $currency['sort_order'],
            ]
        );
    }
}
