<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class FileService
{
    /**
     * Allowed file types for uploads
     */
    const ALLOWED_TYPES = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'text/plain',
        'text/rtf',
        
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ];

    /**
     * Maximum file size in bytes (50MB)
     */
    const MAX_FILE_SIZE = 52428800;

    /**
     * Upload a file for a user
     *
     * @param UploadedFile $file
     * @param User $user
     * @param Order|null $order
     * @param string $type
     * @param array $metadata
     * @return File
     * @throws Exception
     */
    public function uploadFile(
        UploadedFile $file,
        User $user,
        ?Order $order = null,
        string $type = 'document',
        array $metadata = []
    ): File {
        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = $this->generateUniqueFilename($originalName, $extension);

        // Determine storage path
        $storagePath = $this->getStoragePath($user, $type);
        $fullPath = $storagePath . '/' . $filename;

        try {
            // Store file
            $path = $file->storeAs($storagePath, $filename, 'private');

            // Create file record
            $fileRecord = File::create([
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'original_name' => $originalName,
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'type' => $type,
                'is_processed' => false,
                'is_public' => false,
                'metadata' => array_merge($metadata, [
                    'uploaded_at' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
            ]);

            // Log successful upload
            Log::info('File uploaded successfully', [
                'file_id' => $fileRecord->id,
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);

            // Queue virus scan if enabled
            if (config('filesystems.virus_scan_enabled', false)) {
                $this->queueVirusScan($fileRecord);
            }

            return $fileRecord;

        } catch (Exception $e) {
            Log::error('File upload failed', [
                'user_id' => $user->id,
                'filename' => $originalName,
                'error' => $e->getMessage()
            ]);

            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload');
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $maxSizeMB = self::MAX_FILE_SIZE / 1024 / 1024;
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_TYPES)) {
            throw new Exception('File type not allowed: ' . $mimeType);
        }

        // Additional security checks
        $this->performSecurityChecks($file);
    }

    /**
     * Perform additional security checks on file
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    private function performSecurityChecks(UploadedFile $file): void
    {
        $filename = $file->getClientOriginalName();
        
        // Check for dangerous file extensions
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new Exception('File extension not allowed for security reasons');
        }

        // Check for null bytes in filename
        if (strpos($filename, "\0") !== false) {
            throw new Exception('Invalid filename detected');
        }

        // Check filename length
        if (strlen($filename) > 255) {
            throw new Exception('Filename too long');
        }
    }

    /**
     * Generate unique filename
     *
     * @param string $originalName
     * @param string $extension
     * @return string
     */
    private function generateUniqueFilename(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($baseName);
        $uniqueId = Str::random(8);
        
        return $safeName . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Get storage path for file
     *
     * @param User $user
     * @param string $type
     * @return string
     */
    private function getStoragePath(User $user, string $type): string
    {
        $year = date('Y');
        $month = date('m');
        
        return "uploads/{$type}/{$year}/{$month}/{$user->id}";
    }

    /**
     * Queue virus scan for file
     *
     * @param File $file
     */
    private function queueVirusScan(File $file): void
    {
        // This would typically dispatch a job to scan the file
        // For now, we'll just log it
        Log::info('Virus scan queued for file', ['file_id' => $file->id]);
    }

    /**
     * Download a file
     *
     * @param File $file
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws Exception
     */
    public function downloadFile(File $file, User $user)
    {
        // Check permissions
        if (!$this->canUserAccessFile($file, $user)) {
            throw new Exception('Access denied');
        }

        // Check if file exists
        if (!Storage::disk('private')->exists($file->path)) {
            throw new Exception('File not found');
        }

        // Log download
        Log::info('File downloaded', [
            'file_id' => $file->id,
            'user_id' => $user->id,
            'filename' => $file->original_name
        ]);

        return Storage::disk('private')->download($file->path, $file->original_name);
    }

    /**
     * Check if user can access file
     *
     * @param File $file
     * @param User $user
     * @return bool
     */
    public function canUserAccessFile(File $file, User $user): bool
    {
        // File owner can always access
        if ($file->user_id === $user->id) {
            return true;
        }

        // Admin can access all files
        if ($user->isAdmin()) {
            return true;
        }

        // Editor can access files for orders they're assigned to
        if ($user->isEditor() && $file->order_id) {
            return $file->order->assigned_editor_id === $user->id;
        }

        // Public files can be accessed by anyone
        if ($file->is_public) {
            return true;
        }

        return false;
    }

    /**
     * Delete a file
     *
     * @param File $file
     * @param User $user
     * @throws Exception
     */
    public function deleteFile(File $file, User $user): void
    {
        // Check permissions
        if (!$this->canUserDeleteFile($file, $user)) {
            throw new Exception('Access denied');
        }

        try {
            // Delete physical file
            if (Storage::disk('private')->exists($file->path)) {
                Storage::disk('private')->delete($file->path);
            }

            // Delete database record
            $file->delete();

            Log::info('File deleted', [
                'file_id' => $file->id,
                'user_id' => $user->id,
                'filename' => $file->original_name
            ]);

        } catch (Exception $e) {
            Log::error('File deletion failed', [
                'file_id' => $file->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception('File deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can delete file
     *
     * @param File $file
     * @param User $user
     * @return bool
     */
    private function canUserDeleteFile(File $file, User $user): bool
    {
        // File owner can delete
        if ($file->user_id === $user->id) {
            return true;
        }

        // Admin can delete any file
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Get user's files with pagination
     *
     * @param User $user
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUserFiles(User $user, array $filters = [], int $perPage = 15)
    {
        $query = File::where('user_id', $user->id);

        // Apply filters
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['order_id'])) {
            $query->where('order_id', $filters['order_id']);
        }

        if (!empty($filters['search'])) {
            $query->where('original_name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->with(['order'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    /**
     * Get storage usage for user
     *
     * @param User $user
     * @return array
     */
    public function getUserStorageUsage(User $user): array
    {
        $files = File::where('user_id', $user->id)->get();
        
        $totalSize = $files->sum('size');
        $totalFiles = $files->count();
        
        $typeBreakdown = $files->groupBy('type')->map(function ($typeFiles) {
            return [
                'count' => $typeFiles->count(),
                'size' => $typeFiles->sum('size')
            ];
        });

        return [
            'total_size' => $totalSize,
            'total_files' => $totalFiles,
            'formatted_size' => $this->formatBytes($totalSize),
            'type_breakdown' => $typeBreakdown
        ];
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
