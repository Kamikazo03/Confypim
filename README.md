# Confypim

Sistema web de gestión administrativa y financiera para pequeños
emprendimientos dedicados a la elaboración y comercialización de postres
y tortas.

**Versión actual:** 1.0.3

## Descripción general

**Confypim** es una aplicación web diseñada para centralizar la
administración de un pequeño emprendimiento de repostería. Reúne en una
sola plataforma las herramientas necesarias para organizar productos,
ventas, insumos, movimientos financieros y la información general del
negocio.

El sistema cuenta con una página pública de Inicio, desde la cual los
visitantes pueden conocer el propósito de la aplicación, iniciar sesión
o crear una cuenta. Después de autenticarse, el usuario accede a un área
privada con un Dashboard y los módulos de gestión disponibles.

La aplicación busca facilitar el control de las operaciones cotidianas,
mantener organizada la información y ofrecer indicadores que apoyen el
seguimiento del emprendimiento.

## Funcionalidades principales

-   **Inicio público:** presenta el propósito de Confypim y permite
    acceder al inicio de sesión o al registro.
-   **Autenticación:** registro de usuarios, inicio y cierre de sesión,
    y protección de las rutas privadas.
-   **Dashboard:** resumen general de la actividad del emprendimiento.
-   **Productos:** administración del catálogo de productos, precios e
    información de cada creación.
-   **Ventas:** registro y consulta de ventas, productos asociados,
    cantidades, totales y estados.
-   **Insumos:** administración de ingredientes y materiales,
    existencias, movimientos y necesidades de compra.
-   **Ingresos:** consulta de movimientos de ingreso, categorías,
    evolución y rendimiento de productos.
-   **Gastos:** consulta y organización de gastos por movimientos y
    categorías.
-   **Reportes:** consulta de indicadores, balance financiero e
    historial de reportes guardados.
-   **Perfil:** actualización de información personal, fotografía,
    avatar y preferencias disponibles.

## Tecnologías utilizadas

  -----------------------------------------------------------------------
  Tecnología                          Función
  ----------------------------------- -----------------------------------
  PHP                                 Procesamiento de solicitudes y
                                      lógica del backend.

  MVC                                 Organización de controladores,
                                      modelos y vistas.

  Servicios PHP                       Separación y centralización de
                                      reglas de negocio.

  API / AJAX                          Comunicación asíncrona entre las
                                      interfaces y el backend mediante
                                      solicitudes HTTP y respuestas JSON.

  HTML                                Estructura de las interfaces.

  CSS                                 Presentación visual y diseño
                                      adaptable.

  JavaScript                          Interactividad y comunicación con
                                      los endpoints.

  React                               Desarrollo de la interfaz del
                                      módulo de Ventas mediante
                                      componentes.

  Vite                                Herramienta de desarrollo y
                                      compilación de la interfaz React.

  Chart.js                            Generación de gráficos para la
                                      consulta de información financiera.

  MySQL                               Almacenamiento de la información
                                      del sistema.

  PDO                                 Conexión y acceso a la base de
                                      datos desde PHP.

  Apache / XAMPP                      Entorno de ejecución y desarrollo
                                      local.

  Git / GitHub                        Control de versiones y alojamiento
                                      del código fuente.

  Railway                             Despliegue de la aplicación y
                                      alojamiento de la base de datos de
                                      producción.
  -----------------------------------------------------------------------

## Arquitectura general

El backend está organizado siguiendo el patrón
**Modelo-Vista-Controlador (MVC)**, complementado con una capa de
servicios.

-   **Modelos:** realizan las consultas y operaciones sobre la base de
    datos.
-   **Vistas:** presentan las interfaces que utiliza el usuario.
-   **Controladores:** reciben las solicitudes, coordinan el flujo de
    ejecución y preparan las respuestas.
-   **Servicios:** agrupan reglas de negocio, validaciones y operaciones
    que pueden ser compartidas por distintos módulos.
-   **Endpoints AJAX:** reciben solicitudes desde JavaScript y devuelven
    respuestas en formato JSON.
-   **Helpers:** contienen funciones de apoyo reutilizables, como
    autenticación y gestión de almacenamiento.

El acceso a los datos se realiza mediante PDO. Las sesiones de PHP
permiten identificar al usuario autenticado y proteger las
funcionalidades privadas.

### Organización de los módulos financieros

Ingresos, Gastos y Reportes tienen vistas y rutas de acceso
diferenciadas, pero comparten parte de la implementación interna para
evitar repetir consultas y reglas comunes. La lógica compartida se
encuentra principalmente en los archivos `FinanzasModel.php`,
`FinanzasService.php` y `finanzas.js`, mientras que las clases y
endpoints específicos establecen el contexto de cada módulo.

### Interfaz del módulo de Ventas

El módulo de Ventas utiliza React para organizar su interfaz en
componentes. Vite compila los recursos de desarrollo y genera el archivo
JavaScript que se integra en la aplicación PHP.

## Estructura general del proyecto

``` text
Confypim/
├── .git/                         # Historial y configuración local de Git
├── ajax/                         # Endpoints para solicitudes asíncronas
├── app/
│   ├── controllers/               # Controladores de los módulos
│   ├── helpers/                   # Funciones auxiliares reutilizables
│   ├── models/                    # Acceso y operaciones sobre los datos
│   ├── services/                  # Reglas de negocio y validaciones
│   └── views/                     # Interfaces organizadas por módulo
│       ├── auth/
│       ├── dashboard/
│       ├── finanzas/
│       ├── gastos/
│       ├── ingresos/
│       ├── inicio/
│       ├── insumos/
│       ├── perfil/
│       ├── productos/
│       ├── reportes/
│       ├── ventas/
│       └── sinpagina.php
├── assets/
│   ├── css/                       # Hojas de estilo
│   ├── img/                       # Logos, avatares, favicons y fotografías
│   ├── js/                        # JavaScript de los módulos
│   └── react/                     # Recursos compilados de React
├── config/                        # Configuración general y conexión
├── includes/                      # Elementos compartidos de la interfaz
├── react/                         # Código fuente del módulo React
│   ├── services/
│   ├── ventas/
│   │   └── components/
│   └── main.jsx
├── .gitattributes
├── .gitignore
├── API.md                         # Documentación de endpoints
├── composer.json
├── index.php                      # Punto de entrada y enrutador principal
├── logout.php
├── package.json
├── package-lock.json
├── README.md
├── site.webmanifest
└── vite.config.js
```

> La estructura muestra la organización general del proyecto. Cada
> carpeta contiene los archivos correspondientes a los módulos y
> responsabilidades indicados.

## Instalación y ejecución local

1.  Instalar XAMPP y activar los servicios de Apache y MySQL.

2.  Copiar la carpeta `Confypim` dentro del directorio `htdocs`.

3.  Crear una base de datos local e importar el respaldo SQL
    correspondiente.

4.  Revisar la configuración de conexión en `config/database.php` y los
    valores de entorno requeridos por la aplicación.

5.  Instalar las dependencias PHP mediante Composer cuando corresponda.

6.  Instalar las dependencias de JavaScript con npm si se necesita
    trabajar o recompilar el módulo React.

7.  Abrir el proyecto desde el navegador:

    ``` text
    http://localhost/Confypim/
    ```

8.  Crear una cuenta o iniciar sesión para acceder a los módulos
    privados.

Los respaldos SQL, credenciales y archivos que contengan información
personal deben mantenerse fuera del repositorio público.

## Despliegue

La aplicación está desplegada en **Railway**. El flujo habitual de
publicación consiste en probar los cambios en el entorno local,
registrarlos mediante Git, enviarlos al repositorio de GitHub y permitir
que Railway ejecute el despliegue configurado.

Los archivos que deban conservarse entre despliegues, como las
fotografías de perfil, se almacenan en el volumen persistente
configurado para producción. La configuración de producción y sus
credenciales deben administrarse mediante variables de entorno y
mantenerse fuera del repositorio.

## Identidad visual

Confypim utiliza una paleta cálida y suave, basada principalmente en
tonos rosa, beige y blanco:

  Uso                     Color
  ----------------------- -----------
  Fondo principal         `#F8F5F2`
  Tarjetas                `#FFFFFF`
  Color primario          `#EBC8D6`
  Color primario oscuro   `#D58BA6`
  Color primario claro    `#F6E7EE`
  Color secundario        `#B8896A`
  Texto principal         `#4A4A4A`

Las interfaces utilizan hojas de estilo propias y componentes adaptables
para conservar una experiencia coherente en computadores, tabletas y
teléfonos.

## Control de versiones

El proyecto utiliza Git y GitHub para registrar la evolución del código
fuente. Se recomienda realizar commits descriptivos, probar cada cambio
en el entorno local y verificar el funcionamiento de los módulos antes
de publicar una nueva versión.

## Documentación adicional

La documentación de los endpoints y sus operaciones se encuentra en
`API.md`.

## Autoría

Proyecto desarrollado como parte del proceso formativo de **Análisis y
Desarrollo de Software del SENA**.