<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $table = 'vendors';

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'company_reg_number',
        'tax_id',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'fax',
        'street',
        'city',
        'state',
        'postal_code',
        'country',
        'region',
        'bank_name',
        'bank_account_number',
        'bank_swift_code',
        'bank_iban',
        'vendor_account_group',
        'reconciliation_account',
        'sort_key',
        'payment_terms',
        'payment_method',
        'credit_limit',
        'outstanding_balance',
        'status',
        'block_reason',
        'is_one_time_vendor',
        'is_approved',
        'approval_date',
        'created_by',
        'changed_by',
        'company_code',
        'company_id',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_one_time_vendor' => 'boolean',
        'is_approved' => 'boolean',
        'approval_date' => 'date',
        'created_on' => 'datetime',
        'changed_on' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'vendor_type' => 'supplier',
        'vendor_account_group' => 'creditor',
        'outstanding_balance' => 0,
        'is_one_time_vendor' => false,
        'is_approved' => false,
    ];

    // Relationships
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_vendor');
    }

    public function companyCodes(): HasMany
    {
        return $this->hasMany(VendorCompanyCode::class, 'vendor_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'vendor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function requisitions()
    {
        return $this->belongsToMany(Requisition::class, 'requisition_vendors', 'vendor_id', 'requisition_id')
            ->withPivot(['rfq_number', 'rfq_date', 'response_deadline', 'quoted_amount', 'status', 'remarks'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }

    public function scopeByCompanyCode($query, $companyCode)
    {
        return $query->where('company_code', $companyCode);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('vendor_code', 'like', "%{$search}%")
              ->orWhere('vendor_name', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company_reg_number', 'like', "%{$search}%")
              ->orWhere('tax_id', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    public function getFormattedCreditLimitAttribute(): ?string
    {
        return $this->credit_limit ? number_format($this->credit_limit, 2) : null;
    }

    public function getFormattedOutstandingBalanceAttribute(): string
    {
        return number_format($this->outstanding_balance, 2);
    }

    public function getIsCreditLimitExceededAttribute(): bool
    {
        if (!$this->credit_limit) return false;
        
        return $this->outstanding_balance > $this->credit_limit;
    }

    // Business Logic Methods
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isBlockedForPayment(): bool
    {
        return $this->status === 'blocked' && $this->block_reason === 'payment';
    }

    public function canExceedCreditLimit($amount): bool
    {
        if (!$this->credit_limit) return true;
        
        $newBalance = $this->outstanding_balance + $amount;
        return $newBalance <= $this->credit_limit;
    }

    public function updateOutstandingBalance($amount): void
    {
        $this->outstanding_balance += $amount;
        $this->save();
    }

    public function block(string $reason = 'other'): void
    {
        $this->update([
            'status' => 'blocked',
            'block_reason' => $reason,
            'changed_on' => now(),
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'status' => 'active',
            'block_reason' => null,
            'changed_on' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'approval_date' => now(),
            'changed_on' => now(),
        ]);
    }

    // Static Methods
    public static function generateVendorCode(): string
    {
        $latest = static::orderBy('vendor_id', 'desc')->first();
        $nextId = $latest ? $latest->vendor_id + 1 : 1;
        
        return 'V' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }
}