@extends('layouts.app')

@section('title', 'File Manager')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">File Manager</h1>
            <p class="mt-2 text-gray-600">Upload and manage your project files</p>
        </div>

        <!-- File Upload Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" x-data="fileUpload()">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Files</h2>
                
                <!-- Upload Form -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="file-type" class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                            <select x-model="type" id="file-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="document">Document</option>
                                <option value="manuscript">Manuscript</option>
                                <option value="image">Image</option>
                                <option value="cover">Book Cover</option>
                                <option value="illustration">Illustration</option>
                                <option value="archive">Archive</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="order-id" class="block text-sm font-medium text-gray-700 mb-1">Order (Optional)</label>
                            <select x-model="orderId" id="order-id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Order</option>
                                <!-- Orders will be populated via API -->
                            </select>
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                            <input x-model="description" type="text" id="description" placeholder="File description..." 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <!-- Drop Zone -->
                    <div x-ref="dropZone" 
                         :class="dragOver ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                         class="border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-200">
                        
                        <div class="space-y-4">
                            <div class="mx-auto w-16 h-16 text-gray-400">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            
                            <div>
                                <p class="text-lg font-medium text-gray-900">Drop files here or click to browse</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    Supports PDF, DOC, DOCX, images, and archives up to 50MB each
                                </p>
                            </div>
                            
                            <input type="file" multiple @change="handleFileSelect" 
                                   class="hidden" x-ref="fileInput">
                            
                            <button type="button" @click="$refs.fileInput.click()" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Choose Files
                            </button>
                        </div>
                    </div>

                    <!-- Error Messages -->
                    <div x-show="hasErrors" class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Upload Errors</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <template x-for="error in errors" :key="error">
                                            <li x-text="error"></li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Files -->
                    <div x-show="hasFiles" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900">
                                Selected Files (<span x-text="files.length"></span>)
                                - <span x-text="formattedTotalSize"></span>
                            </h3>
                            <button @click="clearAll" type="button" 
                                    class="text-sm text-red-600 hover:text-red-800">
                                Clear All
                            </button>
                        </div>
                        
                        <div class="space-y-2">
                            <template x-for="file in files" :key="file.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 w-8 h-8 text-gray-400">
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900" x-text="file.name"></p>
                                            <p class="text-xs text-gray-500" x-text="file.formattedSize"></p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <div x-show="file.uploaded" class="text-green-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        
                                        <div x-show="file.error" class="text-red-600" :title="file.error">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        
                                        <button @click="removeFile(file.id)" type="button" 
                                                class="text-gray-400 hover:text-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div x-show="uploading" class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Uploading files...</span>
                            <span class="text-gray-900" x-text="`${Math.round(uploadProgress)}%`"></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                 :style="`width: ${uploadProgress}%`"></div>
                        </div>
                    </div>

                    <!-- Upload Buttons -->
                    <div x-show="canUpload" class="flex space-x-3">
                        <button @click="uploadFiles" type="button" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Upload Files
                        </button>
                        
                        <button @click="bulkUpload" type="button" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Bulk Upload
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- File List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="fileManager()">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Your Files</h2>
                    
                    <!-- Filters -->
                    <div class="flex items-center space-x-4">
                        <select x-model="filters.type" @change="loadFiles" 
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="document">Documents</option>
                            <option value="manuscript">Manuscripts</option>
                            <option value="image">Images</option>
                            <option value="cover">Book Covers</option>
                            <option value="illustration">Illustrations</option>
                            <option value="archive">Archives</option>
                        </select>
                        
                        <input x-model="filters.search" @input.debounce.500ms="loadFiles" 
                               type="text" placeholder="Search files..." 
                               class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- File Grid -->
            <div class="p-6">
                <div x-show="loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600">Loading files...</p>
                </div>

                <div x-show="!loading && files.length === 0" class="text-center py-12">
                    <div class="mx-auto w-16 h-16 text-gray-400 mb-4">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No files found</h3>
                    <p class="text-gray-500">Upload your first file to get started</p>
                </div>

                <div x-show="!loading && files.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <template x-for="file in files" :key="file.id">
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0 w-10 h-10 text-gray-400">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="file.original_name"></p>
                                        <p class="text-xs text-gray-500" x-text="file.formatted_size"></p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <a :href="file.download_url" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m-6 4H6a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v9a2 2 0 01-2 2z"/>
                                        </svg>
                                    </a>
                                    
                                    <button @click="deleteFile(file.id)" 
                                            class="text-red-600 hover:text-red-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded" x-text="file.type"></span>
                                    <span class="text-gray-500" x-text="formatDate(file.uploaded_at)"></span>
                                </div>
                                
                                <div x-show="file.order" class="text-xs text-blue-600">
                                    Order: <span x-text="file.order?.order_number"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Pagination -->
                <div x-show="pagination.last_page > 1" class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span> 
                        to <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> 
                        of <span x-text="pagination.total"></span> files
                    </div>
                    
                    <div class="flex space-x-2">
                        <button @click="loadFiles(pagination.current_page - 1)" 
                                :disabled="pagination.current_page <= 1"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                            Previous
                        </button>
                        
                        <button @click="loadFiles(pagination.current_page + 1)" 
                                :disabled="pagination.current_page >= pagination.last_page"
                                class="px-3 py-1 text-sm border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// File Manager Alpine.js component
function fileManager() {
    return {
        files: [],
        loading: false,
        filters: {
            type: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0
        },

        init() {
            this.loadFiles();
        },

        async loadFiles(page = 1) {
            this.loading = true;
            
            try {
                const params = new URLSearchParams({
                    page: page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await fetch(`/api/v1/files?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.files = data.data.files;
                    this.pagination = data.data.pagination;
                }
            } catch (error) {
                console.error('Error loading files:', error);
            } finally {
                this.loading = false;
            }
        },

        async deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file?')) {
                return;
            }

            try {
                const response = await fetch(`/api/v1/files/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.loadFiles(this.pagination.current_page);
                    this.$dispatch('show-notification', { 
                        message: 'File deleted successfully', 
                        type: 'success' 
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                this.$dispatch('show-notification', { 
                    message: error.message || 'Failed to delete file', 
                    type: 'error' 
                });
            }
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    };
}
</script>
@endsection
