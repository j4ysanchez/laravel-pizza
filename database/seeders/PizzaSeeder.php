<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pizza;
use App\Models\Topping;

class PizzaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $toppings = Topping::factory(6)->create();

        Pizza::factory(6)->create()->each(function (Pizza 
        $pizza) use ($toppings) {
            $pizza->toppings()->attach(
                $toppings->random(rand(1,3))->pluck('id')
            );
        });
        //
    }
}
