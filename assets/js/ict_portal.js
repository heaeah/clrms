// ICT Portal JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize ICT Portal
    initializeICTPortal();
    
    // Setup sidebar toggle
    setupSidebarToggle();
    
    // Setup real-time updates
    setupRealTimeUpdates();
    
    // Setup quick actions
    setupQuickActions();
    
    // Setup alerts
    setupAlerts();
});

function initializeICTPortal() {
    console.log('ICT Portal initialized');
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.kpi-card, .quick-action, .alert-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function setupSidebarToggle() {
    console.log('Setting up ICT sidebar toggle');
    
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarLogoToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar) {
        console.log('ICT Sidebar: Sidebar not found');
        return;
    }
    
    console.log('ICT Sidebar: Elements found', {
        sidebar: !!sidebar,
        sidebarToggle: !!sidebarToggle,
        sidebarOverlay: !!sidebarOverlay,
        mainContent: !!mainContent
    });
    
    // Initialize sidebar state from localStorage (ICT specific key)
    const sidebarCollapsed = localStorage.getItem('ictSidebarCollapsed');
    
    if (window.innerWidth >= 768) {
        // Desktop mode - check localStorage for state
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('show');
            if (mainContent) {
                mainContent.classList.add('sidebar-collapsed');
            }
        } else {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('show');
            if (mainContent) {
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
        console.log('ICT Sidebar: Desktop mode - sidebar state:', sidebarCollapsed);
    } else {
        // Mobile mode - start closed
        sidebar.classList.add('collapsed');
        sidebar.classList.remove('show');
        if (mainContent) {
            mainContent.classList.add('sidebar-collapsed');
        }
        console.log('ICT Sidebar: Mobile mode - sidebar closed');
    }
    
    // Toggle sidebar function
    function toggleSidebar() {
        console.log('ICT Sidebar: Toggle clicked');
        if (sidebar) {
            const isCollapsed = sidebar.classList.contains('collapsed');
            console.log('ICT Sidebar: Current state - collapsed:', isCollapsed);
            
            if (isCollapsed) {
                // Open sidebar
                console.log('ICT Sidebar: Opening sidebar');
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('show');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-collapsed');
                }
                if (sidebarOverlay) {
                    sidebarOverlay.style.display = 'block';
                    sidebarOverlay.classList.add('show');
                }
                // Store state
                localStorage.setItem('ictSidebarCollapsed', 'false');
                console.log('ICT Sidebar: Sidebar opened, classes:', sidebar.className);
            } else {
                // Close sidebar
                console.log('ICT Sidebar: Closing sidebar');
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
                if (mainContent) {
                    mainContent.classList.add('sidebar-collapsed');
                }
                if (sidebarOverlay) {
                    sidebarOverlay.style.display = 'none';
                    sidebarOverlay.classList.remove('show');
                }
                // Store state
                localStorage.setItem('ictSidebarCollapsed', 'true');
                console.log('ICT Sidebar: Sidebar closed, classes:', sidebar.className);
            }
        }
    }
    
    // Toggle button event listener
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('ICT Sidebar: Logo toggle button clicked');
            toggleSidebar();
        });
    }
    
    // Overlay click to close
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            console.log('ICT Sidebar: Overlay clicked - closing sidebar');
            if (sidebar) {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
                if (mainContent) {
                    mainContent.classList.add('sidebar-collapsed');
                }
            }
            sidebarOverlay.style.display = 'none';
            sidebarOverlay.classList.remove('show');
            localStorage.setItem('ictSidebarCollapsed', 'true');
        });
    }
    
    // Mobile menu button (if exists)
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('ICT Sidebar: Mobile menu button clicked');
            toggleSidebar();
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            // Desktop mode - restore from localStorage
            const sidebarCollapsed = localStorage.getItem('ictSidebarCollapsed');
            if (sidebarCollapsed === 'true') {
                sidebar.classList.add('collapsed');
                sidebar.classList.remove('show');
                if (mainContent) {
                    mainContent.classList.add('sidebar-collapsed');
                }
            } else {
                sidebar.classList.remove('collapsed');
                sidebar.classList.add('show');
                if (mainContent) {
                    mainContent.classList.remove('sidebar-collapsed');
                }
            }
            if (sidebarOverlay) {
                sidebarOverlay.style.display = 'none';
                sidebarOverlay.classList.remove('show');
            }
        } else {
            // Mobile mode - close sidebar
            sidebar.classList.add('collapsed');
            sidebar.classList.remove('show');
            if (mainContent) {
                mainContent.classList.add('sidebar-collapsed');
            }
        }
    });
    
    console.log('ICT Sidebar: Toggle setup complete');
}

function setupRealTimeUpdates() {
    // Update KPI cards with real-time data
    updateKPICards();
    
    // Update alerts
    updateAlerts();
    
    // Set up periodic updates (every 30 seconds)
    setInterval(() => {
        updateKPICards();
        updateAlerts();
    }, 30000);
}

function updateKPICards() {
    // This would typically fetch data from an API endpoint
    // For now, we'll just add some visual feedback
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach(card => {
        card.classList.add('loading');
        
        setTimeout(() => {
            card.classList.remove('loading');
        }, 1000);
    });
}

function updateAlerts() {
    // Update alert counts and status
    const alertItems = document.querySelectorAll('.alert-item');
    alertItems.forEach(alert => {
        // Add pulse animation for urgent alerts
        if (alert.classList.contains('urgent')) {
            alert.style.animation = 'pulse 2s infinite';
        }
    });
}

function setupQuickActions() {
    const quickActions = document.querySelectorAll('.quick-action');
    
    quickActions.forEach(action => {
        action.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
            
            // Add loading state
            this.classList.add('loading');
            
            // Remove loading state after navigation
            setTimeout(() => {
                this.classList.remove('loading');
            }, 500);
        });
    });
}

function setupAlerts() {
    const alertItems = document.querySelectorAll('.alert-item');
    
    alertItems.forEach(alert => {
        // Add click handler for alert items
        alert.addEventListener('click', function() {
            // Navigate to relevant page based on alert type
            const alertText = this.textContent.toLowerCase();
            
            if (alertText.includes('maintenance')) {
                window.location.href = 'maintenance.php';
            } else if (alertText.includes('support')) {
                window.location.href = 'ict_support_form.php';
            } else if (alertText.includes('software')) {
                window.location.href = 'software.php';
            }
        });
        
        // Add hover effects
        alert.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
        });
    });
}

// Utility functions
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

function refreshData() {
    // Show loading state
    const mainContent = document.querySelector('.main-content');
    mainContent.classList.add('loading');
    
    // Simulate data refresh
    setTimeout(() => {
        mainContent.classList.remove('loading');
        showNotification('Data refreshed successfully', 'success');
    }, 1000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + R: Refresh data
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshData();
    }
    
    // Ctrl + M: Go to maintenance
    if (e.ctrlKey && e.key === 'm') {
        e.preventDefault();
        window.location.href = 'maintenance.php';
    }
    
    // Ctrl + I: Go to inventory
    if (e.ctrlKey && e.key === 'i') {
        e.preventDefault();
        window.location.href = 'inventory.php';
    }
    
    // Ctrl + S: Go to support
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        window.location.href = 'ict_support_form.php';
    }
});

// Add pulse animation for urgent alerts
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);

// Export functions for global use
window.ICT = {
    showNotification,
    refreshData,
    updateKPICards,
    updateAlerts
};