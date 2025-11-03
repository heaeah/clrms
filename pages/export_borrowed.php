<?php
require_once '../vendor/dompdf/dompdf/autoload.inc.php';
require_once '../classes/BorrowRequest.php';

use Dompdf\Dompdf;

$borrowObj = new BorrowRequest();
$borrowList = $borrowObj->getAllBorrowRequests();

$html = '<h1>Borrowed Equipment Report</h1>';
$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<tr>
    <th>Borrower</th>
    <th>Equipment</th>
    <th>Purpose</th>
    <th>Location</th>
    <th>Borrow Date</th>
    <th>Return Date</th>
    <th>Status</th>
</tr>';

foreach ($borrowList as $req) {
    $html .= '<tr>
        <td>'.htmlspecialchars($req['username']).'</td>
        <td>'.htmlspecialchars($req['equipment_name']).'</td>
        <td>'.htmlspecialchars($req['purpose']).'</td>
        <td>'.htmlspecialchars($req['location_of_use']).'</td>
        <td>'.$req['borrow_date'].'</td>
        <td>'.$req['return_date'].'</td>
        <td>'.$req['status'].'</td>
    </tr>';
}

$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('borrowed_report.pdf', ["Attachment" => true]);
?>
