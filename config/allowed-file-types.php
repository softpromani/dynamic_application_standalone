<?php

/**
 * Allowed File Types Configuration
 * Defines safe file types for user uploads, blocking all server executables
 */

return [
    'allowed_extensions' => [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
        
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'rtf', 'odt', 'ods', 'odp',
        
        // Archives
        'zip', 'rar', '7z',
        
        // Others
        'csv', 'tsv',
    ],

    'allowed_mime_types' => [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/rtf',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        
        // Others
        'text/csv',
        'text/tab-separated-values',
    ],

    'blocked_extensions' => [
        // Server executables
        'php', 'phtml', 'php3', 'php4', 'php5', 'php6', 'php7', 'pht', 'phps',
        'phar', 'inc', 'hphp', 'ctp', 'shtml',
        
        // Script executables
        'js', 'jse', 'jsp', 'bat', 'cmd', 'com', 'exe', 'scr', 'vbs', 'vbe',
        'ps1', 'psm1', 'psc1', 'psc2', 'sh', 'bash', 'zsh', 'csh',
        
        // Other dangerous files
        'app', 'bin', 'cgi', 'dll', 'so', 'elf', 'mach', 'o', 'a', 'lib',
        'dylib', 'sys', 'drv', 'vxd', 'efi', 'scr', 'msi', 'apk', 'deb', 'rpm',
        
        // Archive with potential executables
        'tar', 'gz', 'tgz', 'bz2', 'iso', 'dmg',
        
        // HTML/XML that could contain scripts
        'html', 'htm', 'xml', 'xhtml', 'shtml',
        
        // Other risky types
        'jar', 'class', 'py', 'rb', 'pl', 'lua', 'swift', 'go', 'rs',
    ],

    'max_file_size' => 2048, // KB (2MB)

    'max_file_size_bytes' => 2 * 1024 * 1024, // Bytes
];
