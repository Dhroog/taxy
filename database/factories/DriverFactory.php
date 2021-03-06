<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Driver>
 */
class DriverFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'surname' => $this->faker->lastName(),
            'age' => $this->faker->numberBetween(18,50),
            'available' => $this->faker->boolean(),
            'lat' => $this->faker->latitude(),
            'long' => $this->faker->longitude()
        ];
    }
}
