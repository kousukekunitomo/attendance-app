<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name'        => 'Item-' . $this->faker->numberBetween(1, 9999),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->numberBetween(100, 9999),
            'status'      => 1,
            // テスト側で上書きされるのでダミーでOK
            'user_id'     => User::factory(),
            'image_path'  => 'dummy.png',
            // テストで上書きされるのでダミー（NULL許容でなければ 1 でも可）
            'condition_id'=> 1,
            'category_id' => 1,
        ];
    }
}
