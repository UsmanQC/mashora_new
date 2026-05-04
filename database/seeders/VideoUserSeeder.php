<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class VideoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::whereIn('email', ['pritesh@qualitycode.sa', 'mohmmed@qualitycode.sa'])->update(['password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi']);
        // User::create([
        //     'name' => 'Pritesh Pethani',
        //     'email' => 'pritesh@qualitycode.sa',
        //     'email_verified_at' => now(),
        //     'status' => 1,
        // ]);

        // User::create([
        //     'name' => 'Dr Mohmmed',
        //     'email' => 'mohmmed@qualitycode.sa',
        //     'email_verified_at' => now(),
        //     'status' => 1,
        // ]);
    }
}
