<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'display_name' => $this->faker->name,
            'social_id' => $this->faker->unique()->safeEmail,
            'password' => $this->faker->password(),
            'role' => "hero",
            'is_banned' => false,
            'created_at' =>  date("Y-m-d H:m:s"),
            'updated_at' => date("Y-m-d H:m:s"),
        ];
    }
}
