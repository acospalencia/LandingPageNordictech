let systemData = { metricas: {}, usuarios: [], tickets: [], cola: [] };

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-system-tab]').forEach(button => button.addEventListener('click', () => cambiarPanel(button.dataset.systemTab)));
    document.getElementById('search-usuarios')?.addEventListener('input', renderUsuarios);
    document.getElementById('search-tickets')?.addEventListener('input', renderTicketsSistema);
    document.getElementById('btn-logout')?.addEventListener('click', cerrarSesion);
    document.getElementById('btn-logout-mobile')?.addEventListener('click', cerrarSesion);
    document.getElementById('btn-new-user')?.addEventListener('click', () => abrirUsuario());
    document.getElementById('user-form')?.addEventListener('submit', guardarUsuario);
    document.getElementById('ticket-form')?.addEventListener('submit', guardarTicket);
    document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => cerrarModal(button.dataset.closeModal)));
    cargarSistema();
});

async function cargarSistema() {
    try {
        const response = await fetch('/assets/php/gestion_sistema.php', { headers: { Accept: 'application/json' } });
        const result = await response.json();
        if (result.status !== 'success') throw new Error(result.message);
        systemData = result.data;
        renderMetricas(); renderUsuarios(); renderTicketsSistema(); renderCorreos();
    } catch (error) { mostrarAlerta('error', error.message || 'No fue posible cargar el dashboard.'); }
}

function cambiarPanel(nombre) {
    document.querySelectorAll('[data-system-tab]').forEach(button => button.classList.toggle('nt-tabs__btn--active', button.dataset.systemTab === nombre));
    document.querySelectorAll('.nt-system-panel').forEach(panel => panel.classList.add('hidden'));
    document.getElementById(`panel-${nombre}`)?.classList.remove('hidden');
}

function renderMetricas() {
    const labels = [['usuarios_activos','Usuarios activos'],['usuarios_inactivos','Usuarios inactivos'],['tickets_activos','Tickets activos'],['tickets_cerrados','Tickets cerrados'],['correos_pendientes','Correos pendientes']];
    document.getElementById('system-metrics').innerHTML = labels.map(([key,label]) => `<article class="nt-system-metric"><span>${escapeHTML(label)}</span><strong>${Number(systemData.metricas[key] || 0)}</strong></article>`).join('');
}

function renderUsuarios() {
    const query = (document.getElementById('search-usuarios')?.value || '').toLowerCase();
    const roles = {1:'Cliente',2:'Técnico',3:'Administrador'};
    const lista = systemData.usuarios.filter(u => `${u.nombre} ${u.email}`.toLowerCase().includes(query));
    document.getElementById('system-users').innerHTML = lista.length ? lista.map(u => `<article class="nt-system-row ${Number(u.activo) ? '' : 'nt-system-row--inactive'}"><div class="nt-system-row__main"><strong>${escapeHTML(u.nombre)}</strong><span>${escapeHTML(u.email)}</span><small>${Number(u.verificado) ? 'Cuenta verificada' : 'Pendiente de verificación'}</small></div><div class="nt-system-row__actions"><label><span>Rol</span><select class="nt-form__select nt-form__select--compact" onchange="cambiarRol(${Number(u.id_usuario)},this.value)">${[1,2,3].map(r => `<option value="${r}" ${Number(u.id_rol) === r ? 'selected' : ''}>${roles[r]}</option>`).join('')}</select></label><button class="nt-btn nt-btn--ghost nt-btn--sm" onclick="abrirUsuario(${Number(u.id_usuario)})">Editar</button><button class="nt-btn nt-btn--sm ${Number(u.activo) ? 'nt-btn--danger' : ''}" onclick="toggleUsuario(${Number(u.id_usuario)},${Number(u.activo) ? 0 : 1})">${Number(u.activo) ? 'Desactivar' : 'Reactivar'}</button></div></article>`).join('') : '<div class="nt-card nt-card--empty">No se encontraron usuarios.</div>';
}

function renderTicketsSistema() {
    const query = (document.getElementById('search-tickets')?.value || '').toLowerCase();
    const lista = systemData.tickets.filter(t => `${t.id_ticket} ${t.titulo} ${t.cliente || ''} ${t.tecnico || ''}`.toLowerCase().includes(query));
    document.getElementById('system-tickets').innerHTML = lista.length ? lista.map(t => `<article class="nt-system-row ${Number(t.activo) ? '' : 'nt-system-row--inactive'}"><div class="nt-system-row__main"><strong>#TK-${Number(t.id_ticket)} · ${escapeHTML(t.titulo)}</strong><span>Cliente: ${escapeHTML(t.cliente || 'Sin cliente')} · Técnico: ${escapeHTML(t.tecnico || 'Sin asignar')}</span><small>${escapeHTML(t.estado)} · ${escapeHTML(t.prioridad || 'Normal')} · ${escapeHTML(t.categoria || 'Sin categoría')}</small></div><div class="nt-system-row__actions"><button class="nt-btn nt-btn--ghost nt-btn--sm" onclick="abrirTicket(${Number(t.id_ticket)})">Editar</button><button class="nt-btn nt-btn--sm ${Number(t.activo) ? 'nt-btn--danger' : ''}" onclick="toggleTicket(${Number(t.id_ticket)},${Number(t.activo) ? 0 : 1})">${Number(t.activo) ? 'Desactivar' : 'Reactivar'}</button></div></article>`).join('') : '<div class="nt-card nt-card--empty">No se encontraron tickets.</div>';
}

function renderCorreos() {
    document.getElementById('system-emails').innerHTML = systemData.cola.length ? systemData.cola.map(c => `<article class="nt-system-row"><div class="nt-system-row__main"><strong>${escapeHTML(c.asunto)}</strong><span>${escapeHTML(c.destinatario)}</span><small>Estado: ${escapeHTML(c.estado)} · Intentos: ${Number(c.intentos || 0)}</small></div></article>`).join('') : '<div class="nt-card nt-card--empty">No hay correos registrados.</div>';
}

async function ejecutarAccion(payload) {
    try {
        const response = await fetch('/assets/php/gestion_sistema.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        const result = await response.json();
        if (result.status !== 'success') throw new Error(result.message);
        mostrarAlerta('success', result.message); await cargarSistema();
    } catch (error) { mostrarAlerta('error', error.message || 'No fue posible completar la operación.'); }
}

function toggleUsuario(id, activo) { ejecutarAccion({action:'toggle_usuario',id_usuario:id,activo}); }
function cambiarRol(id, rol) { ejecutarAccion({action:'cambiar_rol',id_usuario:id,id_rol:Number(rol)}); }
function toggleTicket(id, activo) { ejecutarAccion({action:'toggle_ticket',id_ticket:id,activo}); }
function abrirUsuario(id = 0) {
    const form = document.getElementById('user-form'); form.reset();
    const usuario = systemData.usuarios.find(item => Number(item.id_usuario) === Number(id));
    form.elements.id_usuario.value = usuario?.id_usuario || '';
    form.elements.nombre.value = usuario?.nombre || '';
    form.elements.email.value = usuario?.email || '';
    form.elements.id_rol.value = usuario?.id_rol || 1;
    form.elements.verificado.value = usuario?.verificado ?? 1;
    abrirModal('user-modal');
}
function abrirTicket(id) {
    const ticket = systemData.tickets.find(item => Number(item.id_ticket) === Number(id)); if (!ticket) return;
    const form = document.getElementById('ticket-form');
    form.elements.id_ticket.value=ticket.id_ticket; form.elements.estado.value=ticket.estado; form.elements.prioridad.value=ticket.prioridad; form.elements.categoria.value=ticket.categoria || '';
    const tecnicos = systemData.usuarios.filter(u => Number(u.id_rol) === 2 && Number(u.activo) === 1);
    form.elements.id_tecnico.innerHTML='<option value="0">Sin asignar</option>'+tecnicos.map(u => `<option value="${Number(u.id_usuario)}">${escapeHTML(u.nombre)}</option>`).join('');
    form.elements.id_tecnico.value=ticket.id_tecnico || 0;
    abrirModal('ticket-modal');
}
async function guardarUsuario(event) { event.preventDefault(); const data=Object.fromEntries(new FormData(event.currentTarget)); await ejecutarAccion({action:'guardar_usuario',...data}); cerrarModal('user-modal'); }
async function guardarTicket(event) { event.preventDefault(); const data=Object.fromEntries(new FormData(event.currentTarget)); await ejecutarAccion({action:'guardar_ticket',...data}); cerrarModal('ticket-modal'); }
function abrirModal(id) { const modal=document.getElementById(id); modal.classList.remove('hidden'); modal.style.display='flex'; }
function cerrarModal(id) { const modal=document.getElementById(id); modal.classList.add('hidden'); modal.style.display='none'; }
function mostrarAlerta(tipo, texto) { const alert=document.getElementById('system-alert'); alert.className=`nt-alert ${tipo === 'success' ? 'nt-alert--success' : 'nt-alert--error'}`; alert.textContent=texto; alert.classList.remove('hidden'); }
async function cerrarSesion() { await fetch('/assets/php/logout.php'); window.location.href='/pages/Login.php'; }
function escapeHTML(value) { return String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[char])); }
window.toggleUsuario=toggleUsuario; window.cambiarRol=cambiarRol; window.toggleTicket=toggleTicket; window.abrirUsuario=abrirUsuario; window.abrirTicket=abrirTicket;
