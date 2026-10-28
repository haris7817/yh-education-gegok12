<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * YH Education (Hong Kong) goodwill improvements.
 *
 * Adds two optional, backward-compatible columns to userprofiles:
 *  - school_name: the student's day school (YH Education is a tutorial centre)
 *  - grade:       the student's grade at that day school
 *
 * Rollback: php artisan migrate:rollback --step=1 (drops only these two columns).
 * No existing columns or data are touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('userprofiles', function (Blueprint $table) {
            if (! Schema::hasColumn('userprofiles', 'school_name')) {
                $table->string('school_name', 150)->nullable()->after('mother_tongue');
            }
            if (! Schema::hasColumn('userprofiles', 'grade')) {
                $table->string('grade', 50)->nullable()->after('school_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('userprofiles', function (Blueprint $table) {
            if (Schema::hasColumn('userprofiles', 'grade')) {
                $table->dropColumn('grade');
            }
            if (Schema::hasColumn('userprofiles', 'school_name')) {
                $table->dropColumn('school_name');
            }
        });
    }
};
