<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Student Assistant']);
require_once '../classes/EquipmentService.php';
require_once '../classes/LabService.php';
require_once '../classes/MasterlistService.php';

$equipmentService = new EquipmentService();
$labService = new LabService();
$masterlistService = new MasterlistService();
$labList = $labService->getAllLabs();

// Handle archiving
if (isset($_GET['archive'])) {
    $id = intval($_GET['archive']);
    $equipment = $equipmentService->getEquipmentById($id);

    if ($equipment) {
        $equipmentService->archiveEquipment($id);
        $equipmentService->logEquipmentAction($id, 'Archived', $_SESSION['user_id']);
        set_flash('success', 'Equipment archived successfully.');
    } else {
        set_flash('danger', 'Equipment not found.');
    }
    header("Location: inventory.php");
    exit;
}

try {
    /**
     * What: Fetch all equipment
     * Why: For listing in the table
     * How: Uses EquipmentService::getAllEquipment
     */
    $equipmentList = $equipmentService->getAllEquipment();
} catch (Exception $e) {
    // What: Error fetching equipment list
    // Why: DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Inventory Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentList = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary">Inventory Management</h2>
            <div>
                <button class="btn btn-outline-info me-2" onclick="generateMissingQRCodes()">
                    <i class="bi bi-qr-code"></i> Generate Missing QR Codes
                </button>
                <a href="archived_equipment.php" class="btn btn-outline-dark me-2">
                    <i class="bi bi-archive-fill"></i> Archived Equipment
                </a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Equipment
                </button>
            </div>
        </div>

        <!-- Lab Cards -->
        <div class="row mb-3" id="lab-cards-row">
            <!-- All Computer Labs Option -->
            <div class="col-md-2 col-6 mb-2">
                <div class="card lab-card text-center p-2 shadow-sm border-primary" data-lab="" id="all-labs-card">
                    <div class="card-body py-2">
                        <div class="fw-bold text-primary mb-1" style="font-size:1.5em;"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                        <div class="lab-title" style="font-size:1.1em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            All Computer Labs
                        </div>
                        <div class="small text-muted" style="font-size:0.9em;">
                            View all equipment
                        </div>
                    </div>
                </div>
            </div>
            <?php foreach ($labList as $lab): ?>
                <div class="col-md-2 col-6 mb-2">
                    <div class="card lab-card text-center p-2 shadow-sm" data-lab="<?= htmlspecialchars($lab['lab_name']) ?>">
                        <div class="card-body py-2">
                            <div class="fw-bold text-primary mb-1" style="font-size:1.5em;"><i class="bi bi-pc-display-horizontal"></i></div>
                            <div class="lab-title" style="font-size:1.1em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($lab['lab_name']) ?>
                            </div>
                            <div class="small text-muted" style="font-size:0.9em;">
                                <?= htmlspecialchars($lab['location']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Category Tabs -->
        <ul class="nav nav-tabs mb-4" id="category-tabs">
            <li class="nav-item"><a class="nav-link active" data-category="" href="#"><i class="bi bi-grid"></i> All</a></li>
            <li class="nav-item"><a class="nav-link" data-category="Equipment" href="#"><i class="bi bi-display"></i> Equipment</a></li>
            <li class="nav-item"><a class="nav-link" data-category="Consumables" href="#"><i class="bi bi-box"></i> Consumables</a></li>
            <li class="nav-item"><a class="nav-link" data-category="Furniture" href="#"><i class="bi bi-bookshelf"></i> Furniture</a></li>
            <li class="nav-item"><a class="nav-link" data-category="Others" href="#"><i class="bi bi-grid-3x3-gap"></i> Others</a></li>
        </ul>
        <!-- Filter Form (status and search only) -->
        <form class="row g-3 mb-4" id="filter-form" onsubmit="return false;">
            <input type="hidden" id="filter-location" value="">
            <input type="hidden" id="filter-category" value="">
            <div class="col-md-3">
                <label for="filter-status" class="form-label">Status</label>
                <select id="filter-status" class="form-select">
                    <option value="">All</option>
                    <option value="Available">Available</option>
                    <option value="Borrowed">Borrowed</option>
                    <option value="Under Repair">Under Repair</option>
                    <option value="Disposed">Disposed</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="filter-search" class="form-label">Search</label>
                <input type="text" id="filter-search" class="form-control" placeholder="Search by name, serial, model...">
            </div>
        </form>

        <div class="card shadow rounded-3">
            <div class="card-body">
                <div class="table-responsive" id="inventory-table">
                    <!-- Table loads here via AJAX -->
                </div>
            </div>
        </div>

        <!-- Add Equipment Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="save_equipment.php" method="POST" class="modal-content shadow rounded-3">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">Add Equipment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="category" name="category" class="form-select" required>
                                <?= $masterlistService->getEquipmentCategoryOptions() ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i>
                                Select category first to filter item names and models
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Item Name <span class="text-danger">*</span></label>
                            <select id="name" name="name" class="form-select" required>
                                <option value="">Select Item Name</option>
                            </select>
                            <small class="form-text text-muted">Items are filtered by category. Select category first.</small>
                        </div>
                        <div class="mb-3">
                            <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                            <input type="text" id="serial_number" name="serial_number" class="form-control" 
                                   placeholder="e.g., IS01_COMP001, LAB01_PROJ001" 
                                   pattern="[A-Z0-9_-]+" 
                                   title="Use uppercase letters, numbers, underscores, and hyphens only"
                                   required>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i>
                                Format: Use uppercase, numbers, and underscores (e.g., IS01_COMP001)
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <select id="model" name="model" class="form-select">
                                <option value="">Select Model (Optional)</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i>
                                Models are filtered by category. Select category first.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <select id="location" name="location" class="form-select" required>
                                <?= $masterlistService->getLabLocationOptions() ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <input type="text" class="form-control" value="Available (Default)" readonly style="background-color: #e9ecef;">
                            <input type="hidden" id="status" name="status" value="Available">
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i>
                                New equipment starts as 'Available'. Status will automatically update based on usage.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="installation_date" class="form-label">Installation Date *</label>
                            <input type="date" id="installation_date" name="installation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            <small class="form-text text-muted">This date will be used to calculate maintenance schedules (every 6 months)</small>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea id="remarks" name="remarks" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Equipment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

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

<script src="../assets/js/inventory.js"></script>
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

function generateMissingQRCodes() {
    if (confirm('This will generate QR codes for all equipment that don\'t have them. Continue?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
        button.disabled = true;
        
        fetch('../generate_missing_qr_codes.php')
        .then(response => response.text())
        .then(data => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            const successMatch = data.match(/Successfully generated.*?(\d+)/);
            const failedMatch = data.match(/Failed to generate.*?(\d+)/);
            let message = '';
            if (successMatch) { message += `Successfully generated: ${successMatch[1]} QR codes\n`; }
            if (failedMatch) { message += `Failed to generate: ${failedMatch[1]} QR codes\n`; }
            if (!successMatch && !failedMatch) { message = 'No missing QR codes found or all generations failed.'; }
            alert(message);
            loadInventoryTable(); // Reload the table to show new QR codes
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating QR codes');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}
</script>
<script>
// Lab card click
let selectedLab = '';
document.querySelectorAll('.lab-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.lab-card').forEach(c => c.classList.remove('border-primary', 'shadow-lg'));
        this.classList.add('border-primary', 'shadow-lg');
        selectedLab = this.getAttribute('data-lab');
        document.getElementById('filter-location').value = selectedLab;
        loadInventoryTable();
    });
});
// Category tab click
let selectedCategory = '';
document.querySelectorAll('#category-tabs .nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('#category-tabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        selectedCategory = this.getAttribute('data-category');
        document.getElementById('filter-category').value = selectedCategory;
        loadInventoryTable();
    });
});

function generateMissingQRCodes() {
    if (confirm('This will generate QR codes for all equipment that don\'t have them. Continue?')) {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
        button.disabled = true;
        
        fetch('../generate_missing_qr_codes.php')
        .then(response => response.text())
        .then(data => {
            // Create a temporary div to parse the HTML response
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;
            
            // Extract results from the response
            const successMatch = data.match(/Successfully generated.*?(\d+)/);
            const failedMatch = data.match(/Failed to generate.*?(\d+)/);
            
            let message = '';
            if (successMatch) {
                message += `Successfully generated: ${successMatch[1]} QR codes\n`;
            }
            if (failedMatch) {
                message += `Failed to generate: ${failedMatch[1]} QR codes\n`;
            }
            
            if (!successMatch && !failedMatch) {
                message = 'No missing QR codes found or all generations failed.';
            }
            
            alert(message);
            
            // Reload the page to show updated QR codes
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error generating QR codes');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Dynamic item name dropdown based on category selection
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    const nameSelect = document.getElementById('name');
    const modelSelect = document.getElementById('model');
    
    if (categorySelect && nameSelect) {
        categorySelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            
            // Clear existing options for both name and model
            nameSelect.innerHTML = '<option value="">Select Item Name</option>';
            modelSelect.innerHTML = '<option value="">Select Model (Optional)</option>';
            
            if (selectedCategory) {
                // Fetch items for the selected category
                fetch(`../pages/api/get_items_by_category.php?category=${encodeURIComponent(selectedCategory)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.items) {
                            data.items.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item;
                                option.textContent = item;
                                nameSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching items:', error);
                    });
                
                // Fetch models for the selected category
                fetch(`../pages/api/get_models_by_category.php?category=${encodeURIComponent(selectedCategory)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.models) {
                            data.models.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model.model_name;
                                const displayName = model.manufacturer ? 
                                    `${model.model_name} (${model.manufacturer})` : 
                                    model.model_name;
                                option.textContent = displayName;
                                modelSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching models:', error);
                    });
            }
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
