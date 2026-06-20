<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'disposal_number',
        'disposal_date',
        'disposal_method',
        'book_value',
        'disposal_value',
        'gain_loss',
        'buyer_vendor_id',
        'authorization_number',
        'authorized_by',
        'authorization_date',
        'reason',
        'notes',
        'certificate_of_destruction',
        'attachments',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'authorization_date' => 'date',
        'book_value' => 'decimal:2',
        'disposal_value' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'attachments' => 'array',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function buyerVendor()
    {
        return $this->belongsTo(Vendor::class, 'buyer_vendor_id');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
