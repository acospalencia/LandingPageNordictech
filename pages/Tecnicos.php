<?php
session_start();

// Validar que exista sesión de usuario
if (!isset($_SESSION['id_usuario'])) {
    header("Location: /pages/Login.php");
    exit;
}

$nombreTecnico = $_SESSION['nombre_usuario'] ?? 'Técnico Especialista';
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Atención Técnica | NordicTech El Salvador</title>
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

        <!-- Reflejo ambiental -->
        <div class="nt-ambient-glow nt-ambient-glow--short"></div>

        <!-- Header -->
        <header class="nt-header">
            <div class="nt-header__inner">
                <div class="nt-logo-wrap">
                    <div class="nt-logo-title">
                        <a href="/pages/Tecnicos.php">
                            <img src="/assets/img/Marca de agua black.png" alt="Logo" class="nt-logo nt-logo--no-offset">
                        </a>
                    </div>
                </div>

                <nav class="nt-nav">
                    <span class="text-xs text-nordic-textMuted hidden md:inline">Técnico: <strong class="text-white"><?php echo htmlspecialchars($nombreTecnico); ?></strong></span>
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                </nav>

                <div class="flex items-center gap-2">
                    <!-- Botón de Clientes -->
                    <button id="btn-sidebar-toggle" class="nt-mobile-toggle flex items-center gap-1.5 px-2.5 py-1.5 border border-nordic-border rounded hover:border-nordic-logoBlue transition-colors" aria-label="Abrir lista de clientes" aria-expanded="false">
                        <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-[10px] uppercase font-bold tracking-wider hidden sm:inline text-slate-300">Mis Clientes</span>
                    </button>

                    <!-- Menú Principal Hamburguesa -->
                    <button id="mobile-toggle" class="nt-mobile-toggle" aria-label="Abrir menú" aria-expanded="false">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path id="mobile-toggle-icon" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <button id="btn-logout" class="nt-btn hidden sm:inline-block">
                        Cerrar Sesión
                    </button>
                </div>

                <!-- Menú móvil desplegable -->
                <div id="mobile-menu" class="nt-mobile-menu">
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                    <button id="btn-logout-mobile" class="nt-btn">Cerrar Sesión</button>
                </div>
            </div>
        </header>

        <!-- Backdrop para drawer con Z-INDEX 40 -->
        <div id="sidebar-backdrop" class="z-[40]" style="z-index: 40 !important;"></div>

        <!-- Distribución del Contenido Principal (Main + Sidebar) -->
        <div class="flex pt-[4.5rem] md:pt-24 min-h-screen overflow-visible relative z-10">

            <!-- PANEL LATERAL DE CLIENTES ASIGNADOS -->
            <aside class="nt-sidebar z-[50]" id="sidebar-clientes" style="z-index: 50 !important;">
                <div class="nt-sidebar__search">
                    <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-nordic-textMuted">Filtrar Mis Clientes</label>
                    <input type="text" id="search-input" placeholder="Nombre o correo..."
                        class="nt-form__input nt-form__input--compact">
                </div>

                <div id="clientes-list" class="nt-sidebar__list">
                    <div class="nt-sidebar__list-empty">Sincronizando clientes asignados...</div>
                </div>
            </aside>

            <!-- ÁREA PRINCIPAL DE SEGUIMIENTO -->
            <main class="nt-main nt-main--with-sidebar">
                <div class="nt-main__inner">

                    <div id="alert-container" class="nt-alert hidden"></div>

                    <!-- ENCABEZADO DINÁMICO DEL CLIENTE SELECCIONADO -->
                    <div class="nt-divider-bottom-soft nt-flex-between--wrap">
                        <div class="min-w-0">
                            <span class="nt-text-eyebrow nt-text-eyebrow--xs">Módulo del Técnico</span>
                            <h1 id="active-client-name" class="text-xl sm:text-2xl font-display font-bold uppercase tracking-tight break-words">Selecciona un cliente</h1>
                            <p id="active-client-meta" class="text-xs text-nordic-textMuted font-light mt-1 break-words">Selecciona un cliente asignado del menú lateral para gestionar sus tickets.</p>
                        </div>
                    </div>

                    <!-- CONTENEDOR DE TICKETS -->
                    <div id="tickets-container" class="hidden space-y-5 sm:space-y-6">

                        <!-- PESTAÑAS DE CONTROL DE ESTADO -->
                        <div class="nt-tabs flex-wrap sm:flex-nowrap">
                            <button onclick="switchTab('En Proceso')" id="tab-EnProceso" class="nt-tabs__btn nt-tabs__btn--active text-[10px] sm:text-xs px-2 py-2 sm:py-3 flex-1 min-w-[50%]">
                                En Proceso (<span id="count-EnProceso">0</span>)
                            </button>
                            <button onclick="switchTab('Cerrado')" id="tab-Cerrado" class="nt-tabs__btn text-[10px] sm:text-xs px-2 py-2 sm:py-3 flex-1 min-w-[50%]">
                                Cerrados (<span id="count-Cerrado">0</span>)
                            </button>
                        </div>

                        <!-- AQUÍ SE INYECTAN LAS CARDS DE TICKETS VIA JS -->
                        <div id="tickets-list" class="space-y-4"></div>
                    </div>

                    <!-- MENSAJE DE ESPERA POR DEFECTO -->
                    <div id="no-client-selected" class="nt-card nt-card--empty">
                        <p>Debes seleccionar un cliente del menú lateral para cargar tus asignaciones activas.</p>
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
                <p>&copy; 2026 NordicTech El Salvador. Consola de atención técnica.</p>
            </div>
        </footer>

    </div>

    <!-- MODAL DE BITÁCORA Y REGISTRO DE AVANCES (ESTRUCURA IDÉNTICA A DASHBOARD) -->
    <div id="close-ticket-modal" class="nt-modal-backdrop nt-modal-backdrop--darker z-[100] hidden" style="z-index: 100 !important;">
        <div class="nt-modal nt-modal--md space-y-4">
            <div class="nt-modal__header">
                <h3 id="modal-title-text" class="nt-modal__title">Registrar Avance / Bitácora</h3>
                <p id="modal-subtitle-text" class="nt-modal__subtitle">Suministra la actualización técnica realizada en el ticket.</p>
            </div>

            <div id="modal-alert" class="nt-alert nt-alert--error nt-alert--small hidden"></div>

            <input type="hidden" id="modal-ticket-id">
            <input type="hidden" id="modal-target-status">

            <div>
                <label id="modal-label-text" class="nt-form__label nt-form__label--xs">Detalle de la Actividad / Notas del Técnico</label>
                <textarea id="modal-resolucion-text" rows="4" class="nt-form__textarea nt-form__textarea--compact" placeholder="Describe la solución aplicada, pruebas de conectividad o motivos del cambio de estado..."></textarea>
            </div>

            <div class="nt-modal__actions">
                <button type="button" id="btn-cancel-modal" class="nt-btn--text">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-modal" class="nt-btn nt-btn--sm">
                    Guardar Avance
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/tecnicos.js?v=1.1.0"></script>
    <script src="/assets/js/nav.js?v=1.0.1"></script>
</body>

</html>