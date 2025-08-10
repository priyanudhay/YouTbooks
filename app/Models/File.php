<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'original_name',
        'filename',
        'path',
        'mime_type',
        'size',
        'type',
        'is_processed',
        'is_public',
        'metadata',
    ];

    protected $casts = [
        'is_processed' => 'boolean',
        'is_public' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'path', // Hide actual file path for security
    ];

    protected $appends = [
        'formatted_size',
        'download_url',
    ];

    /**
     * File belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * File may belong to an order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->size);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('api.files.download', $this->id);
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
            'text/plain',
            'text/rtf',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    /**
     * Check if file is an archive
     */
    public function isArchive(): bool
    {
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ];

        return in_array($this->mime_type, $archiveTypes);
    }

    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon based on type
     */
    public function getIcon(): string
    {
        if ($this->isImage()) {
            return 'image';
        }

        if ($this->isDocument()) {
            return 'document-text';
        }

        if ($this->isArchive()) {
            return 'archive';
        }

        switch ($this->mime_type) {
            case 'application/pdf':
                return 'document-text';
            case 'application/msword':
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'document';
            default:
                return 'document';
        }
    }

    /**
     * Scope for files of specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for processed files
     */
    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    /**
     * Scope for public files
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for files by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for files by order
     */
    public function scopeByOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get metadata value
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set metadata value
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Check if file can be previewed
     */
    public function canPreview(): bool
    {
        $previewableTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
        ];

        return in_array($this->mime_type, $previewableTypes);
    }

    /**
     * Get file type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'document' => 'Document',
            'image' => 'Image',
            'archive' => 'Archive',
            'manuscript' => 'Manuscript',
            'cover' => 'Book Cover',
            'illustration' => 'Illustration',
            default => 'File',
        };
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values
        static::creating(function ($file) {
            if (empty($file->type)) {
                $file->type = 'document';
            }
        });
    }
}
