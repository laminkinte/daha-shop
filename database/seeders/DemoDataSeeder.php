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
use App\Models\State;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
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
            'name' => 'MarketHub Admin',
            'email' => 'admin@markethub.ng',
            'phone' => '+2348010000001',
            'role' => UserRole::Admin,
            'password' => 'password',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $vendorUser1 = User::create([
            'name' => 'Chinedu Okafor',
            'email' => 'vendor1@markethub.ng',
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
            'email' => 'vendor2@markethub.ng',
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

        $agentUser = User::create([
            'name' => 'Ibrahim Musa',
            'email' => 'agent1@markethub.ng',
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
            'email' => 'customer1@markethub.ng',
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
            'email' => 'customer2@markethub.ng',
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

        $vendor1Products = [
            ['name' => 'Samsung Galaxy A15 128GB', 'category_id' => $phonesCategory->id, 'base_price' => 18500000, 'stock' => 25],
            ['name' => 'Infinix Hot 40 Pro', 'category_id' => $phonesCategory->id, 'base_price' => 12500000, 'stock' => 40],
            ['name' => 'LG 43" Smart TV', 'category_id' => $tvCategory->id, 'base_price' => 22000000, 'stock' => 10],
        ];

        foreach ($vendor1Products as $product) {
            Product::create([
                'vendor_id' => $vendor1->id,
                'category_id' => $product['category_id'],
                'name' => $product['name'],
                'slug' => Str::slug($product['name']).'-'.Str::random(5),
                'description' => $product['name'].' — brand new, sealed pack, 1 year warranty.',
                'base_price' => $product['base_price'],
                'stock' => $product['stock'],
                'status' => 'published',
            ]);
        }

        $vendor2Products = [
            ['name' => "Men's Ankara Native Wear", 'category_id' => $menCategory->id, 'base_price' => 1500000, 'stock' => 30],
            ['name' => 'Leather Oxford Shoes', 'category_id' => $shoesCategory->id, 'base_price' => 2200000, 'stock' => 20],
        ];

        foreach ($vendor2Products as $product) {
            Product::create([
                'vendor_id' => $vendor2->id,
                'category_id' => $product['category_id'],
                'name' => $product['name'],
                'slug' => Str::slug($product['name']).'-'.Str::random(5),
                'description' => $product['name'].' — quality fabric, true to size.',
                'base_price' => $product['base_price'],
                'stock' => $product['stock'],
                'status' => 'published',
            ]);
        }
    }
}
