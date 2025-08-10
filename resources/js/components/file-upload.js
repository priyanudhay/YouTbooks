// File Upload Component for Alpine.js
export function fileUpload() {
    return {
        files: [],
        dragOver: false,
        uploading: false,
        uploadProgress: 0,
        maxFileSize: 50 * 1024 * 1024, // 50MB
        allowedTypes: [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
            'text/plain',
            'text/rtf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed'
        ],
        type: 'document',
        orderId: null,
        description: '',
        uploadedFiles: [],
        errors: [],

        init() {
            // Set up drag and drop listeners
            this.setupDragAndDrop();
        },

        setupDragAndDrop() {
            const dropZone = this.$refs.dropZone;
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, this.preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.dragOver = true, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => this.dragOver = false, false);
            });

            dropZone.addEventListener('drop', this.handleDrop.bind(this), false);
        },

        preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        },

        handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            this.handleFiles(files);
        },

        handleFileSelect(event) {
            const files = event.target.files;
            this.handleFiles(files);
        },

        handleFiles(fileList) {
            const files = Array.from(fileList);
            this.errors = [];

            files.forEach(file => {
                if (this.validateFile(file)) {
                    this.files.push({
                        file: file,
                        name: file.name,
                        size: file.size,
                        formattedSize: this.formatBytes(file.size),
                        type: file.type,
                        id: Date.now() + Math.random(),
                        progress: 0,
                        uploaded: false,
                        error: null
                    });
                }
            });
        },

        validateFile(file) {
            // Check file size
            if (file.size > this.maxFileSize) {
                this.errors.push(`${file.name}: File size exceeds 50MB limit`);
                return false;
            }

            // Check file type
            if (!this.allowedTypes.includes(file.type)) {
                this.errors.push(`${file.name}: File type not allowed`);
                return false;
            }

            // Check for dangerous extensions
            const dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (dangerousExtensions.includes(extension)) {
                this.errors.push(`${file.name}: File extension not allowed for security reasons`);
                return false;
            }

            return true;
        },

        removeFile(fileId) {
            this.files = this.files.filter(f => f.id !== fileId);
        },

        clearAll() {
            this.files = [];
            this.errors = [];
            this.uploadedFiles = [];
        },

        async uploadFiles() {
            if (this.files.length === 0) {
                this.showNotification('No files selected', 'error');
                return;
            }

            this.uploading = true;
            this.uploadProgress = 0;
            const totalFiles = this.files.length;
            let completedFiles = 0;

            for (const fileItem of this.files) {
                if (fileItem.uploaded) continue;

                try {
                    await this.uploadSingleFile(fileItem);
                    fileItem.uploaded = true;
                    completedFiles++;
                    this.uploadProgress = (completedFiles / totalFiles) * 100;
                } catch (error) {
                    fileItem.error = error.message;
                    completedFiles++;
                    this.uploadProgress = (completedFiles / totalFiles) * 100;
                }
            }

            this.uploading = false;
            
            const successCount = this.files.filter(f => f.uploaded).length;
            const errorCount = this.files.filter(f => f.error).length;

            if (successCount > 0) {
                this.showNotification(`${successCount} file(s) uploaded successfully`, 'success');
            }
            
            if (errorCount > 0) {
                this.showNotification(`${errorCount} file(s) failed to upload`, 'error');
            }
        },

        async uploadSingleFile(fileItem) {
            const formData = new FormData();
            formData.append('file', fileItem.file);
            formData.append('type', this.type);
            
            if (this.orderId) {
                formData.append('order_id', this.orderId);
            }
            
            if (this.description) {
                formData.append('description', this.description);
            }

            const response = await fetch('/api/v1/files', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Upload failed');
            }

            // Add to uploaded files list
            this.uploadedFiles.push(data.data);
            
            return data.data;
        },

        async bulkUpload() {
            if (this.files.length === 0) {
                this.showNotification('No files selected', 'error');
                return;
            }

            this.uploading = true;
            this.uploadProgress = 0;

            try {
                const formData = new FormData();
                
                this.files.forEach((fileItem, index) => {
                    formData.append(`files[${index}]`, fileItem.file);
                });
                
                formData.append('type', this.type);
                
                if (this.orderId) {
                    formData.append('order_id', this.orderId);
                }
                
                if (this.description) {
                    formData.append('description', this.description);
                }

                const response = await fetch('/api/v1/files/bulk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.uploadedFiles = [...this.uploadedFiles, ...data.data.uploaded_files];
                    this.files.forEach(f => f.uploaded = true);
                    
                    if (data.data.errors.length > 0) {
                        this.errors = data.data.errors.map(e => `${e.file}: ${e.error}`);
                    }
                    
                    this.showNotification(data.message, 'success');
                } else {
                    throw new Error(data.message || 'Bulk upload failed');
                }

            } catch (error) {
                this.showNotification(error.message, 'error');
            } finally {
                this.uploading = false;
                this.uploadProgress = 100;
            }
        },

        formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) {
                return 'photograph';
            }
            
            switch (mimeType) {
                case 'application/pdf':
                    return 'document-text';
                case 'application/msword':
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                    return 'document';
                case 'application/zip':
                case 'application/x-rar-compressed':
                case 'application/x-7z-compressed':
                    return 'archive';
                default:
                    return 'document';
            }
        },

        showNotification(message, type = 'info') {
            // Emit custom event for global notification system
            this.$dispatch('show-notification', { message, type });
        },

        // Computed properties
        get hasFiles() {
            return this.files.length > 0;
        },

        get hasErrors() {
            return this.errors.length > 0;
        },

        get canUpload() {
            return this.hasFiles && !this.uploading;
        },

        get totalSize() {
            return this.files.reduce((total, file) => total + file.size, 0);
        },

        get formattedTotalSize() {
            return this.formatBytes(this.totalSize);
        }
    };
}
