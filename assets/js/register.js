// Enhanced Manage User JavaScript
// Note: Main DOMContentLoaded listener is at the bottom of this file

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const form = document.getElementById('userForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', function(e) {
            // Clear validation classes first
            e.target.classList.remove('is-valid', 'is-invalid');
            
            // If field has content, validate it immediately
            if (e.target.value.trim() !== '') {
                validateField(e);
            }
        });
        
        // Add special handling for mobile number field
        if (input.name === 'mobile_number') {
            input.addEventListener('keypress', restrictToNumbers);
            input.addEventListener('paste', handlePaste);
            input.addEventListener('input', restrictToNumbersOnly);
        }
    });
    
    function validateField(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Clear previous validation
        field.classList.remove('is-valid', 'is-invalid');
        
        // If field is empty, don't show validation state
        if (value === '') {
            return;
        }
        
        switch(field.name) {
            case 'name':
                if (value.length >= 2) {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
                break;
                
            case 'username':
                if (value.length >= 3 && /^[a-zA-Z0-9_]+$/.test(value)) {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
                break;
                
            case 'email':
                if (isValidEmail(value)) {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
                break;
                
            case 'mobile_number':
                if (isValidPhone(value)) {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
                break;
                
            case 'password':
                validatePassword(field);
                break;
                
            case 'confirm_password':
                validateConfirmPassword(field);
                break;
                
            case 'role':
                if (value !== '') {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
                break;
        }
    }
    
    
    function restrictToNumbers(e) {
        // Allow backspace, delete, tab, escape, enter
        if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
            // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
        
        // Limit to 11 digits
        if (e.target.value.length >= 11) {
            e.preventDefault();
        }
    }
    
    function handlePaste(e) {
        e.preventDefault();
        let paste = (e.clipboardData || window.clipboardData).getData('text');
        
        // Remove all non-numeric characters
        paste = paste.replace(/[^0-9]/g, '');
        
        // Limit to 11 digits
        if (paste.length > 11) {
            paste = paste.substring(0, 11);
        }
        
        e.target.value = paste;
        
        // Trigger validation
        e.target.dispatchEvent(new Event('blur'));
    }
    
    function restrictToNumbersOnly(e) {
        // Remove any non-numeric characters that might have been entered
        const value = e.target.value.replace(/[^0-9]/g, '');
        
        // Limit to 11 digits
        const limitedValue = value.substring(0, 11);
        
        if (e.target.value !== limitedValue) {
            e.target.value = limitedValue;
        }
    }
}

/**
 * Initialize password strength checker
 */
function initializePasswordStrength() {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const strengthIndicator = document.getElementById('passwordStrength');
    const requirements = document.getElementById('passwordRequirements');
    
    passwordField.addEventListener('input', function() {
        const password = this.value;
        checkPasswordStrength(password);
        updatePasswordRequirements(password);
        
        // Also validate confirm password if it has a value
        if (confirmPasswordField.value) {
            validateConfirmPassword(confirmPasswordField);
        }
    });
    
    confirmPasswordField.addEventListener('input', function() {
        validateConfirmPassword(this);
    });
    
    function checkPasswordStrength(password) {
        let strength = 0;
        const checks = [
            password.length >= 8,
            /[a-z]/.test(password),
            /[A-Z]/.test(password),
            /[0-9]/.test(password),
            /[@$!%*?&]/.test(password)
        ];
        
        strength = checks.filter(check => check).length;
        
        // Update strength indicator
        strengthIndicator.className = 'password-strength';
        if (strength >= 4) {
            strengthIndicator.classList.add('strong');
        } else if (strength >= 2) {
            strengthIndicator.classList.add('medium');
        } else if (strength >= 1) {
            strengthIndicator.classList.add('weak');
        }
    }
    
    function updatePasswordRequirements(password) {
        const lengthReq = document.getElementById('length');
        const uppercaseReq = document.getElementById('uppercase');
        const lowercaseReq = document.getElementById('lowercase');
        const numberReq = document.getElementById('number');
        const specialReq = document.getElementById('special');
        
        // Length check
        if (password.length >= 8) {
            lengthReq.className = 'valid';
        } else {
            lengthReq.className = 'invalid';
        }
        
        // Uppercase check
        if (/[A-Z]/.test(password)) {
            uppercaseReq.className = 'valid';
        } else {
            uppercaseReq.className = 'invalid';
        }
        
        // Lowercase check
        if (/[a-z]/.test(password)) {
            lowercaseReq.className = 'valid';
        } else {
            lowercaseReq.className = 'invalid';
        }
        
        // Number check
        if (/[0-9]/.test(password)) {
            numberReq.className = 'valid';
        } else {
            numberReq.className = 'invalid';
        }
        
        // Special character check
        if (/[@$!%*?&]/.test(password)) {
            specialReq.className = 'valid';
        } else {
            specialReq.className = 'invalid';
        }
    }
}

/**
 * Validate password field
 */
function validatePassword(field) {
    const password = field.value;
    const requirements = [
        password.length >= 8,
        /[a-z]/.test(password),
        /[A-Z]/.test(password),
        /[0-9]/.test(password),
        /[@$!%*?&]/.test(password)
    ];
    
    if (requirements.every(req => req)) {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    } else {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    }
}

/**
 * Validate confirm password field
 */
function validateConfirmPassword(field) {
    const password = document.getElementById('password').value;
    const confirmPassword = field.value;
    
    if (confirmPassword === password && password.length > 0) {
        field.classList.add('is-valid');
        field.classList.remove('is-invalid');
    } else {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
    }
}

/**
 * Email validation
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Phone validation - exactly 11 digits
 */
function isValidPhone(phone) {
    const phoneRegex = /^[0-9]{11}$/;
    return phoneRegex.test(phone);
}

/**
 * Initialize animations
 */
function initializeAnimations() {
    // Add stagger animation to form groups
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach((group, index) => {
        group.style.animationDelay = `${index * 0.1}s`;
        group.classList.add('slide-in');
    });
    
    // Add focus animations to form controls
    const formControls = document.querySelectorAll('.form-control, .form-select');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
}

/**
 * Initialize form submission
 */
function initializeFormSubmission() {
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all fields
        const isValid = validateAllFields();
        
        if (isValid) {
            // Show loading state
            showLoadingState(submitBtn);
            
            // Submit form after a short delay to show loading animation
            setTimeout(() => {
                form.submit();
            }, 500);
        } else {
            // Show error message
            showNotification('Please fix the errors in the form before submitting.', 'danger');
            
            // Focus on first invalid field
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    function validateAllFields() {
        const inputs = form.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            // Trigger validation
            input.dispatchEvent(new Event('blur'));
            
            if (input.classList.contains('is-invalid') || (!input.classList.contains('is-valid') && input.hasAttribute('required'))) {
                isValid = false;
            }
        });
        
        // Special validation for password match
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            document.getElementById('confirmPassword').classList.add('is-invalid');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showLoadingState(button) {
        const originalContent = button.innerHTML;
        button.innerHTML = '<span class="loading"></span> Creating Account...';
        button.disabled = true;
        
        // Store original content for potential restoration
        button.dataset.originalContent = originalContent;
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        <i class="bi bi-${type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

/**
 * Real-time username availability check (optional feature)
 */
function checkUsernameAvailability(username) {
    if (username.length < 3) return;
    
    // This would typically make an AJAX call to check username availability
    // For now, we'll just simulate it
    setTimeout(() => {
        const usernameField = document.querySelector('input[name="username"]');
        // Simulate random availability
        const isAvailable = Math.random() > 0.3;
        
        if (isAvailable) {
            usernameField.classList.add('is-valid');
            usernameField.classList.remove('is-invalid');
        } else {
            usernameField.classList.add('is-invalid');
            usernameField.classList.remove('is-valid');
            // Could show a specific message about username being taken
        }
    }, 500);
}

/**
 * Form auto-save (optional feature)
 */
function initializeAutoSave() {
    const form = document.getElementById('userForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', debounce(saveFormData, 1000));
    });
    
    function saveFormData() {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== 'password' && key !== 'confirm_password') {
                data[key] = value;
            }
        }
        
        localStorage.setItem('userFormData', JSON.stringify(data));
    }
    
    function loadFormData() {
        const savedData = localStorage.getItem('userFormData');
        if (savedData) {
            const data = JSON.parse(savedData);
            
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = data[key];
                }
            });
        }
    }
    
    // Load saved data on page load
    loadFormData();
    
    // Clear saved data on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem('userFormData');
    });
}

/**
 * Debounce utility function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize tab switching functionality
 */
function initializeTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    console.log('Tab buttons found:', tabButtons.length);
    console.log('Tab contents found:', tabContents.length);
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            console.log('Clicked tab:', targetTab);
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const targetContent = document.getElementById(targetTab + '-tab');
            console.log('Target content element:', targetContent);
            
            if (targetContent) {
                targetContent.classList.add('active');
                console.log('Successfully switched to tab:', targetTab);
            } else {
                console.error('Target tab content not found:', targetTab + '-tab');
            }
        });
    });
}

/**
 * Initialize user management functionality
 */
function initializeUserManagement() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    const userRows = document.querySelectorAll('.user-row');
    
    if (!searchInput || !roleFilter || !statusFilter) return;
    
    // Search functionality
    searchInput.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
    statusFilter.addEventListener('change', filterUsers);
    
    // Clear filters functionality
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            console.log('Clearing all filters...');
            
            // Reset all filter inputs
            searchInput.value = '';
            roleFilter.value = '';
            statusFilter.value = '';
            
            // Show all rows
            userRows.forEach(row => {
                row.style.display = '';
            });
            
            // Visual feedback
            showNotification('All filters cleared', 'success');
            
            // Focus back to search input
            searchInput.focus();
        });
    }
    
    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value;
        const selectedStatus = statusFilter.value;
        
        let visibleCount = 0;
        
        userRows.forEach(row => {
            const name = row.getAttribute('data-name');
            const username = row.getAttribute('data-username');
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');
            
            const matchesSearch = name.includes(searchTerm) || 
                                username.includes(searchTerm) || 
                                role.toLowerCase().includes(searchTerm);
            const matchesRole = !selectedRole || role === selectedRole;
            const matchesStatus = !selectedStatus || status === selectedStatus;
            
            if (matchesSearch && matchesRole && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        console.log('Filtered users:', visibleCount, 'visible out of', userRows.length);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Register.js loaded - initializing...');
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize password strength checker
    initializePasswordStrength();
    
    // Initialize form animations
    initializeAnimations();
    
    // Initialize form submission
    initializeFormSubmission();
    
    // Initialize tab switching
    initializeTabSwitching();
    
    // Initialize user management (search and filter)
    initializeUserManagement();
    
    console.log('Register.js initialization complete');
});