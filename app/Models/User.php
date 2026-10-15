<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'restaurante_id', 'name', 'email', 'password', 'role', 'permisos', 'telefono', 'avatar', 'activo',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'permisos' => 'array',
        ];
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    public function tienePermiso(string $permiso): bool
    {
        if ($this->role === 'superadmin' || $this->role === 'admin') {
            return true;
        }

        if (! empty($this->permisos) && is_array($this->permisos)) {
            return in_array($permiso, $this->permisos, true);
        }

        return match ($this->role) {
            'cajero' => in_array($permiso, ['caja', 'pos'], true),
            'mesero' => in_array($permiso, ['mesero'], true),
            'cocina' => in_array($permiso, ['cocina'], true),
            default  => false,
        };
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        if (in_array($this->role, $roles, true)) {
            return true;
        }

        if ($this->role === 'admin' || $this->role === 'superadmin') {
            return true;
        }

        foreach ($roles as $r) {
            if ($this->tienePermiso($r)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function getInicialesAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $ini = collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
        return mb_strtoupper($ini ?: 'U');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'superadmin' => 'Super Admin',
            'admin'  => 'Administrador',
            'cajero' => 'Cajero',
            'mesero' => 'Mesero',
            'cocina' => 'Cocina',
            default  => ucfirst($this->role),
        };
    }
}
