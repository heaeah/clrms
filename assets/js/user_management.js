// User Management Enhanced JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search and filter functionality
    initializeSearchAndFilter();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize animations
    initializeAnimations();
    
    // Initialize confirmation dialogs
    initializeConfirmations();
});

/**
 * Initialize search and filter functionality
 */
function initializeSearchAndFilter() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const userRows = document.querySelectorAll('.user-row');
    
    if (!searchInput || !roleFilter || !statusFilter) return;
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        filterUsers();
    });
    
    // Role filter functionality
    roleFilter.addEventListener('change', function() {
        filterUsers();
    });
    
    // Status filter functionality
    statusFilter.addEventListener('change', function() {
        filterUsers();
    });
    
    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedRole = roleFilter.value;
        const selectedStatus = statusFilter.value;
        
        let visibleCount = 0;
        
        userRows.forEach(function(row) {
            const name = row.getAttribute('data-name') || '';
            const username = row.getAttribute('data-username') || '';
            const role = row.getAttribute('data-role') || '';
            const status = row.getAttribute('data-status') || '';
            
            // Check search term
            const matchesSearch = searchTerm === '' || 
                name.includes(searchTerm) || 
                username.includes(searchTerm) || 
                role.toLowerCase().includes(searchTerm);
            
            // Check role filter
            const matchesRole = selectedRole === '' || role === selectedRole;
            
            // Check status filter
            const matchesStatus = selectedStatus === '' || status === selectedStatus;
            
            // Show/hide row based on all filters
            if (matchesSearch && matchesRole && matchesStatus) {
                row.style.display = '';
                row.classList.add('fade-in');
                visibleCount++;
            } else {
                row.style.display = 'none';
                row.classList.remove('fade-in');
            }
        });
        
        // Show/hide empty state
        updateEmptyState(visibleCount);
    }
    
    function updateEmptyState(visibleCount) {
        const tableContainer = document.querySelector('.table-container');
        const existingEmptyState = tableContainer.querySelector('.empty-state');
        
        if (visibleCount === 0 && userRows.length > 0) {
            // Show "no results" message
            if (!existingEmptyState) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <i class="bi bi-search"></i>
                    <h3>No Users Found</h3>
                    <p>No users match your current search criteria. Try adjusting your filters.</p>
                `;
                
                const tableResponsive = tableContainer.querySelector('.table-responsive');
                if (tableResponsive) {
                    tableResponsive.style.display = 'none';
                    tableContainer.appendChild(emptyState);
                }
            }
        } else {
            // Hide empty state and show table
            if (existingEmptyState && userRows.length > 0) {
                existingEmptyState.remove();
                const tableResponsive = tableContainer.querySelector('.table-responsive');
                if (tableResponsive) {
                    tableResponsive.style.display = 'block';
                }
            }
        }
    }
}

/**
 * Initialize tooltips for better UX
 */
function initializeTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Initialize animations and visual effects
 */
function initializeAnimations() {
    // Add stagger animation to table rows
    const userRows = document.querySelectorAll('.user-row');
    userRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
    });
    
    // Add hover effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add loading animation to action buttons on click
    const actionButtons = document.querySelectorAll('.btn-action');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Don't add loading if it's a delete action (handled by confirmation)
            if (this.classList.contains('btn-delete')) return;
            
            const originalContent = this.innerHTML;
            this.innerHTML = '<span class="loading"></span> Processing...';
            this.disabled = true;
            
            // Re-enable after a short delay (in case the page doesn't redirect)
            setTimeout(() => {
                this.innerHTML = originalContent;
                this.disabled = false;
            }, 3000);
        });
    });
}

/**
 * Initialize enhanced confirmation dialogs
 */
function initializeConfirmations() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const toggleButtons = document.querySelectorAll('.btn-toggle-active, .btn-toggle-inactive');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userName = this.closest('tr').querySelector('td:nth-child(3)').textContent.trim();
            const confirmMessage = `Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone and will permanently remove all user data.`;
            
            if (confirm(confirmMessage)) {
                // Add loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<span class="loading"></span> Deleting...';
                this.disabled = true;
                
                // Navigate to delete URL
                window.location.href = this.href;
            }
        });
    });
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userName = this.closest('tr').querySelector('td:nth-child(3)').textContent.trim();
            const action = this.classList.contains('btn-toggle-active') ? 'activate' : 'deactivate';
            const confirmMessage = `Are you sure you want to ${action} user "${userName}"?`;
            
            if (confirm(confirmMessage)) {
                // Add loading state
                const originalContent = this.innerHTML;
                this.innerHTML = '<span class="loading"></span> Processing...';
                this.disabled = true;
                
                // Navigate to toggle URL
                window.location.href = this.href;
            }
        });
    });
}

/**
 * Utility function to show notifications
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
 * Export statistics data (optional feature)
 */
function exportUserData() {
    const users = [];
    const userRows = document.querySelectorAll('.user-row');
    
    userRows.forEach(row => {
        if (row.style.display !== 'none') {
            const cells = row.querySelectorAll('td');
            users.push({
                id: cells[0].textContent.trim(),
                name: cells[2].textContent.trim(),
                username: cells[3].textContent.trim(),
                role: cells[4].textContent.trim(),
                status: cells[5].textContent.trim()
            });
        }
    });
    
    // Convert to CSV
    const csvContent = "data:text/csv;charset=utf-8," 
        + "ID,Name,Username,Role,Status\n"
        + users.map(user => `${user.id},"${user.name}","${user.username}","${user.role}","${user.status}"`).join("\n");
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "users_export.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Keyboard shortcuts
 */
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        const searchInput = document.getElementById('searchInput');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        
        if (searchInput && searchInput === document.activeElement) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        }
    }
});

// Add search shortcut hint
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder += ' (Ctrl+K to focus)';
    }
});