document.addEventListener('DOMContentLoaded', function() {
    var trackForm = document.getElementById('trackRequestForm');
    if (trackForm) {
        trackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var codeInput = document.getElementById('trackingCodeInput');
            var code = codeInput.value.trim();
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
                        // Format the date properly (when request was made)
                        const dateReserved = new Date(data.reservation.date_reserved);
                        const formattedDate = dateReserved.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        
                        // Format datetime properly
                        const formatDateTime = (dateTimeStr) => {
                            if (!dateTimeStr) return 'N/A';
                            const dateTime = new Date(dateTimeStr);
                            return dateTime.toLocaleString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });
                        };
                        
                        const startDateTime = formatDateTime(data.reservation.reservation_start);
                        const endDateTime = formatDateTime(data.reservation.reservation_end);
                        
                        html = `<div class='card mb-3'><div class='card-body'>
                            <h5 class='card-title'>Lab Reservation Status</h5>
                            <p><strong>Control #:</strong> ${data.reservation.control_number || 'N/A'}</p>
                            <p><strong>Lab:</strong> ${data.reservation.lab_name || 'N/A'}</p>
                            <p><strong>Date Requested:</strong> ${formattedDate}</p>
                            <p><strong>Reservation Start Date/Time:</strong> ${startDateTime}</p>
                            <p><strong>Reservation End Date/Time:</strong> ${endDateTime}</p>
                            <p><strong>Requested By:</strong> ${data.reservation.requested_by || 'N/A'}</p>
                            <p><strong>Email:</strong> ${data.reservation.borrower_email || 'N/A'}</p>
                            <p><strong>Purpose:</strong> ${data.reservation.purpose || 'N/A'}</p>
                            <p><strong>Needed Tools:</strong> ${data.reservation.needed_tools || 'N/A'}</p>
                            <p><strong>Status:</strong> <span class='badge bg-${statusColor(data.reservation.status)}'>${data.reservation.status || 'Pending'}</span></p>
                            ${data.reservation.remarks ? `<p><strong>Remarks:</strong> ${data.reservation.remarks}</p>` : ''}
                        </div></div>`;
                    }
                    document.getElementById('trackResult').innerHTML = html;
                });
        });
    }
    function statusColor(status) {
        if (status === 'Approved') return 'success';
        if (status === 'Rejected' || status === 'Denied') return 'danger';
        if (status === 'Pending') return 'warning';
        return 'secondary';
    }
}); 