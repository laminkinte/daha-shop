<?php

namespace Database\Seeders;

use App\Enums\AgentAvailability;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\Address;
use App\Models\Category;
use App\Models\DeliveryAgent;
use App\Models\DeliveryFee;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // These accounts have fake but real-looking @dahashop.ng addresses -
        // if this ever ran somewhere with live SMTP configured (a staging
        // box mirroring production config, say), every order/vendor
        // notification email this app sends would genuinely attempt
        // delivery to those non-existent mailboxes. Demo data belongs in
        // local/testing only.
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('DemoDataSeeder creates fake accounts with placeholder emails and must not run outside local/testing.');
        }

        $lagos = State::where('name', 'Lagos')->firstOrFail();
        $ikeja = $lagos->lgas()->where('name', 'Ikeja')->firstOrFail();
        $surulere = $lagos->lgas()->where('name', 'Surulere')->firstOrFail();
        $fct = State::where('name', 'FCT')->firstOrFail();
        $amac = $fct->lgas()->where('name', 'Abuja Municipal')->firstOrFail();

        foreach ([$ikeja, $surulere, $amac] as $lga) {
            $zone = DeliveryZone::create([
                'name' => $lga->name.' Zone',
                'state_id' => $lga->state_id,
                'lga_id' => $lga->id,
            ]);

            DeliveryFee::create([
                'delivery_zone_id' => $zone->id,
                'vendor_id' => null,
                'fee' => 150000,
            ]);
        }

        $admin = User::create([
            'name' => 'Daha Shop Admin',
            'email' => 'admin@dahashop.ng',
            'phone' => '+2348010000001',
            'role' => UserRole::Admin,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendorUser1 = User::create([
            'name' => 'Chinedu Okafor',
            'email' => 'vendor1@dahashop.ng',
            'phone' => '+2348010000002',
            'role' => UserRole::Vendor,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendor1 = Vendor::create([
            'user_id' => $vendorUser1->id,
            'business_name' => 'TechHub Electronics',
            'slug' => 'techhub-electronics',
            'business_phone' => '+2348010000002',
            'business_address' => '12 Allen Avenue, Ikeja',
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ]);

        $vendorUser2 = User::create([
            'name' => "Ada's Fashion House",
            'email' => 'vendor2@dahashop.ng',
            'phone' => '+2348010000003',
            'role' => UserRole::Vendor,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendor2 = Vendor::create([
            'user_id' => $vendorUser2->id,
            'business_name' => "Ada's Fashion House",
            'slug' => 'adas-fashion-house',
            'business_phone' => '+2348010000003',
            'business_address' => '5 Adeniran Ogunsanya St, Surulere',
            'state_id' => $lagos->id,
            'lga_id' => $surulere->id,
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ]);

        $vendorUser3 = User::create([
            'name' => 'Emeka Nwosu',
            'email' => 'vendor3@dahashop.ng',
            'phone' => '+2348010000007',
            'role' => UserRole::Vendor,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendor3 = Vendor::create([
            'user_id' => $vendorUser3->id,
            'business_name' => 'MegaMart General Store',
            'slug' => 'megamart-general-store',
            'business_phone' => '+2348010000007',
            'business_address' => '18 Herbert Macaulay Way, Abuja Municipal',
            'state_id' => $fct->id,
            'lga_id' => $amac->id,
            'status' => VendorStatus::Approved,
            'approved_at' => now(),
        ]);

        $agentUser = User::create([
            'name' => 'Ibrahim Musa',
            'email' => 'agent1@dahashop.ng',
            'phone' => '+2348010000004',
            'role' => UserRole::Agent,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        DeliveryAgent::create([
            'user_id' => $agentUser->id,
            'state_id' => $lagos->id,
            'lga_id' => $ikeja->id,
            'vehicle_type' => 'motorcycle',
            'availability' => AgentAvailability::Available,
        ]);

        $customer1 = User::create([
            'name' => 'Bisi Adewale',
            'email' => 'customer1@dahashop.ng',
            'phone' => '+2348010000005',
            'role' => UserRole::Customer,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        Address::create([
            'user_id' => $customer1->id,
            'state_id' => $lagos->id,
            'lga_id' => $surulere->id,
            'label' => 'Home',
            'area' => 'Surulere',
            'street_address' => '22 Bode Thomas Street',
            'phone' => '+2348010000005',
            'is_default' => true,
        ]);

        User::create([
            'name' => 'Tunde Bakare',
            'email' => 'customer2@dahashop.ng',
            'phone' => '+2348010000006',
            'role' => UserRole::Customer,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $phonesCategory = Category::where('slug', 'phones-tablets-smartphones')->firstOrFail();
        $tvCategory = Category::where('slug', 'electronics-tvs')->firstOrFail();
        $menCategory = Category::where('slug', 'fashion-mens-clothing')->firstOrFail();
        $shoesCategory = Category::where('slug', 'fashion-shoes')->firstOrFail();
        $laptopsCategory = Category::where('slug', 'computing-laptops')->firstOrFail();
        $gamingConsolesCategory = Category::where('slug', 'gaming-gaming-consoles')->firstOrFail();
        $teamSportsCategory = Category::where('slug', 'sports-outdoors-team-sports')->firstOrFail();
        $booksCategory = Category::where('slug', 'books-stationery-books')->firstOrFail();
        $carAccessoriesCategory = Category::where('slug', 'automotive-car-accessories')->firstOrFail();

        $vendor1Products = [
            ['name' => 'Samsung Galaxy A15 128GB', 'category_id' => $phonesCategory->id, 'base_price' => 18500000, 'stock' => 25, 'image' => 'samsung-galaxy.jpg'],
            ['name' => 'Infinix Hot 40 Pro', 'category_id' => $phonesCategory->id, 'base_price' => 12500000, 'stock' => 40, 'image' => 'infinix-phone.jpg'],
            ['name' => 'LG 43" Smart TV', 'category_id' => $tvCategory->id, 'base_price' => 22000000, 'stock' => 10, 'image' => 'lg-tv.jpg'],
        ];

        foreach ($vendor1Products as $product) {
            $this->createProduct($vendor1->id, $product, 'brand new, sealed pack, 1 year warranty.');
        }

        $vendor2Products = [
            ['name' => "Men's Ankara Native Wear", 'category_id' => $menCategory->id, 'base_price' => 1500000, 'stock' => 30, 'image' => 'ankara-wear.jpg'],
            ['name' => 'Leather Oxford Shoes', 'category_id' => $shoesCategory->id, 'base_price' => 2200000, 'stock' => 20, 'image' => 'oxford-shoes.jpg'],
        ];

        foreach ($vendor2Products as $product) {
            $this->createProduct($vendor2->id, $product, 'quality fabric, true to size.');
        }

        $vendor3Products = [
            ['name' => 'HP Pavilion 15" Laptop', 'category_id' => $laptopsCategory->id, 'base_price' => 35000000, 'stock' => 8, 'image' => 'laptop.jpg'],
            ['name' => 'PlayStation 5 Console', 'category_id' => $gamingConsolesCategory->id, 'base_price' => 65000000, 'stock' => 5, 'image' => 'gaming-console.jpg'],
            ['name' => 'Official Size 5 Football', 'category_id' => $teamSportsCategory->id, 'base_price' => 850000, 'stock' => 50, 'image' => 'football.jpg'],
            ['name' => 'Things Fall Apart (Novel)', 'category_id' => $booksCategory->id, 'base_price' => 450000, 'stock' => 60, 'image' => 'novel-book.jpg'],
            ['name' => 'Car Phone Mount Holder', 'category_id' => $carAccessoriesCategory->id, 'base_price' => 350000, 'stock' => 45, 'image' => 'car-accessory.jpg'],
        ];

        foreach ($vendor3Products as $product) {
            $this->createProduct($vendor3->id, $product, 'quality guaranteed, ships fast.');
        }
    }

    private function createProduct(int $vendorId, array $data, string $descriptionSuffix): void
    {
        $product = Product::create([
            'vendor_id' => $vendorId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(5),
            'description' => $data['name'].' — '.$descriptionSuffix,
            'base_price' => $data['base_price'],
            'stock' => $data['stock'],
            'status' => 'published',
        ]);

        $sourcePath = database_path('seeders/images/products/'.$data['image']);

        if (! file_exists($sourcePath)) {
            return;
        }

        $storedPath = 'products/'.Str::random(20).'.jpg';
        Storage::disk('public')->put($storedPath, file_get_contents($sourcePath));

        ProductImage::create([
            'product_id' => $product->id,
            'path' => $storedPath,
        ]);
    }
}
