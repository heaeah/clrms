<?php
$current = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>

<!-- Sidebar Toggle Button (always visible, fixed) -->
<button class="sidebar-logo-toggle btn btn-light d-flex align-items-center justify-content-center position-fixed" id="sidebarLogoToggle" style="z-index:1053; left:16px; top:16px; width:56px; height:56px; box-shadow:0 2px 8px rgba(0,0,0,0.10);">
    <img src="../assets/images/IS_LOGO.jpg" style="width:40px; height:40px; object-fit:contain; border-radius:50%; display:block; margin:auto;">
</button>

<!-- Sidebar Overlay (for closing on mobile/when open) -->
<div class="sidebar-overlay" id="sidebarOverlay" style="display:none;"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4 class="text-center mb-0 sidebar-title">CLRMS</h4>
    </div>

    <div class="d-flex flex-column p-3 h-100">
        <nav class="nav flex-column flex-grow-1">
            <a href="ict_portal.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_portal.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-house-door-fill me-2"></i> <span class="sidebar-text">Dashboard</span>
            </a>
            
            <a href="ict_equipment.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_equipment.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-laptop me-2"></i> <span class="sidebar-text">Equipment Management</span>
            </a>
            
            <a href="ict_maintenance.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_maintenance.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-tools me-2"></i> <span class="sidebar-text">Maintenance</span>
            </a>
            
            <a href="ict_support_dashboard.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_support_dashboard.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-headset me-2"></i> <span class="sidebar-text">ICT Support</span>
            </a>
            
            <a href="ict_software.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_software.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-key me-2"></i> <span class="sidebar-text">Software Licenses</span>
            </a>
            
            <a href="ict_job_accomplishment.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_job_accomplishment.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-clipboard-check me-2"></i> <span class="sidebar-text">Job Accomplishment</span>
            </a>
            
            <a href="ict_reports.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_reports.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-graph-up me-2"></i> <span class="sidebar-text">Reports</span>
            </a>
            
            <a href="ict_profile.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'ict_profile.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-person-fill me-2"></i> <span class="sidebar-text">Profile</span>
            </a>
            
            <a href="../pages/logout.php" class="nav-link text-dark d-flex align-items-center justify-content-start">
                <i class="bi bi-box-arrow-right me-2"></i> <span class="sidebar-text">Logout</span>
            </a>
        </nav>
    </div>
</div>

<div class="sidebar-overlay" id="sidebar-overlay"></div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: -300px;
    width: 300px;
    height: 100vh;
    background: white;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1050;
    transition: left 0.3s ease;
    overflow-y: auto;
    border-right: 1px solid #e9ecef;
}

.sidebar.show {
    left: 0;
}

.sidebar.collapsed {
    left: -300px !important;
}

.sidebar:not(.collapsed) {
    left: 0 !important;
}

.sidebar-header {
    padding: 0 2rem;
    background: #ffffff;
    text-align: center;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 100%;
}

.sidebar-title {
    color: #000000;
    font-weight: bold;
    margin: 0;
    flex: 1;
    text-align: center;
}

.sidebar-logo {
    width: 30px;
    height: 30px;
    object-fit: cover;
    border-radius: 4px;
}

.sidebar-content {
    padding: 1rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.user-info {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.nav-link {
    padding: 0.75rem 1rem;
    border-radius: 8px;
    margin-bottom: 0.25rem;
    transition: all 0.2s;
}

.nav-link:hover {
    background: #e9ecef;
    color: #495057 !important;
}

.nav-link.text-primary {
    background: #e3f2fd;
    color: #1976d2 !important;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    display: none;
}

.sidebar-overlay.show {
    display: block;
}

.main-content {
    margin-left: 0;
    transition: margin-left 0.3s ease;
}

.nav-link {
    padding: 0.5rem 0;
    transition: all 0.2s ease;
}

.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
    display: none;
}

.sidebar-overlay.show {
    display: block;
}

@media (min-width: 768px) {
    .sidebar {
        left: 0;
        position: relative;
        box-shadow: none;
    }
    
    .main-content {
        margin-left: 300px;
    }
    
    .sidebar-overlay {
        display: none !important;
    }
}
</style>

<!-- Sidebar JavaScript is handled by ict_portal.js -->