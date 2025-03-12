
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

        if (searchInput && resultsContainer) {
            searchInput.addEventListener('input', updateTable);
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateTable);
            });
        } else {
            console.error("Elemento de pesquisa ou container de resultados não encontrado!");
        }

        updateColumnVisibility();

        // Lógica de Exclusão
        let itemIdToDelete = null;

        document.querySelectorAll(".btn-eliminar").forEach(button => {
            button.addEventListener("click", function () {
                itemIdToDelete = this.getAttribute("data-id");
                let modalElement = document.getElementById("confirmDeleteModal");
                let modal = new bootstrap.Modal(modalElement);
                modal.show();
            });
        });

        document.getElementById("confirmDeleteBtn").addEventListener("click", function () {
            if (itemIdToDelete) {
                let url;
                if (window.location.pathname.includes("funcionario")) {
                    url = "eliminar_funcionario.php";
                } else if (window.location.pathname.includes("servico")) {
                    url = "eliminar_servico.php";
                } else if (window.location.pathname.includes("cliente")) {
                    url = "eliminar_cliente.php";
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

        function showToast(message, colorClass) {
            let toastElement = document.getElementById("deleteToast");
            if (!toastElement) return;

            toastElement.querySelector(".toast-body").textContent = message;
            toastElement.classList.remove("bg-success", "bg-warning", "bg-dark");
            toastElement.classList.add(colorClass);

            let toast = new bootstrap.Toast(toastElement);
            toast.show();
        }
    });

