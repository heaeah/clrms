// Software Management JavaScript
console.log('=== SOFTWARE.JS LOADED ===');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing software page...');
    
    // Initialize all components
    initializeLabCards();
    initializeFilters();
    
    function initializeLabCards() {
        const labCards = document.querySelectorAll('.lab-card');
        console.log('Found lab cards:', labCards.length);
        
        if (labCards.length === 0) {
            console.warn('No lab cards found!');
            return;
        }
        
        labCards.forEach((card, index) => {
            console.log('Setting up lab card', index, 'with data-lab:', card.getAttribute('data-lab'));
            
            card.style.cursor = 'pointer';
            card.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Lab card clicked:', this.getAttribute('data-lab'));
                
                // Remove selection from all cards
                document.querySelectorAll('.lab-card').forEach(c => {
                    c.classList.remove('border-primary', 'shadow-lg');
                    c.classList.add('shadow-sm');
                });
                
                // Add selection to clicked card
                this.classList.add('border-primary', 'shadow-lg');
                this.classList.remove('shadow-sm');
                
                const selectedLab = this.getAttribute('data-lab');
                
                // Update the lab filter dropdown
                const labFilter = document.getElementById('labFilter');
                if (labFilter) {
                    labFilter.value = selectedLab;
                    console.log('Updated lab filter to:', selectedLab);
                }
                
                // Apply filters
                applyFilters();
            });
        });
        
        // Initialize "All Labs" card as selected
        const allLabsCard = document.getElementById('all-labs-card');
        if (allLabsCard) {
            allLabsCard.classList.add('border-primary', 'shadow-lg');
            allLabsCard.classList.remove('shadow-sm');
            console.log('Initialized all labs card as selected');
        }
    }
    
    function initializeFilters() {
        const labFilter = document.getElementById('labFilter');
        const pcFilter = document.getElementById('pcFilter');
        const tableRows = document.querySelectorAll('tbody tr');
        
        console.log('Found filters - Lab:', labFilter ? 'yes' : 'no', 'PC:', pcFilter ? 'yes' : 'no');
        console.log('Found table rows:', tableRows.length);
        
        if (labFilter) {
            labFilter.addEventListener('change', function() {
                console.log('Lab filter changed to:', this.value);
                
                // Update lab card selection when dropdown changes
                const selectedLab = this.value;
                document.querySelectorAll('.lab-card').forEach(c => {
                    c.classList.remove('border-primary', 'shadow-lg');
                    c.classList.add('shadow-sm');
                });
                
                if (selectedLab) {
                    const card = document.querySelector(`[data-lab="${selectedLab}"]`);
                    if (card) {
                        card.classList.add('border-primary', 'shadow-lg');
                        card.classList.remove('shadow-sm');
                        console.log('Updated lab card selection to:', selectedLab);
                    }
                } else {
                    const allLabsCard = document.getElementById('all-labs-card');
                    if (allLabsCard) {
                        allLabsCard.classList.add('border-primary', 'shadow-lg');
                        allLabsCard.classList.remove('shadow-sm');
                        console.log('Reset to all labs card');
                    }
                }
                
                applyFilters();
            });
        }
        
        if (pcFilter) {
            pcFilter.addEventListener('change', function() {
                console.log('PC filter changed to:', this.value);
                applyFilters();
            });
        }
        
        function applyFilters() {
            const selectedLab = labFilter ? labFilter.value : '';
            const selectedPC = pcFilter ? pcFilter.value : '';
            
            console.log('Applying filters - Lab:', selectedLab, 'PC:', selectedPC);
            
            let visibleCount = 0;
            
            tableRows.forEach((row, index) => {
                const labCell = row.querySelector('td:nth-child(2)'); // Lab column
                const pcCell = row.querySelector('td:nth-child(3)'); // PC column
                
                let showRow = true;
                
                if (selectedLab && labCell) {
                    const labText = labCell.textContent.trim();
                    console.log('Checking lab:', labText, 'against:', selectedLab);
                    if (!labText.includes(selectedLab)) {
                        showRow = false;
                    }
                }
                
                if (selectedPC && pcCell) {
                    const pcText = pcCell.textContent.trim();
                    console.log('Checking PC:', pcText, 'against:', selectedPC);
                    if (!pcText.includes(selectedPC)) {
                        showRow = false;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleCount++;
            });
            
            console.log('Filter applied. Visible rows:', visibleCount);
        }
        
        // Make clearFilters function globally available
        window.clearFilters = function() {
            console.log('Clearing all filters...');
            
            if (labFilter) labFilter.value = '';
            if (pcFilter) pcFilter.value = '';
            
            // Reset lab card selection
            document.querySelectorAll('.lab-card').forEach(c => {
                c.classList.remove('border-primary', 'shadow-lg');
                c.classList.add('shadow-sm');
            });
            
            const allLabsCard = document.getElementById('all-labs-card');
            if (allLabsCard) {
                allLabsCard.classList.add('border-primary', 'shadow-lg');
                allLabsCard.classList.remove('shadow-sm');
            }
            
            tableRows.forEach(row => row.style.display = '');
            console.log('All filters cleared');
        };
    }
    
    console.log('Software page initialization complete');
}); 