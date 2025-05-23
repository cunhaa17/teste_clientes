document.addEventListener('DOMContentLoaded', function () {
    // Exibir Toast de Sucesso por 5 segundos
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.querySelector('tbody');
    const checkboxes = document.querySelectorAll('.form-check-input');

    if (searchInput && resultsContainer) {
        function updateTable() {
            const query = searchInput.value.trim();
            const selectedColumns = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.id.replace('check', '').toLowerCase());

            const url = `${window.location.pathname}?search=${encodeURIComponent(query)}&colunas=${selectedColumns.join(',')}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao buscar dados');
                    }
                    return response.text();
                })
                .then(data => {
                    const parser = new DOMParser();
                    const newDoc = parser.parseFromString(data, "text/html");
                    const newTableBody = newDoc.querySelector('tbody');

                    if (newTableBody) {
                        resultsContainer.innerHTML = newTableBody.innerHTML;
                        updateColumnVisibility();
                    }
                })
                .catch(error => {
                    console.error('Erro na busca:', error);
                    resultsContainer.innerHTML = "<tr><td colspan='5' class='text-center text-danger'>Erro ao buscar dados</td></tr>";
                });
        }

        function updateColumnVisibility() {
            const selectedColumns = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.id.replace('check', '').toLowerCase());

            document.querySelectorAll('th, td').forEach(cell => {
                const column = cell.getAttribute('data-column');
                if (column) {
                    cell.style.display = selectedColumns.includes(column) ? '' : 'none';
                }
            });
        }

        searchInput.addEventListener('input', updateTable);
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTable);
        });
    } else {
        console.error("Elemento de pesquisa ou container de resultados não encontrado!");
    }

    // Lógica de Exclusão
    let itemIdToDelete = null;

    document.querySelectorAll(".btn-eliminar").forEach(button => {
        button.addEventListener("click", function () {
            itemIdToDelete = this.getAttribute("data-id");
            let modalElement = document.getElementById("confirmDeleteModal");
            if (modalElement) {
                let modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        });
    });

    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener("click", function () {
            if (itemIdToDelete) {
                let url;
                if (window.location.pathname.includes("funcionario")) {
                    url = "eliminar_funcionario.php";
                } else if (window.location.pathname.includes("servico")) {
                    url = "eliminar_servico.php";
                } else if (window.location.pathname.includes("cliente")) {
                    url = "eliminar_cliente.php";
                } else if (window.location.pathname.includes("reservas")) {
                    url = "eliminar_reserva.php";
                }

                fetch(url, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${itemIdToDelete}`
                })
                .then(response => response.json())
                .then(data => {
                    let modalElement = document.getElementById("confirmDeleteModal");
                    let modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();

                    if (data.status === "success") {
                        let row = document.querySelector(`button[data-id='${itemIdToDelete}']`).closest("tr");
                        row.classList.add("table-danger");
                        setTimeout(() => row.remove(), 500);
                        showToast("Eliminado com sucesso!", "bg-success");
                    } else {
                        showToast("Erro ao eliminar item.", "bg-warning");
                    }
                })
                .catch(error => showToast("Erro na requisição.", "bg-dark"));
            }
        });
    }

    function showToast(message, colorClass) {
        let toastElement = document.getElementById("deleteToast");
        if (!toastElement) return;

        toastElement.querySelector(".toast-body").textContent = message;
        toastElement.classList.remove("bg-success", "bg-warning", "bg-dark");
        toastElement.classList.add(colorClass);

        let toast = new bootstrap.Toast(toastElement);
        toast.show();
    }

    // **Correção do filtro por data**
    const dateHeader = document.getElementById("dateHeader");
    const datePickerModal = document.getElementById("datePickerModal");
    const datepicker = document.getElementById("datepicker");
    const reservasTableBody = document.getElementById("reservasTableBody");

    if (dateHeader && datePickerModal && datepicker && reservasTableBody) {
        // Quando clicar no cabeçalho "Data" com a lupa, abre o modal do calendário
        dateHeader.addEventListener("click", function () {
            const modal = new bootstrap.Modal(datePickerModal);
            modal.show();
        });

        // Inicializa o Datepicker no campo de input
        $(datepicker).datepicker({
            format: "yyyy-mm-dd",
            autoclose: true,
            todayHighlight: true
        });

        // Evento ao clicar em "Aplicar"
        document.getElementById("applyDate").addEventListener("click", function () {
            const selectedDate = datepicker.value.trim(); // Obtém a data selecionada
            if (selectedDate) {
                const rows = reservasTableBody.querySelectorAll("tr");
                rows.forEach(row => {
                    const dateCell = row.cells[2].textContent.trim(); // Terceira célula contém a data (ajustar índice se necessário)
                    
                    // Converte a data da célula para YYYY-MM-DD
                    const formattedDate = dateCell.split("/").reverse().join("-"); 

                    // Exibe ou oculta as linhas conforme a data selecionada
                    row.style.display = formattedDate === selectedDate ? "" : "none";
                });

                // Fecha o modal corretamente
                const modal = bootstrap.Modal.getInstance(datePickerModal);
                modal.hide();
            } else {
                alert("Por favor, selecione uma data.");
            }
        });
    } else {
        console.error("Elementos necessários para o filtro por data não foram encontrados!");
    }

    // Handle subservice expansion
    document.querySelectorAll('.btn-expand').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var servicoId = this.getAttribute('data-servico-id');
            var row = document.getElementById('subservicos-' + servicoId);
            var contentDiv = row.querySelector('.subservicos-content');

            if (row.style.display === 'none') {
                // Fetch subservices via AJAX
                fetch('get_subtipos.php?servico_id=' + servicoId)
                    .then(response => response.text())
                    .then(html => {
                        contentDiv.innerHTML = html;
                        row.style.display = '';
                        this.textContent = '-';
                        
                        // Add event listeners for delete buttons
                        let subservicoIdToDelete = null;
                        
                        document.querySelectorAll('.btn-eliminar-subservico').forEach(function(deleteBtn) {
                            deleteBtn.addEventListener('click', function() {
                                subservicoIdToDelete = this.getAttribute('data-id');
                                let modalElement = document.getElementById('confirmDeleteSubservicoModal');
                                let modal = new bootstrap.Modal(modalElement);
                                modal.show();
                            });
                        });

                        // Handle subservice deletion confirmation
                        const confirmDeleteSubservicoBtn = document.getElementById('confirmDeleteSubservicoBtn');
                        if (confirmDeleteSubservicoBtn) {
                            confirmDeleteSubservicoBtn.addEventListener('click', function() {
                                if (subservicoIdToDelete) {
                                    var formData = new FormData();
                                    formData.append('id', subservicoIdToDelete);

                                    fetch('eliminar_subservico.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        let modalElement = document.getElementById('confirmDeleteSubservicoModal');
                                        let modal = bootstrap.Modal.getInstance(modalElement);
                                        modal.hide();

                                        if (data.status === 'success') {
                                            // Remove the row from the table
                                            let row = document.querySelector(`button[data-id='${subservicoIdToDelete}']`).closest('tr');
                                            row.classList.add('table-danger');
                                            setTimeout(() => row.remove(), 500);
                                            
                                            // Show success message
                                            var toast = document.getElementById('subservicoToast');
                                            var toastBody = toast.querySelector('.toast-body');
                                            toastBody.textContent = data.message;
                                            var bsToast = new bootstrap.Toast(toast);
                                            bsToast.show();
                                        } else {
                                            alert(data.message);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Erro ao eliminar subtipo de serviço');
                                    });
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Erro ao carregar subtipos de serviço');
                    });
            } else {
                row.style.display = 'none';
                this.textContent = '+';
            }
        });
    });

    // Only run this block if on the reservas page (checks for the filter form)
    if (document.getElementById('filterForm')) {
        // Manipulação da seleção de colunas
        const checkboxes = document.querySelectorAll('.dropdown-menu input[type="checkbox"]');
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const selectedColumns = [];
                checkboxes.forEach(function(cb) {
                    if (cb.checked) {
                        selectedColumns.push(cb.id.replace('check', '').toLowerCase());
                    }
                });
                if (selectedColumns.length > 0) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('colunas', selectedColumns.join(','));
                    window.location.href = url.toString();
                }
            });
        });

        // Confirmar exclusão
        const btnEliminar = document.querySelectorAll('.btn-eliminar');
        const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
        let confirmDeleteModal = null;
        if (confirmDeleteModalEl) {
            confirmDeleteModal = new bootstrap.Modal(confirmDeleteModalEl);
        }
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        let reservaId;
        btnEliminar.forEach(function(btn) {
            btn.addEventListener('click', function() {
                reservaId = this.getAttribute('data-id');
                if (confirmDeleteModal) confirmDeleteModal.show();
            });
        });
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                window.location.href = 'excluir_reserva.php?id=' + reservaId;
            });
        }

        // Alterar status
        const statusLinks = document.querySelectorAll('.alter-status');
        statusLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const status = this.getAttribute('data-status');
                window.location.href = 'alterar_status_reserva.php?id=' + id + '&status=' + status;
            });
        });
    }
}); 