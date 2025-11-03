// Global JS error handler
// What: Catches all uncaught JS errors
// Why: To log and display user-friendly messages
// How: Logs to console and shows alert
window.onerror = function(message, source, lineno, colno, error) {
    console.error('[JS Error]', { message, source, lineno, colno, error });
    alert('An unexpected error occurred. Please try again.');
    return true; // Prevents default browser error
};

// Example: Wrapping a function in try-catch
function exampleFunction() {
    try {
        // Simulate error
        throw new Error('Sample error for demonstration');
    } catch (err) {
        // What: Handles errors in this function
        // Why: To prevent app crash and inform user
        // How: Log and show user-friendly message
        console.error('[Function Error]', err);
        alert('Something went wrong in exampleFunction.');
    }
}

// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarLogoToggle');
    const sidebar = document.getElementById('mainSidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Debug logging
    console.log('Sidebar elements found:', {
        sidebarToggle: !!sidebarToggle,
        sidebar: !!sidebar,
        mainContent: !!mainContent,
        sidebarOverlay: !!sidebarOverlay
    });
    
    // Check if sidebar state is stored in localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    console.log('Sidebar collapsed from localStorage:', sidebarCollapsed);
    
    // Initialize sidebar state - default to expanded if no preference is stored
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
        sidebarToggle.classList.add('sidebar-collapsed');
        console.log('Sidebar set to collapsed');
    } else {
        // Ensure sidebar is visible by default
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('sidebar-collapsed');
        sidebarToggle.classList.remove('sidebar-collapsed');
        console.log('Sidebar set to expanded');
    }
    
    // Sidebar toggle functionality
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Global toggle button clicked!');
            console.log('Before toggle - Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            console.log('Current page:', window.location.pathname);
            
            // Check if this is the dashboard page and let dashboard handle it
            if (window.location.pathname.includes('dashboard.php')) {
                console.log('Dashboard page detected, letting dashboard handle sidebar toggle');
                return;
            }
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
            sidebarToggle.classList.toggle('sidebar-collapsed');
            
            console.log('After toggle - Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            console.log('Sidebar transform style:', sidebar.style.transform);
            console.log('Sidebar computed transform:', window.getComputedStyle(sidebar).transform);
            
            // Store sidebar state
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            console.log('Sidebar state saved to localStorage:', isCollapsed);
            
            // Handle overlay for mobile
            if (window.innerWidth <= 768) {
                if (isCollapsed) {
                    sidebarOverlay.classList.remove('show');
                } else {
                    sidebarOverlay.classList.add('show');
                }
            }
            
            // Trigger calendar resize if it exists
            if (window.calendar) {
                setTimeout(() => {
                    window.calendar.updateSize();
                }, 300);
            }
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
            sidebarToggle.classList.add('sidebar-collapsed');
            sidebarOverlay.classList.remove('show');
            localStorage.setItem('sidebarCollapsed', 'true');
            
            // Trigger calendar resize
            if (window.calendar) {
                setTimeout(() => {
                    window.calendar.updateSize();
                }, 300);
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebarOverlay.classList.remove('show');
        }
        
        // Trigger calendar resize
        if (window.calendar) {
            setTimeout(() => {
                window.calendar.updateSize();
            }, 100);
        }
    });
});
