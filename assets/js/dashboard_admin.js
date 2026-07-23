/* ==========================================================================
   dashboard_admin.js — Production Hotfix (Global Scope, Metrics, Technician Assignment)
   ========================================================================== */

let globalClientes = [];
let globalTecnicos = [];
let idClienteActivo = null;
let currentTab = 'Abierto';
let ticketsCargados = [];

/* ==========================================================================
   HELPERS DE CÁLCULO DE TIEMPO Y MÉTRICAS (DÍAS Y HORAS)
   ========================================================================== */

function formatearDiferenciaTiempo(ms) {
    if (isNaN(ms) || ms < 0) return '0h';

    const totalHoras = Math.floor(ms / (1000 * 60 * 60));
    const dias = Math.floor(totalHoras / 24);
    const horas = totalHoras % 24;

    if (dias > 0) {
        return `${dias}d ${horas}h`;
    }
    return `${horas}h`;
}

function calcularTiempoDesdeCreacion(fechaCreacionStr) {
    if (!fechaCreacionStr) return '0h';
    const inicio = new Date(fechaCreacionStr);
    const hoy = new Date();
    return formatearDiferenciaTiempo(hoy - inicio);
}

function calcularTiempoUltimaActividad(fechaActualizacionStr) {
    if (!fechaActualizacionStr) return '0h';
    const ultima = new Date(fechaActualizacionStr);
    const hoy = new Date();
    return formatearDiferenciaTiempo(hoy - ultima);
}

function calcularTiempoResolucion(fechaCreacionStr, fechaActualizacionStr, estado) {
    if ((estado !== 'Cerrado' && estado !== 'Resuelto') || !fechaActualizacionStr) {
        return 'N/A';
    }
    const inicio = new Date(fechaCreacionStr);
    const cierre = new Date(fechaActualizacionStr);
    return formatearDiferenciaTiempo(cierre - inicio);
}

/* ==========================================================================
   EVENTOS INICIALES DEL DOM
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    fetchClientes();
    fetchTecnicos(); // Cargar técnicos con rol id_rol = 2

    const searchInput = document.getElementById('search-input');
    if (searchInput) searchInput.addEventListener('input', filtrarClientes);

    // Eventos Pestañas (Tabs)
    const tabAbierto = document.getElementById('tab-Abierto');
    if (tabAbierto) tabAbierto.addEventListener('click', () => switchTab('Abierto'));

    const tabEnProceso = document.getElementById('tab-EnProceso');
    if (tabEnProceso) tabEnProceso.addEventListener('click', () => switchTab('En Proceso'));

    const tabCerrado = document.getElementById('tab-Cerrado');
    if (tabCerrado) tabCerrado.addEventListener('click', () => switchTab('Cerrado'));

    // Eventos Modal de Estados
    const btnCancel = document.getElementById('btn-cancel-modal');
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    const btnConfirm = document.getElementById('btn-confirm-modal');
    if (btnConfirm) btnConfirm.addEventListener('click', submitCloseTicket);

    // Eventos Modal de Asignar Técnico
    const btnCancelAssign = document.getElementById('btn-cancel-assign-modal');
    if (btnCancelAssign) btnCancelAssign.addEventListener('click', closeAssignModal);

    const btnConfirmAssign = document.getElementById('btn-confirm-assign-modal');
    if (btnConfirmAssign) btnConfirmAssign.addEventListener('click', submitAssignTech);

    // Logout
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) btnLogout.addEventListener('click', ejecutarCierreSesion);

    const btnLogoutMobile = document.getElementById('btn-logout-mobile');
    if (btnLogoutMobile) btnLogoutMobile.addEventListener('click', ejecutarCierreSesion);

    // Sidebar
    const sidebar = document.getElementById('sidebar-clientes');
    const backdrop = document.getElementById('sidebar-backdrop');
    const btnSidebarToggle = document.getElementById('btn-sidebar-toggle');

    if (btnSidebarToggle) {
        btnSidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar) sidebar.classList.toggle('nt-sidebar--open');
            if (backdrop) backdrop.classList.toggle('nt-sidebar__backdrop--visible');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', cerrarSidebar);
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
            document.querySelectorAll('.flecha-menu').forEach(fl => fl.classList.remove('rotate-180'));
        }

        if (sidebar && sidebar.classList.contains('nt-sidebar--open')) {
            if (!sidebar.contains(e.target) && (!btnSidebarToggle || !btnSidebarToggle.contains(e.target))) {
                cerrarSidebar();
            }
        }
    });
});

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

async function fetchTecnicos() {
    try {
        const response = await fetch('/assets/php/get_admin_data.php?action=get_tecnicos');
        const result = await response.json();
        if (result.status === 'success') {
            globalTecnicos = result.data;
            populateTecnicosSelect();
        }
    } catch (err) {
        console.error('Error al sincronizar lista de técnicos:', err);
    }
}

function populateTecnicosSelect() {
    const select = document.getElementById('select-tecnico');
    if (!select) return;

    select.innerHTML = '<option value="">-- Selecciona un técnico --</option>';
    globalTecnicos.forEach(tec => {
        const option = document.createElement('option');
        option.value = tec.id_usuario;

        // Soporte de Identificador Administrativo opcional + Email opcional
        const idLabel = tec.identificador ? ` [${tec.identificador}]` : (tec.identificador_admin ? ` [${tec.identificador_admin}]` : '');
        const emailLabel = tec.email ? ` (${tec.email})` : '';

        option.textContent = `${tec.nombre}${idLabel}${emailLabel}`;
        select.appendChild(option);
    });
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

        div.addEventListener('click', () => {
            selectCliente(cliente);
            cerrarSidebar();
        });

        div.innerHTML = `
            <div class="flex justify-between items-start">
                <p class="text-xs font-bold uppercase tracking-wider text-white truncate">${cliente.nombre}</p>
                ${cliente.codigo_empresa ? `<span class="text-[8px] px-1.5 py-0.5 bg-nordic-border text-slate-300 font-mono">${cliente.codigo_empresa}</span>` : ''}
            </div>
            <p class="text-[10px] text-nordic-textMuted truncate mt-1">${cliente.email || 'Sin correo asignado'}</p>
        `;
        listContainer.appendChild(div);
    });
}

function filtrarClientes(e) {
    const query = e.target.value.toLowerCase();
    const filtrados = globalClientes.filter(c =>
        (c.nombre && c.nombre.toLowerCase().includes(query)) ||
        (c.email && c.email.toLowerCase().includes(query)) ||
        (c.codigo_empresa && c.codigo_empresa.toLowerCase().includes(query))
    );
    renderClientesList(filtrados);
}

function selectCliente(cliente) {
    idClienteActivo = cliente.id_usuario;
    document.getElementById('active-client-name').textContent = cliente.nombre;
    document.getElementById('active-client-meta').textContent = `ID Operador: ${cliente.id_usuario} | ${cliente.email || 'Sin Correo'} ${cliente.codigo_empresa ? `| Ref: ${cliente.codigo_empresa}` : ''}`;

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

    const elemAbierto = document.getElementById('count-Abierto');
    if (elemAbierto) elemAbierto.textContent = abiertos;

    const elemProceso = document.getElementById('count-EnProceso');
    if (elemProceso) elemProceso.textContent = proceso;

    const elemCerrado = document.getElementById('count-Cerrado');
    if (elemCerrado) elemCerrado.textContent = cerrados;
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
                <button type="button" onclick="window.openAssignModal(event, ${ticket.id_ticket})" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-purple-400 hover:bg-nordic-bg transition-colors">
                    Asignar a Técnico
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
                <button type="button" onclick="window.openAssignModal(event, ${ticket.id_ticket})" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-purple-400 hover:bg-nordic-bg transition-colors">
                    Asignar a Técnico
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

        const tiempoCreacion = calcularTiempoDesdeCreacion(ticket.fecha_creacion);
        const tiempoActividad = calcularTiempoUltimaActividad(ticket.fecha_actualizacion);
        const tiempoCierre = calcularTiempoResolucion(ticket.fecha_creacion, ticket.fecha_actualizacion, ticket.estado);

        div.innerHTML = `
            <div class="p-5 flex flex-col gap-3">
                
                <!-- Encabezado de la Tarjeta -->
                <div class="flex items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3 min-w-0">
                        <span class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-widest border ${badgeColor} shrink-0">
                            ${ticket.estado}
                        </span>
                        <h3 class="text-sm font-display font-bold tracking-wide uppercase text-white truncate">
                            ${ticket.titulo}
                        </h3>

                        ${ticket.nombre_tecnico ? `
                            <span class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-wider border border-purple-500/30 bg-purple-950/40 text-purple-300 shrink-0">
                                Tecnico asignado: ${ticket.nombre_tecnico}
                            </span>
                        ` : ''}
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

                <!-- CONTADORES DE TIEMPO CON BADGE "TIEMPO" -->
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-nordic-border/30 text-xs">
                    
                    <span class="px-2 py-0.5 bg-nordic-logoBlue/20 text-blue-400 border border-nordic-logoBlue/40 font-bold uppercase text-[9px] tracking-wider shrink-0">
                        Tiempo
                    </span>

                    <div class="flex flex-wrap items-center gap-2">
                        <div title="Tiempo transcurrido (días y horas) desde que se abrió el ticket" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-blue-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Apertura:</span>
                            <span class="font-mono font-bold text-slate-200 group-hover:text-blue-400 transition-colors text-[11px]">${tiempoCreacion}</span>
                        </div>

                        <div title="Tiempo transcurrido (días y horas) desde la última actualización o nota agregada" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-amber-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Últ. Actividad:</span>
                            <span class="font-mono font-bold text-amber-400 text-[11px]">${tiempoActividad}</span>
                        </div>

                        <div title="Tiempo total transcurrido (días y horas) desde que se abrió hasta que se cerró el ticket" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-emerald-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Resolución:</span>
                            <span class="font-mono font-bold ${ticket.estado === 'Cerrado' || ticket.estado === 'Resuelto' ? 'text-emerald-400' : 'text-slate-500'} text-[11px]">${tiempoCierre}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Panel Plegable con Detalle Completo -->
            <div class="panel-detalle hidden px-6 pb-6 space-y-4 border-t border-nordic-border/20 pt-4 bg-[#0e172a]">
                
                ${ticket.nombre_tecnico ? `
                    <div class="flex items-center gap-2 p-2.5 bg-purple-950/30 border border-purple-500/30 text-purple-300 text-xs">
                        <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Técnico Responsable: <strong class="text-white uppercase font-bold">${ticket.nombre_tecnico}</strong> ${ticket.identificador_tecnico || ticket.identificador ? `<span class="text-purple-400 font-mono text-[10px]">(${ticket.identificador_tecnico || ticket.identificador})</span>` : ''}</span>
                    </div>
                ` : ''}

                <div>
                    <span class="block text-[8px] uppercase tracking-widest text-nordic-textMuted font-bold mb-1">Descripción del Problema:</span>
                    <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-wrap">${ticket.descripcion}</p>
                </div>

                ${ticket.observacion_proceso ? `
                    <div class="p-3 bg-[#060913] border border-nordic-border/40 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-amber-500 font-bold mb-1">Bitácora de Progreso y Asignaciones:</span>
                        <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">${ticket.observacion_proceso}</p>
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
    if (!modal) return;

    const modalTicketId = document.getElementById('modal-ticket-id');
    const modalTargetStatus = document.getElementById('modal-target-status');
    if (modalTicketId) modalTicketId.value = idTicket;
    if (modalTargetStatus) modalTargetStatus.value = targetStatus;

    const resolucionText = document.getElementById('modal-resolucion-text');
    const modalTitle = document.getElementById('modal-title-text');
    const modalSubtitle = document.getElementById('modal-subtitle-text');
    const modalLabel = document.getElementById('modal-label-text');
    const modalAlert = document.getElementById('modal-alert');

    if (resolucionText) resolucionText.value = '';
    if (modalAlert) modalAlert.classList.add('hidden');

    if (targetStatus === 'En Proceso') {
        if (modalTitle) modalTitle.textContent = "Trabajar Incidente / Agregar Nota";
        if (modalSubtitle) modalSubtitle.textContent = "Agrega un comentario o bitácora de red para informar de los avances.";
        if (modalLabel) modalLabel.textContent = "Nota de Trabajo";
        if (resolucionText) resolucionText.placeholder = "Indica las acciones técnicas que se están realizando...";
    } else if (targetStatus === 'Resuelto') {
        if (modalTitle) modalTitle.textContent = "Resolver Incidente";
        if (modalSubtitle) modalSubtitle.textContent = "Escribe detalladamente el diagnóstico y solución aplicada.";
        if (modalLabel) modalLabel.textContent = "Reporte de Resolución (Obligatorio)";
        if (resolucionText) resolucionText.placeholder = "Describe los cambios físicos o lógicos aplicados...";
    } else if (targetStatus === 'Cerrado') {
        if (modalTitle) modalTitle.textContent = "Cerrar Ticket";
        if (modalSubtitle) modalSubtitle.textContent = "Escribe el motivo de la finalización definitiva del caso.";
        if (modalLabel) modalLabel.textContent = "Motivo de Cierre (Obligatorio)";
        if (resolucionText) resolucionText.placeholder = "Indica la razón de la conclusión del caso...";
    } else if (targetStatus === 'Reabrir') {
        if (modalTitle) modalTitle.textContent = "Reabrir Ticket";
        if (modalSubtitle) modalSubtitle.textContent = "El caso volverá al estado 'En Proceso'.";
        if (modalLabel) modalLabel.textContent = "Motivo de la Reapertura (Obligatorio)";
        if (resolucionText) resolucionText.placeholder = "Explica por qué se reabre el ticket...";
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

/* ==========================================================================
   LÓGICA PARA LA MODAL DE ASIGNACIÓN DE TÉCNICO (EXACTO CON TU HTML)
   ========================================================================== */

function openAssignModal(event, idTicket) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

    const modal = document.getElementById('assign-tech-modal');
    if (!modal) {
        alert('Error HTML: No se encontró el modal con id="assign-tech-modal"');
        return;
    }

    const inputTicketId = document.getElementById('assign-ticket-id');
    const selectTecnico = document.getElementById('select-tecnico');
    const notesTecnico = document.getElementById('assign-notes-tecnico');
    const notesCliente = document.getElementById('assign-notes-cliente');
    const modalAlert = document.getElementById('assign-modal-alert');

    // Asignar ID de ticket y limpiar los campos
    if (inputTicketId) inputTicketId.value = idTicket;
    if (selectTecnico) selectTecnico.value = '';
    if (notesTecnico) notesTecnico.value = '';
    if (notesCliente) notesCliente.value = '';

    // Ocultar alertas previas
    if (modalAlert) {
        modalAlert.textContent = '';
        modalAlert.classList.add('hidden');
        modalAlert.style.display = 'none';
    }

    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function closeAssignModal() {
    const modal = document.getElementById('assign-tech-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

async function submitAssignTech(e) {
    if (e && e.preventDefault) e.preventDefault();

    const inputTicketId = document.getElementById('assign-ticket-id');
    const selectTecnico = document.getElementById('select-tecnico');
    const notesTecnicoEl = document.getElementById('assign-notes-tecnico');
    const notesClienteEl = document.getElementById('assign-notes-cliente');
    const modalAlert = document.getElementById('assign-modal-alert');

    const idTicket = inputTicketId ? inputTicketId.value : '';
    const idTecnico = selectTecnico ? selectTecnico.value : '';
    const notasTecnico = notesTecnicoEl ? notesTecnicoEl.value.trim() : '';
    const notasCliente = notesClienteEl ? notesClienteEl.value.trim() : '';

    // Función auxiliar para mostrar errores dentro del modal
    const mostrarErrorModal = (mensaje) => {
        if (modalAlert) {
            modalAlert.textContent = mensaje;
            modalAlert.classList.remove('hidden');
            modalAlert.style.display = 'block';
        } else {
            alert(mensaje);
        }
    };

    // 1. Validación de Selección de Técnico
    if (!idTecnico || idTecnico === "") {
        mostrarErrorModal("Debes seleccionar obligatoriamente un técnico de la lista.");
        return;
    }

    // 2. Validación de Instrucciones para el Técnico
    if (!notasTecnico) {
        mostrarErrorModal("Debes ingresar las instrucciones internas para el técnico.");
        return;
    }

    // 3. Validación de Comentario para el Cliente
    if (!notasCliente) {
        mostrarErrorModal("Debes ingresar el comentario para el cliente.");
        return;
    }

    // 4. Envío Petición AJAX
    try {
        // Formateamos los parámetros enviando ambos campos de texto
        const params = new URLSearchParams();
        params.append('action', 'assign_tech');
        params.append('id_ticket', idTicket);
        params.append('id_tecnico', idTecnico);
        params.append('notas_tecnico', notasTecnico);
        params.append('notas_cliente', notasCliente);

        // Variables de respaldo por si procesar_ticket_admin.php usa un nombre único
        params.append('notas', notasTecnico);
        params.append('observaciones', notasCliente);
        params.append('comentario', notasCliente);

        const response = await fetch('/assets/php/procesar_ticket_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        const result = await response.json();

        if (result.status === 'success') {
            closeAssignModal();
            showAlert('success', result.message);
            if (idClienteActivo) fetchTicketsCliente(idClienteActivo);
        } else {
            mostrarErrorModal(result.message);
        }
    } catch (err) {
        console.error('Error al intentar asignar técnico:', err);
        mostrarErrorModal("Error de red al intentar asignar el técnico.");
    }
}

async function submitCloseTicket(e) {
    if (e && e.preventDefault) e.preventDefault();

    const inputTicketId = document.getElementById('modal-ticket-id');
    const inputTargetStatus = document.getElementById('modal-target-status');
    const inputResolucion = document.getElementById('modal-resolucion-text');
    const modalAlert = document.getElementById('modal-alert');

    if (!inputTicketId || !inputTargetStatus || !inputResolucion) return;

    const idTicket = inputTicketId.value;
    const targetStatus = inputTargetStatus.value;
    const observaciones = inputResolucion.value.trim();

    if ((targetStatus === 'Resuelto' || targetStatus === 'Cerrado' || targetStatus === 'Reabrir') && observaciones === '') {
        if (modalAlert) {
            modalAlert.textContent = "Es obligatorio suministrar un comentario para esta acción.";
            modalAlert.classList.remove('hidden');
        }
        return;
    }

    try {
        const response = await fetch('/assets/php/procesar_ticket_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=change_status&id_ticket=${idTicket}&estado=${targetStatus}&observaciones=${encodeURIComponent(observaciones)}`
        });
        const result = await response.json();

        if (result.status === 'success') {
            closeModal();
            showAlert('success', result.message);
            if (idClienteActivo) fetchTicketsCliente(idClienteActivo);
        } else {
            if (modalAlert) {
                modalAlert.textContent = result.message;
                modalAlert.classList.remove('hidden');
            }
        }
    } catch (err) {
        if (modalAlert) {
            modalAlert.textContent = "Error al intentar actualizar el estado del ticket.";
            modalAlert.classList.remove('hidden');
        }
    }
}

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

/* ==========================================================================
   ASIGNACIÓN EXPLÍCITA AL OBJETO WINDOW (GLOBAL SCOPE SAFETY)
   ========================================================================== */
window.toggleDropdown = toggleDropdown;
window.handleActionClick = handleActionClick;
window.openAssignModal = openAssignModal;
window.closeAssignModal = closeAssignModal;
window.submitAssignTech = submitAssignTech;
window.switchTab = switchTab;
window.closeModal = closeModal;
window.submitCloseTicket = submitCloseTicket;
window.ejecutarCierreSesion = ejecutarCierreSesion;
window.cerrarSidebar = cerrarSidebar;
window.fetchClientes = fetchClientes;
window.fetchTecnicos = fetchTecnicos;
window.selectCliente = selectCliente;
window.filtrarClientes = filtrarClientes;
window.fetchTicketsCliente = fetchTicketsCliente;
window.renderTabTickets = renderTabTickets;
window.showAlert = showAlert;