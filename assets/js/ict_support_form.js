document.getElementById('addRowBtn').addEventListener('click', function() {
    const table = document.getElementById('ictItemsTable').getElementsByTagName('tbody')[0];
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" class="ict-form-underline-input" name="borrowed[]"></td>
        <td><input type="text" class="ict-form-underline-input" name="returned[]"></td>
        <td><input type="text" class="ict-form-underline-input" name="quantity[]"></td>
        <td><input type="text" class="ict-form-underline-input" name="item_details[]"></td>
        <td><input type="text" class="ict-form-underline-input" name="remarks[]"></td>
    `;
    table.appendChild(row);
});
document.getElementById('cancelBtn').addEventListener('click', function() {
    window.history.back(); // You can change this to clear the form or redirect as needed
}); 