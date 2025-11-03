<?php
require_once '../vendor/dompdf/dompdf/autoload.inc.php';
require_once '../classes/EquipmentService.php';

use Dompdf\Dompdf;

$equipmentService = new EquipmentService();
try {
    /**
     * What: Fetch all equipment
     * Why: For export
     * How: Uses EquipmentService::getAllEquipment
     */
    $equipmentList = $equipmentService->getAllEquipment();
} catch (Exception $e) {
    error_log('[Export Inventory Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentList = [];
}

$html = '<h1>Equipment Inventory Report</h1>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Serial No.</th>
    <th>Model</th>
    <th>Status</th>
    <th>Location</th>
</tr>';

foreach ($equipmentList as $equip) {
    $html .= '<tr>
        <td>'.$equip['id'].'</td>
        <td>'.htmlspecialchars($equip['name']).'</td>
        <td>'.htmlspecialchars($equip['serial_number']).'</td>
        <td>'.htmlspecialchars($equip['model']).'</td>
        <td>'.$equip['status'].'</td>
        <td>'.htmlspecialchars($equip['location']).'</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('inventory_report.pdf', ["Attachment" => true]);
?>
