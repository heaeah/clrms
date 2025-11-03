<?php
require_once '../vendor/dompdf/dompdf/autoload.inc.php';
require_once '../classes/LabReservation.php';

use Dompdf\Dompdf;

$reservationObj = new LabReservation();
$reservationList = $reservationObj->getAllReservations();

$html = '<h1>Lab Reservations Report</h1>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<tr>
    <th>Requester</th>
    <th>Lab</th>
    <th>Date Reserved</th>
    <th>Time Start</th>
    <th>Time End</th>
    <th>Purpose</th>
    <th>Status</th>
</tr>';

foreach ($reservationList as $res) {
    $html .= '<tr>
        <td>'.htmlspecialchars($res['username']).'</td>
        <td>'.htmlspecialchars($res['lab_name']).'</td>
        <td>'.$res['date_reserved'].'</td>
        <td>'.$res['time_start'].'</td>
        <td>'.$res['time_end'].'</td>
        <td>'.htmlspecialchars($res['purpose']).'</td>
        <td>'.$res['status'].'</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('reservations_report.pdf', ["Attachment" => true]);
?>
