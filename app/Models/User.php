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
        'tenant_id', 'username', 'name', 'email', 'password',
        'avatar', 'auth_provider', 'is_active', 'role', 'role_id',
        'phone', 'job_title', 'department',
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

    // ✅ Override to prevent null return crashing Spatie's HasRoles trait
    public function getDirectPermissions(): \Illuminate\Support\Collection
    {
        return parent::getDirectPermissions() ?? collect();
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