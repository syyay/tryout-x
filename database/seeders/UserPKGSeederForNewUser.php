<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TryoutPKG;

class UserPKGSeederForNewUser extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::latest()->first();
        $to = TryoutPKG::take(5)->get();
        foreach ($to as $t) {
            \App\Models\UserPKG::factory(1)
                ->withIDs($user->user_id, $t->tryout_id)
                ->create();
        }
    }
}
