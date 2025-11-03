const actionSelect = document.getElementById('action_type');
const deleteFields = document.getElementById('delete_fields');
const transferFields = document.getElementById('transfer_fields');
const transferToSelect = document.getElementById('transferred_to_select');
const otherLabInput = document.getElementById('other_lab_input');

actionSelect.addEventListener('change', () => {
    deleteFields.classList.add('d-none');
    transferFields.classList.add('d-none');

    if (actionSelect.value === 'delete') {
        deleteFields.classList.remove('d-none');
    } else if (actionSelect.value === 'transfer') {
        transferFields.classList.remove('d-none');
    }
});

transferToSelect?.addEventListener('change', function () {
    otherLabInput.classList.toggle('d-none', this.value !== 'Other');
}); 