let globalClientes = [];
let idClienteActivo = null;
let currentTab = 'Abierto';
let ticketsCargados = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchClientes();

    document.getElementById('search-input').addEventListener('input', filtrarClientes);
    document.getElementById('btn-cancel-modal').addEventListener('click', closeModal);
    document.getElementById('btn-confirm-modal').addEventListener('click', submitCloseTicket);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nt-dropdown-js')) {
            document.querySelectorAll('.nt-dropdown-js__menu').forEach(menu => menu.classList.add('nt-hidden'));
        }
    });
});

function showAlert(type, text) {
    const container = document.getElementById('alert-container');
    container.className = "nt-alert";

    if (type === 'success') {
        container.classList.add('nt-alert--success', 'nt-alert--visible');
    } else {
        container.classList.add('nt-alert--error', 'nt-alert--visible');
    }
    container.textContent = text;
    container.classList.remove('nt-hidden');

    setTimeout(() => { container.classList.add('nt-hidden'); }, 5000);
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
    listContainer.innerHTML = '';

    if (lista.length === 0) {
        listContainer.innerHTML = '<div class="nt-sidebar__list-empty">Sin coincidencias</div>';
        return;
    }

    lista.forEach(cliente => {
        const isSelected = cliente.id_usuario == idClienteActivo;
        const div = document.createElement('div');
        if (isSelected) {
            div.className = "nt-sidebar__list-item nt-sidebar__list-item--active";
        } else {
            div.className = "nt-sidebar__list-item";
        }
        div.onclick = () => selectCliente(cliente);
        const empresaTag = cliente.codigo_empresa
            ? `<span class="nt-badge nt-badge--neutral nt-badge--small">${cliente.codigo_empresa}</span>`
            : '';
        div.innerHTML = `
            <div class="nt-flex-row-2">
                <p class="nt-text-xs nt-font-bold nt-uppercase nt-tracking-wider nt-text-white nt-truncate">${cliente.nombre}</p>
                ${empresaTag}
            </div>
            <p class="nt-text-2xs nt-text-muted nt-truncate nt-mt-1">${cliente.email}</p>
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

    document.getElementById('no-client-selected').classList.add('nt-hidden');
    document.getElementById('tickets-container').classList.remove('nt-hidden');

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
        if (t === tab) {
            element.className = "nt-tabs__btn nt-tabs__btn--active";
        } else {
            element.className = "nt-tabs__btn";
        }
    });

    renderTabTickets();
}

function getBadgeClass(estado) {
    if (estado === 'Abierto') return 'nt-badge nt-badge--abierto';
    if (estado === 'En Proceso') return 'nt-badge nt-badge--en-proceso';
    if (estado === 'Resuelto') return 'nt-badge nt-badge--resuelto';
    if (estado === 'Cerrado') return 'nt-badge nt-badge--cerrado';
    return 'nt-badge nt-badge--neutral';
}

function renderTabTickets() {
    const container = document.getElementById('tickets-list');
    container.innerHTML = '';

    let filtrados = [];
    if (currentTab === 'Cerrado') {
        filtrados = ticketsCargados.filter(t => t.estado === 'Resuelto' || t.estado === 'Cerrado');
    } else {
        filtrados = ticketsCargados.filter(t => t.estado === currentTab);
    }

    if (filtrados.length === 0) {
        container.innerHTML = `<div class="nt-ticket-card__empty">No se encontraron tickets en esta categoría</div>`;
        return;
    }

    filtrados.forEach(ticket => {
        const div = document.createElement('div');
        div.className = "nt-ticket-card nt-ticket-card--admin";

        div.addEventListener('click', (e) => {
            if (e.target.closest('.nt-dropdown-js')) return;

            const panel = div.querySelector('.panel-detalle');

            if (panel.classList.contains('activo')) {
                panel.classList.remove('activo');
                panel.classList.add('nt-hidden');
            } else {
                document.querySelectorAll('.panel-detalle.activo').forEach(p => {
                    p.classList.remove('activo');
                    p.classList.add('nt-hidden');
                });

                panel.classList.remove('nt-hidden');
                panel.classList.add('activo');
            }
        });

        const dateObj = new Date(ticket.fecha_creacion);
        const formattedDate = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

        const badgeClass = getBadgeClass(ticket.estado);

        let actionButtonsHtml = '';
        if (ticket.estado === 'Abierto') {
            actionButtonsHtml = `
                <button onclick="openModal(${ticket.id_ticket}, 'En Proceso')" class="nt-dropdown-js__item nt-dropdown-js__item--amber">
                    Trabajar (En Proceso)
                </button>
                <button onclick="openModal(${ticket.id_ticket}, 'Resuelto')" class="nt-dropdown-js__item nt-dropdown-js__item--green">
                    Resolver Ticket
                </button>
            `;
        } else if (ticket.estado === 'En Proceso') {
            actionButtonsHtml = `
                <button onclick="openModal(${ticket.id_ticket}, 'En Proceso')" class="nt-dropdown-js__item nt-dropdown-js__item--amber">
                    Agregar Nota de Avance
                </button>
                <button onclick="openModal(${ticket.id_ticket}, 'Resuelto')" class="nt-dropdown-js__item nt-dropdown-js__item--green">
                    Resolver Ticket
                </button>
                <button onclick="openModal(${ticket.id_ticket}, 'Cerrado')" class="nt-dropdown-js__item nt-dropdown-js__item--red">
                    Cerrar Ticket
                </button>
            `;
        } else if (ticket.estado === 'Resuelto' || ticket.estado === 'Cerrado') {
            actionButtonsHtml = `
                <button onclick="openModal(${ticket.id_ticket}, 'Reabrir')" class="nt-dropdown-js__item nt-dropdown-js__item--sky">
                    Reabrir (En Proceso)
                </button>
            `;
        }

        div.innerHTML = `
            <div class="nt-ticket-card__header">
                <div class="nt-flex-row-2 nt-min-w-0">
                    <span class="nt-badge ${badgeClass} nt-badge--inline">${ticket.estado}</span>
                    <h3 class="nt-ticket-card__title">
                        ${ticket.titulo}
                    </h3>
                </div>

                <div class="nt-flex-row-2 nt-shrink-0">
                    <span class="nt-ticket-card__id">#TK-${ticket.id_ticket}</span>

                    <div class="nt-dropdown-js">
                        <button onclick="toggleDropdown(event, ${ticket.id_ticket})" class="nt-dropdown-js__toggle">
                            <span class="nt-text-2xs nt-font-bold nt-uppercase nt-tracking-wider nt-ml-1">Opciones</span>
                            <svg class="nt-dropdown-js__arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div id="dropdown-${ticket.id_ticket}" class="nt-dropdown-js__menu nt-hidden">
                            ${actionButtonsHtml}
                        </div>
                    </div>
                </div>
            </div>

            <div class="nt-hidden panel-detalle nt-stack-4 nt-px-6 nt-pb-6 nt-divider-top-soft-20 nt-pt-4 nt-panel-detalle--admin">
                <div>
                    <span class="nt-ticket-card__meta">Descripción del Problema:</span>
                    <p class="nt-ticket-card__body">${ticket.descripcion}</p>
                </div>

                ${ticket.observacion_proceso ? `
                    <div class="nt-ticket-card__log">
                        <span class="nt-ticket-card__meta nt-ticket-card__log--title-admin-progreso">Bitácora de Progreso:</span>
                        <p class="nt-text-slate-300 nt-text-xs nt-text-light nt-text-pre-wrap">${ticket.observacion_proceso}</p>
                    </div>
                ` : ''}

                ${ticket.observacion_cierre ? `
                    <div class="nt-ticket-card__log">
                        <span class="nt-ticket-card__meta nt-ticket-card__log--title-cierre">Bitácora de Cierre / Resolución:</span>
                        <p class="nt-text-slate-300 nt-text-xs nt-text-light nt-text-pre-wrap">${ticket.observacion_cierre}</p>
                    </div>
                ` : ''}

                <div class="nt-ticket-card__footer">
                    <span>Prioridad: <strong class="nt-ticket-card__strong">${ticket.prioridad}</strong></span>
                    <span>Generado el: ${formattedDate}</span>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function toggleDropdown(event, idTicket) {
    event.stopPropagation();
    const targetMenu = document.getElementById(`dropdown-${idTicket}`);
    const boton = event.currentTarget;
    const flecha = boton.querySelector('.nt-dropdown-js__arrow');
    const isHidden = targetMenu.classList.contains('nt-hidden');

    document.querySelectorAll('.nt-dropdown-js__menu').forEach(menu => menu.classList.add('nt-hidden'));
    document.querySelectorAll('.nt-dropdown-js__arrow').forEach(fl => fl.classList.remove('nt-dropdown-js__arrow--rotated'));

    if (isHidden) {
        targetMenu.classList.remove('nt-hidden');
        if (flecha) flecha.classList.add('nt-dropdown-js__arrow--rotated');
    }
}

function openModal(idTicket, targetStatus) {
    document.querySelectorAll('.nt-dropdown-js__menu').forEach(menu => menu.classList.add('nt-hidden'));
    document.querySelectorAll('.nt-dropdown-js__arrow').forEach(fl => fl.classList.remove('nt-dropdown-js__arrow--rotated'));

    document.getElementById('modal-ticket-id').value = idTicket;
    document.getElementById('modal-target-status').value = targetStatus;

    const resolucionText = document.getElementById('modal-resolucion-text');
    const modalTitle = document.getElementById('modal-title-text');
    const modalSubtitle = document.getElementById('modal-subtitle-text');
    const modalLabel = document.getElementById('modal-label-text');

    resolucionText.value = '';
    document.getElementById('modal-alert').classList.add('nt-hidden');

    if (targetStatus === 'En Proceso') {
        modalTitle.textContent = "Trabajar Incidente / Agregar Nota";
        modalSubtitle.textContent = "Agrega un comentario o bitácora de red para informar de los avances lógicos o de hardware.";
        modalLabel.textContent = "Nota de Trabajo (Obligatoria/Opcional si es el primer avance)";
        resolucionText.placeholder = "Indica las acciones técnicas que se están realizando...";
    } else if (targetStatus === 'Resuelto') {
        modalTitle.textContent = "Resolver Incidente";
        modalSubtitle.textContent = "Escribe detalladamente el diagnóstico y solución aplicada.";
        modalLabel.textContent = "Reporte de Resolución (Obligatorio)";
        resolucionText.placeholder = "Describe los cambios físicos o lógicos aplicados...";
    } else if (targetStatus === 'Cerrado') {
        modalTitle.textContent = "Cerrar Ticket";
        modalSubtitle.textContent = "Escribe el motivo de la finalización definitiva del caso.";
        modalLabel.textContent = "Motivo de Cierre (Obligatorio)";
        resolucionText.placeholder = "Indica la razón de la conclusión del caso...";
    } else if (targetStatus === 'Reabrir') {
        modalTitle.textContent = "Reabrir Ticket";
        modalSubtitle.textContent = "El caso volverá al estado 'En Proceso'. Las bitácoras existentes no se perderán.";
        modalLabel.textContent = "Motivo de la Reapertura (Obligatorio)";
        resolucionText.placeholder = "Explica por qué se reabre el ticket...";
    }

    document.getElementById('close-ticket-modal').classList.add('nt-modal-backdrop--visible');
}

function closeModal() {
    document.getElementById('close-ticket-modal').classList.remove('nt-modal-backdrop--visible');
}

async function submitCloseTicket() {
    const idTicket = document.getElementById('modal-ticket-id').value;
    const targetStatus = document.getElementById('modal-target-status').value;
    const observaciones = document.getElementById('modal-resolucion-text').value.trim();
    const modalAlert = document.getElementById('modal-alert');

    if ((targetStatus === 'Resuelto' || targetStatus === 'Cerrado' || targetStatus === 'Reabrir') && observaciones === '') {
        modalAlert.textContent = "Es obligatorio suministrar un comentario para esta acción.";
        modalAlert.classList.remove('nt-hidden');
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
            modalAlert.classList.remove('nt-hidden');
        }
    } catch (err) {
        modalAlert.textContent = "Error al intentar actualizar el estado del ticket.";
        modalAlert.classList.remove('nt-hidden');
    }
}

const btnLogout = document.querySelector('#btn-logout');
if (btnLogout) {
    btnLogout.addEventListener('click', async (e) => {
        e.preventDefault();
        if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            try {
                const response = await fetch('/assets/php/logout.php');
                const resultado = await response.json();
                if (resultado.status === 'success') {
                    window.location.replace('/pages/Login.php');
                }
            } catch (error) {
                console.error('Error al intentar finalizar la sesión:', error);
                alert('❌ Error de conectividad al procesar la salida.');
            }
        }
    });
}
