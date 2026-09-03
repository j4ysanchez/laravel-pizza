<?php

namespace Database\Factories;

use App\Models\Topping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topping>
 */
class ToppingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Pepperoni', 'Mushrooms', 'Onions', 'Sausage',
                'Bacon', 'Extra Cheese', 'Black Olives', 'Green Peppers',
                'Pineapple', 'Spinach',
            ]),
            'price' => fake()->randomFloat(2, 0.5, 3),
        ];
    }
}
