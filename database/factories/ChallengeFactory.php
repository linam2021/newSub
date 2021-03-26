<?php

namespace Database\Factories;

use App\Models\Challenge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChallengeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Challenge::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            //
            "hero_instagram" => $this->faker->name,
            "hero_target" => $this->faker->word,
            "points" => $this->faker->numberBetween(0 , 500),
            "in_leader_board" => true,
            "is_challengVerified" => false,
            "user_id" => $this->faker->numberBetween(2 , 300),
            "created_at" =>  date("Y-m-d H:m:s"),
            "updated_at" => date("Y-m-d H:m:s"),
        ];
    }
}
