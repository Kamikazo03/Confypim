# API de Confypim

**Versión de referencia:** código incluido en este proyecto.  
**Base URL local:** `http://localhost/Confypim/ajax`  
**Formato habitual:** JSON UTF-8. Las operaciones de fotografía de perfil utilizan `multipart/form-data`.

## 1. Consideraciones generales

- La API utiliza sesiones PHP y cookies de sesión; no utiliza tokens JWT.
- Las rutas protegidas requieren una sesión autenticada. `producto.php`, `perfil.php` y `venta.php` ejecutan `exigirAdministradorAPI()`.
- Los datos se limitan al usuario autenticado cuando así lo implementa el modelo/servicio.
- En las peticiones JSON, enviar `Content-Type: application/json` y `Accept: application/json`.
- Los ejemplos usan `localhost`; cambiar el host o la carpeta si la instalación tiene otra URL.
- Los mensajes y códigos de respuesta descritos corresponden al código del proyecto. En los endpoints de Productos y Perfil, algunos errores se responden con códigos generales y no todas las validaciones están centralizadas.

## 2. Resumen de endpoints

| Endpoint | Método | Acción | Acceso |
|---|---|---|---|
| `/auth.php?accion=login` | POST | Iniciar sesión | Público |
| `/auth.php?accion=register` | POST | Registrar cuenta | Público |
| `/auth.php?accion=logout` | POST | Cerrar sesión | Sesión PHP |
| `/producto.php` | GET | Listar productos activos | Sesión autenticada |
| `/producto.php?id={id}` | GET | Consultar un producto | Sesión autenticada |
| `/producto.php` | POST | Crear producto | Sesión autenticada |
| `/producto.php` | PUT | Actualizar producto | Sesión autenticada |
| `/producto.php?id={id}` | DELETE | Desactivar producto | Sesión autenticada |
| `/perfil.php` | GET | Consultar perfil | Sesión autenticada |
| `/perfil.php` | POST | Actualizar perfil, avatar, fotografía, encuadre o eliminar fotografía | Sesión autenticada |
| `/venta.php` | GET | Listar ventas | Sesión autenticada |
| `/venta.php?id={id}` | GET | Consultar venta y detalles | Sesión autenticada |
| `/venta.php?accion=metricas` | GET | Consultar métricas | Sesión autenticada |
| `/venta.php` | POST | Crear venta pendiente | Sesión autenticada |
| `/venta.php?id={id}` | PUT | Actualizar venta pendiente | Sesión autenticada |
| `/venta.php?id={id}&accion=pagar` | PATCH | Pagar venta | Sesión autenticada |
| `/venta.php?id={id}&accion=cancelar` | PATCH | Cancelar venta pendiente | Sesión autenticada |

---

## 3. Autenticación — `auth.php`

Todas las acciones requieren `POST`. La acción predeterminada, cuando no se indica `accion`, es `login`.

### Iniciar sesión

**Solicitud**

`POST /auth.php?accion=login`

```json
{
  "email": "emprendedora@example.com",
  "password": "ClaveSegura123"
}
```

Campos:
- `email`: obligatorio y con formato de correo válido.
- `password`: obligatorio.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Inicio de sesión correcto.",
  "usuario": {
    "id_usuario": 1,
    "nombre": "María",
    "email": "emprendedora@example.com",
    "rol": "administrador"
  }
}
```

El objeto `usuario` puede incluir otros campos de perfil presentes en la sesión. El ejemplo se abrevia.

**Errores habituales:** HTTP 401 para credenciales incorrectas o campos de inicio de sesión inválidos; HTTP 400 para acción o JSON inválidos; HTTP 405 para un método diferente de `POST`; HTTP 500 ante un error interno.

### Registrar cuenta

**Solicitud**

`POST /auth.php?accion=register`

```json
{
  "nombre": "María",
  "apellido": "Gómez",
  "email": "emprendedora@example.com",
  "password": "ClaveSegura123",
  "confirmar_password": "ClaveSegura123"
}
```

Validaciones implementadas:
- `nombre`, `email`, `password` y `confirmar_password` son obligatorios.
- Nombre y apellido no pueden superar 100 caracteres.
- El correo debe ser válido y no superar 100 caracteres.
- La contraseña debe tener al menos 8 caracteres y coincidir con su confirmación.
- No se permiten correos registrados previamente.
- El rol se toma de la configuración del servidor, no del cuerpo enviado.

**Respuesta satisfactoria — HTTP 201**

```json
{
  "success": true,
  "message": "Cuenta creada correctamente. Ya puedes iniciar sesión."
}
```

Los errores de validación y correo duplicado se devuelven con HTTP 400; los errores inesperados, con HTTP 500.

### Cerrar sesión

**Solicitud**

`POST /auth.php?accion=logout`

Se finaliza la sesión PHP actual.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Sesión cerrada correctamente."
}
```

---

## 4. Productos — `producto.php`

Las operaciones requieren una sesión autenticada. El listado y la consulta individual solo incluyen productos activos del usuario actual.

### Listar productos

**Solicitud**

`GET /producto.php`

**Respuesta**

El controlador devuelve el resultado del modelo: una lista de productos, con campos como `id_producto`, `nombre`, `descripcion`, `precio_unitario`, `stock_disponible`, `imagen`, `estado`, `id_usuario` y `fecha_creacion`.

### Consultar un producto

**Solicitud**

`GET /producto.php?id=12`

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "data": {
    "id_producto": 12,
    "nombre": "Torta de chocolate",
    "descripcion": "Torta artesanal",
    "precio_unitario": "45000.00",
    "stock_disponible": 5,
    "imagen": null,
    "id_usuario": 1
  }
}
```

Si no se encuentra, el controlador responde `success: false` con el mensaje `Producto no encontrado.`. En el código actual, la consulta individual no establece explícitamente un código HTTP 404.

### Crear producto

**Solicitud**

`POST /producto.php`

```json
{
  "nombre": "Torta de chocolate",
  "descripcion": "Torta artesanal",
  "precio_unitario": 45000,
  "stock_disponible": 5
}
```

El usuario propietario se toma de la sesión. El modelo usa los campos mostrados en el ejemplo.

**Respuesta satisfactoria — HTTP 201**

```json
{
  "success": true,
  "message": "Producto registrado correctamente."
}
```

El cuerpo debe ser JSON que se pueda decodificar como arreglo; si no, el endpoint responde HTTP 400. El resultado fallido del controlador se responde con HTTP 400.

### Actualizar producto

**Solicitud**

`PUT /producto.php`

```json
{
  "id_producto": 12,
  "nombre": "Torta de chocolate",
  "descripcion": "Torta artesanal grande",
  "precio_unitario": 50000,
  "stock_disponible": 4
}
```

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Producto actualizado correctamente."
}
```

El identificador se envía en el cuerpo como `id_producto`. La consulta solo actualiza productos activos del usuario actual. El resultado fallido del controlador se responde con HTTP 404.

### Desactivar producto

**Solicitud**

`DELETE /producto.php?id=12`

La operación es una eliminación lógica: el producto se desactiva en lugar de borrarse físicamente.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Producto desactivado correctamente."
}
```

Si falta un ID válido, el endpoint responde HTTP 400. Si el controlador no logra desactivarlo, responde HTTP 404.

Los métodos no admitidos responden HTTP 405. Los errores inesperados responden HTTP 500.

---

## 5. Perfil — `perfil.php`

Las operaciones requieren una sesión autenticada. Para `POST`, la acción se envía como campo de formulario `accion`. El endpoint recibe estos datos desde `$_POST` y archivos desde `$_FILES`.

### Consultar perfil

**Solicitud**

`GET /perfil.php`

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "data": {
    "id_usuario": 1,
    "nombre": "María",
    "apellido": "Gómez",
    "email": "emprendedora@example.com",
    "rol": "administrador"
  }
}
```

El perfil puede contener también descripción, avatar, ruta de fotografía, coordenadas de encuadre y fecha de registro.

### Actualizar datos personales

**Solicitud**

`POST /perfil.php`

Tipo de contenido: `application/x-www-form-urlencoded` o `multipart/form-data`.

```text
accion=actualizar
nombre=María
apellido=Gómez
descripcion=Emprendedora de repostería artesanal
```

Validaciones: nombre y apellido obligatorios, máximo 100 caracteres cada uno; descripción máximo 2000 caracteres.

**Respuesta satisfactoria:** el servicio devuelve `success`, un mensaje y el usuario actualizado.

### Seleccionar avatar

**Solicitud**

`POST /perfil.php`

```text
accion=avatar
avatar=avatar-rosa
```

El identificador del avatar debe cumplir el patrón `avatar-[a-zA-Z0-9_-]+` y corresponder a un archivo SVG existente en `assets/img/avatars/`. Enviar `avatar` vacío restaura las iniciales.

### Subir fotografía

**Solicitud**

`POST /perfil.php`, `multipart/form-data`

Campos:
- `accion`: `subir-foto`
- `foto`: archivo de imagen

Validaciones: archivo recibido correctamente, tamaño mayor que 0 y máximo 2 MB, imagen válida y formato JPG, PNG o WebP.

### Actualizar encuadre

**Solicitud**

`POST /perfil.php`

```text
accion=encuadre
posicion_x=50
posicion_y=50
```

Ambas posiciones deben ser enteros entre 0 y 100.

### Eliminar fotografía

**Solicitud**

`POST /perfil.php`

```text
accion=eliminar-foto
```

### Respuestas de Perfil

Las acciones satisfactorias devuelven JSON con `success: true`, `message` y, en las operaciones que actualizan información, `usuario`. Las validaciones lanzadas como `InvalidArgumentException` se responden con HTTP 422; las acciones desconocidas, HTTP 400; los métodos no admitidos, HTTP 405; los errores inesperados, HTTP 500.

---

## 6. Ventas — `venta.php`

Las operaciones requieren una sesión autenticada. Las ventas y productos se consultan en el ámbito del usuario de la sesión. Las ventas canceladas se excluyen del listado predeterminado.

### Listar ventas

**Solicitud**

`GET /venta.php`

Parámetros opcionales:
- `estado`: filtra por estado. El valor `Todos` no aplica filtro; si se omite, se excluyen las ventas `Cancelada`.
- `busqueda`: busca por número de venta o nombre del cliente.

**Ejemplo**

`GET /venta.php?estado=Pendiente&busqueda=María`

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "data": [
    {
      "id_venta": 8,
      "numero_venta": 8,
      "fecha_venta": "2026-10-19 10:30:00",
      "total": "90000.00",
      "id_usuario": 1,
      "id_cliente": null,
      "estado": "Pendiente",
      "cliente": "Cliente General"
    }
  ]
}
```

Los valores son ilustrativos. Cada venta puede incluir los campos que devuelve el modelo.

### Consultar una venta

**Solicitud**

`GET /venta.php?id=8`

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "data": {
    "id_venta": 8,
    "numero_venta": 8,
    "fecha_venta": "2026-10-19 10:30:00",
    "total": "90000.00",
    "estado": "Pendiente",
    "cliente": "Cliente General",
    "detalles": [
      {
        "id_detalle": 14,
        "id_venta": 8,
        "id_producto": 3,
        "producto": "Torta de chocolate",
        "cantidad": 2,
        "precio_unitario": "45000.00",
        "subtotal": "90000.00"
      }
    ]
  }
}
```

El ejemplo se abrevia. Si la venta no existe o no pertenece al usuario autenticado, el servicio responde HTTP 404. Un ID ausente, no entero o menor que 1 responde HTTP 400.

### Consultar métricas

**Solicitud**

`GET /venta.php?accion=metricas`

Las métricas consideran ventas `Pagada` del usuario autenticado.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "data": {
    "ventas_pagadas": 4,
    "ingresos_totales": 180000,
    "productos_vendidos": 5,
    "productos_diferentes": 3,
    "promedio_venta": 45000
  }
}
```

Los números son ilustrativos; el servicio devuelve los campos `ventas_pagadas`, `ingresos_totales`, `productos_vendidos`, `productos_diferentes` y `promedio_venta`.

### Crear venta

**Solicitud**

`POST /venta.php`

`Content-Type: application/json`

```json
{
  "id_cliente": null,
  "fecha_venta": "2026-10-19 10:30:00",
  "detalles": [
    {
      "id_producto": 3,
      "cantidad": 2
    }
  ]
}
```

Reglas implementadas:
- La venta se crea con estado `Pendiente`.
- Debe incluir al menos un producto.
- Los identificadores deben ser positivos y las cantidades mayores que cero.
- No se permite repetir el mismo producto en varios detalles de la venta.
- El precio unitario se toma de la base de datos; el total y los subtotales se calculan en el servidor.
- Crear una venta pendiente no descuenta stock.

**Respuesta satisfactoria — HTTP 201**

```json
{
  "success": true,
  "message": "Venta creada correctamente.",
  "data": {
    "id_venta": 8,
    "estado": "Pendiente",
    "total": "90000.00",
    "detalles": []
  }
}
```

El ejemplo de `data` es ilustrativo y abreviado; la respuesta real contiene la venta recuperada con sus detalles.

### Actualizar una venta pendiente

**Solicitud**

`PUT /venta.php?id=8`

```json
{
  "id_cliente": null,
  "fecha_venta": "2026-10-19 11:00:00",
  "detalles": [
    {
      "id_producto": 3,
      "cantidad": 1
    },
    {
      "id_producto": 4,
      "cantidad": 2
    }
  ]
}
```

Solo se pueden modificar ventas `Pendiente`. Se requiere al menos un detalle y se recalculan precios y total en el servidor.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Venta actualizada correctamente.",
  "data": {}
}
```

`data` representa la venta completa actualizada; se omiten sus campos por brevedad.

### Pagar una venta

**Solicitud**

`PATCH /venta.php?id=8&accion=pagar`

No requiere cuerpo JSON. Solo se pagan ventas `Pendiente`. Antes de confirmar el pago, el servicio valida que los productos existan, estén activos y tengan stock suficiente. El descuento de stock y el cambio de estado se realizan dentro de una transacción.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Venta pagada correctamente.",
  "data": {}
}
```

La venta completa se devuelve en `data`.

### Cancelar una venta

**Solicitud**

`PATCH /venta.php?id=8&accion=cancelar`

No requiere cuerpo JSON. Solo se pueden cancelar ventas `Pendiente`. Las ventas pendientes no han descontado stock.

**Respuesta satisfactoria — HTTP 200**

```json
{
  "success": true,
  "message": "Venta cancelada correctamente.",
  "data": {}
}
```

### Errores de Ventas

Las respuestas de error tienen la forma:

```json
{
  "success": false,
  "message": "Descripción del error."
}
```

Códigos HTTP utilizados:
- `400 Bad Request`: JSON inválido, ID no válido o acción PATCH desconocida.
- `404 Not Found`: venta no encontrada o que no pertenece al usuario.
- `409 Conflict`: regla de negocio incumplida, por ejemplo intentar pagar una venta que no está pendiente o no disponer de stock suficiente.
- `422 Unprocessable Entity`: datos que incumplen las validaciones del servicio.
- `405 Method Not Allowed`: método HTTP no admitido.
- `500 Internal Server Error`: error inesperado; el detalle técnico se registra en el log del servidor y no se expone en la respuesta.

---

## 7. Pruebas recomendadas para Postman (EV04)

Realizar las pruebas con una sesión válida, conservando la cookie de sesión PHP:

1. Iniciar sesión con credenciales válidas y no válidas.
2. Registrar una cuenta con datos correctos, campos faltantes, correo inválido y correo duplicado.
3. Listar, consultar, crear, actualizar y desactivar un producto.
4. Consultar y actualizar el perfil; probar una imagen admitida y otra no admitida.
5. Consultar la lista de ventas, métricas y detalle de una venta.
6. Crear una venta con un producto y con varios productos.
7. Intentar crear una venta sin detalles, con cantidad no positiva o con un producto inactivo.
8. Actualizar una venta pendiente.
9. Pagar una venta con stock suficiente y verificar que el stock disminuye.
10. Intentar pagar con stock insuficiente y verificar que la venta no se marque como pagada.
11. Cancelar una venta pendiente y verificar que no cambia el stock.
12. Probar un ID inválido, una venta inexistente, una acción desconocida y un método HTTP no permitido.

Documentar para cada prueba: objetivo, método y URL, datos enviados, código HTTP, respuesta recibida y captura de Postman.