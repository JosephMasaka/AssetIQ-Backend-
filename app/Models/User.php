<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Plan;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // ✅ Do NOT declare $guard_name here.
    // Spatie will auto-detect it from config/auth.php.
    // Hardcoding it causes a mismatch when auth:sanctum is the active guard.

    protected string $guard_name = 'web';

    protected $fillable = [
        'tenant_id', 'username', 'name', 'email', 'password',
        'avatar', 'auth_provider', 'is_active', 'role', 'role_id',
        'permissions', 'phone', 'job_title', 'department',
        'google_id', 'created_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getCompany()
    {
        $user = auth()->user();
        return $user->role === 'company' ? $user->id : $user->created_by;
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function hasModule($key)
    {
        if (!$this->plan) return false;
        return $this->plan->modules()->where('key', $key)->exists();
    }
}