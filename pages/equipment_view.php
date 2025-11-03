<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid equipment ID.");
}

$equipmentService = new EquipmentService();
try {
    /**
     * What: Fetch equipment by ID
     * Why: For details view
     * How: Uses EquipmentService::getEquipmentById
     */
    $item = $equipmentService->getEquipmentById($_GET['id']);
    if (!$item) {
        die("Equipment not found.");
    }
} catch (Exception $e) {
    // What: Error fetching equipment details
    // Why: DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Equipment View Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    die("Error loading equipment details.");
}

// Badge color for status
$statusBadge = match ($item['status']) {
    'Available' => 'success',
    'Borrowed' => 'warning',
    'Under Repair' => 'danger',
    'Disposed' => 'secondary',
    default => 'dark'
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Details - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/equipment_view.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg rounded-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-info-circle me-2"></i> Equipment Details</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><span class="label-title">Name:</span><br><span class="value-text"><?= htmlspecialchars($item['name']) ?></span></p>
                            <p><span class="label-title">Serial Number:</span><br><span class="value-text"><?= htmlspecialchars($item['serial_number']) ?></span></p>
                            <p><span class="label-title">Model:</span><br><span class="value-text"><?= htmlspecialchars($item['model']) ?></span></p>
                            <p><span class="label-title">Status:</span><br>
                                <span class="badge bg-<?= $statusBadge ?> px-3 py-2"><?= htmlspecialchars($item['status']) ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><span class="label-title">Location:</span><br><span class="value-text"><?= htmlspecialchars($item['location']) ?></span></p>
                            <p><span class="label-title">Remarks:</span><br><span class="value-text"><?= nl2br(htmlspecialchars($item['remarks'])) ?></span></p>
                            <div class="qr-container mt-3">
                                <p class="mb-2 label-title">QR Code:</p>
                                                                 <div class="qr-code-wrapper">
                                     <img src="../uploads/qrcodes/equipment_<?= $item['id'] ?>.png" width="150"
                                          alt="QR Code" class="qr-code-image" 
                                          onclick="showQRModal(this.src, '<?= htmlspecialchars($item['name']) ?>')"
                                          onerror="this.style.display='none'"
                                          style="cursor: pointer; border: 1px solid #ddd; border-radius: 8px; transition: transform 0.2s;"
                                          onmouseover="this.style.transform='scale(1.05)'"
                                          onmouseout="this.style.transform='scale(1)'">
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <a href="../pages/borrowers_portal.php" class="btn btn-success">
                        <i class="bi bi-journal-arrow-up"></i> Borrow Equipment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">QR Code - <span id="equipmentName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="qrModalImage" src="" alt="QR Code" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="downloadQRFromModal()">
                    <i class="bi bi-download"></i> Download
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for modal
let currentQRImageSrc = '';
let currentEquipmentName = '';

function showQRModal(imageSrc, equipmentName) {
    currentQRImageSrc = imageSrc;
    currentEquipmentName = equipmentName;
    
    document.getElementById('qrModalImage').src = imageSrc;
    document.getElementById('equipmentName').textContent = equipmentName;
    
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    modal.show();
}

function downloadQRFromModal() {
    if (currentQRImageSrc && currentEquipmentName) {
        const fileName = `QR_Code_${currentEquipmentName.replace(/[^a-zA-Z0-9]/g, '_')}.png`;
        downloadFile(currentQRImageSrc, fileName);
    }
}

function downloadFile(filePath, fileName) {
    // Create a temporary link element
    const link = document.createElement('a');
    link.href = filePath;
    link.download = fileName;
    link.target = '_blank';
    
    // Append to body, click, and remove
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
</body>
</html>
