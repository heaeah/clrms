// History Logs Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('History Logs page initialized');
    
    // Add event listeners
    addEventListeners();
    
    // Add enhanced styles
    addEnhancedStyles();
    
    // Initialize data quality indicators
    initializeDataQualityIndicators();
    
    // Add table enhancements
    enhanceTableFunctionality();
});

function addEventListeners() {
    // Filter form auto-submit
    const filterForm = document.querySelector('form[method="GET"]');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                setTimeout(() => {
                    filterForm.submit();
                }, 300);
            });
        });
    }
    
    // Export button enhancement
    const exportBtn = document.querySelector('a[href*="export=1"]');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            showExportNotification();
        });
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + E for export
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            const exportBtn = document.querySelector('a[href*="export=1"]');
            if (exportBtn) {
                exportBtn.click();
            }
        }
        
        // Ctrl/Cmd + F for focus on filter
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const firstFilter = document.querySelector('select[name="type"]');
            if (firstFilter) {
                firstFilter.focus();
            }
        }
    });
}

function showExportNotification() {
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-download me-2"></i>
        <strong>Export Started!</strong> 
        Your history logs are being prepared for download.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

function initializeDataQualityIndicators() {
    // Add tooltips to data quality badges
    const qualityBadges = document.querySelectorAll('.badge[class*="bg-"]');
    qualityBadges.forEach(badge => {
        const quality = badge.textContent.trim();
        let tooltipText = '';
        
        switch(quality) {
            case 'High':
                tooltipText = 'Complete audit trail with full details';
                break;
            case 'Medium':
                tooltipText = 'Standard logging with basic information';
                break;
            case 'Low':
                tooltipText = 'Limited information available';
                break;
        }
        
        if (tooltipText) {
            badge.setAttribute('title', tooltipText);
            badge.style.cursor = 'help';
        }
    });
    
    // Add click handlers for data quality details
    const qualityCards = document.querySelectorAll('.card.border-secondary .col-md-4');
    qualityCards.forEach(card => {
        card.addEventListener('click', function() {
            showQualityDetails(this);
        });
        card.style.cursor = 'pointer';
    });
}

function showQualityDetails(cardElement) {
    const qualityType = cardElement.querySelector('small').textContent;
    const count = cardElement.querySelector('.fw-bold').textContent;
    
    let details = '';
    switch(qualityType) {
        case 'High Quality Records':
            details = 'These records contain complete audit information including user actions, timestamps, equipment details, and authorization data. They are the most reliable for legal compliance and audit purposes.';
            break;
        case 'Medium Quality Records':
            details = 'These records contain standard logging information with basic details. They provide adequate audit trail but may lack some detailed information.';
            break;
        case 'Low Quality Records':
            details = 'These records have limited information available. They may be missing critical audit details and should be reviewed for completeness.';
            break;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('qualityModal') || createQualityModal());
    document.getElementById('qualityModalTitle').textContent = qualityType;
    document.getElementById('qualityModalCount').textContent = count;
    document.getElementById('qualityModalDetails').textContent = details;
    modal.show();
}

function createQualityModal() {
    const modalHTML = `
        <div class="modal fade" id="qualityModal" tabindex="-1" aria-labelledby="qualityModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="qualityModalLabel">
                            <i class="bi bi-shield-check me-2"></i>Data Quality Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <h6 class="fw-bold" id="qualityModalTitle"></h6>
                            <span class="badge bg-primary fs-5" id="qualityModalCount"></span>
                        </div>
                        <p id="qualityModalDetails"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    return document.getElementById('qualityModal');
}

function enhanceTableFunctionality() {
    const table = document.getElementById('historyTable');
    if (!table) return;
    
    // Add row highlighting for different activity types
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const activityType = row.querySelector('.badge')?.textContent.trim();
        if (activityType) {
            row.setAttribute('data-activity-type', activityType);
        }
        
        // Add click handler for row details
        row.addEventListener('click', function(e) {
            if (!e.target.closest('.badge')) {
                showRowDetails(this);
            }
        });
    });
    
    // Add search functionality
    addTableSearch();
    
    // Add sorting functionality
    addTableSorting();
}

function showRowDetails(rowElement) {
    const cells = rowElement.querySelectorAll('td');
    const activityType = cells[1]?.querySelector('.badge')?.textContent.trim() || 'Unknown';
    const action = cells[2]?.querySelector('.badge')?.textContent.trim() || 'Unknown';
    const timestamp = cells[3]?.textContent.trim() || 'Unknown';
    const user = cells[4]?.textContent.trim() || 'Unknown';
    const equipment = cells[5]?.textContent.trim() || 'Unknown';
    const details = cells[6]?.textContent.trim() || 'No details available';
    const authorizedBy = cells[7]?.textContent.trim() || 'Unknown';
    const remarks = cells[8]?.textContent.trim() || 'No remarks';
    const quality = cells[9]?.querySelector('.badge')?.textContent.trim() || 'Unknown';
    
    const modal = new bootstrap.Modal(document.getElementById('rowDetailsModal') || createRowDetailsModal());
    document.getElementById('rowDetailsModalTitle').textContent = `${activityType} - ${action}`;
    document.getElementById('rowDetailsContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <p><strong>Timestamp:</strong> ${timestamp}</p>
                <p><strong>User:</strong> ${user}</p>
                <p><strong>Equipment/Resource:</strong> ${equipment}</p>
                <p><strong>Authorized By:</strong> ${authorizedBy}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Data Quality:</strong> <span class="badge bg-${getQualityColor(quality)}">${quality}</span></p>
                <p><strong>Details:</strong> ${details}</p>
                <p><strong>Remarks:</strong> ${remarks}</p>
            </div>
        </div>
    `;
    modal.show();
}

function getQualityColor(quality) {
    switch(quality) {
        case 'High': return 'success';
        case 'Medium': return 'warning';
        case 'Low': return 'danger';
        default: return 'secondary';
    }
}

function createRowDetailsModal() {
    const modalHTML = `
        <div class="modal fade" id="rowDetailsModal" tabindex="-1" aria-labelledby="rowDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title" id="rowDetailsModalLabel">
                            <i class="bi bi-info-circle me-2"></i>Activity Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="rowDetailsContent">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    return document.getElementById('rowDetailsModal');
}

function addTableSearch() {
    const searchHTML = `
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="tableSearch" placeholder="Search activities...">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch">Clear</button>
            </div>
        </div>
    `;
    
    const table = document.getElementById('historyTable');
    if (table) {
        table.parentNode.insertBefore(document.createRange().createContextualFragment(searchHTML), table);
        
        const searchInput = document.getElementById('tableSearch');
        const clearBtn = document.getElementById('clearSearch');
        
        searchInput.addEventListener('input', function() {
            filterTable(this.value);
        });
        
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            filterTable('');
        });
    }
}

function filterTable(searchTerm) {
    const table = document.getElementById('historyTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
    
    // Update results count
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const resultsBadge = document.querySelector('.badge.bg-primary.fs-6');
    if (resultsBadge) {
        resultsBadge.textContent = `${visibleRows.length} Results`;
    }
}

function addTableSorting() {
    const headers = document.querySelectorAll('#historyTable thead th');
    headers.forEach((header, index) => {
        if (index > 0) { // Skip the # column
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(index);
            });
            
            // Add sort indicator
            const sortIcon = document.createElement('i');
            sortIcon.className = 'bi bi-arrow-down-up ms-1';
            sortIcon.style.fontSize = '0.8rem';
            header.appendChild(sortIcon);
        }
    });
}

function sortTable(columnIndex) {
    const table = document.getElementById('historyTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Remove existing sort indicators
    const headers = table.querySelectorAll('thead th i');
    headers.forEach(icon => {
        icon.className = 'bi bi-arrow-down-up ms-1';
    });
    
    // Add sort indicator to clicked header
    const clickedHeader = table.querySelectorAll('thead th')[columnIndex];
    const sortIcon = clickedHeader.querySelector('i');
    sortIcon.className = 'bi bi-arrow-up ms-1';
    
    // Sort rows
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex]?.textContent.trim() || '';
        const bText = b.cells[columnIndex]?.textContent.trim() || '';
        return aText.localeCompare(bText);
    });
    
    // Reorder rows
    rows.forEach(row => tbody.appendChild(row));
}

function addEnhancedStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .table tbody tr {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.08) !important;
        }
        
        .badge {
            transition: all 0.2s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
        }
        
        .alert.position-fixed {
            animation: slideInRight 0.3s ease-out;
        }
        
        .card.border-secondary .col-md-4 {
            transition: all 0.2s ease;
        }
        
        .card.border-secondary .col-md-4:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Enhanced table search */
        #tableSearch:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        /* Sort indicators */
        .table thead th i {
            opacity: 0.5;
            transition: opacity 0.2s ease;
        }
        
        .table thead th:hover i {
            opacity: 1;
        }
    `;
    document.head.appendChild(style);
}

// Print functionality for history logs
function printHistoryLog(logIndex, logType, action) {
    // Find the log data from the table
    const table = document.getElementById('historyTable');
    const rows = table.querySelectorAll('tbody tr');
    const row = rows[logIndex];
    
    if (!row) {
        alert('Log data not found');
        return;
    }
    
    // Extract data from the row
    const cells = row.querySelectorAll('td');
    const logNumber = cells[0].textContent.trim();
    const activityType = cells[1].textContent.trim();
    const actionText = cells[2].textContent.trim();
    const timestamp = cells[3].querySelector('.fw-medium').textContent.trim();
    const time = cells[3].querySelector('small').textContent.trim();
    const user = cells[4].textContent.trim();
    const equipment = cells[5].querySelector('.fw-medium').textContent.trim();
    const equipmentDetails = cells[5].querySelector('small')?.textContent.trim() || '';
    const details = cells[6].textContent.trim();
    const authorizedBy = cells[7].textContent.trim();
    const remarks = cells[8].textContent.trim();
    const quality = cells[9].textContent.trim();
    
    // Create print window content matching the form layout
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>History Log - ${logNumber}</title>
            <style>
                @media print {
                    body { 
                        margin: 0; 
                        padding: 20px; 
                        font-family: Arial, sans-serif; 
                        background: #fff;
                    }
                    .slip-box { 
                        max-width: 900px; 
                        margin: 0 auto; 
                        background: #fff; 
                        border: 2px solid #333; 
                        padding: 32px 40px; 
                        border-radius: 12px; 
                        box-shadow: 0 0 12px #eee; 
                    }
                    .slip-header { 
                        border-bottom: 2px solid #333; 
                        margin-bottom: 16px; 
                        padding-bottom: 8px; 
                        display: flex;
                        align-items: center;
                    }
                    .slip-title { 
                        font-size: 1.5rem; 
                        font-weight: bold; 
                        text-align: center;
                    }
                    .document-control { 
                        border: 2px solid #333; 
                        border-radius: 6px; 
                        padding: 6px 8px; 
                        font-size: 0.75em; 
                        background: #f8f9fa; 
                        min-width: 180px; 
                        white-space: nowrap;
                    }
                    .document-control table { 
                        width: 100%; 
                        font-size: inherit; 
                    }
                    .document-control td { 
                        padding: 2px 4px; 
                    }
                    .form-row { 
                        display: flex; 
                        margin-bottom: 16px; 
                        gap: 16px; 
                    }
                    .form-field { 
                        flex: 1; 
                    }
                    .form-label { 
                        font-weight: bold; 
                        margin-bottom: 4px; 
                        display: block; 
                    }
                    .form-value { 
                        border: 1px solid #ccc; 
                        padding: 8px; 
                        background: #f9f9f9; 
                        min-height: 20px; 
                    }
                    .form-value.full-width { 
                        width: 100%; 
                    }
                    .note { 
                        font-size: 0.95em; 
                        margin-top: 12px; 
                        border: 1px solid #333; 
                        padding: 16px; 
                        background: #f8f9fa; 
                    }
                    .stamp-box { 
                        width: 170px; 
                        height: 130px; 
                        border: 2px solid #222; 
                        border-radius: 6px; 
                        background: #fff; 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                        justify-content: flex-start; 
                        position: relative; 
                        padding-top: 8px; 
                    }
                    .stamp-status-label { 
                        font-weight: bold; 
                        font-size: 1.1em; 
                        letter-spacing: 1px; 
                        text-align: center; 
                        width: 100%; 
                        border-bottom: 2px solid #222; 
                        padding-bottom: 2px; 
                        margin-bottom: 6px; 
                    }
                    .quality-high { color: #28a745; font-weight: bold; }
                    .quality-medium { color: #ffc107; font-weight: bold; }
                    .quality-low { color: #dc3545; font-weight: bold; }
                    .no-print { display: none; }
                    .print-button { position: fixed; top: 20px; right: 20px; z-index: 1000; }
                }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">Print</button>
            
            <div class="slip-box">
                <div class="slip-header">
                    <div style="flex: 0 0 15%;">
                        <img src="../assets/images/chmsu_logo.jpg" alt="CHMSU Logo" style="height: 60px;">
                    </div>
                    <div style="flex: 0 0 55%; text-align: center;">
                        <div class="slip-title">ACTIVITY HISTORY LOG</div>
                    </div>
                    <div style="flex: 0 0 30%; text-align: right;">
                        <div class="document-control">
                            <table>
                                <tr>
                                    <td style="font-weight:bold; padding-right:4px;">Document Code:</td>
                                    <td style="text-align:right;">F.01-BSIS-TAL</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding-right:4px;">Revision No.:</td>
                                    <td style="text-align:right;">0</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding-right:4px;">Effective Date:</td>
                                    <td style="text-align:right;">May 27, 2024</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; padding-right:4px;">Page:</td>
                                    <td style="text-align:right;">1 of 1</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-bottom: 16px; font-weight: bold;">BSIS LABORATORY COPY</div>
                
                <div class="form-row">
                    <div class="form-field">
                        <div class="form-label">Log Number</div>
                        <div class="form-value">${logNumber}</div>
                    </div>
                    <div class="form-field">
                        <div class="form-label">Activity Type</div>
                        <div class="form-value">${activityType}</div>
                    </div>
                    <div class="form-field">
                        <div class="form-label">Action</div>
                        <div class="form-value">${actionText}</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-field">
                        <div class="form-label">Timestamp</div>
                        <div class="form-value">${timestamp} at ${time}</div>
                    </div>
                    <div class="form-field">
                        <div class="form-label">Data Quality</div>
                        <div class="form-value quality-${quality.toLowerCase()}">${quality}</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-field">
                        <div class="form-label">User</div>
                        <div class="form-value">${user}</div>
                    </div>
                    <div class="form-field">
                        <div class="form-label">Authorized By</div>
                        <div class="form-value">${authorizedBy}</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <div class="form-label">Equipment/Resource</div>
                    <div class="form-value">${equipment}</div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <div class="form-label">Equipment Details</div>
                    <div class="form-value">${equipmentDetails}</div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <div class="form-label">Additional Details</div>
                    <div class="form-value">${details}</div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <div class="form-label">Remarks</div>
                    <div class="form-value">${remarks}</div>
                </div>
                
                <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                    <div style="flex: 1;">
                        <div class="note">
                            <strong>Note:</strong> This document serves as an official record of system activity for audit and compliance purposes. All activities are logged for transparency and legal compliance.<br>
                            <strong>Accomplished in duplicate copy.</strong>
                        </div>
                    </div>
                    <div style="flex: 0 0 auto;">
                        <div class="stamp-box">
                            <div class="stamp-status-label">STATUS</div>
                            <!-- Empty area for staff to stamp -->
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Auto-print after content loads
    printWindow.onload = function() {
        printWindow.print();
    };
}

// Print all history logs function
function printHistoryLogs() {
    // Get page title and info
    const pageTitle = document.querySelector('h1').textContent.trim();
    const totalActivities = document.querySelector('.badge.bg-primary.fs-6')?.textContent.trim() || 'N/A';
    const dateRange = document.querySelector('small.text-muted')?.textContent.trim() || '';
    
    // Get filter information
    const filterType = document.querySelector('select[name="type"]')?.value || '';
    const filterAction = document.querySelector('select[name="action"]')?.value || '';
    const filterFrom = document.querySelector('input[name="from"]')?.value || '';
    const filterTo = document.querySelector('input[name="to"]')?.value || '';
    const filterUser = document.querySelector('select[name="user"]')?.value || '';
    const filterEquipment = document.querySelector('select[name="equipment"]')?.value || '';
    
    let filterSummary = '';
    if (filterType || filterAction || filterFrom || filterTo || filterUser || filterEquipment) {
        filterSummary = '<div style="background: #fff3cd; padding: 8px; margin-bottom: 16px; border: 1px solid #ffc107; border-radius: 4px;">';
        filterSummary += '<strong>Active Filters:</strong> ';
        const filters = [];
        if (filterType) filters.push(`Type: ${filterType}`);
        if (filterAction) filters.push(`Action: ${filterAction}`);
        if (filterFrom) filters.push(`From: ${filterFrom}`);
        if (filterTo) filters.push(`To: ${filterTo}`);
        if (filterUser) filters.push(`User: ${filterUser}`);
        if (filterEquipment) filters.push(`Equipment: ${filterEquipment}`);
        filterSummary += filters.join(' | ');
        filterSummary += '</div>';
    }
    
    // Get table data
    const table = document.getElementById('historyTable');
    const rows = table.querySelectorAll('tbody tr');
    
    if (rows.length === 0 || (rows.length === 1 && rows[0].cells.length === 1)) {
        alert('No history logs to print!');
        return;
    }
    
    let tableRows = '';
    rows.forEach((row, index) => {
        // Skip the "No activities found" row
        if (row.cells.length === 1) return;
        
        const cells = row.querySelectorAll('td');
        if (cells.length < 6) return;
        
        const type = cells[0].textContent.trim();
        const action = cells[1].textContent.trim();
        const dateTime = cells[2].textContent.trim().replace(/\s+/g, ' ');
        const user = cells[3].textContent.trim();
        const equipment = cells[4].textContent.trim().replace(/\s+/g, ' ');
        const details = cells[5].textContent.trim().substring(0, 100); // Limit details length
        
        tableRows += `
            <tr>
                <td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">${index + 1}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px;">${type}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px;">${action}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px;">${dateTime}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px;">${user}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px;">${equipment}</td>
                <td style="border: 1px solid #dee2e6; padding: 8px; font-size: 0.9em;">${details}</td>
            </tr>
        `;
    });
    
    // Create print content
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>History Logs - Print</title>
            <style>
                @page {
                    size: landscape;
                    margin: 1cm;
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: #fff;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #333;
                }
                .print-header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #333;
                }
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .print-info {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 15px;
                    font-size: 14px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th {
                    background: #0d6efd;
                    color: white;
                    padding: 10px 8px;
                    text-align: left;
                    font-size: 12px;
                    border: 1px solid #0d6efd;
                }
                td {
                    font-size: 11px;
                }
                tr:nth-child(even) {
                    background: #f8f9fa;
                }
                .footer {
                    margin-top: 20px;
                    padding-top: 10px;
                    border-top: 1px solid #dee2e6;
                    font-size: 12px;
                    color: #666;
                    text-align: center;
                }
                @media print {
                    .no-print {
                        display: none !important;
                    }
                    body {
                        print-color-adjust: exact;
                        -webkit-print-color-adjust: exact;
                    }
                }
                .print-button {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    padding: 10px 20px;
                    background: #0d6efd;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 14px;
                    z-index: 1000;
                }
                .print-button:hover {
                    background: #0b5ed7;
                }
            </style>
        </head>
        <body>
            <button class="print-button no-print" onclick="window.print()">🖨️ Print</button>
            
            <div class="print-header">
                <h1>📋 ${pageTitle}</h1>
                <p>Complete Audit Trail of All System Activities</p>
                ${dateRange ? `<p style="font-size: 13px;">${dateRange}</p>` : ''}
            </div>
            
            <div class="print-info">
                <div>
                    <strong>Total Activities:</strong> ${totalActivities}
                </div>
                <div>
                    <strong>Generated:</strong> ${new Date().toLocaleString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    })}
                </div>
            </div>
            
            ${filterSummary}
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 12%;">Type</th>
                        <th style="width: 12%;">Action</th>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 12%;">User</th>
                        <th style="width: 18%;">Equipment/Resource</th>
                        <th style="width: 28%;">Details</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
            
            <div class="footer">
                <p><strong>CLRMS - Computer Laboratory Resources Management System</strong></p>
                <p>This document is an official record of system activities maintained for audit and compliance purposes.</p>
                <p>Generated on ${new Date().toLocaleString('en-US', { dateStyle: 'full', timeStyle: 'long' })}</p>
            </div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank', 'width=1200,height=800');
    if (!printWindow) {
        alert('Please allow popups for this site to enable printing.');
        return;
    }
    
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Auto-print after content loads
    printWindow.onload = function() {
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };
} 