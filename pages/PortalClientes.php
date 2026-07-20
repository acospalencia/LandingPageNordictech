<?php
session_start();

// Validar que el usuario esté autenticado y posea el rol de Cliente (id_rol = 1)
if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_rol']) !== 1) {
    header("Location: /pages/Login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes | NordicTech El Salvador</title>
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

                <button id="btn-logout" class="nt-btn hidden sm:inline-block">
                    Cerrar Sesión
                </button>

                <!-- Toggle móvil -->
                <button id="mobile-toggle" class="nt-mobile-toggle" aria-label="Abrir menú" aria-expanded="false">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path id="mobile-toggle-icon" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Menú móvil desplegable -->
                <div id="mobile-menu" class="nt-mobile-menu">
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                    <button id="btn-logout-mobile" class="nt-btn">Cerrar Sesión</button>
                </div>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="nt-main nt-main--portal">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-nordic-border/60 pb-5 sm:pb-6 mb-6 sm:mb-10 gap-3 sm:gap-4">
                <div>
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs">Consola de Cliente</span>
                    <h1 class="text-xl sm:text-2xl font-display font-bold uppercase tracking-tight">Gestión de Incidentes</h1>
                </div>
                <div class="bg-nordic-card border border-nordic-border px-3 py-2 sm:px-4 w-full sm:w-auto">
                    <span id="nodo-activo-text" class="nt-text-mono-id break-all">Cargando identidad...</span>
                </div>
            </div>

            <!-- Contenedor Global de Alertas Integradas en Interfaz -->
            <div id="portal-alert" class="nt-alert"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 lg:gap-10 items-start">

                <!-- Formulario de Apertura -->
                <div class="lg:col-span-5 bg-nordic-card border border-nordic-border p-5 sm:p-6 md:p-8 space-y-5 sm:space-y-6">
                    <div class="border-b border-nordic-border pb-4">
                        <h2 class="text-base sm:text-lg font-display font-bold uppercase text-white tracking-wide">Aperturar Nuevo Ticket</h2>
                        <p class="text-xs text-nordic-textMuted font-light mt-1">Reporte fallas activas en sus sistemas de seguridad o red.</p>
                    </div>

                    <form id="form-ticket" class="nt-form nt-form--compact">
                        <div>
                            <label class="nt-form__label nt-form__label--xs">Título del Incidente</label>
                            <input type="text" name="titulo" required placeholder="Ej: Pérdida de enlace en cámara IP perimetral"
                                class="nt-form__input">
                        </div>

                        <div>
                            <label class="nt-form__label nt-form__label--xs">Prioridad Solicitada</label>
                            <select name="prioridad" class="nt-form__select">
                                <option value="Baja" selected>Consulta General / Baja</option>
                                <option value="Media">Afectación Parcial / Media</option>
                                <option value="Alta">Falla Mayor / Alta</option>
                                <option value="Crítica">Fallo Total de Infraestructura / Crítica</option>
                            </select>
                        </div>

                        <div>
                            <label class="nt-form__label nt-form__label--xs">Descripción Técnica</label>
                            <textarea rows="5" name="descripcion" required placeholder="Detalle los síntomas del problema, equipos afectados o pruebas realizadas..."
                                class="nt-form__textarea"></textarea>
                        </div>

                        <button type="submit" class="nt-btn nt-btn--full font-display">
                            Enviar Reporte al Centro de Soporte
                        </button>
                    </form>
                </div>

                <!-- Historial e Incidentes Activos -->
                <div class="lg:col-span-7 space-y-5 sm:space-y-6">
                    <!-- Pestañas de Filtrado -->
                    <div class="nt-tabs">
                        <button id="tab-Abierto" class="nt-tabs__btn nt-tabs__btn--active">
                            Abiertos (0)
                        </button>
                        <button id="tab-EnProceso" class="nt-tabs__btn">
                            En Proceso (0)
                        </button>
                        <button id="tab-Cerrado" class="nt-tabs__btn">
                            Cerrados (0)
                        </button>
                    </div>

                    <!-- Listado Dinámico con Acordeones -->
                    <div id="contenedor-tickets" class="space-y-4">
                        <div class="p-4 text-center text-xs text-nordic-textMuted font-light">Sincronizando flujos con el servidor...</div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
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

    <!-- MODAL PERSONALIZADO PARA CONFIRMACIÓN DE CERRAR TICKET (Reemplaza a confirm()) -->
    <div id="modal-confirmar-cierre" class="nt-modal-backdrop nt-animate-fade-in">
        <div class="nt-modal nt-modal--sm text-center space-y-5">
            <div class="space-y-2">
                <div class="nt-modal__icon-wrap">
                    <svg class="nt-modal__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="nt-modal__title">¿Confirmar Cierre del Ticket?</h3>
                <p class="text-[11px] text-nordic-textMuted">Estás a punto de dar por solucionado este ticket de forma voluntaria. Esta acción no se puede deshacer.</p>
            </div>

            <!-- Campo oculto para almacenar el ID del ticket seleccionado -->
            <input type="hidden" id="cerrar-ticket-id">

            <div class="flex justify-center space-x-3 pt-2">
                <button type="button" id="btn-cancel-cierre" class="nt-btn--text">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-cierre" class="nt-btn nt-btn--danger nt-btn--sm">
                    Sí, Cerrar Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PERSONALIZADO PARA LOGOUT -->
    <div id="modal-logout" class="nt-modal-backdrop">
        <div class="nt-modal nt-modal--sm text-center space-y-5">
            <div class="space-y-2">
                <h3 class="nt-modal__title">¿Finalizar Sesión?</h3>
                <p class="text-[11px] text-nordic-textMuted">Su sesión activa en la consola será destruida de forma segura.</p>
            </div>
            <div class="flex justify-center space-x-3 pt-2">
                <button type="button" id="btn-cancel-logout" class="nt-btn--text">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-logout" class="nt-btn nt-btn--sm">
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/portal_clientes.js?v=1.0.7"></script>
    <script src="/assets/js/nav.js?v=1.0.0"></script>
</body>
</html>
