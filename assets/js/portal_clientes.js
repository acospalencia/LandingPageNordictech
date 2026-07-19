document.addEventListener('DOMContentLoaded', () => {
    let todosLosTickets = [];
    let estadoFiltroActual = 'Abierto';

    const contenedorTickets = document.querySelector('#contenedor-tickets');
    const alertBox = document.querySelector('#portal-alert');
    const tabs = {
        'Abierto': document.getElementById('tab-Abierto'),
        'En Proceso': document.getElementById('tab-EnProceso'),
        'Cerrado': document.getElementById('tab-Cerrado')
    };

    // Vincular selectores de navegación por pestañas
    Object.keys(tabs).forEach(key => {
        tabs[key].addEventListener('click', () => switchTab(key));
    });

    // Controladores de Modales Personalizados
    document.getElementById('btn-cancel-logout').addEventListener('click', () => toggleModal('modal-logout', false));
    document.getElementById('btn-confirm-logout').addEventListener('click', ejecutarCierreSesion);
    document.getElementById('btn-logout').addEventListener('click', () => toggleModal('modal-logout', true));

    document.getElementById('btn-cancel-cierre').addEventListener('click', () => toggleModal('modal-confirmar-cierre', false));
    document.getElementById('btn-confirm-cierre').addEventListener('click', ejecutarCierreTicketCliente);

    // Sistema Interno de Mensajería en Interfaz (Sin pop-ups nativos)
    function showPortalAlert(type, text) {
        alertBox.className = "nt-alert";
        if (type === 'success') {
            alertBox.classList.add('nt-alert--success', 'nt-alert--visible');
        } else {
            alertBox.classList.add('nt-alert--error', 'nt-alert--visible');
        }
        alertBox.textContent = text;
        alertBox.classList.remove('nt-hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { alertBox.classList.add('nt-hidden'); }, 5000);
    }

    function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (show) {
            modal.classList.add('nt-modal-backdrop--visible');
        } else {
            modal.classList.remove('nt-modal-backdrop--visible');
        }
    }

    // Cargar Tickets desde Base de Datos
    async function cargarTickets() {
        try {
            const response = await fetch('/assets/php/get_tickets.php');
            const resultado = await response.json();

            if (resultado.status === 'success') {
                todosLosTickets = resultado.tickets;

                const nodoTexto = document.getElementById('nodo-activo-text');
                if (nodoTexto && resultado.nombre_usuario) {
                    nodoTexto.textContent = `Cliente: ${resultado.nombre_usuario}`;
                }

                renderizarTickets();
                actualizarContadoresTabs();
            } else if (resultado.status === 'session_expired') {
                window.location.replace('/pages/Login.php');
            } else {
                showPortalAlert('error', resultado.message);
            }
        } catch (error) {
            console.error(error);
            contenedorTickets.innerHTML = `<p class="nt-error-msg">❌ ERROR_CONECTIVIDAD: No se pudo establecer comunicación con el clúster.</p>`;
        }
    }

    function getBadgeClass(estado) {
        if (estado === 'Abierto') return 'nt-badge nt-badge--abierto';
        if (estado === 'En Proceso') return 'nt-badge nt-badge--en-proceso';
        if (estado === 'Resuelto' || estado === 'Cerrado') return 'nt-badge nt-badge--resuelto';
        return 'nt-badge nt-badge--cerrado';
    }

    function getPriorityClass(prioridad) {
        if (prioridad === 'Alta') return 'nt-ticket-card__priority--alta';
        if (prioridad === 'Crítica') return 'nt-ticket-card__priority--critica';
        return 'nt-text-slate-400';
    }

    // Renderizar tarjetas con sistema de Acordeones
    function renderizarTickets() {
        contenedorTickets.innerHTML = '';

        const ticketsFiltrados = todosLosTickets.filter(t => {
            const estadoTicket = t.estado.toLowerCase();
            if (estadoFiltroActual === 'Cerrado') {
                return estadoTicket === 'cerrado' || estadoTicket === 'resuelto';
            }
            return estadoTicket === estadoFiltroActual.toLowerCase();
        });

        if (ticketsFiltrados.length === 0) {
            contenedorTickets.innerHTML = `
                <div class="nt-card nt-card--ticket-empty">
                    <p class="nt-text-xs nt-text-muted nt-text-light">No hay registros bajo la categoría: <strong class="nt-text-white nt-uppercase">${estadoFiltroActual}</strong></p>
                </div>
            `;
            return;
        }

        ticketsFiltrados.forEach(ticket => {
            const badgeClass = getBadgeClass(ticket.estado);
            const prioridadClass = getPriorityClass(ticket.prioridad);

            const card = document.createElement('div');
            card.className = "nt-ticket-card nt-ticket-card--cliente";

            // Lógica del Acordeón: Expandir y encoger al presionar la tarjeta
            card.addEventListener('click', (e) => {
                // Si el clic viene de los botones interactivos internos, no colapsar la tarjeta
                if (e.target.closest('.action-btn')) return;

                const panel = card.querySelector('.panel-detalle');
                const flecha = card.querySelector('.flecha-desplegar');

                if (panel.classList.contains('activo')) {
                    panel.classList.remove('activo');
                    if (flecha) flecha.classList.remove('rotate-180');
                } else {
                    // Cerrar cualquier otro acordeón abierto en la vista actual (Opcional, para limpieza visual)
                    document.querySelectorAll('.panel-detalle.activo').forEach(p => p.classList.remove('activo'));
                    document.querySelectorAll('.flecha-desplegar.rotate-180').forEach(f => f.classList.remove('rotate-180'));

                    panel.classList.add('activo');
                    if (flecha) flecha.classList.add('rotate-180');
                }
            });

            card.innerHTML = `
                <!-- Cabecera de la Tarjeta (Siempre visible) -->
                <div class="nt-ticket-card__header">
                    <div class="nt-flex-row-2 nt-min-w-0">
                        <span class="nt-badge ${badgeClass} nt-badge--small nt-badge--inline">
                            ${ticket.estado}
                        </span>
                        <h3 class="nt-text-xs nt-font-semibold nt-uppercase nt-text-white nt-tracking-wide nt-truncate">${escapeHTML(ticket.titulo)}</h3>
                    </div>

                    <div class="nt-flex-row-2 nt-shrink-0">
                        <span class="nt-ticket-card__id">#TK-${ticket.id_ticket}</span>

                        <!-- Flecha de Estado del Acordeón -->
                        <svg class="flecha-desplegar nt-icon--sm nt-text-muted nt-flecha" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <!-- Contenido Oculto Desplegable (Acordeón) -->
                <div class="nt-hidden panel-detalle nt-divider-top-soft-30 nt-px-5 nt-stack-4 nt-panel-detalle--cliente">
                    <div>
                        <span class="nt-ticket-card__meta">Descripción del Problema:</span>
                        <p class="nt-text-slate-300 nt-text-xs nt-text-light nt-leading-relaxed nt-text-pre-wrap">${escapeHTML(ticket.descripcion)}</p>
                    </div>

                    <!-- Bitácora de Avance (Aparece en tickets En Proceso o Cerrados si existe) -->
                    ${ticket.observacion_proceso ? `
                        <div class="nt-ticket-card__log nt-ticket-card__log--proceso">
                            <span class="nt-ticket-card__meta nt-text-sky">Comentario de Progreso Técnico:</span>
                            <p class="nt-text-slate-300 nt-text-xs nt-text-light nt-text-pre-wrap">${escapeHTML(ticket.observacion_proceso)}</p>
                        </div>
                    ` : ''}

                    <!-- Bitácora de Cierre (Aparece en tickets resueltos/cerrados) -->
                    ${ticket.observacion_cierre ? `
                        <div class="nt-ticket-card__log nt-ticket-card__log--cierre">
                            <span class="nt-ticket-card__meta nt-text-emerald">Resolución Final del Especialista:</span>
                            <p class="nt-text-slate-300 nt-text-xs nt-text-light nt-text-pre-wrap">${escapeHTML(ticket.observacion_cierre)}</p>
                        </div>
                    ` : ''}

                    <!-- Línea de Metadatos e Interacciones Finales -->
                    <div class="nt-ticket-card__footer nt-ticket-card__footer--responsive">
                        <div class="nt-space-x-4">
                            <span>Prioridad: <strong class="${prioridadClass}">${ticket.prioridad}</strong></span>
                            <span>Apertura: ${ticket.fecha}</span>
                        </div>

                        <!-- El Botón de Cierre solo se añade dentro del acordeón si el estado es estrictamente Abierto -->
                        ${ticket.estado === 'Abierto' ? `
                            <button onclick="lanzarConfirmacionCierre(event, ${ticket.id_ticket})" class="action-btn nt-action-btn">
                                Cancelar / Cerrar Ticket
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
            contenedorTickets.appendChild(card);
        });
    }

    // Cambiar de Pestaña activa
    function switchTab(estado) {
        estadoFiltroActual = estado;
        Object.keys(tabs).forEach(key => {
            if (key === estado) {
                tabs[key].className = "nt-tabs__btn nt-tabs__btn--active";
            } else {
                tabs[key].className = "nt-tabs__btn";
            }
        });
        renderizarTickets();
    }

    // Disparar el flujo del Modal de cierre
    window.lanzarConfirmacionCierre = function(event, idTicket) {
        event.stopPropagation(); // Evita que el acordeón se cierre al presionar el botón
        document.getElementById('cerrar-ticket-id').value = idTicket;
        toggleModal('modal-confirmar-cierre', true);
    };

    // Procesar la solicitud de Cierre Directo (El cliente finaliza su propio caso abierto)
    async function ejecutarCierreTicketCliente() {
        const idTicket = document.getElementById('cerrar-ticket-id').value;
        toggleModal('modal-confirmar-cierre', false);

        try {
            // Reutiliza el endpoint administrativo enviando la instrucción directa de finalización
            const response = await fetch('/assets/php/procesar_ticket_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_ticket=${idTicket}&estado=Cerrado&observaciones=Ticket dado de baja y cerrado directamente desde la consola autónoma por el cliente.`
            });
            const result = await response.json();

            if (result.status === 'success') {
                showPortalAlert('success', 'El ticket ha sido cerrado correctamente de forma inmediata.');
                cargarTickets();
            } else {
                showPortalAlert('error', result.message);
            }
        } catch (err) {
            showPortalAlert('error', 'Fallo de enlace: No se pudo despachar el cierre al clúster.');
        }
    }

    // Crear un nuevo Ticket
    const formTicket = document.querySelector('#form-ticket');
    if (formTicket) {
        formTicket.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formTicket);
            const submitBtn = formTicket.querySelector('button[type="submit"]');

            submitBtn.disabled = true;
            submitBtn.textContent = 'TRANSMITIENDO TICKET...';

            try {
                const response = await fetch('/assets/php/crear_ticket.php', {
                    method: 'POST',
                    body: formData
                });
                const resultado = await response.json();

                if (resultado.status === 'success') {
                    showPortalAlert('success', resultado.message);
                    formTicket.reset();
                    await cargarTickets();
                } else if (resultado.status === 'session_expired') {
                    window.location.replace('/pages/Login.php');
                } else {
                    showPortalAlert('error', resultado.message);
                }
            } catch (error) {
                showPortalAlert('error', 'Error en el bus de red al intentar alojar el ticket.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Reporte al Centro de Soporte';
            }
        });
    }

    // Terminar Sesión
    async function ejecutarCierreSesion() {
        try {
            const response = await fetch('/assets/php/logout.php');
            const resultado = await response.json();
            if (resultado.status === 'success') {
                window.location.replace('/pages/Login.php');
            }
        } catch (error) {
            showPortalAlert('error', 'Imposible destruir el token de sesión.');
        }
    }

    // Actualizar Contadores Vivos
    function actualizarContadoresTabs() {
        const abiertos = todosLosTickets.filter(t => t.estado === 'Abierto').length;
        const proceso = todosLosTickets.filter(t => t.estado === 'En Proceso').length;
        const cerrados = todosLosTickets.filter(t => t.estado === 'Cerrado' || t.estado === 'Resuelto').length;

        tabs['Abierto'].textContent = `Abiertos (${abiertos})`;
        tabs['En Proceso'].textContent = `En Proceso (${proceso})`;
        tabs['Cerrado'].textContent = `Cerrados (${cerrados})`;
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
    }

    // Inicializar panel
    cargarTickets();
});
