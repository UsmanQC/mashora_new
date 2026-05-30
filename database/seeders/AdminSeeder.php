<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Admin::where('email', "admin@gmail.com")->doesntExist()){
            Admin::create(['name' => "Admin",
                        'email' => "admin@gmail.com",
                        'password' => bcrypt('12345678')
                    ]);
        } else{
            Admin::where('email', 'admin@gmail.com')
                ->update(['password' => Hash::make('d0G*D^KVJLzy'), 'email' => "admin@mashora.co"]);
        }
    }
}
