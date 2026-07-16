let globalClientes = [];
let idClienteActivo = null;
let currentTab = 'Abierto'; // Pestaña inicial
let ticketsCargados = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchClientes();
    
    document.getElementById('search-input').addEventListener('input', filtrarClientes);
    document.getElementById('btn-cancel-modal').addEventListener('click', closeModal);
    document.getElementById('btn-confirm-modal').addEventListener('click', submitCloseTicket);

    // Cerrar los desplegables abiertos al hacer clic fuera de ellos
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));
        }
    });
});

function showAlert(type, text) {
    const container = document.getElementById('alert-container');
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
    listContainer.innerHTML = '';

    if (lista.length === 0) {
        listContainer.innerHTML = '<div class="p-4 text-center text-xs text-nordic-textMuted">Sin coincidencias</div>';
        return;
    }

    lista.forEach(cliente => {
        const isSelected = cliente.id_usuario == idClienteActivo;
        const div = document.createElement('div');
        div.className = `p-4 cursor-pointer transition-colors ${isSelected ? 'bg-nordic-logoBlue/20 border-l-4 border-nordic-logoBlue' : 'hover:bg-nordic-card/60'}`;
        div.onclick = () => selectCliente(cliente);
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

// Actualizar contadores numéricos de las pestañas
function updateTabCounters() {
    const abiertos = ticketsCargados.filter(t => t.estado === 'Abierto').length;
    const proceso = ticketsCargados.filter(t => t.estado === 'En Proceso').length;
    const cerrados = ticketsCargados.filter(t => t.estado === 'Resuelto' || t.estado === 'Cerrado').length;

    document.getElementById('count-Abierto').textContent = abiertos;
    document.getElementById('count-EnProceso').textContent = proceso;
    document.getElementById('count-Cerrado').textContent = cerrados;
}

// Navegar entre pestañas
function switchTab(tab) {
    currentTab = tab;
    
    // Resetear clases de pestañas estéticas
    const tabs = ['Abierto', 'En Proceso', 'Cerrado'];
    tabs.forEach(t => {
        const key = t.replace(' ', '');
        const element = document.getElementById(`tab-${key}`);
        if (t === tab) {
            element.className = "flex-grow py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border bg-nordic-logoBlue text-white";
        } else {
            element.className = "flex-grow py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border text-nordic-textMuted hover:text-white";
        }
    });

    renderTabTickets();
}

// Renderizar tickets de la pestaña actual con formato idéntico a la captura de pantalla
function renderTabTickets() {
    const container = document.getElementById('tickets-list');
    container.innerHTML = '';

    // Filtrar los datos correspondientes a la pestaña activa
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
        div.className = "bg-nordic-card border border-nordic-border p-6 space-y-4 relative transition-all hover:border-slate-700/60";
        
        // Formatear fecha
        const dateObj = new Date(ticket.fecha_creacion);
        const formattedDate = dateObj.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });

        // Determinar color de la etiqueta de estado
        let badgeColor = "border-amber-500/20 bg-amber-950/20 text-amber-500";
        if (ticket.estado === 'En Proceso') badgeColor = "border-sky-500/20 bg-sky-950/20 text-sky-400";
        if (ticket.estado === 'Resuelto') badgeColor = "border-green-500/20 bg-green-950/20 text-green-400";
        if (ticket.estado === 'Cerrado') badgeColor = "border-slate-500/20 bg-slate-800/20 text-slate-400";

        div.innerHTML = `
            <div class="flex items-start justify-between">
                <div>
                    <span class="px-2 py-0.5 text-[9px] uppercase font-bold tracking-widest border ${badgeColor}">
                        ${ticket.estado}
                    </span>
                    <h3 class="text-base font-display font-bold tracking-wide uppercase mt-3 text-white">
                        ${ticket.titulo}
                    </h3>
                </div>
                
                <div class="flex items-center space-x-4">
                    <span class="text-xs font-mono text-nordic-textMuted/70">#TK-${ticket.id_ticket}</span>
                    
                    <div class="relative dropdown-container">
                        <button onclick="toggleDropdown(event, ${ticket.id_ticket})" class="p-1.5 hover:bg-nordic-bg/80 border border-nordic-border/50 text-nordic-textMuted hover:text-white transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div id="dropdown-${ticket.id_ticket}" class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-nordic-card border border-nordic-border shadow-xl z-20 divide-y divide-nordic-border/40">
                            ${ticket.estado === 'Abierto' ? `
                                <button onclick="openModal(${ticket.id_ticket}, 'En Proceso')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-amber-400 hover:bg-nordic-bg transition-colors">
                                    Trabajar (En Proceso)
                                </button>
                            ` : ''}
                            ${ticket.estado === 'Abierto' || ticket.estado === 'En Proceso' ? `
                                <button onclick="openModal(${ticket.id_ticket}, 'Resuelto')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-green-400 hover:bg-nordic-bg transition-colors">
                                    Resolver Ticket
                                </button>
                                <button onclick="openModal(${ticket.id_ticket}, 'Cerrado')" class="w-full text-left px-4 py-2.5 text-xs uppercase tracking-widest font-semibold text-red-400 hover:bg-nordic-bg transition-colors">
                                    Cerrar Ticket
                                </button>
                            ` : ''}
                            <span class="block px-4 py-2.5 text-[10px] text-nordic-textMuted italic">Sin acciones disponibles</span>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-nordic-textMuted leading-relaxed whitespace-pre-wrap">
                ${ticket.descripcion}
            </p>

            ${ticket.observacion_proceso ? `
                <div class="p-3 bg-nordic-bg/50 border border-nordic-border/20 text-xs">
                    <span class="block text-[8px] uppercase tracking-widest text-amber-500 font-bold mb-1">Bitácora de Progreso:</span>
                    <p class="text-slate-300 whitespace-pre-wrap">${ticket.observacion_proceso}</p>
                </div>
            ` : ''}

            ${ticket.observacion_cierre ? `
                <div class="p-3 bg-nordic-bg/50 border border-nordic-border/20 text-xs">
                    <span class="block text-[8px] uppercase tracking-widest text-green-400 font-bold mb-1">Bitácora de Cierre:</span>
                    <p class="text-slate-300 whitespace-pre-wrap">${ticket.observacion_cierre}</p>
                </div>
            ` : ''}

            <div class="flex items-center justify-between text-[10px] text-nordic-textMuted/60 pt-1">
                <span>Prioridad: <strong class="text-slate-300">${ticket.prioridad}</strong></span>
                <span>${formattedDate}</span>
            </div>
        `;
        container.appendChild(div);
    });
}

// Control de apertura/cierre de los menús desplegables
function toggleDropdown(event, idTicket) {
    event.stopPropagation();
    const targetMenu = document.getElementById(`dropdown-${idTicket}`);
    const isHidden = targetMenu.classList.contains('hidden');

    // Cerrar el resto de desplegables activos en la pantalla
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

    if (isHidden) {
        targetMenu.classList.remove('hidden');
    }
}

function openModal(idTicket, targetStatus) {
    // Cerrar menús al lanzar el modal
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.add('hidden'));

    document.getElementById('modal-ticket-id').value = idTicket;
    document.getElementById('modal-target-status').value = targetStatus;
    
    const resolucionText = document.getElementById('modal-resolucion-text');
    const modalTitle = document.getElementById('modal-title-text');
    const modalSubtitle = document.getElementById('modal-subtitle-text');
    const modalLabel = document.getElementById('modal-label-text');
    
    resolucionText.value = '';
    document.getElementById('modal-alert').classList.add('hidden');

    if (targetStatus === 'En Proceso') {
        modalTitle.textContent = "Trabajar Incidente";
        modalSubtitle.textContent = "Agrega un comentario inicial o bitácora de red para informar al cliente que estás trabajando en ello.";
        modalLabel.textContent = "Nota de Trabajo (Opcional)";
        resolucionText.placeholder = "Indica los pasos iniciales a realizar...";
    } else if (targetStatus === 'Resuelto') {
        modalTitle.textContent = "Resolver Incidente";
        modalSubtitle.textContent = "Escribe detalladamente el diagnóstico y solución aplicada.";
        modalLabel.textContent = "Reporte de Resolución (Obligatorio)";
        resolucionText.placeholder = "Describe los cambios físicos o lógicos aplicados...";
    } else if (targetStatus === 'Cerrado') {
        modalTitle.textContent = "Cerrar Ticket";
        modalSubtitle.textContent = "Escribe el motivo del cierre.";
        modalLabel.textContent = "Motivo de Cierre (Obligatorio)";
        resolucionText.placeholder = "Indica la razón de la conclusión del caso...";
    }

    document.getElementById('close-ticket-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('close-ticket-modal').classList.add('hidden');
}

async function submitCloseTicket() {
    const idTicket = document.getElementById('modal-ticket-id').value;
    const targetStatus = document.getElementById('modal-target-status').value;
    const observaciones = document.getElementById('modal-resolucion-text').value.trim();
    const modalAlert = document.getElementById('modal-alert');

    if ((targetStatus === 'Resuelto' || targetStatus === 'Cerrado') && observaciones === '') {
        modalAlert.textContent = "La bitácora técnica de resolución o cierre es obligatoria.";
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

    const btnLogout = document.querySelector('#btn-logout');
    
    if (btnLogout) {
        btnLogout.addEventListener('click', async (e) => {
            e.preventDefault();
            
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                try {
                    const response = await fetch('/assets/php/logout.php');
                    const resultado = await response.json();
                    
                    if (resultado.status === 'success') {
                        // Redirección forzada eliminando el historial
                        window.location.replace('/pages/Login.php');
                    }
                } catch (error) {
                    console.error('Error al intentar finalizar la sesión:', error);
                    alert('❌ Error de conectividad al procesar la salida.');
                }
            }
        });
    }
}