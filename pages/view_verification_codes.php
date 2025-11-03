<?php
require_once '../includes/auth.php';
require_role(['Lab Admin']);

$emailDir = __DIR__ . '/../logs/emails';
$emailFiles = [];

if (is_dir($emailDir)) {
    $files = scandir($emailDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'txt') {
            $filePath = $emailDir . '/' . $file;
            $content = file_get_contents($filePath);
            
            // Extract verification code from content
            preg_match('/VERIFICATION CODE: (\d{6})/', $content, $matches);
            $code = $matches[1] ?? 'N/A';
            
            // Extract email
            preg_match('/To: (.+)/', $content, $emailMatches);
            $email = $emailMatches[1] ?? 'N/A';
            
            $emailFiles[] = [
                'filename' => $file,
                'path' => $filePath,
                'code' => $code,
                'email' => $email,
                'time' => filemtime($filePath),
                'content' => $content
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Codes - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-envelope-check text-primary me-2"></i>Email Verification Codes
                </h1>
                <p class="text-muted small mb-0">View all verification codes sent to users</p>
            </div>
            <div>
                <span class="badge bg-primary fs-6"><?= count($emailFiles) ?> codes</span>
            </div>
        </div>
        
        <!-- Info Alert -->
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Note:</strong> Verification codes are stored in <code>logs/emails/</code> directory. 
            Each code is valid for 10 minutes from the time it was generated.
        </div>
        
        <!-- Verification Codes List -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i>Recent Verification Codes
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($emailFiles)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">No Verification Codes</h5>
                        <p class="text-muted mb-0">No verification codes have been generated yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Email Address</th>
                                    <th>Verification Code</th>
                                    <th>Generated Time</th>
                                    <th class="text-center pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emailFiles as $emailFile): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <strong><?= htmlspecialchars($emailFile['email']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary" style="font-size: 1.2rem; letter-spacing: 3px; padding: 8px 15px;">
                                                <?= htmlspecialchars($emailFile['code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?= date('M d, Y', $emailFile['time']) ?></div>
                                            <small class="text-muted"><?= date('h:i A', $emailFile['time']) ?></small>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?= md5($emailFile['filename']) ?>">
                                                <i class="bi bi-eye"></i> View Full Email
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?= md5($emailFile['filename']) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">
                                                        <i class="bi bi-envelope-open me-2"></i>Email Content
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><?= htmlspecialchars($emailFile['content']) ?></pre>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

