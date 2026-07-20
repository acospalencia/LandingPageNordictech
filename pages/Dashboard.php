<?php
session_start();

// Validar que el usuario esté autenticado y posea el rol de Administrador (id_rol = 3)
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol']) !== 3) {
    header("Location: /pages/Login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consola de Administración | NordicTech El Salvador</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;800&family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Montserrat', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        nordic: {
                            bg: '#060913',
                            card: '#0D1425',
                            border: '#1E293B',
                            logoBlue: '#2A4094',
                            logoBlueHover: '#3C56C4',
                            textMuted: '#94A3B8'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Hoja de estilos centralizada del proyecto -->
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body class="bg-[#060913] text-white font-sans antialiased selection:bg-nordic-logoBlue selection:text-white">

    <div class="nt-app-container">

        <!-- Reflejo ambiental (Heredado exactamente del Portal de Clientes) -->
        <div class="nt-ambient-glow nt-ambient-glow--short"></div>

        <!-- Header -->
        <header class="nt-header">
            <div class="nt-header__inner">
                <div class="nt-logo-wrap">
                    <div class="nt-logo-title">
                        <a href="/">
                            <img src="/assets/img/Marca de agua black.png" alt="Logo" class="nt-logo nt-logo--no-offset">
                        </a>
                    </div>
                </div>

                <nav class="nt-nav">
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                </nav>

                <div class="flex items-center gap-2">
                    <button id="btn-sidebar-toggle" class="nt-mobile-toggle" aria-label="Abrir lista de clientes" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <button id="mobile-toggle" class="nt-mobile-toggle" aria-label="Abrir menú" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path id="mobile-toggle-icon" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button id="btn-logout" class="nt-btn hidden sm:inline-block">
                        Cerrar Sesión
                    </button>
                </div>

                <!-- Menú móvil desplegable (oculto en lg+) -->
                <div id="mobile-menu" class="nt-mobile-menu">
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                    <button id="btn-logout-mobile" class="nt-btn">Cerrar Sesión</button>
                </div>
            </div>
        </header>

        <!-- Backdrop para drawer del sidebar -->
        <div id="sidebar-backdrop" class="nt-sidebar__backdrop"></div>

        <!-- Distribución del Contenido Principal (Main + Sidebar) -->
        <div class="flex pt-[4.5rem] md:pt-24 min-h-screen overflow-hidden relative z-10">

            <!-- PANEL LATERAL DE CLIENTES (OPERADORES) -->
            <aside class="nt-sidebar" id="sidebar-clientes">
                <div class="nt-sidebar__search">
                    <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-nordic-textMuted">Filtrar Clientes</label>
                    <input type="text" id="search-input" placeholder="Nombre, correo o empresa..."
                        class="nt-form__input nt-form__input--compact">
                </div>

                <div id="clientes-list" class="nt-sidebar__list">
                    <div class="nt-sidebar__list-empty">Sincronizando operadores...</div>
                </div>
            </aside>

            <!-- AREA PRINCIPAL DE SEGUIMIENTO -->
            <main class="nt-main nt-main--with-sidebar">
                <div class="nt-main__inner">

                    <div id="alert-container" class="nt-alert"></div>

                    <!-- ENCABEZADO DINÁMICO DEL CLIENTE SELECCIONADO -->
                    <div class="nt-divider-bottom-soft nt-flex-between--wrap">
                        <div class="min-w-0">
                            <span class="nt-text-eyebrow nt-text-eyebrow--xs">Consola de Soporte</span>
                            <h1 id="active-client-name" class="text-xl sm:text-2xl font-display font-bold uppercase tracking-tight break-words">Selecciona un cliente</h1>
                            <p id="active-client-meta" class="text-xs text-nordic-textMuted font-light mt-1 break-words">Selecciona un elemento de la lista para auditar sus tickets de soporte.</p>
                        </div>
                    </div>

                    <!-- CONTENEDOR DE TICKETS -->
                    <div id="tickets-container" class="hidden space-y-5 sm:space-y-6">

                        <!-- PESTAÑAS DE CONTROL DE ESTADO (Con padding p-1 idéntico al portal) -->
                        <div class="nt-tabs flex-wrap sm:flex-nowrap">
                            <button onclick="switchTab('Abierto')" id="tab-Abierto" class="nt-tabs__btn nt-tabs__btn--active text-[10px] sm:text-xs px-2 py-2 sm:py-3 flex-1 min-w-[33%]">
                                Abiertos (<span id="count-Abierto">0</span>)
                            </button>
                            <button onclick="switchTab('En Proceso')" id="tab-EnProceso" class="nt-tabs__btn text-[10px] sm:text-xs px-2 py-2 sm:py-3 flex-1 min-w-[33%]">
                                En Proceso (<span id="count-EnProceso">0</span>)
                            </button>
                            <button onclick="switchTab('Cerrado')" id="tab-Cerrado" class="nt-tabs__btn text-[10px] sm:text-xs px-2 py-2 sm:py-3 flex-1 min-w-[33%]">
                                Cerrados (<span id="count-Cerrado">0</span>)
                            </button>
                        </div>

                        <!-- AQUÍ SE INYECTAN LAS CARDS CON EL DISEÑO DEL PORTAL DE CLIENTES -->
                        <div id="tickets-list" class="space-y-4"></div>
                    </div>

                    <!-- MENSAJE DE ESPERA POR DEFECTO -->
                    <div id="no-client-selected" class="nt-card nt-card--empty">
                        <p>Debes seleccionar un cliente del menú lateral para cargar su flujo de soporte.</p>
                    </div>

                </div>
            </main>
        </div>

        <!-- Footer Estandarizado -->
        <footer class="nt-footer">
            <div class="nt-footer__inner">
                <div class="nt-footer__brand">
                    <p class="nt-footer__brand-name">NORDICTECH</p>
                    <p class="nt-footer__brand-sub">El Salvador</p>
                </div>
                <p>&copy; 2026 NordicTech El Salvador. Consola de monitoreo y soporte.</p>
            </div>
        </footer>

    </div>

    <!-- MODAL DE ACCIONES Y BITÁCORAS -->
    <div id="close-ticket-modal" class="nt-modal-backdrop nt-modal-backdrop--darker">
        <div class="nt-modal nt-modal--md space-y-4">
            <div class="nt-modal__header">
                <h3 id="modal-title-text" class="nt-modal__title">Actualizar Ticket</h3>
                <p id="modal-subtitle-text" class="nt-modal__subtitle">Suministra la información técnica correspondiente.</p>
            </div>

            <div id="modal-alert" class="nt-alert nt-alert--error nt-alert--small"></div>

            <input type="hidden" id="modal-ticket-id">
            <input type="hidden" id="modal-target-status">

            <div>
                <label id="modal-label-text" class="nt-form__label nt-form__label--xs">Comentarios</label>
                <textarea id="modal-resolucion-text" rows="4" class="nt-form__textarea nt-form__textarea--compact"></textarea>
            </div>

            <div class="nt-modal__actions">
                <button type="button" id="btn-cancel-modal" class="nt-btn--text">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-modal" class="nt-btn nt-btn--sm">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/dashboard_admin.js?v=1.0.6"></script>
    <script src="/assets/js/nav.js?v=1.0.0"></script>
</body>
</html>
