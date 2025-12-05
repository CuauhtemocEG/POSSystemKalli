let carrito = [];

document.getElementById('buscarProducto').addEventListener('input', function () {
    const query = this.value;
    const mesaID = document.getElementById('mesaActual').value;
    if (query.length >= 2) {
        fetch('searchProductsPOS.php?query=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => mostrarResultados(data));
    } else {
        document.getElementById('resultadoProductos').innerHTML = '';
    }
});

function mostrarResultados(productos) {
    const container = document.getElementById('resultadoProductos');
    container.innerHTML = '';
    productos.forEach(prod => {
        const card = document.createElement('div');
        card.className = 'col-md-4 mb-2';
        card.innerHTML = `
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${prod.Nombre}</h5>
                    <p class="card-text">Precio: $${prod.PrecioUnitario} | Stock: ${prod.Cantidad}</p>
                    <button class="btn btn-primary btn-sm" onclick='agregarAlCarrito(${JSON.stringify(prod)})'>
                        Agregar
                    </button>
                </div>
            </div>`;
        container.appendChild(card);
    });
}

function agregarAlCarrito(prod) {
    const index = carrito.findIndex(p => p.ProductoID === prod.ProductoID);
    if (index >= 0) {
        carrito[index].cantidad += 1;
    } else {
        carrito.push({ ...prod, cantidad: 1 });
    }
    renderizarCarrito();
}

function renderizarCarrito() {
    const tbody = document.querySelector('#tablaCarrito tbody');
    tbody.innerHTML = '';
    let total = 0;
    carrito.forEach((item, i) => {
        const subtotal = item.PrecioUnitario * item.cantidad;
        total += subtotal;
        tbody.innerHTML += `
            <tr>
                <td>${item.Nombre}</td>
                <td><input type="number" min="1" value="${item.cantidad}" class="form-control form-control-sm" onchange="actualizarCantidad(${i}, this.value)"></td>
                <td>$${item.PrecioUnitario}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td><button class="btn btn-danger btn-sm" onclick="eliminarDelCarrito(${i})">X</button></td>
            </tr>
        `;
    });
    document.getElementById('totalVenta').innerText = total.toFixed(2);
}

function actualizarCantidad(index, nuevaCantidad) {
    carrito[index].cantidad = parseInt(nuevaCantidad) || 1;
    renderizarCarrito();
}

function eliminarDelCarrito(index) {
    carrito.splice(index, 1);
    renderizarCarrito();
}

document.getElementById("formVenta").addEventListener("submit", function (e) {
    e.preventDefault();

    if (carrito.length === 0) {
        alert("No hay productos en el carrito.");
        return;
    }

    const mesaID = document.getElementById("mesaActual").value;
    const metodoPago = document.getElementById("metodoPago").value;

    fetch("finalizarVenta.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            mesaID: mesaID,
            metodoPago: metodoPago,
            carrito: carrito
        })
    })
    .then(res => res.blob())
    .then(blob => {
        // Descargar PDF generado
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ticket_mesa_${mesaID}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.location.href = "mesas.php";
    });
});
