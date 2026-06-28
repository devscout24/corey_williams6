<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhpposCategory extends Model
{
    protected $table = 'phppos_categories';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (PhpposCategory $category) {
            $category->slug = $category->buildSlug();
        });

        static::saved(function (PhpposCategory $category) {
            $category->cascadeSlugToChildren();
        });
    }

    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PhpposCategory::class, 'parent_id');
    }

    public function buildSlug(): string
    {
        $nameSlug = Str::slug($this->name);

        if ($this->parent_id === null) {
            return $nameSlug;
        }

        $parent = $this->parent()->with('parent')->first();

        if ($parent) {
            return rtrim($parent->slug, '/') . '/' . $nameSlug;
        }

        return $nameSlug;
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PhpposCategory::class, 'parent_id');
    }

    private function cascadeSlugToChildren(): void
    {
        foreach ($this->children()->get() as $child) {
            $child->save();
        }
    }
}
