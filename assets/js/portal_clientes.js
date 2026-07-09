document.addEventListener('DOMContentLoaded', () => {
    let todosLosTickets = [];
    let estadoFiltroActual = 'Abierto'; // Filtro inicial por defecto

    // Referencias a la interfaz
    const contenedorTickets = document.querySelector('#contenedor-tickets');
    const tabs = document.querySelectorAll('.flex.border-b button');

    // 1. Función principal para consumir el backend de PHP
    async function cargarTickets() {
        try {
            const response = await fetch('/assets/php/obtener_tickets.php');
            const resultado = await response.json();

            if (resultado.status === 'success') {
                todosLosTickets = resultado.tickets;
                
                // Actualizar el nombre en la interfaz de forma dinámica opcional
                const nodoTexto = document.querySelector('.bg-nordic-card span');
                if(nodoTexto && resultado.nombre_usuario) {
                    nodoTexto.textContent = `Nodo Activo: ${resultado.nombre_usuario}`;
                }

                renderizarTickets();
                actualizarContadoresTabs();
            } else if (resultado.status === 'session_expired') {
                // Intercepción perimetral: Sesión caducada o inexistente
                alert('⚠️ ' + resultado.message);
                window.location.replace('/pages/login.html'); // Redirección forzada sin retorno atrás
            } else {
                alert('❌ Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error al mapear la infraestructura de tickets:', error);
            contenedorTickets.innerHTML = `<p class="text-xs text-rose-400 font-mono">❌ ERROR_CONECTIVIDAD: No se logró enlazar con el servidor central.</p>`;
        }
    }

    // 2. Función para inyectar el HTML dinámico de cada ticket
    function renderizarTickets() {
        contenedorTickets.innerHTML = '';

        // Filtramos el arreglo global según la pestaña seleccionada
        const ticketsFiltrados = todosLosTickets.filter(t => t.estado.toLowerCase() === estadoFiltroActual.toLowerCase());

        if (ticketsFiltrados.length === 0) {
            contenedorTickets.innerHTML = `
                <div class="bg-[#0D1425] border border-[#1E293B] p-8 text-center">
                    <p class="text-xs text-[#94A3B8] font-light">No se registran solicitudes en estado: <strong class="text-white uppercase">${estadoFiltroActual}</strong></p>
                </div>
            `;
            return;
        }

        ticketsFiltrados.forEach(ticket => {
            // Estilos dinámicos para los badges de estado
            let badgeStyle = "bg-amber-500/10 text-amber-400 border-amber-500/20";
            if (ticket.estado === 'En Proceso') badgeStyle = "bg-blue-500/10 text-blue-400 border-blue-500/20";
            if (ticket.estado === 'Resuelto' || ticket.estado === 'Cerrado') badgeStyle = "bg-emerald-500/10 text-emerald-400 border-emerald-500/20";

            // Estilos dinámicos de criticidad
            let prioridadColor = "text-slate-400";
            if (ticket.prioridad === 'Alta') prioridadColor = "text-orange-400 font-bold";
            if (ticket.prioridad === 'Crítica') prioridadColor = "text-rose-400 font-bold animate-pulse";

            const ticketHTML = `
                <div class="bg-[#0D1425] border border-[#1E293B] p-5 hover:border-[#2A4094]/40 transition-all">
                    <div class="flex justify-between items-start gap-4 mb-3">
                        <div>
                            <span class="inline-block ${badgeStyle} text-[9px] font-bold tracking-widest px-2 py-0.5 border uppercase mb-2">
                                ${ticketindexToText(ticket.estado)}
                            </span>
                            <h3 class="text-sm font-semibold uppercase text-white tracking-wide">${escapeHTML(ticket.titulo)}</h3>
                        </div>
                        <span class="text-[10px] font-mono text-[#94A3B8]">#TK-${ticket.id_ticket}</span>
                    </div>
                    <p class="text-xs text-[#94A3B8] font-light leading-relaxed mb-4 whitespace-pre-wrap">${escapeHTML(ticket.descripcion)}</p>
                    <div class="flex justify-between items-center border-t border-[#1E293B]/60 pt-3 text-[10px] font-mono text-slate-400">
                        <span>Prioridad: <strong class="${prioridadColor}">${ticket.prioridad}</strong></span>
                        <span>${ticket.fecha}</span>
                    </div>
                </div>
            `;
            contenedorTickets.insertAdjacentHTML('beforeend', ticketHTML);
        });
    }

    // 3. Manejador de eventos para los filtros (Tabs)
    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            // Remover clases activas de todos los botones
            tabs.forEach(t => {
                t.classList.remove('bg-[#2A4094]', 'text-white');
                t.classList.add('text-[#94A3B8]', 'hover:text-white');
            });

            // Añadir clase activa al botón presionado
            e.target.classList.remove('text-[#94A3B8]', 'hover:text-white');
            e.target.classList.add('bg-[#2A4094]', 'text-white');

            // Actualizar estado del filtro
            const textoTab = e.target.textContent.trim();
            if (textoTab.includes('Abiertos')) estadoFiltroActual = 'Abierto';
            if (textoTab.includes('En Proceso')) estadoFiltroActual = 'En Proceso';
            if (textoTab.includes('Cerrados')) estadoFiltroActual = 'Cerrado';

            renderizarTickets();
        });
    });

    // Helper para actualizar contadores dinámicos en los botones de pestañas
    function actualizarContadoresTabs() {
        const abiertos = todosLosTickets.filter(t => t.estado === 'Abierto').length;
        const proceso = todosLosTickets.filter(t => t.estado === 'En Proceso').length;
        const cerrados = todosLosTickets.filter(t => t.estado === 'Cerrado' || t.estado === 'Resuelto').length;

        tabs[0].textContent = `Abiertos (${abiertos})`;
        tabs[1].textContent = `En Proceso (${proceso})`;
        tabs[2].textContent = `Cerrados (${cerrados})`;
    }

    // Helper de seguridad contra ataques XSS
    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    // Normalizador de texto visible
    function ticketindexToText(estado) {
        return estado === 'Cerrado' ? 'Cerrado / Resuelto' : estado;
    }

    // Ejecución inicial de escaneo de credenciales y datos
    cargarTickets();
});