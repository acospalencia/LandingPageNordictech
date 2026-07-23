/* ==========================================================================
   tecnicos.js — Módulo Técnico (Estructura alineada con dashboard_admin.js)
   ========================================================================== */

let globalClientes = [];
let globalTickets = [];
let clienteSeleccionadoId = null;
let estadoTabActual = 'En Proceso';

/* ==========================================================================
   HELPERS DE TIEMPO
   ========================================================================== */

function formatearDiferenciaTiempo(ms) {
    if (isNaN(ms) || ms < 0) return '0h';
    const totalHoras = Math.floor(ms / (1000 * 60 * 60));
    const dias = Math.floor(totalHoras / 24);
    const horas = totalHoras % 24;

    return dias > 0 ? `${dias}d ${horas}h` : `${horas}h`;
}

function calcularTiempoDesdeCreacion(fechaCreacionStr) {
    if (!fechaCreacionStr) return '0h';
    return formatearDiferenciaTiempo(new Date() - new Date(fechaCreacionStr));
}

function calcularTiempoUltimaActividad(fechaActualizacionStr) {
    if (!fechaActualizacionStr) return '0h';
    return formatearDiferenciaTiempo(new Date() - new Date(fechaActualizacionStr));
}

function calcularTiempoResolucion(fechaCreacionStr, fechaActualizacionStr, estado) {
    const estadoLower = (estado || '').toLowerCase();
    if ((estadoLower !== 'cerrado' && estadoLower !== 'resuelto') || !fechaActualizacionStr) {
        return 'N/A';
    }
    return formatearDiferenciaTiempo(new Date(fechaActualizacionStr) - new Date(fechaCreacionStr));
}

/* ==========================================================================
   EVENTOS INICIALES DEL DOM
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    cargarDatos();
    configurarBuscador();

    // Eventos Modal de Bitácora / Cierre
    const btnCancel = document.getElementById('btn-cancel-modal');
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    const btnConfirm = document.getElementById('btn-confirm-modal');
    if (btnConfirm) btnConfirm.addEventListener('click', submitModalBitacora);

    // Logout (Escucha tanto en versión Desktop como Mobile)
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) btnLogout.addEventListener('click', ejecutarCierreSesion);

    const btnLogoutMobile = document.getElementById('btn-logout-mobile');
    if (btnLogoutMobile) btnLogoutMobile.addEventListener('click', ejecutarCierreSesion);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
            document.querySelectorAll('.flecha-menu').forEach(fl => fl.classList.remove('rotate-180'));
        }
    });
});

/* ==========================================================================
   FUNCIÓN DE CIERRE DE SESIÓN (AJAX FETCH)
   ========================================================================== */

async function ejecutarCierreSesion() {
    try {
        const response = await fetch('/assets/php/logout.php');
        const resultado = await response.json();
        
        if (resultado.status === 'success') {
            window.location.replace('/pages/Login.php');
        } else {
            // Si la respuesta no marca success, redirige como respaldo
            window.location.replace('/pages/Login.php');
        }
    } catch (error) {
        console.error('Imposible destruir el token de sesión:', error);
        // En caso de fallo de red o parseo de JSON, se fuerza la redirección al login
        window.location.replace('/pages/Login.php');
    }
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

async function cargarDatos() {
    try {
        const response = await fetch('/assets/php/obtener_datos_tecnico.php');
        const res = await response.json();

        if (res.status === 'success') {
            globalClientes = res.clientes || [];

            if (res.tickets) {
                globalTickets = res.tickets;
            } else {
                globalTickets = [
                    ...(res.tickets_proceso || []),
                    ...(res.tickets_cerrados || [])
                ];
            }

            renderSidebarClientes(globalClientes);
        }
    } catch (e) {
        console.error('Error al cargar datos del técnico:', e);
    }
}

function renderSidebarClientes(lista) {
    const contenedor = document.getElementById('clientes-list');
    if (!contenedor) return;

    if (!lista || lista.length === 0) {
        contenedor.innerHTML = `<div class="nt-sidebar__list-empty p-4 text-center text-xs text-nordic-textMuted">No tienes clientes asignados actualmente.</div>`;
        return;
    }

    contenedor.innerHTML = lista.map(c => `
        <div onclick="window.seleccionarCliente(${c.id_usuario})" id="cliente-item-${c.id_usuario}" class="nt-sidebar__item cursor-pointer p-3 border-b border-nordic-border/50 hover:bg-white/[0.03] transition-colors">
            <div class="font-semibold text-xs text-white uppercase">${escapeHTML(c.nombre)}</div>
            <div class="text-[11px] text-nordic-textMuted truncate">${escapeHTML(c.email)}</div>
        </div>
    `).join('');
}

function seleccionarCliente(idCliente) {
    clienteSeleccionadoId = idCliente;
    const cliente = globalClientes.find(c => c.id_usuario == idCliente);

    if (!cliente) return;

    document.querySelectorAll('.nt-sidebar__item').forEach(el => el.classList.remove('bg-nordic-logoBlue/20', 'border-l-2', 'border-nordic-logoBlue'));
    const itemEl = document.getElementById(`cliente-item-${idCliente}`);
    if (itemEl) itemEl.classList.add('bg-nordic-logoBlue/20', 'border-l-2', 'border-nordic-logoBlue');

    document.getElementById('active-client-name').innerText = cliente.nombre;
    document.getElementById('active-client-meta').innerText = `Cliente: ${cliente.email}`;

    document.getElementById('no-client-selected').classList.add('hidden');
    document.getElementById('tickets-container').classList.remove('hidden');

    renderTickets();
}

function switchTab(estado) {
    estadoTabActual = estado;
    document.querySelectorAll('.nt-tabs__btn').forEach(btn => btn.classList.remove('nt-tabs__btn--active'));

    const activeTab = document.getElementById(`tab-${estado.replace(/\s+/g, '')}`);
    if (activeTab) activeTab.classList.add('nt-tabs__btn--active');

    renderTickets();
}

function renderTickets() {
    if (!clienteSeleccionadoId) return;

    const ticketsDelCliente = globalTickets.filter(t => (t.id_cliente || t.id_usuario) == clienteSeleccionadoId);

    const ticketsProceso = ticketsDelCliente.filter(t => {
        const est = (t.estado || '').toLowerCase();
        return est !== 'cerrado' && est !== 'resuelto';
    });

    const ticketsCerrados = ticketsDelCliente.filter(t => {
        const est = (t.estado || '').toLowerCase();
        return est === 'cerrado' || est === 'resuelto';
    });

    const countEnProceso = document.getElementById('count-EnProceso');
    if (countEnProceso) countEnProceso.innerText = ticketsProceso.length;

    const countCerrado = document.getElementById('count-Cerrado');
    if (countCerrado) countCerrado.innerText = ticketsCerrados.length;

    const ticketsAmostrar = (estadoTabActual === 'Cerrado') ? ticketsCerrados : ticketsProceso;
    const contenedor = document.getElementById('tickets-list');
    if (!contenedor) return;

    contenedor.innerHTML = '';

    if (ticketsAmostrar.length === 0) {
        contenedor.innerHTML = `<div class="p-8 text-center text-xs text-nordic-textMuted border border-nordic-border/20">No se encontraron tickets en estado "${estadoTabActual}" para este cliente.</div>`;
        return;
    }

    ticketsAmostrar.forEach(t => {
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

        const dateObj = new Date(t.fecha_creacion);
        const formattedDate = !isNaN(dateObj) ? dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'N/A';

        const tiempoCreacion = calcularTiempoDesdeCreacion(t.fecha_creacion);
        const tiempoActividad = calcularTiempoUltimaActividad(t.fecha_actualizacion);
        const tiempoCierre = calcularTiempoResolucion(t.fecha_creacion, t.fecha_actualizacion, t.estado);

        const estadoLower = (t.estado || '').toLowerCase();
        let badgeColor = "border-sky-500/20 bg-sky-950/20 text-sky-400";
        if (estadoLower === 'en proceso') badgeColor = "border-sky-500/20 bg-sky-950/20 text-sky-400";
        if (estadoLower === 'resuelto') badgeColor = "border-green-500/20 bg-green-950/20 text-green-400";
        if (estadoLower === 'cerrado') badgeColor = "border-slate-500/20 bg-slate-800/20 text-slate-400";

        let actionButtonsHtml = '';
        if (estadoLower !== 'cerrado') {
            actionButtonsHtml = `
                <button type="button" onclick="window.handleActionClick(event, ${t.id_ticket}, 'avance')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-amber-400 hover:bg-nordic-bg transition-colors border-b border-nordic-border/30">
                    Registrar Avance
                </button>
                <button type="button" onclick="window.handleActionClick(event, ${t.id_ticket}, 'resolver')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-emerald-400 hover:bg-nordic-bg transition-colors border-b border-nordic-border/30">
                    Resolver Ticket
                </button>
                <button type="button" onclick="window.handleActionClick(event, ${t.id_ticket}, 'cerrar')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-rose-400 hover:bg-nordic-bg transition-colors">
                    Cerrar Ticket
                </button>
            `;
        } else {
            actionButtonsHtml = `
                <div class="px-4 py-2.5 text-[11px] text-nordic-textMuted uppercase font-semibold">
                    Ticket Concluido
                </div>
            `;
        }

        const notasAsignacion = t.notas_asignacion;
        const bitacoraProceso = t.observacion_proceso || t.notas_tecnico;
        const bitacoraCierre = t.observacion_cierre;

        div.innerHTML = `
            <div class="p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center space-x-3 min-w-0">
                        <span class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-widest border ${badgeColor} shrink-0">
                            ${escapeHTML(t.estado)}
                        </span>
                        <h3 class="text-sm font-display font-bold tracking-wide uppercase text-white truncate">
                            ${escapeHTML(t.titulo || t.asunto || 'Sin asunto')}
                        </h3>
                    </div>
                    
                    <div class="flex items-center space-x-4 shrink-0">
                        <span class="text-xs font-mono text-nordic-textMuted/70">#TK-${t.id_ticket}</span>
                        
                        <div class="relative dropdown-container">
                            <button type="button" onclick="window.toggleDropdown(event, ${t.id_ticket})" class="p-1.5 flex items-center space-x-1 hover:bg-nordic-bg/80 border border-nordic-border/50 text-nordic-textMuted hover:text-white transition-colors">
                                <span class="text-[10px] font-bold uppercase tracking-wider pl-1 hidden sm:inline">Opciones</span>
                                <svg class="flecha-menu h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div id="dropdown-${t.id_ticket}" class="dropdown-menu hidden absolute right-0 mt-2 w-52 bg-nordic-card border border-nordic-border shadow-xl z-50 divide-y divide-nordic-border/40">
                                ${actionButtonsHtml}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-nordic-border/30 text-xs">
                    <span class="px-2 py-0.5 bg-nordic-logoBlue/20 text-blue-400 border border-nordic-logoBlue/40 font-bold uppercase text-[9px] tracking-wider shrink-0">
                        Tiempo
                    </span>

                    <div class="flex flex-wrap items-center gap-2">
                        <div title="Tiempo transcurrido desde la creación" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-blue-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Apertura:</span>
                            <span class="font-mono font-bold text-slate-200 group-hover:text-blue-400 transition-colors text-[11px]">${tiempoCreacion}</span>
                        </div>

                        <div title="Tiempo transcurrido desde la última actualización" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-amber-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Últ. Actividad:</span>
                            <span class="font-mono font-bold text-amber-400 text-[11px]">${tiempoActividad}</span>
                        </div>

                        <div title="Tiempo transcurrido hasta la resolución" 
                             class="flex items-center gap-1.5 px-2 py-0.5 bg-[#060913] border border-nordic-border/60 rounded-none hover:border-emerald-500/50 cursor-help transition-colors group">
                            <span class="text-nordic-textMuted text-[10px] font-medium">Resolución:</span>
                            <span class="font-mono font-bold ${estadoLower === 'cerrado' || estadoLower === 'resuelto' ? 'text-emerald-400' : 'text-slate-500'} text-[11px]">${tiempoCierre}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-detalle hidden px-6 pb-6 space-y-4 border-t border-nordic-border/20 pt-4 bg-[#0e172a]">
                ${t.nombre_tecnico ? `
                    <div class="flex items-center gap-2 p-2.5 bg-purple-950/30 border border-purple-500/30 text-purple-300 text-xs">
                        <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Técnico Asignado: <strong class="text-white uppercase font-bold">${escapeHTML(t.nombre_tecnico)}</strong></span>
                    </div>
                ` : ''}

                <div>
                    <span class="block text-[8px] uppercase tracking-widest text-nordic-textMuted font-bold mb-1">Descripción del Problema:</span>
                    <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-wrap">${escapeHTML(t.descripcion || 'Sin descripción disponible.')}</p>
                </div>

                ${notasAsignacion ? `
                    <div class="p-3 bg-[#060913] border border-blue-500/30 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-blue-400 font-bold mb-1">Notas / Indicaciones de Asignación:</span>
                        <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">${escapeHTML(notasAsignacion)}</p>
                    </div>
                ` : ''}

                ${bitacoraProceso ? `
                    <div class="p-3 bg-[#060913] border border-nordic-border/40 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-amber-500 font-bold mb-1">Historial de Bitácora / Avances:</span>
                        <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">${escapeHTML(bitacoraProceso)}</p>
                    </div>
                ` : ''}

                ${bitacoraCierre ? `
                    <div class="p-3 bg-[#060913] border border-emerald-500/40 text-xs">
                        <span class="block text-[8px] uppercase tracking-widest text-emerald-400 font-bold mb-1">Reporte de Resolución / Motivo de Cierre:</span>
                        <p class="text-slate-300 whitespace-pre-wrap leading-relaxed">${escapeHTML(bitacoraCierre)}</p>
                    </div>
                ` : ''}

                <div class="flex items-center justify-between text-[10px] text-nordic-textMuted/60 pt-1 border-t border-nordic-border/10">
                    <span>Prioridad: <strong class="text-slate-300">${escapeHTML(t.prioridad || 'Normal')}</strong></span>
                    <span>Generado el: ${formattedDate}</span>
                </div>
            </div>
        `;

        contenedor.appendChild(div);
    });
}

function configurarBuscador() {
    const input = document.getElementById('search-input');
    if (!input) return;

    input.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const filtrados = globalClientes.filter(c =>
            (c.nombre && c.nombre.toLowerCase().includes(query)) ||
            (c.email && c.email.toLowerCase().includes(query))
        );
        renderSidebarClientes(filtrados);
    });
}

/* ==========================================================================
   LÓGICA DE MODALES DE BITÁCORA
   ========================================================================== */

function handleActionClick(event, idTicket, modo) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    openModal(idTicket, modo);
}

function openModal(idTicket, modo = 'avance') {
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

    const modal = document.getElementById('close-ticket-modal');
    if (!modal) return;

    const modalTicketId = document.getElementById('modal-ticket-id');
    const modalTargetStatus = document.getElementById('modal-target-status');
    const resolucionText = document.getElementById('modal-resolucion-text');

    const modalTitle = document.getElementById('modal-title-text');
    const modalSubtitle = document.getElementById('modal-subtitle-text');
    const modalLabel = document.getElementById('modal-label-text');
    const modalAlert = document.getElementById('modal-alert');
    const btnConfirm = document.getElementById('btn-confirm-modal');

    if (modalTicketId) modalTicketId.value = idTicket;
    if (modalTargetStatus) modalTargetStatus.value = modo;
    if (resolucionText) resolucionText.value = '';
    if (modalAlert) modalAlert.classList.add('hidden');

    if (modo === 'avance') {
        if (modalTitle) modalTitle.textContent = "REGISTRAR AVANCE EN BITÁCORA";
        if (modalSubtitle) modalSubtitle.textContent = "Suministra la actualización técnica realizada en el ticket.";
        if (modalLabel) modalLabel.textContent = "Detalle de la Actividad / Notas del Técnico";
        if (btnConfirm) btnConfirm.textContent = "Guardar Avance";
    } else if (modo === 'resolver') {
        if (modalTitle) modalTitle.textContent = "RESOLVER TICKET";
        if (modalSubtitle) modalSubtitle.textContent = "Escribe el diagnóstico final y la solución aplicada.";
        if (modalLabel) modalLabel.textContent = "Reporte de Resolución";
        if (btnConfirm) btnConfirm.textContent = "Marcar como Resuelto";
    } else if (modo === 'cerrar') {
        if (modalTitle) modalTitle.textContent = "CERRAR TICKET DEFINITIVAMENTE";
        if (modalSubtitle) modalSubtitle.textContent = "Escribe las razones del cierre del ticket.";
        if (modalLabel) modalLabel.textContent = "Motivo de Cierre";
        if (btnConfirm) btnConfirm.textContent = "Cerrar Ticket";
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

async function submitModalBitacora(e) {
    if (e && e.preventDefault) e.preventDefault();

    const inputTicketId = document.getElementById('modal-ticket-id');
    const inputTargetStatus = document.getElementById('modal-target-status');
    const inputResolucion = document.getElementById('modal-resolucion-text');
    const modalAlert = document.getElementById('modal-alert');

    if (!inputResolucion || !inputResolucion.value.trim()) {
        if (modalAlert) {
            modalAlert.textContent = "Por favor, escribe un comentario antes de continuar.";
            modalAlert.classList.remove('hidden');
        } else {
            alert('Por favor, escribe un comentario antes de continuar.');
        }
        return;
    }

    const idTicket = inputTicketId ? inputTicketId.value : null;
    const modoModalActual = inputTargetStatus ? inputTargetStatus.value : 'avance';
    const textoComentario = inputResolucion.value.trim();

    const dataEnvio = {
        id_ticket: idTicket,
        accion: modoModalActual,
        comentario: textoComentario,
        enviar_correo: 1
    };

    try {
        const response = await fetch('/assets/php/guardar_bitacora_tecnico.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataEnvio)
        });

        const res = await response.json();

        if (res.status === 'success') {
            closeModal();
            await cargarDatos();
            if (clienteSeleccionadoId) seleccionarCliente(clienteSeleccionadoId);
        } else {
            if (modalAlert) {
                modalAlert.textContent = res.message || 'Error al procesar la solicitud.';
                modalAlert.classList.remove('hidden');
            } else {
                alert('Error al procesar la solicitud: ' + (res.message || 'Intente de nuevo.'));
            }
        }
    } catch (err) {
        console.error('Error al enviar bitácora:', err);
        if (modalAlert) {
            modalAlert.textContent = "Ocurrió un error al intentar guardar los datos.";
            modalAlert.classList.remove('hidden');
        }
    }
}

function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag));
}

/* ==========================================================================
   ASIGNACIÓN AL OBJETO WINDOW (GLOBAL SCOPE SAFETY)
   ========================================================================== */
window.toggleDropdown = toggleDropdown;
window.handleActionClick = handleActionClick;
window.openModal = openModal;
window.closeModal = closeModal;
window.switchTab = switchTab;
window.seleccionarCliente = seleccionarCliente;
window.ejecutarCierreSesion = ejecutarCierreSesion;