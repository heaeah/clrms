document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: 'api/resource_events.php',
        eventClick: function(info) {
            fetch('api/resource_events.php?id=' + info.event.id)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('eventDetails').innerHTML = html;
                    var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
                    eventModal.show();
                });
        },
        eventColor: '#3788d8',
        eventDidMount: function(info) {
            // Color code by type/status
            if (info.event.extendedProps.type === 'equipment') {
                info.el.style.backgroundColor = '#28a745'; // green
            } else if (info.event.extendedProps.type === 'lab') {
                info.el.style.backgroundColor = '#007bff'; // blue
            }
            if (info.event.extendedProps.status === 'Under Repair') {
                info.el.style.backgroundColor = '#ffc107'; // yellow
            }
        }
    });
    calendar.render();
}); 