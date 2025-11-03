<?php
require_once '../includes/auth.php';
require_role(['Admin', 'Lab Admin']);

require_once '../classes/Database.php';

$db = (new Database())->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $table = $_POST['table'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                
                if (empty($name)) {
                    throw new Exception('Name is required');
                }
                
                // Get table-specific fields
                $fields = ['name' => $name];
                $values = [':name' => $name];
                
                if ($table === 'equipment_categories' || $table === 'maintenance_types') {
                    $fields['description'] = $description;
                    $values[':description'] = $description;
                }
                
                if ($table === 'equipment_status') {
                    $color = $_POST['color'] ?? '';
                    $fields['color'] = $color;
                    $values[':color'] = $color;
                }
                
                if ($table === 'labs') {
                    $location = $_POST['location'] ?? '';
                    $capacity = $_POST['capacity'] ?? '';
                    
                    $fields = ['lab_name' => $name, 'location' => $location, 'capacity' => $capacity];
                    $values = [':lab_name' => $name, ':location' => $location, ':capacity' => $capacity];
                }
                
                if ($table === 'equipment_items_master') {
                    $category = $_POST['category'] ?? '';
                    if (empty($category)) {
                        throw new Exception('Category is required for equipment items');
                    }
                    $fields = ['category' => $category, 'item_name' => $name];
                    $values = [':category' => $category, ':item_name' => $name];
                } elseif ($table === 'equipment_models_master') {
                    $category = $_POST['category'] ?? '';
                    $manufacturer = $_POST['manufacturer'] ?? '';
                    if (empty($category)) {
                        throw new Exception('Category is required for equipment models');
                    }
                    $fields = ['category' => $category, 'model_name' => $name, 'manufacturer' => $manufacturer];
                    $values = [':category' => $category, ':model_name' => $name, ':manufacturer' => $manufacturer];
                }
                
                
                $fieldNames = implode(', ', array_keys($fields));
                $placeholders = ':' . implode(', :', array_keys($fields));
                
                $query = "INSERT INTO {$table} ({$fieldNames}) VALUES ({$placeholders})";
                $stmt = $db->prepare($query);
                $stmt->execute($values);
                
                set_flash('success', 'Item added successfully!');
                break;
                
            case 'toggle':
                // Check if table has is_active column (only labs doesn't have it)
                $tablesWithoutStatus = ['labs'];
                if (in_array($table, $tablesWithoutStatus)) {
                    throw new Exception('This table does not support status toggling.');
                }
                
                $id = $_POST['id'];
                $isActive = $_POST['is_active'] ? 0 : 1;
                
                $query = "UPDATE {$table} SET is_active = :is_active WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':is_active', $isActive);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                set_flash('success', 'Status updated successfully!');
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = trim($_POST['name']);
                
                if (empty($name)) {
                    throw new Exception('Name is required');
                }
                
                if ($table === 'labs') {
                    $location = trim($_POST['location'] ?? '');
                    $capacity = $_POST['capacity'] ?? 0;
                    
                    $query = "UPDATE {$table} SET lab_name = :name, location = :location, capacity = :capacity WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':location', $location);
                    $stmt->bindParam(':capacity', $capacity);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                } else {
                    $query = "UPDATE {$table} SET name = :name WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':id', $id);
                    $stmt->execute();
                }
                
                set_flash('success', 'Item updated successfully!');
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Check if the item is being used
                if ($table === 'labs') {
                    // Check if lab is used in equipment
                    $checkQuery = "SELECT COUNT(*) as count FROM equipment WHERE location = (SELECT lab_name FROM labs WHERE id = :id)";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':id', $id);
                    $checkStmt->execute();
                    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        throw new Exception('Cannot delete this lab because it is being used by ' . $result['count'] . ' equipment item(s).');
                    }
                    
                    // Check if lab has reservations
                    $checkQuery = "SELECT COUNT(*) as count FROM lab_reservations WHERE lab_id = :id";
                    $checkStmt = $db->prepare($checkQuery);
                    $checkStmt->bindParam(':id', $id);
                    $checkStmt->execute();
                    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        throw new Exception('Cannot delete this lab because it has ' . $result['count'] . ' reservation(s).');
                    }
                }
                
                $query = "DELETE FROM {$table} WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                set_flash('success', 'Item deleted successfully!');
                break;
        }
        
        header("Location: manage_masterlists.php");
        exit;
        
    } catch (Exception $e) {
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
}

// Get data for each masterlist
$masterlists = [
    'equipment_categories' => 'Equipment Categories',
    'equipment_status' => 'Equipment Status',
    'labs' => 'Lab Locations',
    'courses' => 'Student Courses',
    'years' => 'Student Year Levels',
    'sections' => 'Student Sections',
    'departments' => 'Faculty Departments',
    'maintenance_types' => 'Maintenance Types',
    'user_roles' => 'User Roles',
    'equipment_items_master' => 'Equipment Items',
    'equipment_models_master' => 'Equipment Models'
];

$data = [];

foreach ($masterlists as $table => $title) {
    if ($table === 'equipment_items_master') {
        $query = "SELECT * FROM {$table} ORDER BY category ASC, item_name ASC";
    } elseif ($table === 'equipment_models_master') {
        $query = "SELECT * FROM {$table} ORDER BY category ASC, model_name ASC";
    } elseif ($table === 'labs') {
        // Labs table doesn't have is_active, only id, lab_name, location, capacity
        $query = "SELECT id, lab_name as name, location, capacity FROM {$table} ORDER BY lab_name ASC";
    } else {
        // All other tables have standard structure with is_active
        $query = "SELECT * FROM {$table} ORDER BY name ASC";
    }
    $stmt = $db->prepare($query);
    $stmt->execute();
    $data[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Masterlists - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="container-fluid px-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Masterlists</h2>
                <p class="text-muted mb-0">Prevent dirty data with standardized masterlists</p>
            </div>
            
            <?php show_flash(); ?>
            
            <!-- Masterlist Tabs -->
            <ul class="nav nav-tabs mb-4" id="masterlistTabs" role="tablist">
                <?php foreach ($masterlists as $table => $title): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $table === 'equipment_categories' ? 'active' : '' ?>" 
                            id="<?= $table ?>-tab" data-bs-toggle="tab" data-bs-target="#<?= $table ?>" 
                            type="button" role="tab">
                        <i class="bi bi-list-ul me-2"></i><?= $title ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="masterlistTabContent">
                <?php foreach ($masterlists as $table => $title): ?>
                <div class="tab-pane fade <?= $table === 'equipment_categories' ? 'show active' : '' ?>" 
                     id="<?= $table ?>" role="tabpanel">
                    
                    <!-- Add New Item -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Add New <?= $title ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="table" value="<?= $table ?>">
                                
                                <div class="col-md-4">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                
                                <?php if (in_array($table, ['equipment_categories', 'maintenance_types'])): ?>
                                <div class="col-md-4">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" name="description">
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($table === 'equipment_status'): ?>
                                <div class="col-md-4">
                                    <label for="color" class="form-label">Color</label>
                                    <select class="form-select" name="color">
                                        <option value="primary">Primary</option>
                                        <option value="success">Success</option>
                                        <option value="warning">Warning</option>
                                        <option value="danger">Danger</option>
                                        <option value="info">Info</option>
                                        <option value="secondary">Secondary</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($table === 'labs'): ?>
                                <div class="col-md-4">
                                    <label for="location" class="form-label">Location/Room</label>
                                    <input type="text" class="form-control" name="location" placeholder="e.g., LSA Building - Room 311">
                                </div>
                                <div class="col-md-2">
                                    <label for="capacity" class="form-label">Capacity</label>
                                    <input type="number" class="form-control" name="capacity" placeholder="e.g., 30">
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($table === 'equipment_items_master'): ?>
                                <div class="col-md-4">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Equipment">Equipment</option>
                                        <option value="Consumables">Consumables</option>
                                        <option value="Furniture">Furniture</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($table === 'equipment_models_master'): ?>
                                <div class="col-md-4">
                                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Equipment">Equipment</option>
                                        <option value="Consumables">Consumables</option>
                                        <option value="Furniture">Furniture</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="manufacturer" class="form-label">Manufacturer</label>
                                    <input type="text" class="form-control" name="manufacturer" placeholder="e.g., Acer, HP, Dell">
                                </div>
                                <?php endif; ?>
                                
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Add <?= $title ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Masterlist Items -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?= $title ?> List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($data[$table])): ?>
                                <p class="text-muted">No items found. Add some items above.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <?php if ($table === 'equipment_categories' || $table === 'maintenance_types'): ?>
                                                <th>Description</th>
                                                <?php endif; ?>
                                                <?php if ($table === 'equipment_status'): ?>
                                                <th>Color</th>
                                                <?php endif; ?>
                                                <?php if ($table === 'labs'): ?>
                                                <th>Location/Room</th>
                                                <th>Capacity</th>
                                                <?php endif; ?>
                                                <?php if ($table === 'equipment_items_master'): ?>
                                                <th>Category</th>
                                                <?php endif; ?>
                                                <?php if ($table === 'equipment_models_master'): ?>
                                                <th>Category</th>
                                                <th>Manufacturer</th>
                                                <?php endif; ?>
                                                <?php 
                                                // Only labs table doesn't have is_active column
                                                $tablesWithoutStatus = ['labs'];
                                                $hasStatusColumn = !in_array($table, $tablesWithoutStatus);
                                                ?>
                                                <?php if ($hasStatusColumn): ?>
                                                <th>Status</th>
                                                <?php endif; ?>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data[$table] as $item): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($table === 'equipment_items_master' ? $item['item_name'] : ($table === 'equipment_models_master' ? $item['model_name'] : $item['name'])) ?></strong></td>
                                                
                                                <?php if ($table === 'equipment_categories' || $table === 'maintenance_types'): ?>
                                                <td><?= htmlspecialchars($item['description'] ?? '') ?></td>
                                                <?php endif; ?>
                                                
                                                <?php if ($table === 'equipment_status'): ?>
                                                <td>
                                                    <span class="badge bg-<?= $item['color'] ?>">
                                                        <?= ucfirst($item['color']) ?>
                                                    </span>
                                                </td>
                                                <?php endif; ?>
                                                
                                                <?php if ($table === 'labs'): ?>
                                                <td><?= htmlspecialchars($item['location'] ?? '') ?></td>
                                                <td><?= $item['capacity'] ?? '' ?></td>
                                                <?php endif; ?>
                                                
                                                <?php if ($table === 'equipment_items_master'): ?>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($item['category']) ?></span>
                                                </td>
                                                <?php endif; ?>
                                                
                                                <?php if ($table === 'equipment_models_master'): ?>
                                                <td>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($item['category']) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($item['manufacturer'] ?? 'N/A') ?></td>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                // Only labs table doesn't have is_active column
                                                $tablesWithoutStatus = ['labs'];
                                                $hasStatusColumn = !in_array($table, $tablesWithoutStatus);
                                                ?>
                                                
                                                <?php if ($hasStatusColumn && isset($item['is_active'])): ?>
                                                <td>
                                                    <span class="badge bg-<?= $item['is_active'] ? 'success' : 'secondary' ?>">
                                                        <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <?php endif; ?>
                                                
                                                <!-- Edit/Delete/Toggle Actions Column -->
                                                <td>
                                                    <?php if ($hasStatusColumn && isset($item['is_active'])): ?>
                                                    <form method="POST" class="d-inline me-1">
                                                        <input type="hidden" name="action" value="toggle">
                                                        <input type="hidden" name="table" value="<?= $table ?>">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        <input type="hidden" name="is_active" value="<?= $item['is_active'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-<?= $item['is_active'] ? 'warning' : 'success' ?>">
                                                            <i class="bi bi-<?= $item['is_active'] ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-sm btn-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $table . $item['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $table . $item['id'] ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Edit and Delete Modals for Labs -->
                            <?php if ($table === 'labs'): ?>
                                <?php foreach ($data[$table] as $item): ?>
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $table . $item['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Lab</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="table" value="<?= $table ?>">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Lab Name <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Location/Room</label>
                                                            <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($item['location'] ?? '') ?>" placeholder="e.g., LSA Building - Room 311">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Capacity</label>
                                                            <input type="number" class="form-control" name="capacity" value="<?= $item['capacity'] ?? '' ?>" placeholder="e.g., 30">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?= $table . $item['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">Delete Lab</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="table" value="<?= $table ?>">
                                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                        
                                                        <p>Are you sure you want to delete <strong><?= htmlspecialchars($item['name']) ?></strong>?</p>
                                                        <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action cannot be undone!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
