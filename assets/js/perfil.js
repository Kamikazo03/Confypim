/* ==========================================================
   Perfil
   ==========================================================
   Gestiona la información personal, la fotografía y el avatar
   del usuario autenticado.
   ========================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('perfilApp');

    if (!app) {
        return;
    }

    const endpoint = app.dataset.endpoint;
    const baseUrl = app.dataset.baseUrl;

    const formPerfil = document.getElementById('formPerfil');
    const mensaje = document.getElementById('perfilMensaje');

    const inputNombre = document.getElementById('perfilNombre');
    const inputApellido = document.getElementById('perfilApellido');

    const inputFoto = document.getElementById('perfilFotoArchivo');
    const fotoPreview = document.getElementById('perfilFotoPreview');
    const fotoPlaceholder = document.getElementById('perfilFotoPlaceholder');

    const avatarPreview = document.getElementById('perfilAvatarPreview');
    const avatarTexto = document.getElementById('perfilAvatarTexto');

    const btnGuardar = document.getElementById('btnGuardarPerfil');
    const btnEliminarFoto = document.getElementById('btnEliminarFoto');
    const btnElegirAvatar = document.getElementById('btnElegirAvatar');
    const btnRestaurarIniciales = document.getElementById(
        'btnRestaurarIniciales'
    );

    // Los avatares se leen de los archivos detectados por PHP.
    let avataresDisponibles = [];

    try {
        avataresDisponibles = JSON.parse(app.dataset.avatares || '[]');
    } catch {
        avataresDisponibles = [];
    }

    let avatarActual = app.dataset.avatar || '';

    /*
    |--------------------------------------------------------------------------
    | Mensajes
    |--------------------------------------------------------------------------
    */

    function mostrarMensaje(texto, tipo = 'exito') {
        mensaje.textContent = texto;
        mensaje.className = 'perfil-mensaje';

        if (texto) {
            mensaje.classList.add(
                tipo === 'error'
                    ? 'perfil-mensaje-error'
                    : 'perfil-mensaje-exito'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Iniciales y nombre
    |--------------------------------------------------------------------------
    */

    function obtenerIniciales() {
        const nombre = inputNombre.value.trim().split(/\s+/)[0] || '';
        const apellido = inputApellido.value.trim().split(/\s+/)[0] || '';

        return (
            (Array.from(nombre)[0] || '') +
            (Array.from(apellido)[0] || '')
        ).toLocaleUpperCase('es');
    }

    function obtenerNombreMostrar() {
        const nombre = inputNombre.value.trim().split(/\s+/)[0] || '';
        const apellido = inputApellido.value.trim().split(/\s+/)[0] || '';

        return `${nombre} ${apellido}`.trim() || 'Usuario';
    }

    /*
    |--------------------------------------------------------------------------
    | Actualización de la barra superior
    |--------------------------------------------------------------------------
    */

    function actualizarNombreTopbar() {
        const elementoNombre = document.querySelector('.user-name');

        if (elementoNombre) {
            elementoNombre.textContent = obtenerNombreMostrar();
        }

        const elementoRol = document.querySelector('.user-role');

        if (elementoRol) {
            const rolActual = document.getElementById('perfilRol');

            if (rolActual) {
                elementoRol.textContent = rolActual.value;
            }
        }

        if (!avatarActual) {
            actualizarAvatarVisual('');
        }
    }

    function actualizarAvatarVisual(avatar) {
        avatarActual = avatar || '';
        app.dataset.avatar = avatarActual;

        const iniciales = obtenerIniciales();

        // Actualizar la vista previa del perfil.
        avatarPreview.replaceChildren();

        if (avatarActual) {
            const imagen = document.createElement('img');
            imagen.src =
                `${baseUrl}assets/img/avatars/${encodeURIComponent(avatarActual)}.svg`;
            imagen.alt = 'Avatar seleccionado';

            avatarPreview.appendChild(imagen);

            avatarTexto.textContent = 'Tienes un avatar seleccionado.';
            btnRestaurarIniciales.disabled = false;
        } else {
            avatarPreview.textContent = iniciales;
            avatarTexto.textContent = 'Estás utilizando tus iniciales.';
            btnRestaurarIniciales.disabled = true;
        }

        // Actualizar el avatar de la topbar.
        const avatarTopbar = document.querySelector('.user-avatar');

        if (!avatarTopbar) {
            return;
        }

        avatarTopbar.replaceChildren();

        if (avatarActual) {
            const imagen = document.createElement('img');

            imagen.src =
                `${baseUrl}assets/img/avatars/${encodeURIComponent(avatarActual)}.svg`;

            imagen.alt = `Avatar de ${obtenerNombreMostrar()}`;

            avatarTopbar.appendChild(imagen);
        } else {
            avatarTopbar.textContent = iniciales;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Comunicación con el servidor
    |--------------------------------------------------------------------------
    */

    async function enviarSolicitud(datos) {
        const respuesta = await fetch(endpoint, {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json'
            }
        });

        let resultado;

        try {
            resultado = await respuesta.json();
        } catch {
            throw new Error(
                'El servidor devolvió una respuesta inválida.'
            );
        }

        if (!respuesta.ok || !resultado.success) {
            throw new Error(
                resultado.message || 'No se pudo completar la operación.'
            );
        }

        return resultado;
    }

    /*
    |--------------------------------------------------------------------------
    | Guardar información personal
    |--------------------------------------------------------------------------
    */

    formPerfil.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const datos = new FormData(formPerfil);
        datos.append('accion', 'actualizar');

        btnGuardar.disabled = true;
        mostrarMensaje('');

        try {
            const resultado = await enviarSolicitud(datos);

            // Usar los datos devueltos por el servidor.
            if (resultado.usuario) {
                inputNombre.value = resultado.usuario.nombre || '';
                inputApellido.value = resultado.usuario.apellido || '';

                const descripcion = document.getElementById(
                    'perfilDescripcion'
                );

                if (descripcion) {
                    descripcion.value = resultado.usuario.descripcion || '';
                }

                actualizarAvatarVisual(resultado.usuario.avatar || '');
            }

            actualizarNombreTopbar();

            mostrarMensaje(
                resultado.message || 'Perfil actualizado correctamente.'
            );
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        } finally {
            btnGuardar.disabled = false;
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Descartar cambios del formulario
    |--------------------------------------------------------------------------
    |
    | El formulario vuelve a sus valores originales, pero el topbar
    | conserva el nombre que ya estaba guardado.
    |
    */

    formPerfil.addEventListener('reset', () => {
        window.setTimeout(() => {
            mostrarMensaje('');
        }, 0);
    });

    /*
    |--------------------------------------------------------------------------
    | Subir fotografía
    |--------------------------------------------------------------------------
    */

    inputFoto.addEventListener('change', async () => {
        const archivo = inputFoto.files[0];

        if (!archivo) {
            return;
        }

        const tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!tiposPermitidos.includes(archivo.type)) {
            mostrarMensaje(
                'Selecciona una imagen JPG, PNG o WebP.',
                'error'
            );

            inputFoto.value = '';
            return;
        }

        if (archivo.size <= 0 || archivo.size > 2 * 1024 * 1024) {
            mostrarMensaje(
                'La fotografía debe pesar más de 0 bytes y no superar los 2 MB.',
                'error'
            );

            inputFoto.value = '';
            return;
        }

        const datos = new FormData();
        datos.append('accion', 'subir-foto');
        datos.append('foto', archivo);

        try {
            mostrarMensaje('Subiendo fotografía...');

            const resultado = await enviarSolicitud(datos);
            const foto = resultado.usuario?.foto_perfil;

            if (foto) {
                fotoPreview.src = `${baseUrl}${foto}`;
                fotoPreview.hidden = false;
                fotoPlaceholder.hidden = true;
                btnEliminarFoto.disabled = false;
            }

            mostrarMensaje(resultado.message);
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        } finally {
            inputFoto.value = '';
        }
    });

    // ==========================================================
    // Encuadre de la fotografía del perfil
    // ==========================================================
    (() => {
        const app = document.getElementById('perfilApp');
        const contenedor = document.getElementById('perfilFotoContenedor');
        const imagen = document.getElementById('perfilFotoPreview');

        if (!app || !contenedor || !imagen) {
            console.warn(
                'Encuadre: no se encontró perfilApp, perfilFotoContenedor o perfilFotoPreview.'
            );
            return;
        }

        const endpoint = app.dataset.endpoint;

        let posicionX = Number(contenedor.dataset.posicionX ?? 50);
        let posicionY = Number(contenedor.dataset.posicionY ?? 50);

        let arrastrando = false;
        let inicioX = 0;
        let inicioY = 0;
        let posicionInicialX = 50;
        let posicionInicialY = 50;
        let anchoContenedor = 1;
        let altoContenedor = 1;

        function aplicarPosicion() {
            imagen.style.objectPosition = `${posicionX}% ${posicionY}%`;
            contenedor.dataset.posicionX = String(posicionX);
            contenedor.dataset.posicionY = String(posicionY);
        }

        function hayFotografia() {
            return !imagen.hidden && Boolean(imagen.getAttribute('src'));
        }

        aplicarPosicion();

        contenedor.addEventListener('pointerdown', (evento) => {
            if (!hayFotografia()) {
                return;
            }

            if (evento.pointerType === 'mouse' && evento.button !== 0) {
                return;
            }

            const rect = contenedor.getBoundingClientRect();

            if (!rect.width || !rect.height) {
                return;
            }

            arrastrando = true;
            inicioX = evento.clientX;
            inicioY = evento.clientY;

            posicionInicialX = posicionX;
            posicionInicialY = posicionY;

            anchoContenedor = rect.width;
            altoContenedor = rect.height;

            contenedor.classList.add('arrastrando');
            contenedor.setPointerCapture(evento.pointerId);

            evento.preventDefault();
        });

        contenedor.addEventListener('pointermove', (evento) => {
            if (!arrastrando) {
                return;
            }

            const desplazamientoX = evento.clientX - inicioX;
            const desplazamientoY = evento.clientY - inicioY;

            posicionX = Math.round(Math.max(
                0,
                Math.min(
                    100,
                    posicionInicialX - desplazamientoX / anchoContenedor * 100
                )
            ));

            posicionY = Math.round(Math.max(
                0,
                Math.min(
                    100,
                    posicionInicialY - desplazamientoY / altoContenedor * 100
                )
            ));

            aplicarPosicion();
        });

        async function terminarArrastre() {
            if (!arrastrando) {
                return;
            }

            arrastrando = false;
            contenedor.classList.remove('arrastrando');

            const datos = new FormData();
            datos.append('accion', 'encuadre');
            datos.append('posicion_x', String(posicionX));
            datos.append('posicion_y', String(posicionY));

            try {
                const respuesta = await fetch(endpoint, {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin'
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.success) {
                    throw new Error(
                        resultado.message ||
                        resultado.mensaje ||
                        'No se pudo guardar el encuadre.'
                    );
                }

                console.info('Encuadre guardado:', posicionX, posicionY);
            } catch (error) {
                console.error('Error al guardar el encuadre:', error);
            }
        }

        contenedor.addEventListener('pointerup', terminarArrastre);
        contenedor.addEventListener('pointercancel', terminarArrastre);
    })();

    /*
    |--------------------------------------------------------------------------
    | Eliminar fotografía
    |--------------------------------------------------------------------------
    */

    btnEliminarFoto.addEventListener('click', async () => {
        if (!window.confirm('¿Deseas eliminar tu fotografía de perfil?')) {
            return;
        }

        const datos = new FormData();
        datos.append('accion', 'eliminar-foto');

        try {
            const resultado = await enviarSolicitud(datos);

            fotoPreview.removeAttribute('src');
            fotoPreview.hidden = true;
            fotoPlaceholder.hidden = false;
            btnEliminarFoto.disabled = true;

            mostrarMensaje(resultado.message);
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Modal de selección de avatares
    |--------------------------------------------------------------------------
    */

    function abrirModalAvatares() {
        const modalAnterior = document.getElementById('modalAvatares');

        if (modalAnterior) {
            modalAnterior.remove();
        }

        const modal = document.createElement('div');
        modal.id = 'modalAvatares';
        modal.className = 'perfil-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'tituloModalAvatares');

        const contenido = document.createElement('div');
        contenido.className = 'perfil-modal-content';

        const encabezado = document.createElement('div');
        encabezado.className = 'perfil-modal-header';

        const titulo = document.createElement('h2');
        titulo.id = 'tituloModalAvatares';
        titulo.textContent = 'Elige tu avatar';

        const cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'perfil-modal-close';
        cerrar.setAttribute('aria-label', 'Cerrar');
        cerrar.innerHTML = '&times;';

        encabezado.append(titulo, cerrar);

        const descripcion = document.createElement('p');
        descripcion.className = 'perfil-help-text';
        descripcion.textContent =
            'Selecciona una ilustración para la barra superior.';

        const rejilla = document.createElement('div');
        rejilla.className = 'perfil-avatar-grid';

        if (avataresDisponibles.length === 0) {
            const aviso = document.createElement('p');
            aviso.textContent =
                'No hay avatares disponibles en la carpeta de imágenes.';
            rejilla.appendChild(aviso);
        }

        avataresDisponibles.forEach((avatar) => {
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'perfil-avatar-option';
            boton.setAttribute(
                'aria-pressed',
                String(avatar === avatarActual)
            );

            if (avatar === avatarActual) {
                boton.classList.add('seleccionado');
            }

            const imagen = document.createElement('img');
            imagen.src =
                `${baseUrl}assets/img/avatars/${encodeURIComponent(avatar)}.svg`;
            imagen.alt = `Avatar ${avatar}`;

            const texto = document.createElement('span');
            texto.textContent = avatar;

            boton.append(imagen, texto);

            boton.addEventListener('click', async () => {
                const datos = new FormData();
                datos.append('accion', 'avatar');
                datos.append('avatar', avatar);

                try {
                    const resultado = await enviarSolicitud(datos);

                    actualizarAvatarVisual(
                        resultado.usuario?.avatar || avatar
                    );

                    mostrarMensaje(resultado.message);
                    modal.remove();
                } catch (error) {
                    mostrarMensaje(error.message, 'error');
                }
            });

            rejilla.appendChild(boton);
        });

        const pie = document.createElement('div');
        pie.className = 'perfil-modal-footer';

        const cancelar = document.createElement('button');
        cancelar.type = 'button';
        cancelar.className = 'perfil-btn';
        cancelar.textContent = 'Cerrar';
        cancelar.addEventListener('click', () => modal.remove());

        pie.appendChild(cancelar);
        contenido.append(encabezado, descripcion, rejilla, pie);
        modal.appendChild(contenido);

        modal.addEventListener('click', (evento) => {
            if (evento.target === modal) {
                modal.remove();
            }
        });

        cerrar.addEventListener('click', () => modal.remove());

        function cerrarConEscape(evento) {
            if (evento.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', cerrarConEscape);
            }
        }

        document.addEventListener('keydown', cerrarConEscape);
        document.body.appendChild(modal);
        cerrar.focus();
    }

    btnElegirAvatar.addEventListener('click', abrirModalAvatares);

    /*
    |--------------------------------------------------------------------------
    | Restaurar iniciales
    |--------------------------------------------------------------------------
    */

    btnRestaurarIniciales.addEventListener('click', async () => {
        const datos = new FormData();
        datos.append('accion', 'avatar');
        datos.append('avatar', '');

        try {
            const resultado = await enviarSolicitud(datos);

            actualizarAvatarVisual('');
            mostrarMensaje(resultado.message);
        } catch (error) {
            mostrarMensaje(error.message, 'error');
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Estado inicial
    |--------------------------------------------------------------------------
    */

    actualizarAvatarVisual(avatarActual);
    actualizarNombreTopbar();
});