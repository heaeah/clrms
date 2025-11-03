<?php

try {
    // Example: Fetch all labs
    $labs = $labObj->getAllLabs();
} catch (Exception $e) {
    // What: Error fetching lab list
    // Why: DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Lab List Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $labs = [];
} 