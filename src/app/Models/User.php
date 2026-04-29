<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'is_admin',
        'permissions',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public static function defaultPermissions(): array
    {
        return [
            'categories_view' => false,
            'categories_manage' => false,
            'categories_delete' => false,

            'products_view' => false,
            'products_manage' => false,
            'products_delete' => false,

            'calendar_view' => false,
            'calendar_manage' => false,
            'calendar_delete' => false,

            'activity_view' => false,
            'users_manage' => false,
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function getPermissions(): array
    {
        // Mezclamos los permisos guardados con la lista por defecto para que
        // cualquier permiso nuevo exista siempre como false en usuarios antiguos.
        return array_merge(
            self::defaultPermissions(),
            $this->permissions ?? []
        );
    }

    public function hasPermission(string $permission): bool
    {
        // El administrador tiene acceso completo. El resto depende del array
        // permissions, que tambien alimenta las abilities de los tokens API.
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = $this->getPermissions();

        return (bool) ($permissions[$permission] ?? false);
    }
}
