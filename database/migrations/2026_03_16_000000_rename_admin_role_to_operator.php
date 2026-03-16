<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delete the old 'operator' role (and its permissions/assignments)
        $oldOperatorRole = DB::table('roles')->where('name', 'operator')->first();

        if ($oldOperatorRole) {
            DB::table('model_has_roles')->where('role_id', $oldOperatorRole->id)->delete();
            DB::table('role_has_permissions')->where('role_id', $oldOperatorRole->id)->delete();
            DB::table('roles')->where('id', $oldOperatorRole->id)->delete();
        }

        // Rename the 'admin' role to 'operator'
        DB::table('roles')->where('name', 'admin')->update(['name' => 'operator']);
    }

    public function down(): void
    {
        // Rename 'operator' back to 'admin'
        DB::table('roles')->where('name', 'operator')->update(['name' => 'admin']);

        // Re-create the old 'operator' role
        DB::table('roles')->insert([
            'name'       => 'operator',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
