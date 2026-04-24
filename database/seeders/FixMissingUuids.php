<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixMissingUuids extends Seeder
{
    public function run(): void
    {
        $tables = [
            'users', 'drugs', 'drug_units', 'categories', 'depots',
            'transfers', 'stock_requests', 'sales', 'roles', 'permissions'
        ];

        foreach ($tables as $table) {
            $count = DB::table($table)->whereNull('uuid')->count();
            if ($count > 0) {
                DB::table($table)->whereNull('uuid')->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                    }
                });
                $this->command->info("Fixed $count UUIDs in table: $table");
            }
        }
    }
}
