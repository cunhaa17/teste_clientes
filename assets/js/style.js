document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.querySelector('tbody');
    const checkboxes = document.querySelectorAll('.form-check-input'); // Get all checkboxes

    function updateTable() {
        const query = searchInput.value.trim();
        const selectedColumns = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.id.replace('check', '').toLowerCase());

        const url = `clientes.php?search=${encodeURIComponent(query)}&` + 
                    selectedColumns.map(col => `colunas[]=${col}`).join('&');

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar clientes');
                }
                return response.text();
            })
            .then(data => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(data, "text/html");
                const newTableBody = newDoc.querySelector('tbody');

                if (newTableBody) {
                    resultsContainer.innerHTML = newTableBody.innerHTML;
                }
                updateColumnVisibility(); // Atualiza a exibição das colunas
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
        searchInput.addEventListener('input', updateTable); // Update table on search input
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTable); // Update table on checkbox change
        });
    } else {
        console.error("Elemento de pesquisa ou container de resultados não encontrado!");
    }

    updateColumnVisibility(); // Aplica as configurações iniciais ao carregar a página
});
document.addEventListener("DOMContentLoaded", function () {
    let clienteIdParaEliminar = null;

    // Captura clique no botão de eliminar e abre o modal
    document.querySelectorAll(".btn-eliminar").forEach(button => {
        button.addEventListener("click", function () {
            clienteIdParaEliminar = this.getAttribute("data-id");
            let modal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
            modal.show();
        });
    });

    // Confirmação dentro do modal
    document.getElementById("confirmDeleteBtn").addEventListener("click", function () {
        if (clienteIdParaEliminar) {
            fetch("eliminar_cliente.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${clienteIdParaEliminar}`
            })
            .then(response => response.json())
            .then(data => {
                let modal = bootstrap.Modal.getInstance(document.getElementById("confirmDeleteModal"));
                modal.hide();

                if (data.status === "success") {
                    // Efeito de fade-out antes de remover a linha
                    let row = document.querySelector(`button[data-id='${clienteIdParaEliminar}']`).closest("tr");
                    row.classList.add("table-danger");
                    setTimeout(() => row.remove(), 500);

                    // Exibe Toast de Sucesso
                    showToast("Cliente eliminado com sucesso!", "bg-success");
                } else {
                    showToast("Erro ao eliminar cliente.", "bg-warning");
                }
            })
            .catch(error => showToast("Erro na requisição.", "bg-dark"));
        }
    });

    // Função para exibir Toasts personalizados
    function showToast(message, colorClass) {
        let toastElement = document.getElementById("deleteToast");
        toastElement.querySelector(".toast-body").textContent = message;
        
        // Remove classes antigas e adiciona a nova cor
        toastElement.classList.remove("bg-success", "bg-warning", "bg-dark");
        toastElement.classList.add(colorClass);

        let toast = new bootstrap.Toast(toastElement);
        toast.show();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.querySelector('tbody');

    searchInput.addEventListener('input', function () {
        const query = searchInput.value.trim();
        const url = `clientes.php?search=${encodeURIComponent(query)}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar clientes');
                }
                return response.text();
            })
            .then(data => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(data, "text/html");
                const newTableBody = newDoc.querySelector('tbody');

                if (newTableBody) {
                    resultsContainer.innerHTML = newTableBody.innerHTML;
                }
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                resultsContainer.innerHTML = "<tr><td colspan='5' class='text-center text-danger'>Erro ao buscar dados</td></tr>";
            });
    });
});
