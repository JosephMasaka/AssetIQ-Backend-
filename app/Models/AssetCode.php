<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCode extends Model
{
    protected $fillable = [
        'asset_id', 
        'code_type', //QR, RFID, Barcode etc
        'code_value',
        'company_id',
        'created_by',
    ];

    public function asset() {
        return $this->belongsTo(Asset::class);
    }
}
