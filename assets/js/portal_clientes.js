document.addEventListener('DOMContentLoaded', () => {
    let todosLosTickets = [];
    let estadoFiltroActual = 'Abierto'; // Filtro por defecto

    // Referencias a la interfaz
    const contenedorTickets = document.querySelector('#contenedor-tickets');
    const alertBox = document.querySelector('#portal-alert');
    const tabs = {
        'Abierto': document.getElementById('tab-Abierto'),
        'En Proceso': document.getElementById('tab-EnProceso'),
        'Cerrado': document.getElementById('tab-Cerrado')
    };

    // Vincular selectores de pestañas
    Object.keys(tabs).forEach(key => {
        tabs[key].addEventListener('click', () => switchTab(key));
    });

    // Vincular modales internos
    document.getElementById('btn-cancel-logout').addEventListener('click', () => toggleModal('modal-logout', false));
    document.getElementById('btn-confirm-logout').addEventListener('click', ejecutarCierreSesion);
    document.getElementById('btn-logout').addEventListener('click', () => toggleModal('modal-logout', true));

    document.getElementById('btn-cancel-cierre').addEventListener('click', () => toggleModal('modal-confirmar-cierre', false));
    document.getElementById('btn-confirm-cierre').addEventListener('click', ejecutarCierreCliente);

    // Sistema de Alertas internas sin Pop-ups
    function showPortalAlert(type, text) {
        alertBox.className = "mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none";
        if (type === 'success') {
            alertBox.classList.add('bg-green-950/20', 'border-green-500/20', 'text-green-400');
        } else {
            alertBox.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
        }
        alertBox.textContent = text;
        alertBox.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { alertBox.classList.add('hidden'); }, 6000);
    }

    // Cambiar visibilidad de modales
    function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (show) {
            modal.classList.remove('hidden');
        } else {
            modal.classList.add('hidden');
        }
    }

    // 1. Obtener tickets desde la API PHP
    async function cargarTickets() {
        try {
            const response = await fetch('/assets/php/get_tickets.php');
            const resultado = await response.json();

            if (resultado.status === 'success') {
                todosLosTickets = resultado.tickets;
                
                const nodoTexto = document.getElementById('nodo-activo-text');
                if (nodoTexto && resultado.nombre_usuario) {
                    nodoTexto.textContent = `Nodo Activo: ${resultado.nombre_usuario}`;
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
            contenedorTickets.innerHTML = `<p class="text-xs text-rose-400 font-mono">❌ ERROR_CONECTIVIDAD: Enlace de datos inalcanzable.</p>`;
        }
    }

    // 2. Renderizar lista con Acordeones Desplegables al dar clic
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
                <div class="bg-nordic-card border border-nordic-border p-8 text-center">
                    <p class="text-xs text-nordic-textMuted font-light">No se registran solicitudes en estado: <strong class="text-white uppercase">${estadoFiltroActual}</strong></p>
                </div>
            `;
            return;
        }

        ticketsFiltrados.forEach(ticket => {
            let badgeStyle = "bg-amber-500/10 text-amber-400 border-amber-500/20";
            if (ticket.estado === 'En Proceso') badgeStyle = "bg-blue-500/10 text-blue-400 border-blue-500/20";
            if (ticket.estado === 'Resuelto' || ticket.estado === 'Cerrado') badgeStyle = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";

            let prioridadColor = "text-slate-400";
            if (ticket.prioridad === 'Alta') prioridadColor = "text-orange-400 font-bold";
            if (ticket.prioridad === 'Crítica') prioridadColor = "text-rose-400 font-bold animate-pulse";

            const div = document.createElement('div');
            div.className = "bg-nordic-card border border-nordic-border hover:border-nordic-logoBlue/45 transition-all cursor-pointer overflow-hidden";
            
            // Evento interactivo para colapsar y expandir suavemente el cuerpo al hacer clic
            div.addEventListener('click', (e) => {
                if (e.target.closest('.btn-cerrar-accion')) return; // Prevenir cierre si presiona el botón
                
                const panelDetalle = div.querySelector('.panel-detalle');
                const flechaIcono = div.querySelector('.flecha-desplegar');
                
                if (panelDetalle.classList.contains('hidden')) {
                    panelDetalle.classList.remove('hidden');
                    if (flechaIcono) flechaIcono.classList.add('rotate-180');
                } else {
                    panelDetalle.classList.add('hidden');
                    if (flechaIcono) flechaIcono.classList.remove('rotate-180');
                }
            });

            div.innerHTML = `
                <div class="p-5 flex justify-between items-center gap-4">
                    <div class="flex items-center space-x-3">
                        <span class="inline-block ${badgeStyle} text-[9px] font-bold tracking-widest px-2 py-0.5 border uppercase">
                            ${ticketindexToText(ticket.estado)}
                        </span>
                        <h3 class="text-sm font-semibold uppercase text-white tracking-wide truncate max-w-xs md:max-w-md">${escapeHTML(ticket.titulo)}</h3>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-[10px] font-mono text-nordic-textMuted">#TK-${ticket.id_ticket}</span>
                        
                        ${ticket.estado === 'Abierto' ? `
                            <button onclick="solicitarCierreCliente(event, ${ticket.id_ticket})" class="btn-cerrar-accion px-3 py-1.5 bg-red-950/30 hover:bg-red-950/70 border border-red-500/25 text-red-400 text-[9px] uppercase tracking-widest font-bold transition-all">
                                Cerrar Caso
                            </button>
                        ` : ''}

                        <svg class="flecha-desplegar h-4 w-4 text-nordic-textMuted transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="panel-detalle hidden border-t border-nordic-border/30 bg-[#060a14] p-5 space-y-4">
                    <div>
                        <span class="block text-[8px] uppercase tracking-widest text-nordic-textMuted font-bold mb-1">Descripción del Incidente:</span>
                        <p class="text-xs text-[#b4c6ef] font-light leading-relaxed whitespace-pre-wrap">${escapeHTML(ticket.descripcion)}</p>
                    </div>

                    ${ticket.observacion_proceso ? `
                        <div class="p-3 bg-nordic-card border border-blue-500/10 text-xs">
                            <span class="block text-[8px] uppercase tracking-widest text-blue-400 font-bold mb-1">Comentario Técnico (Progreso):</span>
                            <p class="text-slate-300 whitespace-pre-wrap">${escapeHTML(ticket.observacion_proceso)}</p>
                        </div>
                    ` : ''}

                    ${ticket.observacion_cierre ? `
                        <div class="p-3 bg-nordic-card border border-emerald-500/10 text-xs">
                            <span class="block text-[8px] uppercase tracking-widest text-emerald-400 font-bold mb-1">Diagnóstico Final de Resolución:</span>
                            <p class="text-slate-300 whitespace-pre-wrap">${escapeHTML(ticket.observacion_cierre)}</p>
                        </div>
                    ` : ''}

                    <div class="flex justify-between items-center text-[10px] font-mono text-slate-500 pt-2 border-t border-nordic-border/20">
                        <span>Prioridad asignada: <strong class="${prioridadColor}">${ticket.prioridad}</strong></span>
                        <span>Apertura: ${ticket.fecha}</span>
                    </div>
                </div>
            `;
            contenedorTickets.appendChild(div);
        });
    }

    // 3. Control de Pestañas
    function switchTab(estado) {
        estadoFiltroActual = estado;
        
        Object.keys(tabs).forEach(key => {
            if (key === estado) {
                tabs[key].className = "flex-grow py-3 text-xs font-bold uppercase tracking-wider text-center transition-all bg-[#2A4094] text-white rounded-none";
            } else {
                tabs[key].className = "flex-grow py-3 text-xs font-bold uppercase tracking-wider text-center transition-all text-nordic-textMuted hover:text-white rounded-none";
            }
        });

        renderizarTickets();
    }

    // 4. Solicitar cierre (Inicia flujo directo)
    window.solicitarCierreCliente = function(event, idTicket) {
        event.stopPropagation(); // Prevenir colapso
        document.getElementById('cerrar-ticket-id').value = idTicket;
        toggleModal('modal-confirmar-cierre', true);
    };

    async function ejecutarCierreCliente() {
        const idTicket = document.getElementById('cerrar-ticket-id').value;

        try {
            // El cliente cierra el ticket directamente, enviando un mensaje predefinido automático
            const response = await fetch('/assets/php/procesar_ticket_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_ticket=${idTicket}&estado=Cerrado&observaciones=Caso concluido de forma anticipada directamente por confirmación del cliente.`
            });
            const result = await response.json();

            if (result.status === 'success') {
                toggleModal('modal-confirmar-cierre', false);
                showPortalAlert('success', 'Procedimiento concluido: Caso cerrado exitosamente.');
                cargarTickets();
            } else {
                showPortalAlert('error', result.message);
            }
        } catch (err) {
            showPortalAlert('error', 'Error al procesar la solicitud de cierre con el clúster.');
        }
    }

    // 5. Gestión del Logout
    async function ejecutarCierreSesion() {
        try {
            const response = await fetch('/assets/php/logout.php');
            const resultado = await response.json();
            if (resultado.status === 'success') {
                window.location.replace('/pages/Login.php');
            }
        } catch (error) {
            showPortalAlert('error', 'Error de comunicación al cerrar la sesión.');
        }
    }

    // 6. Enviar Formulario de Creación de Tickets
    const formTicket = document.querySelector('#form-ticket');
    if (formTicket) {
        formTicket.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(formTicket);

            // Cambiar visual del botón a carga
            const submitBtn = formTicket.querySelector('button[type="submit"]');
            const origText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.textContent = 'PROCESANDO SOLICITUD...';

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
                showPortalAlert('error', 'Ocurrió un problema de conectividad al registrar el ticket.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origText;
            }
        });
    }

    // 7. Actualizar Contadores de Pestañas
    function actualizarContadoresTabs() {
        const abiertos = todosLosTickets.filter(t => t.estado === 'Abierto').length;
        const proceso = todosLosTickets.filter(t => t.estado === 'En Proceso').length;
        const cerrados = todosLosTickets.filter(t => t.estado === 'Cerrado' || t.estado === 'Resuelto').length;

        tabs['Abierto'].textContent = `Abiertos (${abiertos})`;
        tabs['En Proceso'].textContent = `En Proceso (${proceso})`;
        tabs['Cerrado'].textContent = `Cerrados (${cerrados})`;
    }

    // Helpers
    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    function ticketindexToText(estado) {
        if (estado === 'Cerrado' || estado === 'Resuelto') {
            return 'Cerrado / Resuelto';
        }
        return estado;
    }

    // Ejecución de entrada
    cargarTickets();
});