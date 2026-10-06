<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Categoria extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'categorias';

    protected $fillable = [
        'restaurante_id', 'nombre', 'descripcion', 'icono', 'color', 'activo', 'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
