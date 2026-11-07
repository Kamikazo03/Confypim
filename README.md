# Confypim

Aplicación web para la gestión administrativa de pequeños emprendimientos dedicados a la elaboración y comercialización de postres y tortas.

## Descripción

**Confypim** es un sistema de gestión que permite centralizar las operaciones principales de un pequeño negocio de repostería mediante una aplicación web. Su propósito es facilitar la administración de usuarios, productos, ventas e información general del emprendimiento.

El proyecto utiliza una arquitectura MVC en el backend PHP y una API REST para comunicar los diferentes componentes. El módulo de Ventas incorpora React para desarrollar una interfaz basada en componentes.

## Tecnologías utilizadas

| Tecnología | Función |
|---|---|
| PHP | Procesamiento de solicitudes y lógica del backend. |
| MVC | Organización de controladores, modelos y vistas. |
| API REST | Comunicación entre el frontend y el backend. |
| HTML | Estructura de las interfaces. |
| CSS | Presentación visual de la aplicación. |
| JavaScript | Interactividad y comunicación con la API. |
| React | Componentes de interfaz del módulo de Ventas. |
| Vite | Herramienta de desarrollo y compilación de React. |
| MySQL | Almacenamiento de información. |
| PDO | Conexión y acceso a la base de datos. |
| Apache | Servidor web utilizado en el entorno local. |
| XAMPP | Entorno local de desarrollo. |
| Git y GitHub | Control de versiones y almacenamiento del código fuente. |

## Módulos del sistema

Actualmente, Confypim integra los siguientes módulos:

- **Autenticación:** registro, inicio y cierre de sesión, y control de acceso.
- **Dashboard:** panel principal con información general y navegación.
- **Productos:** registro, consulta, actualización y desactivación de productos, además de la gestión del inventario.
- **Ventas:** creación, consulta y administración de ventas, productos asociados, cantidades, totales y estados.
- **Perfil:** consulta y actualización de información personal e imagen de perfil.

## Arquitectura del proyecto

El backend utiliza el patrón Modelo-Vista-Controlador (MVC), complementado con servicios que centralizan operaciones y reglas de negocio.

La comunicación entre las interfaces y el servidor se realiza mediante una API REST, utilizando solicitudes HTTP y respuestas en formato JSON.

La autenticación utiliza sesiones PHP. El acceso a la base de datos se realiza mediante PDO.

El módulo de Ventas utiliza React y Vite. Sus recursos compilados se integran con las vistas PHP de la aplicación.

### Estructura principal

```text
Confypim/
├── ajax/
├── app/
│   ├── controllers/
│   ├── helpers/
│   ├── models/
│   ├── services/
│   └── views/
├── assets/
│   ├── css/
│   ├── img/
│   ├── js/
│   └── react/
├── config/
├── includes/
├── react/
├── index.php
├── API.md
├── package.json
├── package-lock.json
└── vite.config.js
```

## Requisitos para ejecución local

- XAMPP.
- PHP compatible con las características utilizadas por el proyecto.
- MySQL o MariaDB.
- phpMyAdmin.
- Navegador web.
- Node.js y npm, cuando sea necesario reconstruir los componentes React.

## Instalación local

### 1. Obtener el código fuente

Clonar el repositorio:

```bash
git clone https://github.com/Kamikazo03/Confypim.git
```

Ubicar la carpeta del proyecto dentro del directorio `htdocs` de XAMPP.

### 2. Iniciar los servicios

Desde el panel de control de XAMPP, iniciar:

- Apache.
- MySQL.

### 3. Preparar la base de datos

Crear la base de datos `confypim` desde phpMyAdmin e importar el respaldo SQL autorizado para el entorno de desarrollo.

El respaldo de la base de datos no forma parte del repositorio público. Debe obtenerse y administrarse por separado.

### 4. Configurar la conexión

El archivo `config/database.php` permite utilizar los valores locales predeterminados de XAMPP o las variables de entorno configuradas para el servidor.

Para el entorno local se utilizan los parámetros correspondientes a la instalación de XAMPP.

### 5. Acceder a la aplicación

Si el proyecto está ubicado en `htdocs/Confypim`, acceder desde:

```text
http://localhost/Confypim/
```

El acceso a las funcionalidades protegidas requiere una cuenta con permisos de administrador.

## Recursos React

Los componentes del módulo de Ventas se encuentran dentro del directorio `react/`.

Para instalar las dependencias y reconstruir los recursos:

```bash
npm ci
npm run build
```

Los archivos generados se integran con la aplicación PHP mediante los recursos compilados ubicados en `assets/react/ventas/`.

## Variables de entorno

La configuración de producción utiliza variables de entorno para evitar almacenar credenciales en el código fuente.

Entre las variables utilizadas se encuentran:

| Variable | Descripción |
|---|---|
| `APP_ENV` | Identifica el entorno de ejecución. |
| `APP_BASE_URL` | Define la URL base de la aplicación. |
| `MYSQLHOST` | Servidor de MySQL. |
| `MYSQLPORT` | Puerto de conexión. |
| `MYSQLDATABASE` | Nombre de la base de datos. |
| `MYSQLUSER` | Usuario de conexión. |
| `MYSQLPASSWORD` | Contraseña de conexión. |

Las credenciales deben configurarse directamente en el entorno de ejecución y no publicarse en el repositorio.

## Documentación de la API

La documentación de los endpoints, métodos HTTP, parámetros y respuestas se encuentra en:

```text
API.md
```

## Despliegue

El proyecto está preparado para su despliegue en Railway, utilizando servicios independientes para la aplicación PHP y la base de datos MySQL.

El backend atenderá las solicitudes de la aplicación web y de los clientes móviles que consuman la API.

Las fotografías de perfil requieren almacenamiento persistente en producción.

## Seguridad

- No publicar credenciales de conexión.
- No incluir archivos `.env` en el repositorio.
- No publicar contraseñas ni hashes de cuentas personales.
- No incluir fotografías personales ni archivos subidos por usuarios.
- Mantener la base de datos protegida mediante la red privada del proveedor.
- Utilizar HTTPS en producción.
- Validar las solicitudes y proteger las funcionalidades que requieren autenticación.

## Repositorio

[https://github.com/Kamikazo03/Confypim](https://github.com/Kamikazo03/Confypim)

---

**Confypim** — Proyecto de desarrollo de software para la gestión de pequeños emprendimientos de repostería.