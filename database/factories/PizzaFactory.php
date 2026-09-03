<?php

namespace Database\Factories;

use App\Models\Pizza;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pizza>
 */
class PizzaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Margherita', 
            'Pepperoni', 'BBQ Chicken', 'Veggie Supreme',
            'Hawaiian']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 8, 20),
            'image' => null,
            //
        ];
    }
}
