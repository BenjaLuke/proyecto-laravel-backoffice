<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function descendantIds(): array
    {
        // Recorremos el arbol hacia abajo sin recursividad para saber que
        // categorias no pueden usarse como padre de esta categoria.
        $ids = [];
        $pending = [$this->id];

        while (!empty($pending)) {
            $children = self::whereIn('parent_id', $pending)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $children);
            $pending = $children;
        }

        return $ids;
    }
}
