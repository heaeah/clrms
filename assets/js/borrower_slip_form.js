document.getElementById('borrowerType').addEventListener('change', function () {
    const studentFields = document.getElementById('studentFields');
    const facultyFields = document.getElementById('facultyFields');

    // Get all student and faculty field elements
    const studentInputs = studentFields.querySelectorAll('select, input');
    const facultyInputs = facultyFields.querySelectorAll('select, input');

    if (this.value === 'Student') {
        studentFields.style.display = 'block';
        facultyFields.style.display = 'none';
        // Set required for student fields, remove for faculty
        studentInputs.forEach(el => el.required = true);
        facultyInputs.forEach(el => el.required = false);
    } else {
        studentFields.style.display = 'none';
        facultyFields.style.display = 'block';
        // Set required for faculty fields, remove for student
        studentInputs.forEach(el => el.required = false);
        facultyInputs.forEach(el => el.required = true);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('borrowerType').dispatchEvent(new Event('change'));

    // Equipment add/remove logic
    const equipmentSelect = document.getElementById('equipmentSelect');
    const addItemRow = document.getElementById('addItemRow');
    const itemsTableBody = document.querySelector('#itemsTable tbody');
    const selectedEquipmentInputs = document.getElementById('selectedEquipmentInputs');

    addItemRow.addEventListener('click', function() {
        const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex];
        const equipId = selectedOption.value;
        const equipText = selectedOption.text;
        if (!equipId) return;

        // Add row to table
        const row = document.createElement('tr');
        row.innerHTML = `<td>${equipText}<input type="hidden" name="equipment_ids[]" value="${equipId}"></td><td><button type="button" class="btn btn-danger btn-sm remove-row">-</button></td>`;
        itemsTableBody.appendChild(row);

        // Remove from dropdown
        equipmentSelect.remove(equipmentSelect.selectedIndex);
        equipmentSelect.selectedIndex = 0;
    });

    // Remove row and restore to dropdown
    itemsTableBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const row = e.target.closest('tr');
            const hiddenInput = row.querySelector('input[type="hidden"]');
            const equipId = hiddenInput.value;
            const equipText = row.querySelector('td').childNodes[0].textContent;
            // Restore to dropdown
            const option = document.createElement('option');
            option.value = equipId;
            option.textContent = equipText;
            equipmentSelect.appendChild(option);
            // Remove row
            row.remove();
        }
    });

    // On form submit, require at least one equipment item
    document.getElementById('borrowForm').addEventListener('submit', function(e) {
        if (document.querySelectorAll('input[name="equipment_ids[]"]').length === 0) {
            e.preventDefault();
            alert("Please add at least one equipment item.");
            return false;
        }
    });
}); 