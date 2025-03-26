document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const articuloId = document.querySelector('#articulo-id').value;
    let selectedFiles = []; // Para nuevos archivos
    let selectedArchivos = []; // Para archivos existentes
    let paginaActual = 1; // Para mantener la página actual
    
    // Inicialización de componentes
    initGaleria();
    initModalAsociar();
    initModalSubir();
    
    // ===== Galería de archivos =====
    function initGaleria() {
        // Manejar clic en botón "Establecer como principal"
        document.querySelectorAll('.archivo-principal').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const id = this.value;
                    establecerArchivoPrincipal(id);
                }
            });
        });
        
        // Manejar clic en botón "Desasociar"
        document.querySelectorAll('.btn-desasociar').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                confirmarDesasociarArchivo(id);
            });
        });
    }
    
    // ===== Modal Asociar Archivos =====
    function initModalAsociar() {
        const btnBuscarArchivo = document.getElementById('btnBuscarArchivo');
        const buscarArchivo = document.getElementById('buscarArchivo');
        const btnAsociarSeleccionados = document.getElementById('btnAsociarSeleccionados');
        
        // Escuchar evento de apertura del modal
        const modalAsociarArchivos = document.getElementById('modalAsociarArchivos');
        if (modalAsociarArchivos) {
            modalAsociarArchivos.addEventListener('shown.bs.modal', function() {
                // Reset de la búsqueda al abrir el modal
                paginaActual = 1;
                selectedArchivos = []; // Limpiar selección
                document.querySelector('.selected-count').textContent = '0';
                btnAsociarSeleccionados.disabled = true;
                
                // Limpiar resultados anteriores
                const resultadosContainer = document.getElementById('resultadosArchivos');
                if (resultadosContainer) {
                    resultadosContainer.innerHTML = `
                        <div class="col-12 text-center py-5" id="archivosMensaje">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Realice una búsqueda para ver los archivos disponibles.</p>
                        </div>
                    `;
                }
                
                // Ocultar paginación
                ocultarPaginacion();
            });
        }
        
        if (btnBuscarArchivo) {
            // Evento de búsqueda al hacer clic en el botón
            btnBuscarArchivo.addEventListener('click', function() {
                buscarArchivos(1); // Siempre comenzar desde la página 1
            });
        } else {
            console.warn('No se encontró el elemento #btnBuscarArchivo');
        }
        
        if (buscarArchivo) {
            // Buscar al presionar Enter
            buscarArchivo.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarArchivos(1); // Siempre comenzar desde la página 1
                }
            });
        } else {
            console.warn('No se encontró el elemento #buscarArchivo');
        }
        
        if (btnAsociarSeleccionados) {
            // Evento de asociar seleccionados
            btnAsociarSeleccionados.addEventListener('click', function() {
                asociarArchivosSeleccionados();
            });
        } else {
            console.warn('No se encontró el elemento #btnAsociarSeleccionados');
        }
        
        // Añadir un evento global para los botones de paginación
        // (estos se crean dinámicamente, así que usamos delegación de eventos)
        const paginacionElement = document.getElementById('paginacionArchivos');
        if (paginacionElement) {
            paginacionElement.addEventListener('click', function(e) {
                // Verificar si el clic fue en un botón de página
                const pageBtn = e.target.closest('.page-link');
                if (pageBtn && pageBtn.dataset.page) {
                    e.preventDefault();
                    const page = parseInt(pageBtn.dataset.page);
                    buscarArchivos(page);
                }
            });
        }
    }
    
    // ===== Modal Subir Archivos =====
    function initModalSubir() {
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('archivos-input');
        const uploadBtn = document.getElementById('btn-confirmar-subida');
        const previewContainer = document.getElementById('vista-previa');
        
        if (dropArea && fileInput) {
            // Al hacer clic en el área de drop, activar el input de archivos
            dropArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Configurar área de drop
            dropArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            dropArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            dropArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                handleFiles(files);
            });
            
            // Input de archivo tradicional
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });
        } else {
            console.warn('No se encontraron los elementos #drop-area o #archivos-input');
        }
        
        if (uploadBtn) {
            // Botón de subida
            uploadBtn.addEventListener('click', function() {
                subirArchivos();
            });
        } else {
            console.warn('No se encontró el elemento #btn-confirmar-subida');
        }
    }
    
    // Manejar archivos seleccionados
    function handleFiles(files) {
        const previewContainer = document.getElementById('vista-previa');
        if (!previewContainer) return;
        
        // Validar archivos
        const validFiles = Array.from(files).filter(file => {
            // Validar tipo y tamaño
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!validTypes.includes(file.type)) {
                mostrarError(`El archivo "${file.name}" no tiene un formato válido.`);
                return false;
            }
            
            if (file.size > maxSize) {
                mostrarError(`El archivo "${file.name}" excede el tamaño máximo permitido (10MB).`);
                return false;
            }
            
            return true;
        });
        
        // Añadir a la lista de seleccionados
        selectedFiles = [...selectedFiles, ...validFiles];
        
        // Actualizar interfaz
        renderizarPrevisualizaciones();
        
        const uploadBtn = document.getElementById('btn-confirmar-subida');
        if (uploadBtn) {
            uploadBtn.disabled = selectedFiles.length === 0;
        }
    }
    
    // Renderizar previsualizaciones
    function renderizarPrevisualizaciones() {
        const previewContainer = document.getElementById('vista-previa');
        if (!previewContainer) return;
        
        previewContainer.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            const previewCard = document.createElement('div');
            previewCard.className = 'col';
            
            reader.onload = function(e) {
                let previewContent = '';
                
                if (file.type.startsWith('image/')) {
                    previewContent = `<img src="${e.target.result}" class="img-thumbnail mb-2" style="height: 100px; object-fit: cover; width: 100%;">`;
                } else {
                    previewContent = `
                        <div class="text-center p-3 bg-light mb-2">
                            <i class="fas fa-file fa-2x text-secondary"></i>
                        </div>
                    `;
                }
                
                previewCard.innerHTML = `
                    <div class="card h-100">
                        <div class="card-body p-2">
                            ${previewContent}
                            <h6 class="card-title text-truncate" title="${file.name}">${file.name}</h6>
                            <p class="card-text small text-muted">${formatBytes(file.size)}</p>
                        </div>
                        <div class="card-footer p-2 bg-white">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-file w-100" data-index="${index}">
                                <i class="fas fa-times"></i> Quitar
                            </button>
                        </div>
                    </div>
                `;
                
                // Evento para quitar archivo
                previewCard.querySelector('.btn-remove-file').addEventListener('click', function() {
                    const fileIndex = parseInt(this.dataset.index);
                    selectedFiles.splice(fileIndex, 1);
                    renderizarPrevisualizaciones();
                    
                    const uploadBtn = document.getElementById('btn-confirmar-subida');
                    if (uploadBtn) {
                        uploadBtn.disabled = selectedFiles.length === 0;
                    }
                });
            };
            
            reader.readAsDataURL(file);
            previewContainer.appendChild(previewCard);
        });
    }
    
    // Función para buscar archivos
    function buscarArchivos(pagina = 1) {
        paginaActual = pagina; // Actualizar página actual
        
        const query = document.getElementById('buscarArchivo')?.value || '';
        const tipo = document.getElementById('filtroTipo')?.value || '';
        const fechaDesde = document.getElementById('filtroFechaDesde')?.value || '';
        const fechaHasta = document.getElementById('filtroFechaHasta')?.value || '';
        const resultadosContainer = document.getElementById('resultadosArchivos');
        
        if (!resultadosContainer) return;
        
        // Mostrar indicador de carga
        resultadosContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2">Buscando archivos...</p>
            </div>
        `;
        
        // Construir URL con todos los parámetros
        const url = new URL('/admin/articulo-archivo/buscar', window.location.origin);
        url.searchParams.append('q', query);
        url.searchParams.append('tipo', tipo);
        url.searchParams.append('fechaDesde', fechaDesde);
        url.searchParams.append('fechaHasta', fechaHasta);
        url.searchParams.append('pagina', pagina);
        
        // Realizar petición AJAX
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                renderizarResultadosBusqueda(data.archivos || [], resultadosContainer);
                // Verificar si data tiene información de paginación
                if (data.paginacion) {
                    actualizarPaginacion(data.paginacion);
                } else {
                    ocultarPaginacion();
                }
            })
            .catch(error => {
                console.error('Error al buscar archivos:', error);
                resultadosContainer.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                        <p class="text-muted">Error al buscar archivos. Inténtelo de nuevo.</p>
                    </div>
                `;
                ocultarPaginacion();
            });
    }
    
    // Renderizar resultados de búsqueda
    function renderizarResultadosBusqueda(archivos, container) {
        if (!container) return;
        
        if (archivos.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron archivos que coincidan con su búsqueda.</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        archivos.forEach(archivo => {
            const isSelected = selectedArchivos.includes(archivo.id);
            const isImage = archivo.tipoMime?.startsWith('image/');
            
            html += `
                <div class="col-3 mb-3">
                    <div class="card ${isSelected ? 'border-primary' : ''}">
                        ${isImage 
                            ? `<img src="/uploads/archivos/${archivo.filePath}" class="card-img-top" alt="${archivo.fileName}" style="height: 120px; object-fit: cover;">`
                            : `<div class="card-img-top d-flex align-items-center justify-content-center bg-light p-3" style="height: 120px">
                                <i class="fas fa-file fa-3x text-secondary"></i>
                               </div>`
                        }
                        <div class="card-body p-2">
                            <h6 class="card-title text-truncate" title="${archivo.fileName}">
                                ${archivo.fileName}
                            </h6>
                            <p class="card-text small text-muted mb-2">
                                ${archivo.tamanioFormateado || formatBytes(archivo.tamanio)}
                            </p>
                        </div>
                        <div class="card-footer p-2 bg-white text-center">
                            <div class="form-check">
                                <input class="form-check-input archivo-checkbox" type="checkbox" 
                                       value="${archivo.id}" id="archivo-${archivo.id}" 
                                       ${isSelected ? 'checked' : ''}>
                                <label class="form-check-label" for="archivo-${archivo.id}">
                                    Seleccionar
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Eventos de selección
        container.querySelectorAll('.archivo-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const archivoId = parseInt(this.value);
                const card = this.closest('.card');
                
                if (this.checked) {
                    if (!selectedArchivos.includes(archivoId)) {
                        selectedArchivos.push(archivoId);
                    }
                    card.classList.add('border-primary');
                } else {
                    selectedArchivos = selectedArchivos.filter(id => id !== archivoId);
                    card.classList.remove('border-primary');
                }
                
                // Actualizar botón
                const asociarBtn = document.getElementById('btnAsociarSeleccionados');
                if (asociarBtn) {
                    asociarBtn.disabled = selectedArchivos.length === 0;
                }
            });
        });
    }
    
    // Función para actualizar la paginación
    function actualizarPaginacion(paginacion) {
        const paginacionEl = document.getElementById('paginacionArchivos');
        if (!paginacionEl) return;
        
        // Verificar si hay más de una página, sino ocultar la paginación
        if (!paginacion || paginacion.totalPaginas <= 1) {
            ocultarPaginacion();
            return;
        }
        
        paginacionEl.classList.remove('d-none');
        
        const ul = paginacionEl.querySelector('ul');
        if (!ul) return;
        
        // Limpiar la paginación anterior
        ul.innerHTML = '';
        
        // Botón anterior
        const btnAnterior = document.createElement('li');
        btnAnterior.className = `page-item ${paginacion.paginaActual <= 1 ? 'disabled' : ''}`;
        btnAnterior.innerHTML = `
            <button class="page-link" data-page="${paginacion.paginaActual - 1}" 
                    ${paginacion.paginaActual <= 1 ? 'disabled' : ''} aria-label="Anterior">
                <span aria-hidden="true">&laquo;</span>
            </button>
        `;
        ul.appendChild(btnAnterior);
        
        // Mostrar un número limitado de páginas si hay muchas
        let startPage = Math.max(1, paginacion.paginaActual - 2);
        let endPage = Math.min(paginacion.totalPaginas, startPage + 4);
        
        // Ajustar para mostrar siempre 5 páginas si es posible
        if (endPage - startPage < 4) {
            startPage = Math.max(1, endPage - 4);
        }
        
        // Primera página y elipsis si es necesario
        if (startPage > 1) {
            const firstPageLi = document.createElement('li');
            firstPageLi.className = 'page-item';
            firstPageLi.innerHTML = `
                <button class="page-link" data-page="1">1</button>
            `;
            ul.appendChild(firstPageLi);
            
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `
                    <span class="page-link">...</span>
                `;
                ul.appendChild(ellipsisLi);
            }
        }
        
        // Páginas numeradas
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === paginacion.paginaActual ? 'active' : ''}`;
            li.innerHTML = `
                <button class="page-link" data-page="${i}">${i}</button>
            `;
            ul.appendChild(li);
        }
        
        // Última página y elipsis si es necesario
        if (endPage < paginacion.totalPaginas) {
            if (endPage < paginacion.totalPaginas - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                ellipsisLi.innerHTML = `
                    <span class="page-link">...</span>
                `;
                ul.appendChild(ellipsisLi);
            }
            
            const lastPageLi = document.createElement('li');
            lastPageLi.className = 'page-item';
            lastPageLi.innerHTML = `
                <button class="page-link" data-page="${paginacion.totalPaginas}">${paginacion.totalPaginas}</button>
            `;
            ul.appendChild(lastPageLi);
        }
        
        // Botón siguiente
        const btnSiguiente = document.createElement('li');
        btnSiguiente.className = `page-item ${paginacion.paginaActual >= paginacion.totalPaginas ? 'disabled' : ''}`;
        btnSiguiente.innerHTML = `
            <button class="page-link" data-page="${paginacion.paginaActual + 1}"
                    ${paginacion.paginaActual >= paginacion.totalPaginas ? 'disabled' : ''} aria-label="Siguiente">
                <span aria-hidden="true">&raquo;</span>
            </button>
        `;
        ul.appendChild(btnSiguiente);
    }
    
    // Función para ocultar la paginación
    function ocultarPaginacion() {
        const paginacionEl = document.getElementById('paginacionArchivos');
        if (paginacionEl) {
            paginacionEl.classList.add('d-none');
        }
    }
    
    // Función para asociar archivos seleccionados
    function asociarArchivosSeleccionados() {
        if (selectedArchivos.length === 0) return;
        
        const formData = new FormData();
        
        // Obtener el token CSRF del campo oculto
        const tokenField = document.getElementById('token_asociar_archivos');
        if (tokenField) {
            formData.append('_token', tokenField.value);
        } else {
            console.error('No se encontró el token CSRF para la asociación de archivos');
            mostrarError('Error de seguridad: Token CSRF no encontrado');
            return;
        }
        
        selectedArchivos.forEach(id => {
            formData.append('archivos[]', id);
        });
        
        const btnAsociar = document.getElementById('btnAsociarSeleccionados');
        const originalText = btnAsociar ? btnAsociar.innerHTML : '';
        
        if (btnAsociar) {
            btnAsociar.disabled = true;
            btnAsociar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Asociando...';
        }
        
        fetch(`/admin/articulo-archivo/${articuloId}/asociar-archivos`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (btnAsociar) {
                btnAsociar.disabled = false;
                btnAsociar.innerHTML = originalText;
            }
            
            if (data.success) {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsociarArchivos'));
                if (modal) modal.hide();
                
                // Limpiar selección
                selectedArchivos = [];
                
                // Recargar galería
                location.reload();
                
                // Mostrar mensaje de éxito
                mostrarNotificacion('Archivos asociados correctamente', 'success');
            } else {
                mostrarError('Error al asociar los archivos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (btnAsociar) {
                btnAsociar.disabled = false;
                btnAsociar.innerHTML = originalText;
            }
            mostrarError('Error al procesar la solicitud: ' + error.message);
        });
    }
    
    // Función para subir archivos
    function subirArchivos() {
        if (selectedFiles.length === 0) return;
        
        const formData = new FormData();
        
        // Obtener el token CSRF del campo oculto
        const tokenField = document.getElementById('token_subir_archivos');
        if (tokenField) {
            formData.append('_token', tokenField.value);
        } else {
            console.error('No se encontró el token CSRF para la subida de archivos');
            mostrarError('Error de seguridad: Token CSRF no encontrado');
            return;
        }
        
        selectedFiles.forEach((file, index) => {
            formData.append(`archivos[${index}]`, file);
        });
        
        const btnSubir = document.getElementById('btn-confirmar-subida');
        const originalText = btnSubir ? btnSubir.innerHTML : '';
        
        if (btnSubir) {
            btnSubir.disabled = true;
            btnSubir.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Subiendo...';
        }
        
        fetch(`/admin/articulo-archivo/${articuloId}/subir-archivos`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (btnSubir) {
                btnSubir.disabled = false;
                btnSubir.innerHTML = originalText;
            }
            
            if (data.success) {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalSubirArchivos'));
                if (modal) modal.hide();
                
                // Limpiar selección
                selectedFiles = [];
                document.getElementById('vista-previa').innerHTML = '';
                
                // Recargar galería
                location.reload();
                
                // Mostrar mensaje de éxito
                mostrarNotificacion('Archivos subidos correctamente', 'success');
            } else {
                mostrarError('Error al subir los archivos: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (btnSubir) {
                btnSubir.disabled = false;
                btnSubir.innerHTML = originalText;
            }
            mostrarError('Error al procesar la solicitud: ' + error.message);
        });
    }
    
    // Función para confirmar desasociación de archivo
    function confirmarDesasociarArchivo(id) {
        if (confirm('¿Está seguro de que desea desasociar este archivo? No se eliminará del sistema, solo se desasociará del artículo.')) {
            desasociarArchivo(id);
        }
    }
    
    // Función para desasociar archivo
    function desasociarArchivo(id) {
        const formData = new FormData();
        formData.append('_token', getCSRFToken());
        
        fetch(`/admin/articulo-archivo/${id}/desasociar`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Eliminar elemento de la galería
                const archivoItem = document.querySelector(`.archivo-item[data-id="${id}"]`);
                if (archivoItem) {
                    archivoItem.remove();
                    
                    // Verificar si hay archivos
                    const galeria = document.getElementById('archivos-galeria');
                    if (galeria && galeria.querySelectorAll('.archivo-item').length === 0) {
                        galeria.innerHTML = `
                            <div class="alert alert-info">
                                Este artículo no tiene archivos asociados.
                            </div>
                        `;
                    }
                }
                
                mostrarNotificacion('Archivo desasociado correctamente', 'success');
            } else {
                mostrarError('Error al desasociar el archivo: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al procesar la solicitud: ' + error.message);
        });
    }
    
    // Función para establecer archivo principal
    function establecerArchivoPrincipal(id) {
        const formData = new FormData();
        formData.append('_token', getCSRFToken());
        
        fetch(`/admin/articulo-archivo/${id}/establecer-principal`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Archivo establecido como principal correctamente', 'success');
            } else {
                // Si hay error, volver a la selección anterior
                document.querySelectorAll('.archivo-principal').forEach(radio => {
                    if (radio.value == id) {
                        radio.checked = false;
                    }
                });
                mostrarError('Error al establecer el archivo como principal: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Si hay error, volver a la selección anterior
            document.querySelectorAll('.archivo-principal').forEach(radio => {
                if (radio.value == id) {
                    radio.checked = false;
                }
            });
            mostrarError('Error al procesar la solicitud: ' + error.message);
        });
    }
    
    // Función para formatear tamaño en bytes
    function formatBytes(bytes, decimals = 2) {
        if (!bytes || bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    // Función para mostrar notificación
    function mostrarNotificacion(mensaje, tipo) {
        // Crear contenedor de toasts si no existe
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Crear toast
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Eliminar toast cuando se oculte
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }
    
    // Función para mostrar errores
    function mostrarError(mensaje) {
        mostrarNotificacion(mensaje, 'error');
    }
    
    // Función para obtener CSRF token
    function getCSRFToken() {
        // Buscar token en meta tags
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            return tokenMeta.getAttribute('content');
        }
        
        // Buscar token en formularios (para Symfony)
        const tokenInput = document.querySelector('input[name="_token"]');
        if (tokenInput) {
            return tokenInput.value;
        }
        
        // Buscar token en el script (generado dinámicamente)
        const articuloIdElement = document.querySelector('#articulo-id');
        if (articuloIdElement) {
            return articuloIdElement.dataset.token || '';
        }
        
        return ''; // Devolver vacío si no se encuentra
    }
});