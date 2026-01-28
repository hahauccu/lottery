<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization): void {
            if ($organization->slug) {
                return;
            }

            $organization->slug = static::generateUniqueSlug($organization->name);
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name, '-', null);

        if ($base === '') {
            $base = 'org';
        }

        $slug = $base;
        $counter = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function employeeGroups(): HasMany
    {
        return $this->hasMany(EmployeeGroup::class);
    }

    public function lotteryEvents(): HasMany
    {
        return $this->hasMany(LotteryEvent::class);
    }
}
