<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Admin;
use App\Models\Owner;
use App\Models\Customer;
use App\Models\SalesAgent;
use Illuminate\Database\Seeder;
use App\Models\WarehouseManager;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'owner']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'warehouse']);
        Role::create(['name' => 'sales']);
        Role::create(['name' => 'customer']);
        
        // OWNER
        for ($i=1; $i <= 2; $i++) { 
            $userOwner = User::create([
                'email' => 'owner' . $i . '@app.id',
                'password' => bcrypt('owner123'),
            ]);
            $userOwner->assignRole('owner');

            Owner::create([
                'user_id' => $userOwner->id,
                'name' => 'Owner ' . $i,
                'phone' => '08123456789' . $i,
                'photo' => 'uploads/images/users/user-1.jpg',
                'address' => 'Jl. Owner No. ' . $i,
            ]);
        }

        // ADMIN
        for ($i = 1; $i <= 10; $i++) {
            $userAdmin = User::create([
                'email' => 'admin' . $i . '@app.id',
                'password' => bcrypt('admin123'),
            ]);
            $userAdmin->assignRole('admin');

            Admin::create([
                'user_id' => $userAdmin->id,
                'name' => 'Admin ' . $i,
                'phone' => '08123456789' . $i,
                'photo' => 'uploads/images/users/user-1.jpg',
                'address' => 'Jl. Admin No. ' . $i,
            ]);
        }

        // WAREHOUSE MANAGER
        for ($i = 1; $i <= 5; $i++) {
            $userWarehouse = User::create([
                'email' => 'warehouse'.$i.'@app.id',
                'password' => bcrypt('warehouse123'),
            ]);
            $userWarehouse->assignRole('warehouse');

            // WarehouseManager
            $warehouseManager = WarehouseManager::create([
                'user_id' => $userWarehouse->id,
                'name' => 'Warehouse Manager '. $i,
                'phone' => '081234567890',
                'photo' => 'uploads/images/users/user-1.jpg',
                'address' => 'Jl. Gudang No. '. $i,
            ]);
        }

        // SALES AGENT
        for ($i = 1; $i <= 5; $i++) {
            $userSales = User::create([
                'email' => 'sales'.$i.'@app.id',
                'password' => bcrypt('sales123'),
            ]);
            $userSales->assignRole('sales');

            // SalesAgent
            $salesAgent = SalesAgent::create([
                'user_id' => $userSales->id,
                'name' => 'Sales Agent '. $i,
                'phone' => '081234567894',
                'photo' => 'uploads/images/users/user-1.jpg',
                'address' => 'Jl. Sales No. '. $i,
            ]);
        }

        // CUSTOMER
        for ($i = 1; $i <= 5; $i++) {
            $userCustomer = User::create([
                'email' => 'customer' . $i . '@app.id',
                'password' => bcrypt('customer123'),
            ]);
            $userCustomer->assignRole('customer');

            // Customer
            $customer = Customer::create([
                'user_id' => $userCustomer->id,
                'name' => 'Customer ' . $i,
                'phone' => '081234567892',
                'photo' => 'uploads/images/users/user-1.jpg',
                'address' => 'Jl. Customer No. ' . $i,
            ]);
        }
    }
}
