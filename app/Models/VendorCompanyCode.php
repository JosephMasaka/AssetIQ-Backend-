<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCompanyCode extends Model
{
    use HasFactory;

    protected $table = 'vendor_company_codes'; // ensure correct table name if different

    protected $primaryKey = 'id'; // or whatever your PK is
    public $incrementing = true;

    protected $fillable = [
        'vendor_id',
        'company_code',
        'purchasing_organization',
        'reconciliation_account',
        'payment_terms',
        'is_blocked',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
