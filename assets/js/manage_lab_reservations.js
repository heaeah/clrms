// Wrap all code in DOMContentLoaded to ensure DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manage Lab Reservations JS loaded');
    
    // Pass reservation ID to Approve modal
    var approveModal = document.getElementById('approveModal');
    if (approveModal) {
        console.log('Approve modal found, attaching event listener');
        approveModal.addEventListener('show.bs.modal', function (event) {
            console.log('Approve modal opening');
            var button = event.relatedTarget;
            var reservationId = button.getAttribute('data-id');
            var borrowerName = button.getAttribute('data-borrower') || 'this requester';
            console.log('Reservation ID:', reservationId, 'Borrower:', borrowerName);
            
            var reservationIdInput = document.getElementById('approveReservationId');
            if (reservationIdInput) {
                reservationIdInput.value = reservationId;
                console.log('Set approveReservationId input to:', reservationIdInput.value);
            } else {
                console.error('approveReservationId input not found');
            }
            
            document.getElementById('approveBorrowerInfo').textContent = 
                'Are you sure you want to approve the lab reservation for ' + borrowerName + '?';
        });
    } else {
        console.error('Approve modal not found');
    }
    
    // Add form submit listener to debug
    var approveForm = document.querySelector('#approveModal form');
    if (approveForm) {
        console.log('Approve form found, attaching submit listener');
        approveForm.addEventListener('submit', function(e) {
            var reservationIdValue = document.getElementById('approveReservationId').value;
            var actionValue = document.querySelector('#approveModal input[name="action"]').value;
            console.log('Form submitting with reservation_id:', reservationIdValue, 'action:', actionValue);
            
            // Validate that reservation_id is set
            if (!reservationIdValue || reservationIdValue === '') {
                e.preventDefault();
                alert('Error: Reservation ID is missing. Please close the modal and try again.');
                console.error('Form submission prevented: reservation_id is empty');
                return false;
            }
        });
    } else {
        console.error('Approve form not found');
    }

    // Pass reservation ID to Deny modal
    var denyModal = document.getElementById('denyModal');
    if (denyModal) {
        denyModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var reservationId = button.getAttribute('data-id');
            document.getElementById('denyReservationId').value = reservationId;
        });
    }
}); 