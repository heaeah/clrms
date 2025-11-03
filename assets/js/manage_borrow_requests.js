// Wrap all code in DOMContentLoaded to ensure DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manage Borrow Requests JS loaded');
    
    // Pass request ID to Approve modal
    var approveModal = document.getElementById('approveModal');
    if (approveModal) {
        console.log('Approve modal found, attaching event listener');
        approveModal.addEventListener('show.bs.modal', function (event) {
            console.log('Approve modal opening');
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-id');
            var borrowerName = button.getAttribute('data-borrower') || 'this borrower';
            console.log('Request ID:', requestId, 'Borrower:', borrowerName);
            
            var requestIdInput = document.getElementById('approveRequestId');
            if (requestIdInput) {
                requestIdInput.value = requestId;
                console.log('Set approveRequestId input to:', requestIdInput.value);
            } else {
                console.error('approveRequestId input not found');
            }
            
            document.getElementById('approveBorrowerInfo').textContent = 
                'Are you sure you want to approve the borrow request for ' + borrowerName + '?';
        });
    } else {
        console.error('Approve modal not found');
    }
    
    // Add form submit listener to debug
    var approveForm = document.getElementById('approveForm');
    if (approveForm) {
        console.log('Approve form found, attaching submit listener');
        approveForm.addEventListener('submit', function(e) {
            var requestIdValue = document.getElementById('approveRequestId').value;
            var actionValue = document.querySelector('input[name="action"]').value;
            console.log('Form submitting with request_id:', requestIdValue, 'action:', actionValue);
            
            // Validate that request_id is set
            if (!requestIdValue || requestIdValue === '') {
                e.preventDefault();
                alert('Error: Request ID is missing. Please close the modal and try again.');
                console.error('Form submission prevented: request_id is empty');
                return false;
            }
        });
    } else {
        console.error('Approve form not found');
    }

    // Pass request ID to Deny modal
    var denyModal = document.getElementById('denyModal');
    if (denyModal) {
        denyModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-id');
            document.getElementById('denyRequestId').value = requestId;
        });
    }

    // Pass request ID to Return modal and set default date/time
    var returnModal = document.getElementById('returnModal');
    if (returnModal) {
        returnModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var requestId = button.getAttribute('data-id');
            document.getElementById('returnRequestId').value = requestId;
            // Set default date and time to now
            var now = new Date();
            document.getElementById('returnDate').value = now.toISOString().slice(0,10);
            document.getElementById('returnTime').value = now.toTimeString().slice(0,5);
        });
    }
}); 