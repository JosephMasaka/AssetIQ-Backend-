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

    protected $fillable = [
        'tenant_id',
        'username',
        'name',
        'email',
        'password',
        'avatar',
        'auth_provider',
        'is_active',
        'role',
        'role_id',
        'permissions',
        'phone',
        'job_title',
        'department',
        'google_id',
        'created_by'
    ];

    // ✅ Changed from 'api' to 'web' for session-based auth
    protected $guard_name = 'web';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ✅ Removed: getJWTIdentifier() — not needed for session auth
    // ✅ Removed: getJWTCustomClaims()  — not needed for session auth

    public function getCompany()
    {
        // ✅ Changed from auth('api') to auth() for session-based auth
        $user = auth()->user();

        if ($user->role === 'company') {
            $companyID = $user->id;
        } else {
            $companyID = $user->created_by;
        }

        return $companyID;
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