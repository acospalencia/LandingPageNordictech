/* ==========================================================================
   portal_clientes.js — Consola de Clientes (Hotfix Cierre Sesión & Modales)
   ========================================================================== */

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

    // --- FIX 1: Ocultar modales inmediatamente en la carga para no bloquear la pantalla ---
    toggleModal('modal-logout', false);
    toggleModal('modal-confirmar-cierre', false);

    // Vincular selectores de navegación por pestañas
    Object.keys(tabs).forEach(key => {
        if (tabs[key]) {
            tabs[key].addEventListener('click', () => switchTab(key));
        }
    });

    // Controladores de Modales y Cierre de Sesión (Desktop y Móvil)
    const btnLogoutDesktop = document.getElementById('btn-logout');
    if (btnLogoutDesktop) {
        btnLogoutDesktop.addEventListener('click', (e) => {
            e.preventDefault();
            toggleModal('modal-logout', true);
        });
    }

    const btnLogoutMobile = document.getElementById('btn-logout-mobile');
    if (btnLogoutMobile) {
        btnLogoutMobile.addEventListener('click', (e) => {
            e.preventDefault();
            toggleModal('modal-logout', true);
        });
    }

    const btnCancelLogout = document.getElementById('btn-cancel-logout');
    if (btnCancelLogout) {
        btnCancelLogout.addEventListener('click', () => toggleModal('modal-logout', false));
    }

    const btnConfirmLogout = document.getElementById('btn-confirm-logout');
    if (btnConfirmLogout) {
        btnConfirmLogout.addEventListener('click', ejecutarCierreSesion);
    }

    const btnCancelCierre = document.getElementById('btn-cancel-cierre');
    if (btnCancelCierre) {
        btnCancelCierre.addEventListener('click', () => toggleModal('modal-confirmar-cierre', false));
    }

    const btnConfirmCierre = document.getElementById('btn-confirm-cierre');
    if (btnConfirmCierre) {
        btnConfirmCierre.addEventListener('click', ejecutarCierreTicketCliente);
    }

    // Sistema Interno de Mensajería en Interfaz
    function showPortalAlert(type, text) {
        if (!alertBox) return;
        alertBox.className = "mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none transition-all duration-300";
        if (type === 'success') {
            alertBox.classList.add('bg-green-950/20', 'border-green-500/20', 'text-green-400');
        } else {
            alertBox.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
        }
        alertBox.textContent = text;
        alertBox.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { alertBox.classList.add('hidden'); }, 5000);
    }

    // --- FIX 2: Manejador de modales forzando inline CSS para ganar prioridad a norditch.css ---
    function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        if (show) {
            modal.classList.remove('hidden');
            modal.style.setProperty('display', 'flex', 'important');
        } else {
            modal.classList.add('hidden');
            modal.style.setProperty('display', 'none', 'important');
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
            if (contenedorTickets) {
                contenedorTickets.innerHTML = `<p class="text-xs text-rose-400 font-mono">❌ ERROR_CONECTIVIDAD: No se pudo establecer comunicación con el clúster.</p>`;
            }
        }
    }

    // Renderizar tarjetas con sistema de Acordeones
    function renderizarTickets() {
        if (!contenedorTickets) return;
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
                    <p class="text-xs text-nordic-textMuted font-light">No hay registros bajo la categoría: <strong class="text-white uppercase">${estadoFiltroActual}</strong></p>
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

            const card = document.createElement('div');
            card.className = "bg-nordic-card border border-nordic-border hover:border-nordic-logoBlue/60 transition-all cursor-pointer overflow-hidden select-none mb-4";
            
            // Acordeón de la tarjeta
            card.addEventListener('click', (e) => {
                if (e.target.closest('.action-btn')) return;

                const panel = card.querySelector('.panel-detalle');
                const flecha = card.querySelector('.flecha-desplegar');
                if (!panel) return;

                const estaOculto = panel.classList.contains('hidden');

                if (estaOculto) {
                    document.querySelectorAll('.panel-detalle').forEach(p => p.classList.add('hidden'));
                    document.querySelectorAll('.flecha-desplegar').forEach(f => f.classList.remove('rotate-180'));

                    panel.classList.remove('hidden');
                    if (flecha) flecha.classList.add('rotate-180');
                } else {
                    panel.classList.add('hidden');
                    if (flecha) flecha.classList.remove('rotate-180');
                }
            });

            card.innerHTML = `
                <div class="p-5 flex justify-between items-center gap-4">
                    <div class="flex items-center space-x-3 min-w-0">
                        <span class="inline-block ${badgeStyle} text-[9px] font-bold tracking-widest px-2 py-0.5 border uppercase shrink-0">
                            ${ticket.estado}
                        </span>
                        <h3 class="text-xs font-semibold uppercase text-white tracking-wide truncate">${escapeHTML(ticket.titulo)}</h3>
                    </div>
                    
                    <div class="flex items-center space-x-4 shrink-0">
                        <span class="text-[10px] font-mono text-nordic-textMuted hidden sm:inline">#TK-${ticket.id_ticket}</span>
                        <svg class="flecha-desplegar h-4 w-4 text-nordic-textMuted transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="panel-detalle hidden border-t border-nordic-border/30 bg-[#060a14] px-5 py-4 space-y-4">
                    <div>
                        <span class="block text-[8px] uppercase tracking-widest text-nordic-textMuted font-bold mb-1">Descripción del Problema:</span>
                        <p class="text-xs text-slate-300 font-light leading-relaxed whitespace-pre-wrap">${escapeHTML(ticket.descripcion)}</p>
                    </div>

                    ${ticket.observacion_proceso ? `
                        <div class="p-3 bg-[#0d162d] border border-blue-500/20 text-xs rounded-none">
                            <span class="block text-[8px] uppercase tracking-widest text-blue-400 font-bold mb-1">Comentario de Progreso Técnico:</span>
                            <p class="text-slate-300 font-light whitespace-pre-wrap">${escapeHTML(ticket.observacion_proceso)}</p>
                        </div>
                    ` : ''}

                    ${ticket.observacion_cierre ? `
                        <div class="p-3 bg-[#0a1c18] border border-emerald-500/20 text-xs rounded-none">
                            <span class="block text-[8px] uppercase tracking-widest text-emerald-400 font-bold mb-1">Resolución Final del Especialista:</span>
                            <p class="text-slate-300 font-light whitespace-pre-wrap">${escapeHTML(ticket.observacion_cierre)}</p>
                        </div>
                    ` : ''}

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center text-[10px] font-mono text-slate-500 pt-3 border-t border-nordic-border/20 gap-3">
                        <div class="space-x-4">
                            <span>Prioridad: <strong class="${prioridadColor}">${ticket.prioridad}</strong></span>
                            <span>Apertura: ${ticket.fecha}</span>
                        </div>
                        
                        ${ticket.estado === 'Abierto' ? `
                            <button onclick="lanzarConfirmacionCierre(event, ${ticket.id_ticket})" class="action-btn px-4 py-2 bg-red-950/40 hover:bg-red-900 border border-red-700 text-red-300 text-[9px] uppercase tracking-widest font-bold transition-colors">
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
            if (!tabs[key]) return;
            if (key === estado) {
                tabs[key].className = "flex-grow py-3 text-xs font-bold uppercase tracking-wider text-center transition-all bg-[#2A4094] text-white";
            } else {
                tabs[key].className = "flex-grow py-3 text-xs font-bold uppercase tracking-wider text-center transition-all text-nordic-textMuted hover:text-white";
            }
        });
        renderizarTickets();
    }

    // Modal de Cierre de Ticket
    window.lanzarConfirmacionCierre = function(event, idTicket) {
        if (event) event.stopPropagation();
        const idInput = document.getElementById('cerrar-ticket-id');
        if (idInput) idInput.value = idTicket;
        toggleModal('modal-confirmar-cierre', true);
    };

    // Procesar Cierre de Ticket
    async function ejecutarCierreTicketCliente() {
        const idInput = document.getElementById('cerrar-ticket-id');
        if (!idInput) return;
        const idTicket = idInput.value;
        toggleModal('modal-confirmar-cierre', false);

        try {
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

    // --- FIX 3: Proceso robusto de cierre de sesión ---
    async function ejecutarCierreSesion() {
        try {
            await fetch('/assets/php/logout.php');
        } catch (error) {
            console.error('Error de red al ejecutar logout, redirigiendo igual...', error);
        } finally {
            window.location.replace('/pages/Login.php');
        }
    }

    // Actualizar Contadores Vivos
    function actualizarContadoresTabs() {
        const abiertos = todosLosTickets.filter(t => t.estado === 'Abierto').length;
        const proceso = todosLosTickets.filter(t => t.estado === 'En Proceso').length;
        const cerrados = todosLosTickets.filter(t => t.estado === 'Cerrado' || t.estado === 'Resuelto').length;

        if (tabs['Abierto']) tabs['Abierto'].textContent = `Abiertos (${abiertos})`;
        if (tabs['En Proceso']) tabs['En Proceso'].textContent = `En Proceso (${proceso})`;
        if (tabs['Cerrado']) tabs['Cerrado'].textContent = `Cerrados (${cerrados})`;
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
    }

    // Inicializar panel
    cargarTickets();
});