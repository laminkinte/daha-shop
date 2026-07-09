<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            'Phones & Tablets' => ['Smartphones', 'Tablets', 'Phone Accessories'],
            'Electronics' => ['TVs', 'Home Audio', 'Generators & Power'],
            'Fashion' => ["Men's Clothing", "Women's Clothing", 'Shoes', 'Bags'],
            'Home & Kitchen' => ['Furniture', 'Kitchenware', 'Home Decor'],
            'Groceries' => ['Foodstuff', 'Beverages', 'Household Essentials'],
            'Health & Beauty' => ['Skincare', 'Haircare', 'Personal Care'],
            'Baby Products' => ['Diapers & Wipes', 'Baby Gear', 'Toys'],
        ];

        foreach ($tree as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($parentName.'-'.$childName),
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
