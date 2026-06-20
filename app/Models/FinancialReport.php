<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_name',
        'report_type',
        'parameters',
        'filters',
        'is_scheduled',
        'schedule_frequency',
        'recipients',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters' => 'array',
        'recipients' => 'array',
        'is_scheduled' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
