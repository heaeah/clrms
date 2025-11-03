// Maintenance Page JavaScript
console.log('=== MAINTENANCE.JS LOADED ===');

document.addEventListener('DOMContentLoaded', function() {
    console.log('Maintenance page initialized');
    
    // Auto-submit filters when changed
    const filterSelects = document.querySelectorAll('#type, #status');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // Small delay to allow user to see the change
            setTimeout(() => {
                this.closest('form').submit();
            }, 100);
        });
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add hover effects to action buttons
    const actionButtons = document.querySelectorAll('.btn-group .btn');
    actionButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Form validation for add maintenance modal
    const addForm = document.querySelector('#addMaintenanceModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
    
    // Auto-set maintenance date to today
    const maintenanceDateField = document.querySelector('input[name="maintenance_date"]');
    if (maintenanceDateField && !maintenanceDateField.value) {
        maintenanceDateField.value = new Date().toISOString().split('T')[0];
    }
    
    // Equipment selection change handler
    const equipmentSelect = document.querySelector('select[name="equipment_id"]');
    if (equipmentSelect) {
        equipmentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                console.log('Selected equipment:', selectedOption.text);
            }
        });
    }
    
    // Status change handler
    const statusSelect = document.querySelector('select[name="repair_status"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            console.log('Status changed to:', this.value);
        });
    }
    
    // File upload preview (if needed)
    const photoInput = document.querySelector('input[name="photo"]');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                console.log('Photo selected:', file.name);
                // You can add image preview functionality here if needed
            }
        });
    }
    
    // Clear filters function
    window.clearFilters = function() {
        document.querySelector('#type').value = '';
        document.querySelector('#status').value = '';
        document.querySelector('#type').closest('form').submit();
    };
    

    
    // Let the existing sidebar system handle responsiveness naturally
    // No custom JavaScript needed - the sidebar has its own built-in functionality
    
    console.log('Maintenance page setup complete');
}); 