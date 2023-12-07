<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VisitorAttendance>
 */
class VisitorAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'date' => $this->faker->date(),
            'visitor_id' => $this->faker->numberBetween(1, 100), // Assuming visitor_id is an integer field.
            'status' => $this->faker->randomElement(['A', 'B', 'C']), // Assuming status is a string field with values A, B, or C.
            'in' => $this->faker->time('H:i'),
            'out' => $this->faker->time('H:i'),
            'total_hrs' => $this->faker->randomFloat(2, 1, 8), // Assuming total_hrs is a floating-point field with 2 decimal places.
            'device_id_in' => $this->faker->word,
            'device_id_out' => $this->faker->word,
            'date_in' => $this->faker->date(),
            'date_out' => $this->faker->date(),
            'company_id' => 8, // As
        ];
    }
}
