<?php
require_once '../classes/EquipmentService.php';

$equipmentService = new EquipmentService();

$status = $_GET['status'] ?? '';
$location = $_GET['location'] ?? '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

try {
    /**
     * What: Filter and search equipment
     * Why: For AJAX table/filter
     * How: Uses EquipmentService::filterAndSearchEquipment
     */
    $equipmentList = $equipmentService->filterAndSearchEquipment($status, $location, $search, $category);
} catch (Exception $e) {
    error_log('[Inventory Table Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentList = [];
}
?>

<table class="table table-hover align-middle">
    <thead class="table-primary">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Serial No.</th>
        <th>Model</th>
        <th>Status</th>
        <th>Location</th>
        <th>Installation Date</th>
        <th>Remarks</th>
        <th>QR Code</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($equipmentList)): ?>
        <tr><td colspan="10" class="text-center text-muted">No equipment found.</td></tr>
    <?php else: ?>
        <?php foreach ($equipmentList as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['id']) ?></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['serial_number']) ?></td>
                <td><?= htmlspecialchars($item['model']) ?></td>
                <td>
                    <span class="badge bg-<?= match($item['status']) {
                        'Available' => 'success',
                        'Borrowed' => 'warning',
                        'Maintenance' => 'info',
                        'Repair' => 'danger',
                        'Under Repair' => 'danger',
                        'Disposed' => 'secondary',
                        'Retired' => 'secondary',
                        'Transferred' => 'primary',
                        default => 'dark'
                    } ?>">
                        <?= htmlspecialchars($item['status']) ?>
                        <?php if (in_array($item['status'], ['Borrowed', 'Maintenance', 'Repair'])): ?>
                            <i class="bi bi-lightning-fill" title="Automatic status" style="font-size: 0.8em;"></i>
                        <?php endif; ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($item['location']) ?></td>
                <td>
                    <?php if (!empty($item['installation_date'])): ?>
                        <?= date('M d, Y', strtotime($item['installation_date'])) ?>
                        <br><small class="text-muted">
                            <?php 
                            $installDate = new DateTime($item['installation_date']);
                            $nextMaintenance = clone $installDate;
                            $nextMaintenance->add(new DateInterval('P6M'));
                            $today = new DateTime();
                            $daysUntilMaintenance = $today->diff($nextMaintenance)->days;
                            
                            if ($nextMaintenance < $today) {
                                echo "<span class='text-danger'>Overdue</span>";
                            } elseif ($daysUntilMaintenance <= 30) {
                                echo "<span class='text-warning'>Due in {$daysUntilMaintenance} days</span>";
                            } else {
                                echo "<span class='text-success'>Due in {$daysUntilMaintenance} days</span>";
                            }
                            ?>
                        </small>
                    <?php else: ?>
                        <span class="text-muted">Not set</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['remarks']) ?></td>
                <td class="text-center">
                    <?php $qr = "../uploads/qrcodes/equipment_" . $item['id'] . ".png"; ?>
                    <?php if (file_exists($qr)): ?>
                                       <div class="qr-code-wrapper">
                   <img src="<?= $qr ?>" width="80" 
                        alt="QR Code" class="qr-code-image"
                        onclick="showQRModal('<?= $qr ?>', '<?= htmlspecialchars($item['name']) ?>')"
                        style="cursor: pointer; border: 1px solid #ddd; border-radius: 4px; transition: transform 0.2s;"
                        onmouseover="this.style.transform='scale(1.1)'"
                        onmouseout="this.style.transform='scale(1)'">
               </div>
                    <?php else: ?>
                        <span class="text-muted">No QR</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_equipment.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning me-1">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="inventory.php?archive=<?= $item['id'] ?>" class="btn btn-sm btn-outline-dark"
                       onclick="return confirm('Archive this equipment?');">
                        <i class="bi bi-archive"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
