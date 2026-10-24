<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $table = 'categories';

    public $timestamps = false;

    public static function options(): array
    {
        return DB::table('categories')
            ->orderByDesc('type')
            ->orderBy('id')
            ->get()
            ->map(fn ($category) => (array) $category)
            ->all();
    }
}
