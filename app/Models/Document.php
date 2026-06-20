<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_number',
        'title',
        'description',
        'document_type',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'documentable_type',
        'documentable_id',
        'category',
        'tags',
        'issue_date',
        'expiry_date',
        'status',
        'is_confidential',
        'access_permissions',
        'version',
        'parent_document_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'access_permissions' => 'array',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_confidential' => 'boolean',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function parentDocument()
    {
        return $this->belongsTo(Document::class, 'parent_document_id');
    }

    public function versions()
    {
        return $this->hasMany(Document::class, 'parent_document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
