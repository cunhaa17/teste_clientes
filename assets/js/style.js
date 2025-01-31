document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: '/fetch_events.php', // URL para buscar eventos via PHP
        editable: true,
    });
    calendar.render();
});

function submitForm() {
    document.getElementById('filterForm').submit();
}

$(document).ready(function () {
    $("#searchInput").on("input", function () {
        let search = $(this).val();
        let selectedColumns = [];
        $('input[name="colunas[]"]:checked').each(function() {
            selectedColumns.push($(this).val());
        });
        $.ajax({
            url: "procurar_clientes.php",
            method: "GET",
            data: { search: search, colunas: selectedColumns },
            success: function (data) {
                $("#clientesTabela").html(data);
            }
        });
    });
});

let deleteId;

$('#confirmDeleteModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget); // Button that triggered the modal
    deleteId = button.data('id'); // Extract info from data-* attributes
});

$('#confirmDeleteButton').on('click', function () {
    window.location.href = 'eliminar_cliente.php?id=' + deleteId; // Redirect to delete
});

$('#confirmDeleteModal').modal('show');