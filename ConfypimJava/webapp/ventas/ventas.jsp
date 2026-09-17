<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>

<%@ page import="java.util.List" %>
<%@ page import="confypim.model.Venta" %>
<%@ page import="confypim.model.Producto" %>
<%@ page import="confypim.model.DetalleVenta" %>

<%
    List<Venta> ventas =
            (List<Venta>) request.getAttribute("ventas");

    List<Producto> productos =
            (List<Producto>) request.getAttribute("productos");

    Venta ventaEditar =
            (Venta) request.getAttribute("ventaEditar");

    List<DetalleVenta> detallesEditar =
            (List<DetalleVenta>) request.getAttribute("detallesEditar");

    Venta ventaDetalle =
            (Venta) request.getAttribute("ventaDetalle");

    List<DetalleVenta> detallesDetalle =
            (List<DetalleVenta>) request.getAttribute("detallesDetalle");

    String mensaje =
            (String) request.getAttribute("mensaje");

    String error =
            (String) request.getAttribute("error");

    boolean modoEdicion =
            ventaEditar != null;
%>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Ventas | Confypim</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    <style>

        :root {
            --bg: #F8F5F2;
            --card: #FFFFFF;
            --primary: #EBC8D6;
            --primary-dark: #D58BA6;
            --primary-light: #F6E7EE;
            --secondary: #B8896A;
            --text: #4A4A4A;
            --border: #E7DED8;
            --success: #6BA88B;
            --danger: #C96B6B;
            --warning: #C99B55;
            --shadow: 0 8px 20px rgba(0, 0, 0, .08);
            --radius: 16px;
            --font: 'Poppins', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: var(--bg);
            color: var(--text);
            font-family: var(--font);
        }

        .container {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            margin: 0;
            color: var(--text);
            font-size: 28px;
        }

        .page-header p {
            margin: 6px 0 0;
            color: #777;
            font-size: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 24px;
            margin-bottom: 25px;
        }

        .card-title {
            margin: 0 0 20px;
            font-size: 20px;
            color: var(--text);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 14px;
        }

        select,
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #FFFFFF;
            color: var(--text);
            font-family: var(--font);
            font-size: 14px;
            outline: none;
        }

        select:focus,
        input:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .products-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .products-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .product-row {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) 130px 110px;
            gap: 12px;
            align-items: end;
            padding: 15px;
            margin-bottom: 12px;
            background: #FFFBFD;
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .product-price {
            padding-bottom: 12px;
            font-size: 13px;
            color: #777;
        }

        .button-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 10px 15px;
            border: none;
            border-radius: 10px;
            font-family: var(--font);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: .2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            opacity: .9;
        }

        .btn-primary {
            background: var(--primary-dark);
            color: white;
        }

        .btn-secondary {
            background: var(--primary-light);
            color: var(--text);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-small {
            padding: 8px 10px;
            font-size: 12px;
        }

        .btn:disabled {
            cursor: not-allowed;
            opacity: .45;
            transform: none;
        }

        .total-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
            padding: 18px;
            border-radius: 12px;
            background: var(--primary-light);
        }

        .total-label {
            font-size: 16px;
            font-weight: 600;
        }

        .total-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #E6F4EC;
            color: #347653;
            border: 1px solid #B8DEC7;
        }

        .alert-error {
            background: #FBE8E8;
            color: #9A3D3D;
            border: 1px solid #E9BABA;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: 13px;
        }

        th {
            background: var(--primary-light);
            color: var(--text);
            font-weight: 700;
        }

        tr:hover td {
            background: #FFFBFD;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .status-pendiente {
            background: #FFF0D7;
            color: #986A27;
        }

        .status-pagada {
            background: #E1F3E8;
            color: #347653;
        }

        .status-cancelada {
            background: #FBE3E3;
            color: #9A3D3D;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        .detail-card {
            background: #FFFBFD;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .detail-header h2 {
            margin: 0;
            font-size: 19px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: rgba(74, 74, 74, .55);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            width: 100%;
            max-width: 450px;
            padding: 25px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .modal h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .modal p {
            font-size: 14px;
            line-height: 1.6;
            color: #666;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
        }

        @media (max-width: 700px) {

            body {
                padding: 15px;
            }

            .card {
                padding: 17px;
            }

            .product-row {
                grid-template-columns: 1fr;
            }

            .total-container {
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .page-header h1 {
                font-size: 23px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <header class="page-header">

        <div>
            <h1>Gestión de ventas</h1>
            <p>Registra, consulta y administra las ventas de Confypim.</p>
        </div>

        <a class="btn btn-secondary"
           href="${pageContext.request.contextPath}/ventas">
            Actualizar lista
        </a>

    </header>

    <% if (mensaje != null && !mensaje.isBlank()) { %>

        <div class="alert alert-success">
            <%= mensaje %>
        </div>

    <% } %>

    <% if (error != null && !error.isBlank()) { %>

        <div class="alert alert-error">
            <%= error %>
        </div>

    <% } %>


    <% if (ventaDetalle != null) { %>

        <section class="detail-card">

            <div class="detail-header">

                <h2>
                    Detalle de la venta #<%= ventaDetalle.getIdVenta() %>
                </h2>

                <a class="btn btn-secondary btn-small"
                   href="${pageContext.request.contextPath}/ventas">
                    Cerrar detalle
                </a>

            </div>

            <p>
                <strong>Estado:</strong>
                <%= ventaDetalle.getEstado() %>
            </p>

            <p>
                <strong>Fecha:</strong>
                <%= ventaDetalle.getFechaVenta() %>
            </p>

            <div class="table-container">

                <table>

                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                    </thead>

                    <tbody>

                    <% if (detallesDetalle != null) {
                        for (DetalleVenta detalle : detallesDetalle) {
                    %>

                        <tr>

                            <td>
                                Producto #<%= detalle.getIdProducto() %>
                            </td>

                            <td>
                                <%= detalle.getCantidad() %>
                            </td>

                            <td>
                                $<%= String.format(
                                        "%,.0f",
                                        detalle.getPrecioUnitario()
                                ) %>
                            </td>

                            <td>
                                $<%= String.format(
                                        "%,.0f",
                                        detalle.getSubtotal()
                                ) %>
                            </td>

                        </tr>

                    <%  }
                    } %>

                    </tbody>

                </table>

            </div>

            <div class="total-container">

                <span class="total-label">Total:</span>

                <span class="total-value">
                    $<%= String.format(
                            "%,.0f",
                            ventaDetalle.getTotal()
                    ) %>
                </span>

            </div>

        </section>

    <% } %>


    <section class="card">

        <h2 class="card-title">

            <%= modoEdicion
                    ? "Actualizar venta #" + ventaEditar.getIdVenta()
                    : "Registrar nueva venta" %>

        </h2>

        <form method="post"
              action="${pageContext.request.contextPath}/ventas"
              id="ventaForm">

            <input type="hidden"
                   name="accion"
                   value="<%= modoEdicion ? "actualizar" : "crear" %>">

            <% if (modoEdicion) { %>

                <input type="hidden"
                       name="idVenta"
                       value="<%= ventaEditar.getIdVenta() %>">

            <% } %>

            <div class="form-grid">

                <div class="form-group">

                    <label for="cliente">
                        Cliente
                    </label>

                    <select id="cliente"
                            name="idCliente">

                        <option value="">
                            Cliente General
                        </option>

                    </select>

                </div>

            </div>


            <div class="products-header">

                <h3>Productos de la venta</h3>

                <button type="button"
                        class="btn btn-secondary btn-small"
                        id="addProductButton">

                    + Agregar producto

                </button>

            </div>


            <div id="productsContainer">

                <% if (modoEdicion
                        && detallesEditar != null
                        && !detallesEditar.isEmpty()) {

                    for (DetalleVenta detalle : detallesEditar) {
                %>

                    <div class="product-row">

                        <div class="form-group">

                            <label>Producto</label>

                            <select name="producto[]"
                                    class="product-select"
                                    required>

                                <option value="">
                                    Seleccione un producto
                                </option>

                                <% if (productos != null) {
                                    for (Producto producto : productos) {

                                        boolean seleccionado =
                                                producto.getIdProducto()
                                                        == detalle.getIdProducto();
                                %>

                                    <option value="<%= producto.getIdProducto() %>"
                                            data-price="<%= producto.getPrecio() %>"
                                            <%= seleccionado ? "selected" : "" %>>

                                        <%= producto.getNombre() %>
                                        -
                                        $<%= String.format(
                                                "%,.0f",
                                                producto.getPrecio()
                                        ) %>
                                        -
                                        Stock: <%= producto.getStock() %>

                                    </option>

                                <%  }
                                } %>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Cantidad</label>

                            <input type="number"
                                   name="cantidad[]"
                                   class="quantity-input"
                                   min="1"
                                   value="<%= detalle.getCantidad() %>"
                                   required>

                        </div>


                        <button type="button"
                                class="btn btn-danger btn-small remove-product">

                            Eliminar

                        </button>

                    </div>

                <%  }

                } else {
                %>

                    <div class="product-row">

                        <div class="form-group">

                            <label>Producto</label>

                            <select name="producto[]"
                                    class="product-select"
                                    required>

                                <option value="">
                                    Seleccione un producto
                                </option>

                                <% if (productos != null) {
                                    for (Producto producto : productos) {
                                %>

                                    <option value="<%= producto.getIdProducto() %>"
                                            data-price="<%= producto.getPrecio() %>">

                                        <%= producto.getNombre() %>
                                        -
                                        $<%= String.format(
                                                "%,.0f",
                                                producto.getPrecio()
                                        ) %>
                                        -
                                        Stock: <%= producto.getStock() %>

                                    </option>

                                <%  }
                                } %>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>Cantidad</label>

                            <input type="number"
                                   name="cantidad[]"
                                   class="quantity-input"
                                   min="1"
                                   value="1"
                                   required>

                        </div>


                        <button type="button"
                                class="btn btn-danger btn-small remove-product"
                                disabled>

                            Eliminar

                        </button>

                    </div>

                <% } %>

            </div>


            <div class="total-container">

                <span class="total-label">
                    Total estimado:
                </span>

                <span class="total-value"
                      id="totalValue">

                    $0

                </span>

            </div>


            <div class="form-actions">

                <% if (modoEdicion) { %>

                    <a class="btn btn-secondary"
                       href="${pageContext.request.contextPath}/ventas">

                        Cancelar edición

                    </a>

                    <button type="submit"
                            class="btn btn-primary">

                        Guardar cambios

                    </button>

                <% } else { %>

                    <button type="reset"
                            class="btn btn-secondary"
                            id="resetButton">

                        Limpiar

                    </button>

                    <button type="submit"
                            class="btn btn-primary">

                        Registrar venta

                    </button>

                <% } %>

            </div>

        </form>

    </section>


    <section class="card">

        <h2 class="card-title">
            Ventas registradas
        </h2>

        <div class="table-container">

            <table>

                <thead>

                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

                </thead>

                <tbody>

                <% if (ventas != null && !ventas.isEmpty()) {

                    for (Venta venta : ventas) {
                %>

                    <tr>

                        <td>
                            #<%= venta.getIdVenta() %>
                        </td>

                        <td>
                            <%= venta.getFechaVenta() %>
                        </td>

                        <td>
                            Cliente General
                        </td>

                        <td>
                            $<%= String.format(
                                    "%,.0f",
                                    venta.getTotal()
                            ) %>
                        </td>

                        <td>

                            <span class="status
                                <%= "Pagada".equalsIgnoreCase(venta.getEstado())
                                        ? "status-pagada"
                                        : "status-pendiente" %>">

                                <%= venta.getEstado() %>

                            </span>

                        </td>

                        <td>

                            <div class="button-group">

                                <a class="btn btn-secondary btn-small"
                                   href="${pageContext.request.contextPath}/ventas?accion=detalle&idVenta=<%= venta.getIdVenta() %>">

                                    Detalle

                                </a>


                                <% if ("Pendiente".equalsIgnoreCase(
                                        venta.getEstado())) {
                                %>

                                    <a class="btn btn-warning btn-small"
                                       href="${pageContext.request.contextPath}/ventas?accion=editar&idVenta=<%= venta.getIdVenta() %>">

                                        Actualizar

                                    </a>


                                    <button type="button"
                                            class="btn btn-success btn-small open-confirm-modal"
                                            data-action="pagar"
                                            data-id="<%= venta.getIdVenta() %>">

                                        Pagar

                                    </button>


                                    <button type="button"
                                            class="btn btn-danger btn-small open-confirm-modal"
                                            data-action="cancelar"
                                            data-id="<%= venta.getIdVenta() %>">

                                        Cancelar

                                    </button>

                                <% } %>

                            </div>

                        </td>

                    </tr>

                <%  }

                } else {
                %>

                    <tr>

                        <td colspan="6"
                            class="empty-message">

                            No hay ventas registradas.

                        </td>

                    </tr>

                <% } %>

                </tbody>

            </table>

        </div>

    </section>

</div>


<div class="modal-overlay"
     id="confirmationModal">

    <div class="modal">

        <h2 id="modalTitle">
            Confirmar acción
        </h2>

        <p id="modalMessage">
            ¿Desea continuar con esta acción?
        </p>

        <form method="post"
              action="${pageContext.request.contextPath}/ventas"
              id="confirmationForm">

            <input type="hidden"
                   name="accion"
                   id="modalAction">

            <input type="hidden"
                   name="idVenta"
                   id="modalSaleId">

            <div class="modal-actions">

                <button type="button"
                        class="btn btn-secondary"
                        id="closeModalButton">

                    Cancelar

                </button>

                <button type="submit"
                        class="btn btn-primary"
                        id="confirmModalButton">

                    Confirmar

                </button>

            </div>

        </form>

    </div>

</div>


<script>

    const productsContainer =
        document.getElementById("productsContainer");

    const addProductButton =
        document.getElementById("addProductButton");

    const totalValue =
        document.getElementById("totalValue");

    const confirmationModal =
        document.getElementById("confirmationModal");

    const confirmationForm =
        document.getElementById("confirmationForm");

    const modalAction =
        document.getElementById("modalAction");

    const modalSaleId =
        document.getElementById("modalSaleId");

    const modalTitle =
        document.getElementById("modalTitle");

    const modalMessage =
        document.getElementById("modalMessage");

    const confirmModalButton =
        document.getElementById("confirmModalButton");

    const closeModalButton =
        document.getElementById("closeModalButton");


    function formatCurrency(value) {

        return new Intl.NumberFormat("es-CO", {
            style: "currency",
            currency: "COP",
            maximumFractionDigits: 0
        }).format(value);

    }


    function calculateTotal() {

        let total = 0;

        const rows =
            productsContainer.querySelectorAll(".product-row");

        rows.forEach(row => {

            const productSelect =
                row.querySelector(".product-select");

            const quantityInput =
                row.querySelector(".quantity-input");

            const selectedOption =
                productSelect.options[
                    productSelect.selectedIndex
                ];

            const price =
                Number(selectedOption?.dataset.price || 0);

            const quantity =
                Number(quantityInput.value || 0);

            total += price * quantity;

        });

        totalValue.textContent =
            formatCurrency(total);

    }


    function validateDuplicateProducts() {

        const selectedProducts = [];

        const selects =
            productsContainer.querySelectorAll(".product-select");

        for (const select of selects) {

            const productId = select.value;

            if (!productId) {
                continue;
            }

            if (selectedProducts.includes(productId)) {

                alert(
                    "No puede agregar el mismo producto más de una vez."
                );

                select.value = "";

                calculateTotal();

                return false;
            }

            selectedProducts.push(productId);
        }

        return true;
    }


    function updateRemoveButtons() {

        const rows =
            productsContainer.querySelectorAll(".product-row");

        const buttons =
            productsContainer.querySelectorAll(".remove-product");

        buttons.forEach(button => {

            button.disabled = rows.length === 1;

        });

    }


    function createProductRow() {

        const row =
            document.createElement("div");

        row.className = "product-row";

        row.innerHTML = `

            <div class="form-group">

                <label>Producto</label>

                <select name="producto[]"
                        class="product-select"
                        required>

                    <option value="">
                        Seleccione un producto
                    </option>

                    <% if (productos != null) {
                        for (Producto producto : productos) {
                    %>

                        <option value="<%= producto.getIdProducto() %>"
                                data-price="<%= producto.getPrecio() %>">

                            <%= producto.getNombre() %>
                            -
                            $<%= String.format(
                                    "%,.0f",
                                    producto.getPrecio()
                            ) %>
                            -
                            Stock: <%= producto.getStock() %>

                        </option>

                    <%  }
                    } %>

                </select>

            </div>


            <div class="form-group">

                <label>Cantidad</label>

                <input type="number"
                       name="cantidad[]"
                       class="quantity-input"
                       min="1"
                       value="1"
                       required>

            </div>


            <button type="button"
                    class="btn btn-danger btn-small remove-product">

                Eliminar

            </button>

        `;

        productsContainer.appendChild(row);

        addRowEvents(row);

        updateRemoveButtons();

        calculateTotal();

    }


    function addRowEvents(row) {

        const productSelect =
            row.querySelector(".product-select");

        const quantityInput =
            row.querySelector(".quantity-input");

        const removeButton =
            row.querySelector(".remove-product");


        productSelect.addEventListener("change", () => {

            validateDuplicateProducts();

            calculateTotal();

        });


        quantityInput.addEventListener("input", () => {

            calculateTotal();

        });


        removeButton.addEventListener("click", () => {

            row.remove();

            updateRemoveButtons();

            calculateTotal();

        });

    }


    function initializeRows() {

        const rows =
            productsContainer.querySelectorAll(".product-row");

        rows.forEach(row => {

            addRowEvents(row);

        });

        updateRemoveButtons();

        calculateTotal();

    }


    addProductButton.addEventListener("click", () => {

        createProductRow();

    });


    document.getElementById("ventaForm")
        .addEventListener("submit", event => {

            if (!validateDuplicateProducts()) {

                event.preventDefault();

                return;

            }

            const selects =
                productsContainer.querySelectorAll(".product-select");

            for (const select of selects) {

                if (!select.value) {

                    event.preventDefault();

                    alert(
                        "Debe seleccionar un producto en cada fila."
                    );

                    return;

                }

            }

        });


    document.querySelectorAll(".open-confirm-modal")
        .forEach(button => {

            button.addEventListener("click", () => {

                const action =
                    button.dataset.action;

                const saleId =
                    button.dataset.id;

                modalAction.value = action;

                modalSaleId.value = saleId;

                confirmationModal.classList.add("active");

                if (action === "pagar") {

                    modalTitle.textContent =
                        "Confirmar pago";

                    modalMessage.textContent =
                        "¿Desea marcar la venta #" + saleId
                        + " como pagada? "
                        + "Esta acción descontará los productos del stock.";

                    confirmModalButton.className =
                        "btn btn-success";

                    confirmModalButton.textContent =
                        "Confirmar pago";

                } else {

                    modalTitle.textContent =
                        "Confirmar cancelación";

                    modalMessage.textContent =
                        "¿Desea cancelar la venta #" + saleId
                        + "? La venta se conservará en el sistema.";

                    confirmModalButton.className =
                        "btn btn-danger";

                    confirmModalButton.textContent =
                        "Cancelar venta";

                }

            });

        });


    function closeModal() {

        confirmationModal.classList.remove("active");

    }


    closeModalButton.addEventListener(
        "click",
        closeModal
    );


    confirmationModal.addEventListener("click", event => {

        if (event.target === confirmationModal) {

            closeModal();

        }

    });


    initializeRows();

</script>

</body>

</html>