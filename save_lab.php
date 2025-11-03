$link = "http://localhost/clrms/pages/lab_details.php?id=" . $lastId;
$qrPath = "../uploads/qrcodes/lab_" . $lastId . ".png";
QRcode::png($link, $qrPath, QR_ECLEVEL_L, 5);
