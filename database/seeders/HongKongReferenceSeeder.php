<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Reference data for Hong Kong localization (YH Education).
 *
 * Idempotent and production-safe: inserts a Hong Kong country/state/city row
 * only when missing, never touches existing rows, and seeds NO demo users.
 * Run standalone: php artisan db:seed --class=HongKongReferenceSeeder
 */
class HongKongReferenceSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        $country = DB::table('countries')->where('name', 'Hong Kong')->first();
        if (! $country) {
            $id = DB::table('countries')->insertGetId([
                'name'       => 'Hong Kong',
                'short_name' => 'HK',
                'iso_code'   => 'HKG',
                'tel_prefix' => '+852',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $country = DB::table('countries')->where('id', $id)->first();
        }

        $state = DB::table('states')
            ->where('country_id', $country->id)
            ->where('name', 'Hong Kong')
            ->first();
        if (! $state) {
            $id = DB::table('states')->insertGetId([
                'country_id' => $country->id,
                'name'       => 'Hong Kong',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $state = DB::table('states')->where('id', $id)->first();
        }

        if (! DB::table('cities')
            ->where('state_id', $state->id)
            ->where('name', 'Hong Kong')
            ->exists()) {
            DB::table('cities')->insert([
                'country_id' => $country->id,
                'state_id'   => $state->id,
                'name'       => 'Hong Kong',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('Hong Kong reference rows present (country/state/city).');
    }
}
