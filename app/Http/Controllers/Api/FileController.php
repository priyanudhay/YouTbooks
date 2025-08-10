<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FileService;
use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class FileController extends Controller
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Upload a file
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'type' => 'required|string|in:document,image,archive,manuscript,cover,illustration',
            'order_id' => 'nullable|integer|exists:orders,id',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $file = $request->file('file');
            $type = $request->input('type');
            $orderId = $request->input('order_id');
            $description = $request->input('description');

            // Get order if provided and validate access
            $order = null;
            if ($orderId) {
                $order = Order::find($orderId);
                if (!$order || ($order->user_id !== $user->id && !$user->isAdmin() && !$user->isEditor())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found or access denied'
                    ], 404);
                }
            }

            // Prepare metadata
            $metadata = [];
            if ($description) {
                $metadata['description'] = $description;
            }

            // Upload file
            $fileRecord = $this->fileService->uploadFile($file, $user, $order, $type, $metadata);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $fileRecord->id,
                    'original_name' => $fileRecord->original_name,
                    'filename' => $fileRecord->filename,
                    'size' => $fileRecord->size,
                    'formatted_size' => $this->formatBytes($fileRecord->size),
                    'type' => $fileRecord->type,
                    'mime_type' => $fileRecord->mime_type,
                    'uploaded_at' => $fileRecord->created_at->toISOString(),
                    'download_url' => route('api.files.download', $fileRecord->id)
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get user's files
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:document,image,archive,manuscript,cover,illustration',
            'order_id' => 'nullable|integer|exists:orders,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $filters = $request->only(['type', 'order_id', 'search']);
            $perPage = $request->input('per_page', 15);

            $files = $this->fileService->getUserFiles($user, $filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'files' => $files->items(),
                    'pagination' => [
                        'current_page' => $files->currentPage(),
                        'last_page' => $files->lastPage(),
                        'per_page' => $files->perPage(),
                        'total' => $files->total()
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files'
            ], 500);
        }
    }

    /**
     * Get file details
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = File::with(['order', 'user'])->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Check access permissions
            if (!$this->fileService->canUserAccessFile($file, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'filename' => $file->filename,
                    'size' => $file->size,
                    'formatted_size' => $this->formatBytes($file->size),
                    'type' => $file->type,
                    'mime_type' => $file->mime_type,
                    'is_processed' => $file->is_processed,
                    'is_public' => $file->is_public,
                    'metadata' => $file->metadata,
                    'uploaded_at' => $file->created_at->toISOString(),
                    'order' => $file->order ? [
                        'id' => $file->order->id,
                        'order_number' => $file->order->order_number,
                        'status' => $file->order->status
                    ] : null,
                    'download_url' => route('api.files.download', $file->id)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file details'
            ], 500);
        }
    }

    /**
     * Download a file
     *
     * @param int $id
     * @return mixed
     */
    public function download(int $id)
    {
        try {
            $user = Auth::user();
            $file = File::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return $this->fileService->downloadFile($file, $user);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Access denied' ? 403 : 500);
        }
    }

    /**
     * Delete a file
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $file = File::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $this->fileService->deleteFile($file, $user);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getMessage() === 'Access denied' ? 403 : 500);
        }
    }

    /**
     * Get user's storage usage
     *
     * @return JsonResponse
     */
    public function storageUsage(): JsonResponse
    {
        try {
            $user = Auth::user();
            $usage = $this->fileService->getUserStorageUsage($user);

            return response()->json([
                'success' => true,
                'data' => $usage
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve storage usage'
            ], 500);
        }
    }

    /**
     * Bulk upload files
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:51200',
            'type' => 'required|string|in:document,image,archive,manuscript,cover,illustration',
            'order_id' => 'nullable|integer|exists:orders,id',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $files = $request->file('files');
            $type = $request->input('type');
            $orderId = $request->input('order_id');
            $description = $request->input('description');

            // Get order if provided and validate access
            $order = null;
            if ($orderId) {
                $order = Order::find($orderId);
                if (!$order || ($order->user_id !== $user->id && !$user->isAdmin() && !$user->isEditor())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order not found or access denied'
                    ], 404);
                }
            }

            $uploadedFiles = [];
            $errors = [];

            foreach ($files as $index => $file) {
                try {
                    $metadata = [];
                    if ($description) {
                        $metadata['description'] = $description;
                    }
                    $metadata['bulk_upload_index'] = $index;

                    $fileRecord = $this->fileService->uploadFile($file, $user, $order, $type, $metadata);
                    
                    $uploadedFiles[] = [
                        'id' => $fileRecord->id,
                        'original_name' => $fileRecord->original_name,
                        'size' => $fileRecord->size,
                        'formatted_size' => $this->formatBytes($fileRecord->size),
                        'download_url' => route('api.files.download', $fileRecord->id)
                    ];

                } catch (Exception $e) {
                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => count($uploadedFiles) > 0,
                'message' => count($uploadedFiles) . ' files uploaded successfully' . 
                           (count($errors) > 0 ? ', ' . count($errors) . ' failed' : ''),
                'data' => [
                    'uploaded_files' => $uploadedFiles,
                    'errors' => $errors
                ]
            ], count($uploadedFiles) > 0 ? 201 : 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk upload failed: ' . $e->getMessage()
            ], 500);
        }
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
