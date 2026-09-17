<%@ page contentType="text/html; charset=UTF-8" pageEncoding="UTF-8" %>

<%--
    ==========================================================
    index.jsp
    ==========================================================

    Página inicial de la aplicación web Confypim.

    Esta vista pertenece a la capa de presentación y
    posteriormente podrá funcionar como punto de entrada
    al módulo de ventas.

    ==========================================================
--%>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Confypim - Aplicación Web</title>

</head>

<body>

    <h1>Confypim</h1>

    <p>
        Aplicación web desarrollada con Java, Servlet y JSP.
    </p>

    <h2>Módulos</h2>

    <ul>

        <li>
            <a href="${pageContext.request.contextPath}/ventas">
                Módulo de Ventas
            </a>
        </li>

    </ul>

</body>

</html>