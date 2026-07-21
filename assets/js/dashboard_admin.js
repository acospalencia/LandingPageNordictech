/* ==========================================================================
   dashboard_admin.js — Production Hotfix (Global Scope & Event Delegation)
   ========================================================================== */

let globalClientes = [];
let idClienteActivo = null;
let currentTab = 'Abierto'; 
let ticketsCargados = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchClientes();
    
    const searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.addEventListener('input', filtrarClientes);

    const btnCancel = document.getElementById('btn-cancel-modal');
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    const btnConfirm = document.getElementById('btn-confirm-modal');
    if (btnConfirm) btnConfirm.addEventListener('click', submitCloseTicket);

    // --- FIX 1: Event Listener para Botones de Cerrar Sesión (Desktop y Mobile) ---
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) btnLogout.addEventListener('click', ejecutarCierreSesion);

    const btnLogoutMobile = document.getElementById('btn-logout-mobile');
    if (btnLogoutMobile) btnLogoutMobile.addEventListener('click', ejecutarCierreSesion);

    // --- FIX 2: Apertura y Cierre del Sidebar de Clientes ---
    const sidebar = document.getElementById('sidebar-clientes');
    const backdrop = document.getElementById('sidebar-backdrop');
    const btnSidebarToggle = document.getElementById('btn-sidebar-toggle');

    // Toggle de apertura/cierre al presionar el botón "Clientes"
    if (btnSidebarToggle) {
        btnSidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar) sidebar.classList.toggle('nt-sidebar--open');
            if (backdrop) backdrop.classList.toggle('nt-sidebar__backdrop--visible');
        });
    }

    // Cerrar al hacer clic directo sobre el fondo transparente/oscuro (backdrop)
    if (backdrop) {
        backdrop.addEventListener('click', cerrarSidebar);
    }

    // Cierra dropdowns y sidebar al hacer clic fuera de ellos en cualquier parte de la pantalla
    document.addEventListener('click', (e) => {
        // Cierra los menús desplegables de Opciones de tickets
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
            document.querySelectorAll('.flecha-menu').forEach(fl => fl.classList.remove('rotate-180'));
        }

        // Cierra el sidebar de clientes si está abierto y el clic fue afuera del panel y del botón
        if (sidebar && sidebar.classList.contains('nt-sidebar--open')) {
            if (!sidebar.contains(e.target) && (!btnSidebarToggle || !btnSidebarToggle.contains(e.target))) {
                cerrarSidebar();
            }
        }
    });
});

// Función para forzar la ocultación del Sidebar y Backdrop
function cerrarSidebar() {
    const sidebar = document.getElementById('sidebar-clientes');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar) sidebar.classList.remove('nt-sidebar--open');
    if (backdrop) backdrop.classList.remove('nt-sidebar__backdrop--visible');
}

function showAlert(type, text) {
    const container = document.getElementById('alert-container');
    if (!container) return;
    container.className = "mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none";
    
    if (type === 'success') {
        container.classList.add('bg-green-950/20', 'border-green-500/20', 'text-green-400');
    } else {
        container.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
    }
    container.textContent = text;
    container.classList.remove('hidden');
    
    setTimeout(() => { container.classList.add('hidden'); }, 5000);
}

async function fetchClientes() {
    try {
        const response = await fetch('/assets/php/get_admin_data.php?action=get_clientes');
        const result = await response.json();
        if (result.status === 'success') {
            globalClientes = result.data;
            renderClientesList(globalClientes);
        } else {
            showAlert('error', result.message);
        }
    } catch (err) {
        showAlert('error', 'Error crítico al cargar lista de clientes.');
    }
}

function renderClientesList(lista) {
    const listContainer = document.getElementById('clientes-list');
    if (!listContainer) return;
    listContainer.innerHTML = '';

    if (lista.length === 0) {
        listContainer.innerHTML = '<div class="p-4 text-center text-xs text-nordic-textMuted">Sin coincidencias</div>';
        return;
    }

    lista.forEach(cliente => {
        const isSelected = cliente.id_usuario == idClienteActivo;
        const div = document.createElement('div');
        div.className = `p-4 cursor-pointer transition-colors ${isSelected ? 'bg-nordic-logoBlue/20 border-l-4 border-nordic-logoBlue' : 'hover:bg-nordic-card/60'}`;
        
        div.addEventListener('click', (e) => {
            selectCliente(cliente);
            // Oculta sidebar al seleccionar un cliente
            cerrarSidebar();
        });

        div.innerHTML = `
            <div class="flex justify-between items-start">
                <p class="text-xs font-bold uppercase tracking-wider text-white truncate">${cliente.nombre}</p>
                ${cliente.codigo_empresa ? `<span class="text-[8px] px-1.5 py-0.5 bg-nordic-border text-slate-300 font-mono">${cliente.codigo_empresa}</span>` : ''}
            </div>
            <p class="text-[10px] text-nordic-textMuted truncate mt-1">${cliente.email}</p>
        `;
        listContainer.appendChild(div);
    });
}

function filtrarClientes(e) {
    const query = e.target.value.toLowerCase();
    const filtrados = globalClientes.filter(c => 
        c.nombre.toLowerCase().includes(query) || 
        c.email.toLowerCase().includes(query) ||
        (c.codigo_empresa && c.codigo_empresa.toLowerCase().includes(query))
    );
    renderClientesList(filtrados);
}

function selectCliente(cliente) {
    idClienteActivo = cliente.id_usuario;
    document.getElementById('active-client-name').textContent = cliente.nombre;
    document.getElementById('active-client-meta').textContent = `ID Operador: ${cliente.id_usuario} | ${cliente.email} ${cliente.codigo_empresa ? `| Ref: ${cliente.codigo_empresa}` : ''}`;
    
    document.getElementById('no-client-selected').classList.add('hidden');
    document.getElementById('tickets-container').classList.remove('hidden');

    renderClientesList(globalClientes);
    fetchTicketsCliente(cliente.id_usuario);
}

async function fetchTicketsCliente(idUsuario) {
    try {
        const response = await fetch(`/assets/php/get_admin_data.php?action=get_tickets&id_usuario=${idUsuario}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            ticketsCargados = result.data;
            updateTabCounters();
            renderTabTickets();
        } else {
            showAlert('error', result.message);
        }
    } catch (err) {
        showAlert('error', 'Fallo al procesar la carga de incidentes.');
    }
}

function updateTabCounters() {
    const abiertos = ticketsCargados.filter(t => t.estado === 'Abierto').length;
    const proceso = ticketsCargados.filter(t => t.estado === 'En Proceso').length;
    const cerrados = ticketsCargados.filter(t => t.estado === 'Resuelto' || t.estado === 'Cerrado').length;

    document.getElementById('count-Abierto').textContent = abiertos;
    document.getElementById('count-EnProceso').textContent = proceso;
    document.getElementById('count-Cerrado').textContent = cerrados;
}

function switchTab(tab) {
    currentTab = tab;
    const tabs = ['Abierto', 'En Proceso', 'Cerrado'];
    tabs.forEach(t => {
        const key = t.replace(' ', '');
        const element = document.getElementById(`tab-${key}`);
        if (!element) return;
        if (t === tab) {
            element.className = "flex-1 py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border bg-nordic-logoBlue text-white cursor-pointer";
        } else {
            element.className = "flex-1 py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border text-nordic-textMuted hover:text-white cursor-pointer";
        }
    });

    renderTabTickets();
}

function renderTabTickets() {
    const container = document.getElementById('tickets-list');
    if (!container) return;
    container.innerHTML = '';

    let filtrados = [];
    if (currentTab === 'Cerrado') {
        filtrados = ticketsCargados.filter(t => t.estado === 'Resuelto' || t.estado === 'Cerrado');
    } else {
        filtrados = ticketsCargados.filter(t => t.estado === currentTab);
    }

    if (filtrados.length === 0) {
        container.innerHTML = `<div class="p-8 text-center text-xs text-nordic-textMuted border border-nordic-border/20">No se encontraron tickets en esta categoría</div>`;
        return;
    }

    filtrados.forEach(ticket => {
        const div = document.createElement('div');
        div.className = "bg-nordic-card border border-nordic-border relative transition-all hover:border-slate-700/60 cursor-pointer mb-4 select-none";
        
        div.addEventListener('click', (e) => {
            if (e.target.closest('.dropdown-container') || e.target.closest('.dropdown-menu')) return;

            const panel = div.querySelector('.panel-detalle');
            if (!panel) return;
            
            if (panel.classList.contains('activo')) {
                panel.classList.remove('activo');
                panel.classList.add('hidden');
            } else {
                document.querySelectorAll('.panel-detalle.activo').forEach(p => {
                    p.classList.remove('activo');
                    p.classList.add('hidden');
                });
                
                panel.classList.remove('hidden');
                panel.classList.add('activo');
            }
        });

        const dateObj = new Date(ticket.fecha_creacion);
        const formattedDate = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

        let badgeColor = "border-amber-500/20 bg-amber-950/20 text-amber-500";
        if (ticket.estado === 'En Proceso') badgeColor = "border-sky-500/20 bg-sky-950/20 text-sky-400";
        if (ticket.estado === 'Resuelto') badgeColor = "border-green-500/20 bg-green-950/20 text-green-400";
        if (ticket.estado === 'Cerrado') badgeColor = "border-slate-500/20 bg-slate-800/20 text-slate-400";

        let actionButtonsHtml = '';
        if (ticket.estado === 'Abierto') {
            actionButtonsHtml = `
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'En Proceso')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-amber-400 hover:bg-nordic-bg transition-colors">
                    Trabajar (En Proceso)
                </button>
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'Resuelto')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-green-400 hover:bg-nordic-bg transition-colors">
                    Resolver Ticket
                </button>
            `;
        } else if (ticket.estado === 'En Proceso') {
            actionButtonsHtml = `
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'En Proceso')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-amber-400 hover:bg-nordic-bg transition-colors">
                    Agregar Nota de Avance
                </button>
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'Resuelto')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-green-400 hover:bg-nordic-bg transition-colors">
                    Resolver Ticket
                </button>
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'Cerrado')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-red-400 hover:bg-nordic-bg transition-colors">
                    Cerrar Ticket
                </button>
            `;
        } else if (ticket.estado === 'Resuelto' || ticket.estado === 'Cerrado') {
            actionButtonsHtml = `
                <button type="button" onclick="window.handleActionClick(event, ${ticket.id_ticket}, 'Reabrir')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-sky-400 hover:bg-nordic-bg transition-colors">
                    Reabrir (En Proceso)
                </button>
            `;
        }

        div.innerHTML = `
            <div class="p-6 flex items-center justify-between gap-4">
                <div class="flex items-center space-x-3 min-w-0">
                    <span class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-widest border ${badgeColor} shrink-0">
                        ${ticket.estado}
                    </span>
                    <h3 class="text-sm font-display font-bold tracking-wide uppercase text-white truncate">
                        ${ticket.titulo}
                    </h3>
                </div>
                
                <div class="flex items-center space-x-4 shrink-0">
                    <span class="text-xs font-mono text-nordic-textMuted/70">#TK-${ticket.id_ticket}</span>
                    
                    <div class="relative dropdown-container">
                        <button type="button" onclick="window.toggleDropdown(event, ${ticket.id_ticket})" class="p-1.5 flex items-center space-x-1 hover:bg-nordic-bg/80 border border-nordic-border/50 text-nordic-textMuted hover:text-white transition-colors">
                            <span class="text-[10px] font-bold uppercase tracking-wider pl-1 hidden sm:inline">Opciones</span>
                            <svg class="flecha-menu h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div id="dropdown-${ticket.id_ticket}" class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-nordic-card border border-nordic-border shadow-xl z-50 divide-y divide-nordic-border/40">
                            ${actionButtonsHtml}
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-detalle hidden px-6 pb-6 space-y-4 border-t border-nordic-border/20 pt-4 bg-[#0e172a]">
                <div>
                    <span class="block text-[8px] uppercase tracking-widest text-nordic-textMuted font-bold mb-1">Descripción del Problema:</span>
                    <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-wrap">${ticket.descripcion}</p>
                </div>

                ${ticket.observacion_proceso ? `
                    <div class="p-3 bg-[#060913] border border-nordic-border/40 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-amber-500 font-bold mb-1">Bitácora de Progreso:</span>
                        <p class="text-slate-300 whitespace-pre-wrap">${ticket.observacion_proceso}</p>
                    </div>
                ` : ''}

                ${ticket.observacion_cierre ? `
                    <div class="p-3 bg-[#060913] border border-nordic-border/40 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-green-400 font-bold mb-1">Bitácora de Cierre / Resolución:</span>
                        <p class="text-slate-300 whitespace-pre-wrap">${ticket.observacion_cierre}</p>
                    </div>
                ` : ''}

                <div class="flex items-center justify-between text-[10px] text-nordic-textMuted/60 pt-1 border-t border-nordic-border/10">
                    <span>Prioridad: <strong class="text-slate-300">${ticket.prioridad}</strong></span>
                    <span>Generado el: ${formattedDate}</span>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function toggleDropdown(event, idTicket) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const targetMenu = document.getElementById(`dropdown-${idTicket}`);
    if (!targetMenu) return;

    const isHidden = targetMenu.classList.contains('hidden');

    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
    document.querySelectorAll('.flecha-menu').forEach(fl => fl.classList.remove('rotate-180'));

    if (isHidden) {
        targetMenu.classList.remove('hidden');
        const parentBtn = targetMenu.previousElementSibling;
        if (parentBtn) {
            const flecha = parentBtn.querySelector('.flecha-menu');
            if (flecha) flecha.classList.add('rotate-180');
        }
    }
}

function handleActionClick(event, idTicket, targetStatus) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    openModal(idTicket, targetStatus);
}

function openModal(idTicket, targetStatus) {
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

    const modal = document.getElementById('close-ticket-modal');
    if (!modal) {
        alert('Error: No se encontró el elemento modal en el DOM.');
        return;
    }

    document.getElementById('modal-ticket-id').value = idTicket;
    document.getElementById('modal-target-status').value = targetStatus;
    
    const resolucionText = document.getElementById('modal-resolucion-text');
    const modalTitle = document.getElementById('modal-title-text');
    const modalSubtitle = document.getElementById('modal-subtitle-text');
    const modalLabel = document.getElementById('modal-label-text');
    
    resolucionText.value = '';
    document.getElementById('modal-alert').classList.add('hidden');

    if (targetStatus === 'En Proceso') {
        if(modalTitle) modalTitle.textContent = "Trabajar Incidente / Agregar Nota";
        if(modalSubtitle) modalSubtitle.textContent = "Agrega un comentario o bitácora de red para informar de los avances.";
        if(modalLabel) modalLabel.textContent = "Nota de Trabajo";
        resolucionText.placeholder = "Indica las acciones técnicas que se están realizando...";
    } else if (targetStatus === 'Resuelto') {
        if(modalTitle) modalTitle.textContent = "Resolver Incidente";
        if(modalSubtitle) modalSubtitle.textContent = "Escribe detalladamente el diagnóstico y solución aplicada.";
        if(modalLabel) modalLabel.textContent = "Reporte de Resolución (Obligatorio)";
        resolucionText.placeholder = "Describe los cambios físicos o lógicos aplicados...";
    } else if (targetStatus === 'Cerrado') {
        if(modalTitle) modalTitle.textContent = "Cerrar Ticket";
        if(modalSubtitle) modalSubtitle.textContent = "Escribe el motivo de la finalización definitiva del caso.";
        if(modalLabel) modalLabel.textContent = "Motivo de Cierre (Obligatorio)";
        resolucionText.placeholder = "Indica la razón de la conclusión del caso...";
    } else if (targetStatus === 'Reabrir') {
        if(modalTitle) modalTitle.textContent = "Reabrir Ticket";
        if(modalSubtitle) modalSubtitle.textContent = "El caso volverá al estado 'En Proceso'.";
        if(modalLabel) modalLabel.textContent = "Motivo de la Reapertura (Obligatorio)";
        resolucionText.placeholder = "Explica por qué se reabre el ticket...";
    }

    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('close-ticket-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

async function submitCloseTicket() {
    const idTicket = document.getElementById('modal-ticket-id').value;
    const targetStatus = document.getElementById('modal-target-status').value;
    const observaciones = document.getElementById('modal-resolucion-text').value.trim();
    const modalAlert = document.getElementById('modal-alert');

    if ((targetStatus === 'Resuelto' || targetStatus === 'Cerrado' || targetStatus === 'Reabrir') && observaciones === '') {
        modalAlert.textContent = "Es obligatorio suministrar un comentario para esta acción.";
        modalAlert.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch('/assets/php/procesar_ticket_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_ticket=${idTicket}&estado=${targetStatus}&observaciones=${encodeURIComponent(observaciones)}`
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            closeModal();
            showAlert('success', result.message);
            fetchTicketsCliente(idClienteActivo);
        } else {
            modalAlert.textContent = result.message;
            modalAlert.classList.remove('hidden');
        }
    } catch (err) {
        modalAlert.textContent = "Error al intentar actualizar el estado del ticket.";
        modalAlert.classList.remove('hidden');
    }
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
        showAlert('error', 'Imposible destruir el token de sesión.');
    }
}

// ASIGNACIÓN EXPLÍCITA AL OBJETO WINDOW (Global Scope)
window.toggleDropdown = toggleDropdown;
window.handleActionClick = handleActionClick;
window.switchTab = switchTab;
window.closeModal = closeModal;
window.submitCloseTicket = submitCloseTicket;
window.ejecutarCierreSesion = ejecutarCierreSesion;
window.cerrarSidebar = cerrarSidebar;