<?php
session_start();
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol'] ?? 0) !== 3) {
    header('Location: /pages/Login.php');
    exit;
}
$nombreAdministrador = $_SESSION['nombre_usuario'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión del Sistema | NordicTech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;800&family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Montserrat','sans-serif'],display:['Space Grotesk','sans-serif']},colors:{nordic:{bg:'#060913',card:'#0D1425',border:'#1E293B',logoBlue:'#2A4094',logoBlueHover:'#3C56C4',textMuted:'#94A3B8'}}}}}</script>
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body class="bg-[#060913] text-white font-sans antialiased">
<div class="nt-app-container">
    <div class="nt-ambient-glow nt-ambient-glow--short"></div>
    <header class="nt-header">
        <div class="nt-header__inner">
            <a href="/pages/GestionSistema.php"><img src="/assets/img/Marca de agua black.png" alt="NordicTech" class="nt-logo nt-logo--no-offset"></a>
            <nav class="nt-nav">
                <a href="/pages/Dashboard.php" class="nt-nav__link">Gestionar tickets</a>
                <a href="/pages/GestionSistema.php" class="nt-nav__link text-white">Sistema</a>
                <span class="text-xs text-nordic-textMuted"><?php echo htmlspecialchars($nombreAdministrador); ?></span>
            </nav>
            <div class="flex items-center gap-2">
                <button id="mobile-toggle" class="nt-mobile-toggle" aria-label="Abrir menú" aria-expanded="false"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path id="mobile-toggle-icon" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg></button>
                <button id="btn-logout" class="nt-btn hidden sm:inline-block">Cerrar sesión</button>
            </div>
            <div id="mobile-menu" class="nt-mobile-menu">
                <a href="/pages/Dashboard.php" class="nt-nav__link">Gestionar tickets</a>
                <a href="/pages/GestionSistema.php" class="nt-nav__link">Sistema</a>
                <button id="btn-logout-mobile" class="nt-btn">Cerrar sesión</button>
            </div>
        </div>
    </header>

    <main class="nt-main nt-main--system">
        <div class="nt-system-shell">
            <div class="nt-divider-bottom-soft pb-5 mb-6">
                <span class="nt-text-eyebrow nt-text-eyebrow--xs">Administración general</span>
                <h1 class="text-xl sm:text-2xl font-display font-bold uppercase tracking-tight">Dashboard del sistema</h1>
                <p class="text-xs text-nordic-textMuted mt-1">Gestiona usuarios, roles, tickets y el estado operativo sin acceder directamente a la base de datos.</p>
            </div>

            <div id="system-alert" class="nt-alert hidden"></div>
            <section id="system-metrics" class="nt-system-metrics" aria-label="Resumen del sistema"></section>

            <div class="nt-tabs nt-system-tabs" role="tablist">
                <button class="nt-tabs__btn nt-tabs__btn--active" data-system-tab="usuarios">Usuarios</button>
                <button class="nt-tabs__btn" data-system-tab="tickets">Tickets</button>
                <button class="nt-tabs__btn" data-system-tab="correos">Correos</button>
            </div>

            <section id="panel-usuarios" class="nt-system-panel">
                <div class="nt-system-toolbar"><input id="search-usuarios" class="nt-form__input nt-form__input--compact" placeholder="Buscar usuario o correo..."><button id="btn-new-user" class="nt-btn">Nuevo usuario</button></div>
                <div id="system-users" class="nt-system-list"></div>
            </section>
            <section id="panel-tickets" class="nt-system-panel hidden">
                <div class="nt-system-toolbar"><input id="search-tickets" class="nt-form__input nt-form__input--compact" placeholder="Buscar ticket, cliente o técnico..."></div>
                <div id="system-tickets" class="nt-system-list"></div>
            </section>
            <section id="panel-correos" class="nt-system-panel hidden"><div id="system-emails" class="nt-system-list"></div></section>
        </div>
    </main>
</div>

<div id="user-modal" class="nt-modal-backdrop nt-modal-backdrop--darker hidden" role="dialog" aria-modal="true">
    <form id="user-form" class="nt-modal nt-modal--md space-y-4">
        <div><h2 class="nt-modal__title">Datos del usuario</h2><p class="nt-modal__subtitle">La contraseña es obligatoria solamente al crear la cuenta.</p></div>
        <input type="hidden" name="id_usuario">
        <div><label class="nt-form__label">Nombre</label><input name="nombre" class="nt-form__input" required></div>
        <div><label class="nt-form__label">Correo</label><input name="email" type="email" class="nt-form__input" required></div>
        <div class="nt-form__row"><div><label class="nt-form__label">Rol</label><select name="id_rol" class="nt-form__select"><option value="1">Cliente</option><option value="2">Técnico</option><option value="3">Administrador</option></select></div><div><label class="nt-form__label">Aprobación</label><select name="verificado" class="nt-form__select"><option value="1">Verificado</option><option value="0">Pendiente</option></select></div></div>
        <div><label class="nt-form__label">Nueva contraseña</label><input name="password" type="password" minlength="8" class="nt-form__input" placeholder="Mínimo 8 caracteres"></div>
        <div class="nt-modal__actions"><button type="button" class="nt-btn--text" data-close-modal="user-modal">Cancelar</button><button class="nt-btn nt-btn--sm" type="submit">Guardar usuario</button></div>
    </form>
</div>

<div id="ticket-modal" class="nt-modal-backdrop nt-modal-backdrop--darker hidden" role="dialog" aria-modal="true">
    <form id="ticket-form" class="nt-modal nt-modal--md space-y-4">
        <div><h2 class="nt-modal__title">Editar ticket</h2><p class="nt-modal__subtitle">Actualiza su clasificación, estado y técnico asignado.</p></div>
        <input type="hidden" name="id_ticket">
        <div class="nt-form__row"><div><label class="nt-form__label">Estado</label><select name="estado" class="nt-form__select"><option>Abierto</option><option>En Proceso</option><option>Resuelto</option><option>Cerrado</option></select></div><div><label class="nt-form__label">Prioridad</label><select name="prioridad" class="nt-form__select"><option>Baja</option><option>Media</option><option>Alta</option><option>Crítica</option></select></div></div>
        <div><label class="nt-form__label">Categoría</label><input name="categoria" class="nt-form__input" required></div>
        <div><label class="nt-form__label">Técnico</label><select name="id_tecnico" id="ticket-technician" class="nt-form__select"></select></div>
        <div class="nt-modal__actions"><button type="button" class="nt-btn--text" data-close-modal="ticket-modal">Cancelar</button><button class="nt-btn nt-btn--sm" type="submit">Guardar ticket</button></div>
    </form>
</div>
<script src="/assets/js/gestion_sistema.js?v=1.0.0"></script>
<script src="/assets/js/nav.js?v=1.0.2"></script>
</body>
</html>
