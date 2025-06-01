<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Electronics
            ['name' => 'Laptop',     'category' => 'electronics', 'description' => 'High-performance laptop for professionals.'],
            ['name' => 'Phone',      'category' => 'electronics', 'description' => 'Smartphone with large display and fast processor.'],
            ['name' => 'Headphones', 'category' => 'electronics', 'description' => 'Wireless headphones with noise cancellation.'],
            ['name' => 'Tablet',     'category' => 'electronics', 'description' => 'Portable tablet with vibrant display.'],
            ['name' => 'Camera',     'category' => 'electronics', 'description' => 'Digital camera with 4K video support.'],
            ['name' => 'Smartwatch', 'category' => 'electronics', 'description' => 'Wearable device for tracking health.'],

            // Clothing
            ['name' => 'T-Shirt',    'category' => 'clothing', 'description' => 'Comfortable cotton t-shirt in various sizes.'],
            ['name' => 'Shoes',      'category' => 'clothing', 'description' => 'Durable running shoes for all terrains.'],
            ['name' => 'Jeans',      'category' => 'clothing', 'description' => 'Classic blue denim jeans.'],
            ['name' => 'Jacket',     'category' => 'clothing', 'description' => 'Waterproof winter jacket.'],
            ['name' => 'Socks',      'category' => 'clothing', 'description' => 'Pack of cotton socks.'],
            ['name' => 'Hat',        'category' => 'clothing', 'description' => 'Stylish baseball cap.'],

            // Books
            ['name' => 'Book',       'category' => 'books', 'description' => 'Bestselling novel with thrilling storyline.'],
            ['name' => 'Cookbook',   'category' => 'books', 'description' => 'Delicious recipes from around the world.'],
            ['name' => 'Textbook',   'category' => 'books', 'description' => 'Educational material for university students.'],
            ['name' => 'Notebook',   'category' => 'books', 'description' => 'Lined notebook for writing.'],
            ['name' => 'Planner',    'category' => 'books', 'description' => 'Daily planner to stay organized.'],
            ['name' => 'Comic Book', 'category' => 'books', 'description' => 'Popular superhero comic.'],

            // Furniture
            ['name' => 'Desk',       'category' => 'furniture', 'description' => 'Wooden desk with drawers.'],
            ['name' => 'Chair',      'category' => 'furniture', 'description' => 'Ergonomic office chair.'],
            ['name' => 'Bookshelf',  'category' => 'furniture', 'description' => 'Tall bookshelf with 5 levels.'],
            ['name' => 'Sofa',       'category' => 'furniture', 'description' => 'Comfortable two-seat sofa.'],
            ['name' => 'Bed Frame',  'category' => 'furniture', 'description' => 'Queen size metal bed frame.'],
            ['name' => 'Table',      'category' => 'furniture', 'description' => 'Dining table for six.'],

            // Accessories
            ['name' => 'Watch',      'category' => 'accessories', 'description' => 'Analog wristwatch with leather band.'],
            ['name' => 'Backpack',   'category' => 'accessories', 'description' => 'Water-resistant travel backpack.'],
            ['name' => 'Sunglasses', 'category' => 'accessories', 'description' => 'UV-protected stylish sunglasses.'],
            ['name' => 'Belt',       'category' => 'accessories', 'description' => 'Leather belt for formal wear.'],
            ['name' => 'Scarf',      'category' => 'accessories', 'description' => 'Wool scarf for winter.'],
            ['name' => 'Wallet',     'category' => 'accessories', 'description' => 'Compact leather wallet.'],
        ];

        foreach ($products as $product) {
            $category = Category::where('name', $product['category'])->first();

            if ($category) {
                Product::create([
                    'name' => $product['name'],
                    'category_id' => $category->id,
                    'description' => $product['description'],
                ]);
            }
        }
    }
}

