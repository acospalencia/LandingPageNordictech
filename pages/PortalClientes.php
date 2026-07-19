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

    <!-- Hoja de estilos centralizada del proyecto -->
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body>

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

                <button id="btn-logout" class="nt-btn">
                    Cerrar Sesión
                </button>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="nt-main nt-main--portal">

            <div class="nt-portal-header">
                <div>
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs">Consola de Cliente</span>
                    <h1 class="nt-text-2xl nt-font-display nt-font-bold nt-uppercase nt-tracking-tight">Gestión de Incidentes</h1>
                </div>
                <div class="nt-card--node-identity">
                    <span id="nodo-activo-text" class="nt-mono-id">Cargando identidad...</span>
                </div>
            </div>

            <!-- Contenedor Global de Alertas Integradas en Interfaz -->
            <div id="portal-alert" class="nt-alert"></div>

            <div class="nt-portal-grid">

                <!-- Formulario de Apertura -->
                <div class="nt-col-span-5 nt-card nt-card--form nt-stack-6">
                    <div class="nt-divider-bottom nt-pb-4">
                        <h2 class="nt-text-lg nt-font-display nt-font-bold nt-uppercase nt-text-white nt-tracking-wide">Aperturar Nuevo Ticket</h2>
                        <p class="nt-text-xs nt-text-muted nt-text-light nt-mt-1">Reporte fallas activas en sus sistemas de seguridad o red.</p>
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

                        <button type="submit" class="nt-btn nt-btn--full nt-font-display">
                            Enviar Reporte al Centro de Soporte
                        </button>
                    </form>
                </div>

                <!-- Historial e Incidentes Activos -->
                <div class="nt-col-span-7 nt-stack-6">
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
                    <div id="contenedor-tickets" class="nt-space-y-4">
                        <div class="nt-p-4 nt-text-center nt-text-xs nt-text-muted nt-text-light">Sincronizando flujos con el servidor...</div>
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
        <div class="nt-modal nt-modal--sm nt-text-center nt-stack-5">
            <div class="nt-stack-2">
                <div class="nt-modal__icon-wrap">
                    <svg class="nt-modal__icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="nt-modal__title">¿Confirmar Cierre del Ticket?</h3>
                <p class="nt-text-sm nt-text-muted">Estás a punto de dar por solucionado este ticket de forma voluntaria. Esta acción no se puede deshacer.</p>
            </div>

            <!-- Campo oculto para almacenar el ID del ticket seleccionado -->
            <input type="hidden" id="cerrar-ticket-id">

            <div class="nt-flex-cta nt-justify-center nt-pt-2">
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
        <div class="nt-modal nt-modal--sm nt-text-center nt-stack-5">
            <div class="nt-stack-2">
                <h3 class="nt-modal__title">¿Finalizar Sesión?</h3>
                <p class="nt-text-sm nt-text-muted">Su sesión activa en la consola será destruida de forma segura.</p>
            </div>
            <div class="nt-flex-cta nt-justify-center nt-pt-2">
                <button type="button" id="btn-cancel-logout" class="nt-btn--text">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-logout" class="nt-btn nt-btn--sm">
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/portal_clientes.js?v=1.1.0"></script>
</body>
</html>
