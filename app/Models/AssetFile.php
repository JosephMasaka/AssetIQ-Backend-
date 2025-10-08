<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetFile extends Model
{
    use HasFactory;
    
    protected $table = 'files';

    protected $fillable = [
        'asset_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_by',
        'company_id',
        'created_by',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
