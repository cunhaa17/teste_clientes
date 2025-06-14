document.addEventListener('DOMContentLoaded', function () {
    // Exibir Toast de Sucesso por 5 segundos
    var toastEl = document.getElementById('successToast');
    if (toastEl) {
        var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    // Search functionality - only run if we're on a page with search
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.querySelector('tbody');
    const checkboxes = document.querySelectorAll('.form-check-input');

    if (searchInput && resultsContainer) {
        let searchTimeout;

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
                        // Re-attach delete event listeners to new buttons
                        attachDeleteEventListeners();
                    }
                })
                .catch(error => {
                    console.error('Erro na busca:', error);
                    resultsContainer.innerHTML = "<tr><td colspan='5' class='text-center text-danger'>Erro ao buscar dados</td></tr>";
                });
        }

        // Add debounce to search input
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateTable, 300); // 300ms delay
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTable);
        });

        // Initial column visibility update
        updateColumnVisibility();
    }

    // Delete functionality
    let itemIdToDelete = null;

    // Function to attach delete event listeners
    function attachDeleteEventListeners() {
        document.querySelectorAll(".btn-eliminar").forEach(button => {
            // Remove existing listeners to avoid duplicates
            button.removeEventListener("click", handleDeleteClick);
            button.addEventListener("click", handleDeleteClick);
        });
    }

    // Delete button click handler
    function handleDeleteClick(e) {
        e.preventDefault();
        e.stopPropagation();
        itemIdToDelete = this.getAttribute("data-id");
        console.log("Delete button clicked, ID:", itemIdToDelete);
        
        let modalElement = document.getElementById("confirmDeleteModal");
        if (modalElement) {
            let modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    // Initial attachment of delete event listeners
    attachDeleteEventListeners();

    // Handle delete confirmation
    const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (itemIdToDelete) {
                // Determine the correct delete URL based on the current page filename
                let deleteUrl;
                const currentPath = window.location.pathname;
                const fileName = currentPath.split('/').pop(); // Get just the filename
                console.log("Current path:", currentPath);
                console.log("Current filename:", fileName);
                
                // Get the base path
                const basePath = window.location.pathname.substring(0, window.location.pathname.indexOf('/dashboard_pap') + '/dashboard_pap'.length);
                console.log("Base path:", basePath);
                
                // Map filenames to their corresponding delete URLs
                if (fileName === 'funcionario.php') {
                    deleteUrl = basePath + "/funcionario/eliminar_funcionario.php";
                } else if (fileName === 'servico.php') {
                    deleteUrl = basePath + "/servico/eliminar_servico.php";
                } else if (fileName === 'clientes.php') {
                    deleteUrl = basePath + "/cliente/eliminar_cliente.php";
                } else if (fileName === 'reservas.php') {
                    deleteUrl = basePath + "/reservas/eliminar_reserva.php";
                } else if (fileName === 'horarios.php') {
                    deleteUrl = basePath + "/horarios/eliminar_horario.php";
                } else if (fileName === 'subservico.php') {
                    deleteUrl = basePath + "/servico/eliminar_subservico.php";
                } else if (fileName === 'agenda.php') {
                    deleteUrl = basePath + "/agenda/eliminar_agenda.php";
                } else if (fileName === 'funcionario_servicos.php') {
                    deleteUrl = basePath + "/funcionario/eliminar_associacao.php";
                } else if (currentPath.includes("/funcionario/")) {
                    deleteUrl = basePath + "/funcionario/eliminar_funcionario.php";
                } else if (currentPath.includes("/servico/")) {
                    deleteUrl = basePath + "/servico/eliminar_servico.php";
                } else if (currentPath.includes("/cliente/")) {
                    deleteUrl = basePath + "/cliente/eliminar_cliente.php";
                } else if (currentPath.includes("/reservas/")) {
                    deleteUrl = basePath + "/reservas/eliminar_reserva.php";
                } else if (currentPath.includes("/horarios/")) {
                    deleteUrl = basePath + "/horarios/eliminar_horario.php";
                } else if (currentPath.includes("/subservico/")) {
                    deleteUrl = basePath + "/servico/eliminar_subservico.php";
                } else if (currentPath.includes("/agenda/")) {
                    deleteUrl = basePath + "/agenda/eliminar_agenda.php";
                } else if (currentPath.includes("/funcionario_servicos/")) {
                    deleteUrl = basePath + "/funcionario/eliminar_associacao.php";
                }

                console.log("Selected delete URL:", deleteUrl);

                if (deleteUrl) {
                    console.log("Sending delete request to:", deleteUrl, "with ID:", itemIdToDelete);
                    
                    // Show loading state
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Eliminando...';
                    this.disabled = true;
                    
                    fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${encodeURIComponent(itemIdToDelete)}`
                    })
                    .then(response => {
                        console.log("Response status:", response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("Response data:", data);
                        
                        // Reset button state
                        this.innerHTML = originalText;
                        this.disabled = false;
                        
                        // Close modal
                        let modalElement = document.getElementById("confirmDeleteModal");
                        let modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Clean up modal backdrop
                        setTimeout(() => {
                            document.querySelector('.modal-backdrop')?.remove();
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 300);

                        if (data.status === "success") {
                            // Show success message and reload page
                            showToast(data.message || "Item eliminado com sucesso!", "bg-success");
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast(data.message || "Erro ao eliminar item.", "bg-danger");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Reset button state
                        this.innerHTML = originalText;
                        this.disabled = false;
                        
                        showToast("Erro na requisição. Tente novamente.", "bg-danger");
                        
                        // Close modal on error
                        let modalElement = document.getElementById("confirmDeleteModal");
                        let modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    });
                } else {
                    console.error("Could not determine delete URL for current page");
                    showToast("Erro: não foi possível determinar a URL de eliminação.", "bg-danger");
                }
            }
        });
    }

    // Handle modal close
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modalElement = document.getElementById("confirmDeleteModal");
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                    // Clean up modal backdrop
                    setTimeout(() => {
                        document.querySelector('.modal-backdrop')?.remove();
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 300);
                }
            }
        });
    });

    function showToast(message, colorClass) {
        // Create toast container if it doesn't exist
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1050';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toastId = 'toast-' + Date.now();
        const toastElement = document.createElement('div');
        toastElement.id = toastId;
        toastElement.className = "toast align-items-center text-white border-0";
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        // Set background color
        if (colorClass === "bg-success") {
            toastElement.style.background = "linear-gradient(45deg, #28a745, #20c997)";
        } else if (colorClass === "bg-danger") {
            toastElement.style.background = "linear-gradient(45deg, #dc3545, #c82333)";
        } else {
            toastElement.style.background = "linear-gradient(45deg, #ffc107, #fd7e14)";
        }
        
        toastElement.style.borderRadius = "10px";
        toastElement.style.boxShadow = "0 4px 15px rgba(0,0,0,0.1)";

        // Set toast content
        const iconClass = colorClass === "bg-success" ? "bi-check-circle-fill" : 
                         colorClass === "bg-danger" ? "bi-exclamation-circle-fill" : "bi-info-circle-fill";
        
        toastElement.innerHTML = `
            <div class="d-flex align-items-center p-3">
                <div class="toast-icon me-3">
                    <i class="bi ${iconClass} fs-4"></i>
                </div>
                <div class="toast-body fs-5">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        // Add to container and show
        toastContainer.appendChild(toastElement);
        
        let toast = new bootstrap.Toast(toastElement, {
            animation: true,
            autohide: true,
            delay: 3000
        });
        
        toast.show();
        
        // Remove element after hiding
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    // Date filter functionality - only run if we're on a page with date filtering
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
            const selectedDate = datepicker.value.trim();
            if (selectedDate) {
                const rows = reservasTableBody.querySelectorAll("tr");
                rows.forEach(row => {
                    const dateCell = row.cells[2].textContent.trim();
                    const formattedDate = dateCell.split("/").reverse().join("-"); 
                    row.style.display = formattedDate === selectedDate ? "" : "none";
                });

                const modal = bootstrap.Modal.getInstance(datePickerModal);
                modal.hide();
            } else {
                alert("Por favor, selecione uma data.");
            }
        });
    }

    // Handle subservice expansion - only run if we're on a page with subservices
    const expandButtons = document.querySelectorAll('.btn-expand');
    if (expandButtons.length > 0) {
        expandButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var servicoId = this.getAttribute('data-servico-id');
                var row = document.getElementById('subservicos-' + servicoId);
                var contentDiv = row.querySelector('.subservicos-content');

                if (row.style.display === 'none') {
                    fetch('get_subtipos.php?servico_id=' + servicoId)
                        .then(response => response.text())
                        .then(html => {
                            contentDiv.innerHTML = html;
                            row.style.display = '';
                            this.textContent = '-';
                            
                            let subservicoIdToDelete = null;
                            
                            document.querySelectorAll('.btn-eliminar-subservico').forEach(function(deleteBtn) {
                                deleteBtn.addEventListener('click', function() {
                                    subservicoIdToDelete = this.getAttribute('data-id');
                                    let modalElement = document.getElementById('confirmDeleteSubservicoModal');
                                    let modal = new bootstrap.Modal(modalElement);
                                    modal.show();
                                });
                            });
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
    }

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

        // Alterar status
        const statusLinks = document.querySelectorAll('.alter-status');
        statusLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const id = this.getAttribute('data-id');
                const status = this.getAttribute('data-status');
                
                fetch('atualizar.reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const statusCell = document.querySelector(`button[data-id='${id}']`).closest('tr').querySelector('td:nth-child(3)');
                        if (statusCell) {
                            let statusClass = '';
                            switch (status) {
                                case 'pendente':
                                    statusClass = 'bg-warning text-dark';
                                    break;
                                case 'confirmada':
                                    statusClass = 'bg-info text-dark';
                                    break;
                                case 'cancelada':
                                    statusClass = 'bg-danger text-white';
                                    break;
                                case 'concluída':
                                    statusClass = 'bg-success text-white';
                                    break;
                            }
                            statusCell.innerHTML = `<span class="badge ${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                        }
                        showToast("Status atualizado com sucesso!", "bg-success");
                    } else {
                        showToast(data.message || "Erro ao atualizar status.", "bg-warning");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast("Erro ao atualizar status.", "bg-dark");
                });
            });
        });
    }

    // Handle accordion expansion - only run if we're on a page with accordions
    const accordionButtons = document.querySelectorAll('.btn-expand');
    const loadingIndicator = document.getElementById('loadingIndicator');

    if (accordionButtons.length > 0 && loadingIndicator) {
        accordionButtons.forEach(button => {
            button.addEventListener('click', function() {
                loadingIndicator.style.display = 'block';
                setTimeout(() => {
                    loadingIndicator.style.display = 'none';
                }, 500);
            });
        });
    }

    /**
     * Animate a regressive progress bar for a Bootstrap toast.
     * @param {HTMLElement} toastEl - The toast element (with id 'successToast' or 'errorToast')
     * @param {HTMLElement} progressBar - The progress bar element inside the toast
     * @param {number} duration - Duration in ms (default 3000)
     */
    function animateToastProgressBar(toastEl, progressBar, duration = 3000) {
        if (!toastEl || !progressBar) return;
        let width = 100;
        const intervalTime = 30;
        const toast = new bootstrap.Toast(toastEl, { autohide: false });
        toast.show();
        const interval = setInterval(() => {
            width -= (intervalTime / duration) * 100;
            progressBar.style.width = width + "%";
            if (width <= 0) {
                clearInterval(interval);
                toast.hide();
            }
        }, intervalTime);
    }
});
