const loadInventoryTable = () => {
    const status = document.getElementById('filter-status').value;
    const location = document.getElementById('filter-location').value;
    const category = document.getElementById('filter-category').value || '';
    const search = document.getElementById('filter-search').value;

    const params = new URLSearchParams({ status, location, category, search });

    fetch('inventory_table.php?' + params)
        .then(response => response.text())
        .then(html => {
            document.getElementById('inventory-table').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading inventory table:', error);
        });
};

document.addEventListener('DOMContentLoaded', () => {
    loadInventoryTable();
    
    // Check if we need to refresh the table (after adding equipment)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('refresh') === '1') {
        // Remove the refresh parameter from URL
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('refresh');
        window.history.replaceState({}, '', newUrl);
        
        // Refresh the table after a short delay to ensure QR code is generated
        setTimeout(() => {
            loadInventoryTable();
        }, 1000);
    }
});

document.getElementById('filter-status').addEventListener('change', loadInventoryTable);
document.getElementById('filter-location').addEventListener('change', loadInventoryTable);
if (document.getElementById('filter-category')) {
    document.getElementById('filter-category').addEventListener('change', loadInventoryTable);
}

let debounceTimeout;
document.getElementById('filter-search').addEventListener('input', () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(loadInventoryTable, 300);
}); 