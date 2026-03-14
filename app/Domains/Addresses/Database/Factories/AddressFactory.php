<?php

namespace App\Domains\Addresses\Database\Factories;

use App\Domains\Addresses\Models\Address;
use App\Domains\Clients\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address1' => fake()->streetAddress(),
            'address2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'country' => 'US',
            'client_id' => Client::factory(),
        ];
    }
}
