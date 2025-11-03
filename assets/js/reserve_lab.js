document.addEventListener('DOMContentLoaded', function() {
function attachLabClickHandlers() {
    document.querySelectorAll('input[name="lab_id"]').forEach(radio => {
        radio.removeEventListener('click', radio._labClickHandler || (()=>{}));
        radio._labClickHandler = function(e) {
            if (radio.disabled) {
                e.preventDefault();
                alert('This lab is already reserved or pending for the selected time slot. Please choose another lab or time.');
                return false;
            }
        };
        radio.addEventListener('click', radio._labClickHandler);
    });
}

function updateOccupiedLabs() {
    const reservationStart = document.querySelector('input[name="reservation_start"]').value;
    const reservationEnd = document.querySelector('input[name="reservation_end"]').value;
    if (!reservationStart || !reservationEnd) return;
    const date = reservationStart.split('T')[0];
    const timeStart = reservationStart.split('T')[1];
    const timeEnd = reservationEnd.split('T')[1];
    fetch(`api/occupied_labs.php?date=${date}&time_start=${timeStart}&time_end=${timeEnd}`)
        .then(res => res.json())
        .then(occupied => {
            let allDisabled = true;
            document.querySelectorAll('input[name="lab_id"]').forEach(radio => {
                // Use strict string comparison
                if (occupied.map(String).includes(String(radio.value))) {
                    radio.disabled = true;
                    radio.closest('label').querySelector('.lab-occupied-label').style.display = '';
                    radio.closest('label').classList.add('lab-disabled');
                    radio.closest('label').setAttribute('title', 'Occupied for selected time');
                } else {
                    radio.disabled = false;
                    radio.closest('label').querySelector('.lab-occupied-label').style.display = 'none';
                    radio.closest('label').classList.remove('lab-disabled');
                    radio.closest('label').removeAttribute('title');
                    allDisabled = false;
                }
            });
            // If the currently selected radio is now disabled, uncheck it
            const checked = document.querySelector('input[name="lab_id"]:checked');
            if (checked && checked.disabled) {
                checked.checked = false;
            }
            // Show a message if all are disabled
            let msg = document.getElementById('noLabsMsg');
            if (!msg) {
                msg = document.createElement('div');
                msg.id = 'noLabsMsg';
                msg.className = 'text-danger mt-2';
                document.querySelector('.lab-checkboxes').appendChild(msg);
            }
            msg.textContent = allDisabled ? 'No labs available for the selected time.' : '';
            attachLabClickHandlers(); // re-attach after update
        });
}

document.querySelector('input[name="reservation_start"]').addEventListener('change', updateOccupiedLabs);
document.querySelector('input[name="reservation_end"]').addEventListener('change', updateOccupiedLabs);
attachLabClickHandlers();

document.querySelector('form').addEventListener('submit', function(e) {
    const checked = document.querySelector('input[name="lab_id"]:checked');
    console.log('Form submit: checked lab:', checked);
    if (!checked) {
        e.preventDefault();
        alert('Please select a laboratory.');
        return false;
    }
});
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const datetimeNeeded = document.querySelector('input[name="datetime_needed"]').value;
        const timeEnd = document.querySelector('input[name="time_end"]').value;
        if (!datetimeNeeded || !timeEnd) return;
        const startTime = datetimeNeeded.split('T')[1];
        if (startTime && timeEnd && timeEnd <= startTime) {
            e.preventDefault();
            alert('End time must be after start time.');
            return false;
        }
    });
}); 