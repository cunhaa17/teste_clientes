document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.querySelector('tbody');
    const checkboxes = document.querySelectorAll('.form-check-input'); // Pegando todos os checkboxes

    function updateTable() {
        const query = searchInput.value.trim();
        const selectedColumns = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.id.replace('check', '').toLowerCase());

        const url = `clientes.php?search=${encodeURIComponent(query)}&colunas=${selectedColumns.join(',')}`;

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
                    updateColumnVisibility(); // Atualiza a exibição das colunas
                }
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                resultsContainer.innerHTML = "<tr><td colspan='5' class='text-center text-danger'>Erro ao buscar dados</td></tr>";
            });
    }
        document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll("th[data-column]").forEach(header => {
            header.addEventListener("click", function (event) {
                event.preventDefault();
                const column = this.getAttribute("data-column");
                const currentOrder = new URLSearchParams(window.location.search).get("ordem") || "ASC";
                const newOrder = currentOrder === "ASC" ? "DESC" : "ASC";
    
                const url = `clientes.php?search=${encodeURIComponent(document.getElementById('searchInput').value)}&ordenar_por=${column}&ordem=${newOrder}`;
    
                fetch(url)
                    .then(response => response.text())
                    .then(data => {
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(data, "text/html");
                        document.querySelector("tbody").innerHTML = newDoc.querySelector("tbody").innerHTML;
                    })
                    .catch(error => console.error("Erro ao ordenar:", error));
            });
        });
    });
    
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
        searchInput.addEventListener('input', updateTable); // Atualiza tabela ao digitar na pesquisa
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateTable); // Atualiza tabela ao mudar colunas
        });
    } else {
        console.error("Elemento de pesquisa ou container de resultados não encontrado!");
    }

    updateColumnVisibility(); // Aplica as configurações iniciais ao carregar a página
});

// Lógica de Eliminação de Cliente
document.addEventListener("DOMContentLoaded", function () {
    let clienteIdParaEliminar = null;

    document.querySelectorAll(".btn-eliminar").forEach(button => {
        button.addEventListener("click", function () {
            clienteIdParaEliminar = this.getAttribute("data-id");
            let modal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));
            modal.show();
        });
    });

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
                    let row = document.querySelector(`button[data-id='${clienteIdParaEliminar}']`).closest("tr");
                    row.classList.add("table-danger");
                    setTimeout(() => row.remove(), 500);    

                    showToast("Cliente eliminado com sucesso!", "bg-success");
                } else {
                    showToast("Erro ao eliminar cliente.", "bg-warning");
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

// Acaba clientes
//Começa Serviços
