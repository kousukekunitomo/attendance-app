<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run()
    {
        $items = [
            [
                'user_id' => 1,
                'name' => '腕時計',
                'brand_name' => 'Rolax',
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'category_id' => 1,
                'price' => 15000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg',
                'condition_id' => 3,
            ],
            [
                'user_id' => 2,
                'name' => 'HDD',
                'brand_name' => '西芝',
                'description' => '高速で信頼性の高いハードディスク',
                'category_id' => 1,
                'price' => 5000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg',
                'condition_id' => 3,
            ],
            [
                'user_id' => 3,
                'name' => '玉ねぎ3束',
                'brand_name' => null,
                'description' => '新鮮な玉ねぎ3束のセット',
                'category_id' => 2,
                'price' => 300,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg',
                'condition_id' => 2,
            ],
            [
                'user_id' => 4,
                'name' => '革靴',
                'brand_name' => null,
                'description' => 'クラシックなデザインの革靴',
                'category_id' => 3,
                'price' => 4000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg',
                'condition_id' => 1,
            ],
            [
                'user_id' => 5,
                'name' => 'ノートPC',
                'brand_name' => 'Lenovo',
                'description' => '高性能なノートパソコン',
                'category_id' => 1,
                'price' => 45000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg',
                'condition_id' => 2,
            ],
            [
                'user_id' => 6,
                'name' => 'マイク',
                'brand_name' => 'AudioTech',
                'description' => '高音質のレコーディング用マイク',
                'category_id' => 4,
                'price' => 8000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg',
                'condition_id' => 3,
            ],
            [
                'user_id' => 7,
                'name' => 'ショルダーバッグ',
                'brand_name' => null,
                'description' => 'おしゃれなショルダーバッグ',
                'category_id' => 3,
                'price' => 3500,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+packet.jpg',
                'condition_id' => 2,
            ],
            [
                'user_id' => 8,
                'name' => 'タンブラー',
                'brand_name' => null,
                'description' => '使いやすいタンブラー',
                'category_id' => 5,
                'price' => 500,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg',
                'condition_id' => 1,
            ],
            [
                'user_id' => 9,
                'name' => 'コーヒーミル',
                'brand_name' => 'Starbacks',
                'description' => '手動のコーヒーミル',
                'category_id' => 4,
                'price' => 4000,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg',
                'condition_id' => 2,
            ],
            [
                'user_id' => 10,
                'name' => 'メイクセット',
                'brand_name' => null,
                'description' => '便利なメイクアップセット',
                'category_id' => 5,
                'price' => 2500,
                'status' => 1,
                'image_path' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E8%A6%B3%E3%81%AE%E3%82%A4%E3%83%A1%E3%83%BC%E3%82%B8.jpg',
                'condition_id' => 3,
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
