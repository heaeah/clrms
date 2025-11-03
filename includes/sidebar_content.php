<?php
// This file contains the sidebar HTML and PHP rendering logic, used by SidebarRenderer::render()
?>
<!-- Sidebar Toggle Button (always visible, fixed) -->
<button class="sidebar-logo-toggle btn btn-light d-flex align-items-center justify-content-center position-fixed" id="sidebarLogoToggle" style="z-index:1053; left:16px; top:16px; width:56px; height:56px; box-shadow:0 2px 8px rgba(0,0,0,0.10);">
    <img src="../assets/images/IS_LOGO.jpg" style="width:40px; height:40px; object-fit:contain; border-radius:50%; display:block; margin:auto;">
</button>

<!-- Sidebar Overlay (for closing on mobile/when open) -->
<div class="sidebar-overlay" id="sidebarOverlay" style="display:none;"></div>

<nav class="sidebar bg-white shadow-sm" id="mainSidebar">
    <div class="d-flex flex-column p-3 h-100">
        <h4 class="text-primary text-center mb-4 sidebar-title">CLRMS</h4>

        <?php if (in_array($role, ['Lab Admin', 'Student Assistant'])): ?>
            <a href="dashboard.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'dashboard.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-house-door-fill me-2"></i> <span class="sidebar-text">Dashboard</span>
            </a>

            <a href="inventory.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'inventory.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-box2-fill me-2"></i> <span class="sidebar-text">Inventory</span>
            </a>

            <!-- Borrow Requests Collapsible -->
            <a class="nav-link text-dark d-flex justify-content-between align-items-center <?= in_array($current, ['borrower_slip_form.php', 'reserve_lab.php', 'ict_support_form.php']) ? 'text-primary fw-bold' : '' ?>"
               data-bs-toggle="collapse"
               href="#borrowRequestsCollapse"
               role="button"
               aria-expanded="<?= in_array($current, ['borrower_slip_form.php', 'reserve_lab.php', 'ict_support_form.php']) ? 'true' : 'false' ?>"
               aria-controls="borrowRequestsCollapse">
                <span class="d-flex align-items-center"><i class="bi bi-journal-arrow-up me-2"></i> <span class="sidebar-text">Forms</span></span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current, ['borrower_slip_form.php', 'reserve_lab.php', 'ict_support_form.php']) ? 'show' : '' ?>" id="borrowRequestsCollapse">
                <ul class="list-unstyled ps-4 mt-2">
                    <li>
                        <a class="nav-link text-dark d-flex align-items-center <?= $current === 'borrower_slip_form.php' ? 'text-primary fw-bold' : '' ?>" href="borrower_slip_form.php">
                            <i class="bi bi-file-earmark-text me-2"></i> <span class="sidebar-text">Borrower's Slip</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link text-dark d-flex align-items-center <?= $current === 'reserve_lab.php' ? 'text-primary fw-bold' : '' ?>" href="reserve_lab.php">
                            <i class="bi bi-pc-display-horizontal me-2"></i> <span class="sidebar-text">Request Form (Use of Computer Labs)</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link text-dark d-flex align-items-center <?= $current === 'ict_support_form.php' ? 'text-primary fw-bold' : '' ?>" href="ict_support_form.php">
                            <i class="bi bi-tools me-2"></i> <span class="sidebar-text">Request for ICT Support Services</span>
                        </a>
                    </li>
                </ul>
            </div>
            <a href="lab_details.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'lab_details.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-building me-2"></i> <span class="sidebar-text">Labs</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($role, ['Lab Admin', 'ICT Staff'])): ?>
            <!-- Maintenance Collapsible -->
            <a class="nav-link text-dark d-flex justify-content-between align-items-center <?= in_array($current, ['maintenance_scheduled.php', 'repair.php']) ? 'text-primary fw-bold' : '' ?>"
               data-bs-toggle="collapse"
               href="#maintenanceCollapse"
               role="button"
               aria-expanded="<?= in_array($current, ['maintenance_scheduled.php', 'repair.php']) ? 'true' : 'false' ?>"
               aria-controls="maintenanceCollapse">
                <span class="d-flex align-items-center"><i class="bi bi-tools me-2"></i> <span class="sidebar-text">Maintenance & Repair</span></span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse <?= in_array($current, ['maintenance_scheduled.php', 'repair.php']) ? 'show' : '' ?>" id="maintenanceCollapse">
                <ul class="list-unstyled ps-4 mt-2">
                    <li>
                        <a class="nav-link text-dark d-flex align-items-center <?= $current === 'maintenance_scheduled.php' ? 'text-primary fw-bold' : '' ?>" href="maintenance_scheduled.php">
                            <i class="bi bi-calendar-check me-2"></i> <span class="sidebar-text">Scheduled Maintenance</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link text-dark d-flex align-items-center <?= $current === 'repair.php' ? 'text-primary fw-bold' : '' ?>" href="repair.php">
                            <i class="bi bi-wrench me-2"></i> <span class="sidebar-text">Equipment Repairs</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (in_array($role, ['Lab Admin', 'Chairperson'])): ?>
            <a href="history_logs.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'history_logs.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-clock-history me-2"></i> <span class="sidebar-text">History Logs</span>
            </a>
            <a class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'manage_borrowers.php' ? 'text-primary fw-bold' : '' ?>" href="manage_borrowers.php">
                <i class="bi bi-people-fill me-2"></i> <span class="sidebar-text">Manage Borrowers</span>
            </a>
            <a href="manage_borrow_requests.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'manage_borrow_requests.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-journal-check me-2"></i> <span class="sidebar-text">Manage Request</span>
            </a>
            <a href="manage_lab_reservations.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'manage_lab_reservations.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-calendar-check me-2"></i> <span class="sidebar-text">Manage Lab Reservations</span>
            </a>
            <a href="software.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'software.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-pc-display me-2"></i> <span class="sidebar-text">Software Licenses</span>
            </a>

        <?php endif; ?>

        <a href="profile.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'profile.php' ? 'text-primary fw-bold' : '' ?>">
            <i class="bi bi-person-fill me-2"></i> <span class="sidebar-text">Profile</span>
        </a>

        <?php if ($role === 'Lab Admin'): ?>
            <a href="register.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'register.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-person-gear me-2"></i> <span class="sidebar-text">Manage User</span>
            </a>
            <a href="manage_masterlists.php" class="nav-link text-dark d-flex align-items-center justify-content-start <?= $current === 'manage_masterlists.php' ? 'text-primary fw-bold' : '' ?>">
                <i class="bi bi-list-ul me-2"></i> <span class="sidebar-text">Masterlists</span>
            </a>
        <?php endif; ?>

        <hr class="bg-light my-3">

        <a href="../pages/logout.php" class="nav-link text-danger d-flex align-items-center justify-content-start">
            <i class="bi bi-box-arrow-right me-2"></i> <span class="sidebar-text">Logout</span>
        </a>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 250px;
    z-index: 1052;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    background: #fff;
    overflow-x: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,0.07);
}
.sidebar.hide {
    transform: translateX(-100%);
}
.sidebar-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.25);
    z-index: 1050;
    display: none;
}
.sidebar.show-overlay + .sidebar-overlay {
    display: block;
}
@media (max-width: 991.98px) {
    .sidebar {
        width: 80vw;
        min-width: 200px;
        max-width: 320px;
    }
}
@media (min-width: 992px) {
    .main-content, .main {
        margin-left: 250px;
        transition: margin-left 0.3s cubic-bezier(.4,0,.2,1);
    }
    .sidebar.hide ~ .main-content,
    .sidebar.hide ~ .main {
        margin-left: 0;
    }
}
</style>

<script>
const sidebar = document.getElementById('mainSidebar');
const sidebarLogoToggle = document.getElementById('sidebarLogoToggle');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.remove('hide');
    sidebar.classList.add('show-overlay');
    sidebarOverlay.style.display = 'block';
}
function closeSidebar() {
    sidebar.classList.add('hide');
    sidebar.classList.remove('show-overlay');
    sidebarOverlay.style.display = 'none';
}

sidebarLogoToggle.addEventListener('click', function() {
    if (sidebar.classList.contains('hide')) {
        openSidebar();
    } else {
        closeSidebar();
    }
});

sidebarOverlay.addEventListener('click', closeSidebar);

// Close sidebar when clicking a link (on mobile)
sidebar.querySelectorAll('a.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 992) closeSidebar();
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 