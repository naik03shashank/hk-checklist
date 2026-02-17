<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Essential: Setup roles and permissions (Spatie)
        if (class_exists(\Database\Seeders\SetupRolesAndPermissionsSeeder::class)) {
            $this->call(SetupRolesAndPermissionsSeeder::class);
        }

        /* 
        |--------------------------------------------------------------------------
        | DEMO / DEVELOPMENT DATA
        |--------------------------------------------------------------------------
        | The following seeders add sample users (admin@example.com, etc.) 
        | and properties for testing. Commented out for final delivery as 
        | per client request since they already have their own data.
        */
        
        // $this->call(DemoUsersSeeder::class);
        // $this->call(RoomSeeder::class);
        // $this->call(TaskSeeder::class);
        // $this->call(BulkDemoDataSeeder::class);
        // $this->call(SpecificHousekeeperSeeder::class);
    }
}
