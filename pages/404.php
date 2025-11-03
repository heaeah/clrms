<?php
try {
    // Example: Log 404 error or perform any file/DB operation
    // file_put_contents(...);
} catch (Exception $e) {
    // What: Error during 404 logging
    // Why: File system error, etc.
    // How: Log error and show user-friendly message
    error_log('[404 Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
} 