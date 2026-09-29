<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    protected string $disk;
    protected string $path;

    public function __construct(string $disk = 'public', string $path = 'resources')
    {
        $this->disk = $disk;
        $this->path = $path;
    }

    /**
     * Upload a single file.
     */
    public function upload(UploadedFile $file, array $options = []): array
    {
        $this->validateFile($file, $options);

        $filename = $this->generateFilename($file, $options);
        $directory = $this->getDirectory($options);

        $path = $file->storeAs($directory, $filename, $this->disk);

        return [
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple(array $files, array $options = []): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $results[] = $this->upload($file, $options);
            } catch (\Throwable $e) {
                $errors[] = [
                    'index' => $index,
                    'original_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'uploaded' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Get file info.
     */
    public function getFileInfo(string $path): ?array
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return null;
        }

        return [
            'path' => $path,
            'url' => Storage::disk($this->disk)->url($path),
            'size' => Storage::disk($this->disk)->size($path),
            'mime_type' => Storage::disk($this->disk)->mimeType($path),
            'last_modified' => Storage::disk($this->disk)->lastModified($path),
        ];
    }

    /**
     * Validate uploaded file.
     */
    protected function validateFile(UploadedFile $file, array $options): void
    {
        $maxSize = $options['max_size'] ?? config('filesystems.max_upload_size', 10240); // KB
        $allowedMimes = $options['allowed_mimes'] ?? [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/wav',
            'text/plain',
            'text/csv',
        ];

        if ($file->getSize() > $maxSize * 1024) {
            throw ValidationException::withMessages([
                'file' => "File size exceeds maximum allowed size of {$maxSize} KB.",
            ]);
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw ValidationException::withMessages([
                'file' => 'File type not allowed. Allowed types: ' . implode(', ', $allowedMimes),
            ]);
        }
    }

    /**
     * Generate unique filename.
     */
    protected function generateFilename(UploadedFile $file, array $options): string
    {
        $prefix = $options['prefix'] ?? 'resource';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        $extension = $file->getClientOriginalExtension();

        return "{$prefix}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get storage directory.
     */
    protected function getDirectory(array $options): string
    {
        $base = $this->path;
        $subdir = $options['subdir'] ?? now()->format('Y/m');

        return "{$base}/{$subdir}";
    }

    /**
     * Get temporary upload URL for direct browser uploads.
     */
    public function getTemporaryUploadUrl(array $options = []): array
    {
        $key = $this->getDirectory($options) . '/' . $this->generateFilename(
            new \Illuminate\Http\UploadedFile(
                sys_get_temp_dir() . '/dummy',
                'dummy.' . ($options['extension'] ?? 'tmp')
            ),
            $options
        );

        $url = Storage::disk($this->disk)->temporaryUrl(
            $key,
            now()->addMinutes($options['expires_in'] ?? 30),
            ['Content-Type' => $options['content_type'] ?? 'application/octet-stream']
        );

        return [
            'upload_url' => $url,
            'key' => $key,
            'expires_at' => now()->addMinutes($options['expires_in'] ?? 30)->toIso8601String(),
        ];
    }
}