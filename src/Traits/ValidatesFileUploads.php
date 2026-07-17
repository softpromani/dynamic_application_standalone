<?php

namespace Softpro\Core\Traits;

use Illuminate\Http\UploadedFile;

/**
 * File Upload Validation Trait
 * Handles secure file type validation and blocking of dangerous files
 */
trait ValidatesFileUploads
{
    /**
     * Validate file upload for allowed types
     * 
     * @param UploadedFile $file
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateFileUpload(UploadedFile $file): array
    {
        $config = config('allowed-file-types');

        // Check file size
        if ($file->getSize() > $config['max_file_size_bytes']) {
            return [
                'valid' => false,
                'message' => 'File size exceeds ' . ($config['max_file_size'] / 1024) . 'MB limit.'
            ];
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        // Check if extension is blocked
        if (in_array($extension, $config['blocked_extensions'])) {
            return [
                'valid' => false,
                'message' => 'File type ".' . $extension . '" is not allowed for security reasons.'
            ];
        }

        // Check if extension is in allowed list
        if (!in_array($extension, $config['allowed_extensions'])) {
            return [
                'valid' => false,
                'message' => 'File type ".' . $extension . '" is not allowed. Allowed types: ' . implode(', ', $config['allowed_extensions'])
            ];
        }

        // Verify MIME type matches extension (basic MIME validation)
        if (!in_array($mimeType, $config['allowed_mime_types'])) {
            return [
                'valid' => false,
                'message' => 'File MIME type does not match the allowed types.'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate multiple files
     * 
     * @param array $files
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateMultipleFiles(array $files): array
    {
        $errors = [];

        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFile) {
                $validation = $this->validateFileUpload($file);
                if (!$validation['valid']) {
                    $errors[$key] = $validation['message'];
                }
            } elseif (is_array($file)) {
                // Handle nested arrays (e.g., table uploads)
                $nested = $this->validateMultipleFiles($file);
                if (!$nested['valid']) {
                    $errors[$key] = $nested['errors'];
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if file extension is dangerous
     * 
     * @param string $extension
     * @return bool
     */
    public function isDangerousExtension(string $extension): bool
    {
        $config = config('allowed-file-types');
        return in_array(strtolower($extension), $config['blocked_extensions']);
    }

    /**
     * Get allowed file extensions for frontend validation
     * 
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return config('allowed-file-types.allowed_extensions', []);
    }

    /**
     * Get allowed MIME types for frontend validation
     * 
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return config('allowed-file-types.allowed_mime_types', []);
    }
}
