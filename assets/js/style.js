// Removed old Toast function and related logic.
document.addEventListener('DOMContentLoaded', function () {
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

    // Export functionality
    $('#exportPdfBtn').on('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Gerando PDF...',
            text: 'Por favor, aguarde enquanto geramos o relatório.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });

    $('#exportExcelBtn').on('click', function() {
        Swal.fire({
            icon: 'info',
            title: 'Gerando Excel...',
            text: 'Por favor, aguarde enquanto geramos o relatório.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });

    // Sync Status function
    function updateSyncStatus() {
        const now = new Date();
        $('#syncStatus').text(`Atualizado: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`);
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
        document.getElementById("applyDateFilter").addEventListener("click", function () {
            const selectedDate = datepicker.value;
            if (selectedDate) {
                // Filtra as linhas da tabela com base na data selecionada
                const rows = reservasTableBody.getElementsByTagName("tr");
                for (let row of rows) {
                    const dateCell = row.querySelector("td:nth-child(2)"); // Ajuste o índice conforme necessário
                    if (dateCell) {
                        const rowDate = dateCell.textContent.trim();
                        if (rowDate === selectedDate) {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    }
                }
            }
            // Fecha o modal
            const modal = bootstrap.Modal.getInstance(datePickerModal);
            modal.hide();
        });

        // Evento ao clicar em "Limpar"
        document.getElementById("clearDateFilter").addEventListener("click", function () {
            datepicker.value = "";
            // Mostra todas as linhas da tabela
            const rows = reservasTableBody.getElementsByTagName("tr");
            for (let row of rows) {
                row.style.display = "";
            }
            // Fecha o modal
            const modal = bootstrap.Modal.getInstance(datePickerModal);
            modal.hide();
        });
    }

    // Handle subservice expansion - only run if we're on a page with subservices
    const expandButtons = document.querySelectorAll('.btn-expand');
    if (expandButtons.length > 0) {
        expandButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var servicoId = this.getAttribute('data-servico-id');
                var row = document.getElementById('subservicos-' + servicoId);
                
                if (row.style.display === 'none' || row.style.display === '') {
                    row.style.display = 'table-row';
                    this.textContent = '-';
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: "Status atualizado com sucesso!",
                            confirmButtonColor: '#6C5CE7',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atenção!',
                            text: data.message || "Erro ao atualizar status.",
                            confirmButtonColor: '#6C5CE7',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: "Erro ao atualizar status.",
                        confirmButtonColor: '#6C5CE7',
                        confirmButtonText: 'OK'
                    });
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

    // Faturamento page specific code
    const datatablesFaturamento = document.getElementById('datatablesFaturamento');
    if (datatablesFaturamento) {
        new simpleDatatables.DataTable(datatablesFaturamento, {
            searchable: true,
            perPage: 10,
            perPageSelect: [10, 25, 50, 100],
            labels: {
                placeholder: "Pesquisar...",
                perPage: "Itens por página",
                noRows: "Nenhuma reserva encontrada",
                info: "Mostrando {start} até {end} de {rows} reservas",
                noResults: "Nenhum resultado encontrado para {query}"
            }
        });

        // Show loading overlay on page load
        $('.loading-overlay').addClass('active');
        
        // Hide loading overlay when page is fully loaded
        $(window).on('load', function() {
            setTimeout(function() {
                $('.loading-overlay').removeClass('active');
            }, 500);
            updateSyncStatus(); // Update sync status after page load
        });

        // Handle payment status change
        $('.payment-status-select').on('change', function() {
            const reservaId = $(this).data('reserva-id');
            const newStatus = $(this).val();
            const select = $(this);

            // Show loading overlay
            $('.loading-overlay').addClass('active');

            $.ajax({
                url: 'update_payment_status.php',
                type: 'POST',
                data: {
                    reserva_id: reservaId,
                    pagamento_status: newStatus
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Status de pagamento atualizado com sucesso!',
                            confirmButtonColor: '#6C5CE7',
                            confirmButtonText: 'OK'
                        });
                        // Update the select's class to match the new status
                        select.removeClass('status-pendente status-pago status-cancelado')
                             .addClass('status-' + newStatus);

                        // Atualizar os cards
                        $.ajax({
                            url: 'get_faturamento_stats.php',
                            type: 'GET',
                            data: {
                                data_inicio: $('#data_inicio').val(),
                                data_fim: $('#data_fim').val(),
                                status_filter: $('#status_filter').val(),
                                cliente_filter: $('#cliente_filter').val()
                            },
                            success: function(stats) {
                                // Atualizar os valores dos cards
                                $('.card-text').eq(0).text(stats.total_faturado + ' MZN');
                                $('.card-text').eq(1).text(stats.media_por_fatura + ' MZN');
                                $('.card-text').eq(2).text(stats.faturas_pendentes);
                                $('.card-text').eq(3).text(stats.faturas_pagas);
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro',
                                    text: 'Erro ao atualizar estatísticas',
                                    confirmButtonColor: '#6C5CE7',
                                    confirmButtonText: 'OK'
                                });
                            },
                            complete: function() {
                                // Hide loading overlay only after both requests are complete
                                $('.loading-overlay').removeClass('active');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Erro ao atualizar status de pagamento: ' + result.message,
                            confirmButtonColor: '#6C5CE7',
                            confirmButtonText: 'OK'
                        });
                        // Revert the select to its previous value
                        select.val(select.data('previous-value'));
                        // Hide loading overlay on error
                        $('.loading-overlay').removeClass('active');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a solicitação: ' + error,
                        confirmButtonColor: '#6C5CE7',
                        confirmButtonText: 'OK'
                    });
                    // Revert the select to its previous value
                    select.val(select.data('previous-value'));
                    // Hide loading overlay on error
                    $('.loading-overlay').removeClass('active');
                }
            });
        });

        // Store the previous value before change
        $('.payment-status-select').on('focus', function() {
            $(this).data('previous-value', $(this).val());
        });

        // Error handling for AJAX requests
        // $(document).ajaxError(function(event, jqXHR, settings, error) {
        //     showToast('Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.', 'error');
        // });

        // Sync Status function
        function updateSyncStatus() {
            const now = new Date();
            $('#syncStatus').text(`Atualizado: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`);
        }

        // Export functionality
        $('#exportPdfBtn').on('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Gerando PDF...',
                text: 'Por favor, aguarde enquanto geramos o relatório.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });

        $('#exportExcelBtn').on('click', function() {
            Swal.fire({
                icon: 'info',
                title: 'Gerando Excel...',
                text: 'Por favor, aguarde enquanto geramos o relatório.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    }

    // Index page specific code
    const indexPageElements = document.getElementById('statusFilter'); // A unique element from index.php

    if (indexPageElements) {
        // Status filter functionality (keep this if still needed for status)
        $('#statusFilter').on('change', function() {
            const selectedStatus = $(this).val().toLowerCase();
            const rows = $('#reservasTableBody tr');
            let visibleRows = 0;

            console.log('Selected Status:', selectedStatus); // DEBUG
            console.log('Rows Length:', rows.length); // DEBUG

            rows.each(function() {
                const statusCell = $(this).find('.status-badge').text().trim().toLowerCase();
                const isVisible = selectedStatus === '' || statusCell === selectedStatus;
                
                console.log('Row Status:', statusCell, 'Is Visible:', isVisible); // DEBUG

                $(this).toggle(isVisible);
                if (isVisible) visibleRows++;
            });

            // Show toast message
            Swal.fire({
                icon: 'info',
                title: 'Filtro Atualizado',
                text: selectedStatus === '' ? 'Mostrando todos os registros' : `Filtrado por: ${selectedStatus}`,
                confirmButtonColor: '#6C5CE7',
                confirmButtonText: 'OK'
            });
        });

        // Removed client-side Date filter functionality - handled by AJAX in index.php

        // Card hover effects (keep this)
        $('.card').hover(
            function() { $(this).addClass('animate__animated animate__pulse'); },
            function() { $(this).removeClass('animate__animated animate__pulse'); }
        );

        // Refresh functionality (keep this)
        $('#refreshReservas').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true);
            $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Atualizando...');
            
            $('.loading-overlay').addClass('active');
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        });
    }
});
