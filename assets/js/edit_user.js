// Edit User Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeEditUserPage();
    setupFormValidation();
    setupRoleChangeHandler();
    setupFormSubmission();
});

function initializeEditUserPage() {
    console.log('Edit User page initialized');
    
    // Add fade-in animation to the main card
    const mainCard = document.querySelector('.card');
    if (mainCard) {
        mainCard.style.opacity = '0';
        mainCard.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            mainCard.style.transition = 'all 0.6s ease';
            mainCard.style.opacity = '1';
            mainCard.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Add animation to permission items
    const permissionItems = document.querySelectorAll('.permission-item');
    permissionItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.4s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 300 + (index * 100));
    });
}

function setupFormValidation() {
    const form = document.getElementById('editUserForm');
    const nameInput = document.getElementById('name');
    const roleSelect = document.getElementById('role');
    
    // Real-time validation for name field
    nameInput.addEventListener('input', function() {
        const value = this.value.trim();
        
        // Remove existing validation classes
        this.classList.remove('is-valid', 'is-invalid');
        
        if (value.length === 0) {
            this.classList.add('is-invalid');
            showFieldError(this, 'Name is required');
        } else if (value.length < 2) {
            this.classList.add('is-invalid');
            showFieldError(this, 'Name must be at least 2 characters');
        } else if (!/^[a-zA-Z\s]+$/.test(value)) {
            this.classList.add('is-invalid');
            showFieldError(this, 'Name can only contain letters and spaces');
        } else {
            this.classList.add('is-valid');
            hideFieldError(this);
        }
    });
    
    // Real-time validation for role field
    roleSelect.addEventListener('change', function() {
        const value = this.value;
        
        // Remove existing validation classes
        this.classList.remove('is-valid', 'is-invalid');
        
        if (value === '') {
            this.classList.add('is-invalid');
            showFieldError(this, 'Please select a role');
        } else {
            this.classList.add('is-valid');
            hideFieldError(this);
        }
    });
}

function setupRoleChangeHandler() {
    const roleSelect = document.getElementById('role');
    const currentRole = roleSelect.value;
    
    // Highlight current role option
    if (currentRole) {
        const currentOption = roleSelect.querySelector(`option[value="${currentRole}"]`);
        if (currentOption) {
            currentOption.style.backgroundColor = '#e3f2fd';
            currentOption.style.fontWeight = 'bold';
        }
    }
    
    // Handle role change
    roleSelect.addEventListener('change', function() {
        const selectedRole = this.value;
        
        // Show role change confirmation if different from original
        if (selectedRole !== currentRole) {
            showRoleChangeWarning(selectedRole);
        }
        
        // Update role badge in the header
        updateRoleBadge(selectedRole);
    });
}

function setupFormSubmission() {
    const form = document.getElementById('editUserForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form before submission
        if (!validateForm()) {
            showNotification('Please fix the errors before submitting', 'danger');
            return;
        }
        
        // Show loading state
        showLoadingState();
        
        // Submit form
        setTimeout(() => {
            form.submit();
        }, 500);
    });
}

function validateForm() {
    const nameInput = document.getElementById('name');
    const roleSelect = document.getElementById('role');
    
    let isValid = true;
    
    // Validate name
    const nameValue = nameInput.value.trim();
    if (nameValue.length === 0) {
        nameInput.classList.add('is-invalid');
        showFieldError(nameInput, 'Name is required');
        isValid = false;
    } else if (nameValue.length < 2) {
        nameInput.classList.add('is-invalid');
        showFieldError(nameInput, 'Name must be at least 2 characters');
        isValid = false;
    } else {
        nameInput.classList.add('is-valid');
        hideFieldError(nameInput);
    }
    
    // Validate role
    const roleValue = roleSelect.value;
    if (roleValue === '') {
        roleSelect.classList.add('is-invalid');
        showFieldError(roleSelect, 'Please select a role');
        isValid = false;
    } else {
        roleSelect.classList.add('is-valid');
        hideFieldError(roleSelect);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    hideFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.id = `${field.id}-error`;
    
    field.parentNode.appendChild(errorDiv);
}

function hideFieldError(field) {
    const existingError = document.getElementById(`${field.id}-error`);
    if (existingError) {
        existingError.remove();
    }
}

function showRoleChangeWarning(newRole) {
    const roleNames = {
        'Admin': 'Administrator',
        'Lab Admin': 'Laboratory Administrator',
        'Faculty Borrower': 'Faculty Borrower',
        'Chairperson': 'Chairperson',
        'ICT Staff': 'ICT Staff'
    };
    
    const currentRole = document.getElementById('role').getAttribute('data-original-role') || 
                       document.querySelector('option[selected]').value;
    
    if (confirm(`Are you sure you want to change the role from "${roleNames[currentRole]}" to "${roleNames[newRole]}"?\n\nThis will affect the user's permissions and access to system features.`)) {
        // Role change confirmed
        console.log(`Role changed from ${currentRole} to ${newRole}`);
    } else {
        // Revert selection
        document.getElementById('role').value = currentRole;
    }
}

function updateRoleBadge(role) {
    const badge = document.querySelector('.badge');
    if (badge) {
        const roleColors = {
            'Admin': 'danger',
            'Lab Admin': 'primary',
            'Faculty Borrower': 'success',
            'Chairperson': 'warning',
            'ICT Staff': 'info'
        };
        
        badge.className = `badge bg-${roleColors[role] || 'secondary'} fs-6`;
        badge.textContent = role;
        
        // Add animation
        badge.classList.add('success-animation');
        setTimeout(() => {
            badge.classList.remove('success-animation');
        }, 600);
    }
}

function showLoadingState() {
    const form = document.getElementById('editUserForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    form.classList.add('form-submitting');
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Updating...';
    submitBtn.disabled = true;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S: Save form
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('editUserForm').dispatchEvent(new Event('submit'));
    }
    
    // Escape: Cancel/Go back
    if (e.key === 'Escape') {
        if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
            window.location.href = 'user_management.php';
        }
    }
});

// Auto-save functionality (optional)
let autoSaveTimeout;
function setupAutoSave() {
    const form = document.getElementById('editUserForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                // Auto-save logic here (if needed)
                console.log('Auto-save triggered');
            }, 2000);
        });
    });
}

// Initialize auto-save
setupAutoSave();

// Form change detection
let formChanged = false;
function setupChangeDetection() {
    const form = document.getElementById('editUserForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            formChanged = true;
        });
    });
    
    // Warn before leaving if form has changes
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}

// Initialize change detection
setupChangeDetection();