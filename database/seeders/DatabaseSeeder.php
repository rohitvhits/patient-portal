<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Patient accounts are created via OTP login (App\Http\Controllers\Auth\AuthController),
        // not seeded — there is nothing to seed here.
    }
}
