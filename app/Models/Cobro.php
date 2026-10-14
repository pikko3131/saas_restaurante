<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class Cobro extends Model
{
    use BelongsToTenant;
    protected $fillable = ['restaurante_id','mesa_id','user_id','monto','metodo_pago','efectivo','tarjeta'];
    protected $casts = ['monto'=>'decimal:2','efectivo'=>'decimal:2','tarjeta'=>'decimal:2'];
}
