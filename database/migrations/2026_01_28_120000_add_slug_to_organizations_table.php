<?php

use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        Organization::query()
            ->where(function ($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($organizations): void {
                foreach ($organizations as $organization) {
                    $organization->slug = Organization::generateUniqueSlug($organization->name);
                    $organization->save();
                }
            });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
