<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/ICTSupport.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = (int)$_POST['request_id'];
    $resolutionNotes = trim($_POST['resolution_notes'] ?? '');
    
    if (empty($resolutionNotes)) {
        set_flash('danger', 'Resolution notes are required.');
        header("Location: ict_support_dashboard.php");
        exit;
    }
    
    try {
        $ictSupport = new ICTSupport();
        
        // Update the request status to resolved
        $query = "UPDATE ict_support_requests SET 
                    status = 'Resolved',
                    resolution_notes = :resolution_notes,
                    resolved_at = NOW()
                  WHERE id = :id";
        
        $stmt = $ictSupport->getConn()->prepare($query);
        $stmt->bindParam(':resolution_notes', $resolutionNotes);
        $stmt->bindParam(':id', $requestId);
        
        if ($stmt->execute()) {
            set_flash('success', 'Support request has been resolved successfully.');
            
            // Log the resolution
            error_log("[ICT Support] Request #{$requestId} resolved by {$_SESSION['name']} with notes: {$resolutionNotes}", 3, __DIR__ . '/../logs/ict_support.log');
        } else {
            set_flash('danger', 'Failed to resolve the support request.');
        }
        
    } catch (Exception $e) {
        error_log('[ICT Support Resolution Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error resolving request: ' . $e->getMessage());
    }
    
    header("Location: ict_support_dashboard.php");
    exit;
} else {
    header("Location: ict_support_dashboard.php");
    exit;
}
?>