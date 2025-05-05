document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formChat');
    const input = document.getElementById('inputMensaje');
    const chatContainer = document.getElementById('chatContainer');
    const usuarioId = window.usuarioId || 1; // Ajusta según tu sistema de login
    
    // Guardar historial de conversación
    let historialChat = [];

    // Mostrar mensaje en el chat
    function mostrarMensaje(origen, texto) {
        const div = document.createElement('div');
        div.className = origen === 'usuario' ? 'mensaje-usuario' : 'mensaje-asistente';
        if (origen === 'asistente' && window.marked) {
            div.innerHTML = `
                <div class="d-flex align-items-start gap-2">
                    <img src="/images/avatar.png" alt="Tito" class="avatar-tito me-2" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover;">
                    <span class="burbuja burbuja-${origen}">${marked.parse(texto)}</span>
                </div>
            `;
        } else {
            div.innerHTML = `<span class="burbuja burbuja-${origen}">${texto}</span>`;
        }
        chatContainer.appendChild(div);
        chatContainer.scrollTop = chatContainer.scrollHeight;
        
        // Guardar mensaje en el historial
        if (origen === 'usuario') {
            historialChat.push({ role: 'user', content: texto });
        } else if (origen === 'asistente') {
            historialChat.push({ role: 'assistant', content: texto });
        }
    }

    // Mostrar animación "Tito está escribiendo..."
    function mostrarEscribiendo() {
        const div = document.createElement('div');
        div.className = 'mensaje-asistente escribiendo';
        div.innerHTML = `
            <div class="d-flex align-items-start gap-2">
                <img src="/images/avatar.png" alt="Tito" class="avatar-tito me-2" style="width: 52px; height: 52px; border-radius: 50%; object-fit: cover;">
                <span class="burbuja burbuja-asistente"><span class="puntos">Tito está escribiendo<span class="punto">.</span><span class="punto">.</span><span class="punto">.</span></span></span>
            </div>
        `;
        div.id = 'tito-escribiendo';
        chatContainer.appendChild(div);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function ocultarEscribiendo() {
        const escribiendo = document.getElementById('tito-escribiendo');
        if (escribiendo) escribiendo.remove();
    }

    // Mostrar producto en el chat
    function mostrarProducto(producto) {
        const div = document.createElement('div');
        div.className = 'mensaje-asistente';
        div.innerHTML = `
            <div class="card-producto p-2">
                ${producto.imagen ? `<img src="${producto.imagen}" class="img-fluid mb-2" alt="${producto.detalle}">` : ''}
                <div><strong>${producto.detalle}</strong></div>
                <div>Marca: <span class="text-primary">${producto.marca}</span></div>
                <div>Precio: <span class="text-success fw-bold">$${producto.precio.toLocaleString('es-AR', {minimumFractionDigits: 2})}</span></div>
                ${producto.fichaTecnica ? `<a href="${producto.fichaTecnica}" target="_blank" class="btn btn-sm btn-outline-info mt-1">Ficha técnica</a>` : ''}
            </div>
        `;
        chatContainer.appendChild(div);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Enviar mensaje al backend
    function enviarMensajeAlAsistente(mensaje, usuarioId, contextoUsuario) {
        // Mostrar el mensaje del usuario pero NO lo agregamos al historial aquí
        // porque lo agregaremos en mostrarMensaje
        mostrarMensaje('usuario', mensaje);
        input.value = '';
        mostrarEscribiendo();
        try {
            fetch('/asistente/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mensaje: mensaje,
                    usuario_id: usuarioId,
                    historial: historialChat, // Enviamos el historial completo
                    contexto_usuario: contextoUsuario // objeto con info relevante
                })
            })
            .then(response => response.json())
            .then(data => {
                ocultarEscribiendo();
                mostrarMensaje('asistente', data.respuesta);
                if (data.productos && data.productos.length > 0) {
                    data.productos.forEach(mostrarProducto);
                }
            });
        } catch (e) {
            ocultarEscribiendo();
            mostrarMensaje('asistente', 'Ocurrió un error al consultar el asistente.');
        }
    }

    // Manejar envío del formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const mensaje = input.value.trim();
        if (mensaje) {
            enviarMensajeAlAsistente(mensaje, usuarioId, {});
        }
    });

    // Mensaje de bienvenida inicial
    mostrarMensaje('asistente', '¡Hola! Soy el asistente virtual de Ciardi Hnos. ¿En qué puedo ayudarte hoy?');
});
