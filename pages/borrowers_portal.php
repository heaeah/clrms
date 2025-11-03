<?php
// Borrowers Portal: Access to Borrower's Slip and Lab Request forms
$portalUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/borrowers_portal.php";
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($portalUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowers Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/borrowers_portal.css" rel="stylesheet">
</head>
<body>
<div class="portal-box">
    <h2>Borrowers Portal</h2>
    <p>Scan the QR code below or use the links to access forms:</p>
    <img src="<?php echo $qrCodeUrl; ?>" alt="Borrowers Portal QR Code" class="qr-img" width="200" height="200">
    <div>
        <a href="borrower_slip_form.php?from=portal" class="btn btn-primary portal-link w-100 mb-2">Borrower's Slip Form</a>
        <a href="reserve_lab.php?from=portal" class="btn btn-success portal-link w-100 mb-2">Request Form (Use of Computer Labs)</a>
        <button type="button" class="btn btn-info portal-link w-100 mb-2" id="trackRequestBtn">Track Request</button>
    </div>
</div>
<!-- Track Request Modal -->
<div class="modal fade" id="trackModal" tabindex="-1" aria-labelledby="trackModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="trackModalLabel">Track My Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="trackRequestForm" class="mb-4 d-flex flex-wrap align-items-center gap-2">
            <input type="text" id="trackingCodeInput" class="form-control" placeholder="Enter your tracking code" maxlength="20" style="max-width:250px;" required>
            <button type="submit" class="btn btn-primary">Track</button>
        </form>
        <div id="trackResult"></div>
      </div>
    </div>
  </div>
</div>
<!-- End of Track Request Modal -->
<script src="../assets/js/borrowers_portal.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fetchMyRequests() {
    fetch('api/my_requests.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            // Borrow Requests
            let brRows = '';
            data.borrow_requests.forEach(r => {
                brRows += `<tr><td>${r.control_number}</td><td>${r.date_requested}</td><td>${r.purpose}</td><td>${r.location_of_use}</td><td>${statusBadge(r.status)}</td></tr>`;
            });
            document.querySelector('#myBorrowRequestsTable tbody').innerHTML = brRows || '<tr><td colspan="5" class="text-muted text-center">No borrow requests found.</td></tr>';
            // Lab Reservations
            let lrRows = '';
            data.lab_reservations.forEach(r => {
                lrRows += `<tr><td>${r.lab_name}</td><td>${r.date_reserved}</td><td>${r.time_start} - ${r.time_end}</td><td>${r.purpose}</td><td>${statusBadge(r.status)}</td></tr>`;
            });
            document.querySelector('#myLabReservationsTable tbody').innerHTML = lrRows || '<tr><td colspan="5" class="text-muted text-center">No lab reservations found.</td></tr>';
        });
}
function statusBadge(status) {
    let color = 'secondary';
    if (status === 'Approved') color = 'success';
    else if (status === 'Rejected') color = 'danger';
    else if (status === 'Pending') color = 'warning';
    return `<span class="badge bg-${color}">${status}</span>`;
}
document.getElementById('trackRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const code = document.getElementById('trackingCodeInput').value.trim();
    if (!code) return;
    fetch('api/track_request.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (!data.success) {
                html = `<div class='alert alert-danger'>${data.message || 'No request found for this code.'}</div>`;
            } else if (data.type === 'borrow') {
                html = `<div class='card mb-3'><div class='card-body'>
                    <h5 class='card-title'>Borrow Request Status</h5>
                    <p><strong>Control #:</strong> ${data.request.control_number}</p>
                    <p><strong>Date:</strong> ${data.request.date_requested}</p>
                    <p><strong>Borrower:</strong> ${data.request.borrower_name}</p>
                    <p><strong>Email:</strong> ${data.request.borrower_email}</p>
                    <p><strong>Purpose:</strong> ${data.request.purpose}</p>
                    <p><strong>Location:</strong> ${data.request.location_of_use}</p>
                    <p><strong>Status:</strong> <span class='badge bg-${statusColor(data.request.status)}'>${data.request.status}</span></p>
                    ${data.request.remarks ? `<p><strong>Remarks:</strong> ${data.request.remarks}</p>` : ''}
                    <h6 class='mt-3'>Items Borrowed:</h6>
                    <ul>${(data.request.items||[]).map(i=>`<li>${i.name} (Qty: ${i.quantity})</li>`).join('') || '<li>No items found.</li>'}</ul>
                </div></div>`;
            } else if (data.type === 'lab') {
                // Format the date properly
                const dateReserved = new Date(data.reservation.date_reserved);
                const formattedDate = dateReserved.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                // Format times properly
                const formatTime = (timeStr) => {
                    if (!timeStr) return 'N/A';
                    const time = new Date(`2000-01-01T${timeStr}`);
                    return time.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                };
                
                const startTime = formatTime(data.reservation.time_start);
                const endTime = formatTime(data.reservation.time_end);
                
                html = `<div class='card mb-3'><div class='card-body'>
                    <h5 class='card-title'>Lab Reservation Status</h5>
                    <p><strong>Control #:</strong> ${data.reservation.control_number || 'N/A'}</p>
                    <p><strong>Lab:</strong> ${data.reservation.lab_name || 'N/A'}</p>
                    <p><strong>Date Reserved:</strong> ${formattedDate}</p>
                    <p><strong>Time:</strong> ${startTime} - ${endTime}</p>
                    <p><strong>Requested By:</strong> ${data.reservation.requested_by || 'N/A'}</p>
                    <p><strong>Email:</strong> ${data.reservation.borrower_email || 'N/A'}</p>
                    <p><strong>Purpose:</strong> ${data.reservation.purpose || 'N/A'}</p>
                    <p><strong>Needed Tools:</strong> ${data.reservation.needed_tools || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class='badge bg-${statusColor(data.reservation.status)}'>${data.reservation.status || 'Pending'}</span></p>
                    ${data.reservation.remarks ? `<p><strong>Remarks:</strong> ${data.reservation.remarks}</p>` : ''}
                    
                    <h6 class='mt-3'>Submitted Documents:</h6>
                    <div class='row'>
                        <div class='col-md-6'>
                            ${data.reservation.approved_letter ? 
                                `<a href="../${data.reservation.approved_letter}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                    <i class="bi bi-file-earmark-text me-1"></i>View Approved Letter
                                </a>` : 
                                '<span class="text-muted small">No approved letter uploaded</span>'
                            }
                        </div>
                        <div class='col-md-6'>
                            ${data.reservation.id_photo ? 
                                `<a href="../${data.reservation.id_photo}" target="_blank" class="btn btn-sm btn-outline-info mb-2">
                                    <i class="bi bi-person-badge me-1"></i>View ID Photo
                                </a>` : 
                                '<span class="text-muted small">No ID photo uploaded</span>'
                            }
                        </div>
                    </div>
                </div></div>`;
            }
            document.getElementById('trackResult').innerHTML = html;
        });
});
function statusColor(status) {
    if (status === 'Approved') return 'success';
    if (status === 'Rejected' || status === 'Denied') return 'danger';
    if (status === 'Pending') return 'warning';
    return 'secondary';
}
fetchMyRequests();
setInterval(fetchMyRequests, 15000);
document.getElementById('trackRequestBtn').addEventListener('click', function() {
    var modal = new bootstrap.Modal(document.getElementById('trackModal'));
    modal.show();
    setTimeout(function() {
        document.getElementById('trackingCodeInput').focus();
    }, 300);
});
</script>
</body>
</html> 