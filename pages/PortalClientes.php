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
    
    <!-- CSS Externo -->
    <link rel="stylesheet" href="/assets/css/portal_clientes.css">
</head>
<body class="bg-[#060913] text-white font-sans antialiased selection:bg-nordic-logoBlue selection:text-white">

    <div class="bg-[#060913] text-white min-h-screen w-full relative flex flex-col justify-between isolation-auto">

        <!-- Reflejo ambiental -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[400px] bg-gradient-to-b from-[#2A4094]/10 via-transparent to-transparent blur-3xl pointer-events-none z-0"></div>

        <!-- Header -->
        <header class="fixed top-0 w-full bg-[#101729]/80 backdrop-blur-md z-50 border-b border-nordic-border/40">
            <div class="max-w-7xl bg-[#101729]/80 mx-auto px-6 h-24 flex items-center justify-between">
                <div class="flex flex-col items-center pt-2">
                    <div style='font-family: "Space Grotesk", sans-serif; font-weight: bold; font-size: 24px; letter-spacing: -0.02em; color: #ffffff; text-transform: uppercase;'>
                        <a href="/">
                            <img src="/assets/img/Marca de agua black.png" alt="Logo" style='display: inline-block; height: 84px; width: auto; vertical-align: middle;'>
                        </a>
                    </div>
                </div>

                <nav class="hidden md:flex space-x-8 text-xs uppercase tracking-widest font-semibold text-nordic-textMuted">
                    <a href="/" class="hover:text-white transition-colors">Inicio</a>
                    <a href="/#servicios" class="hover:text-white transition-colors">Servicios</a>
                    <a href="/#contacto" class="hover:text-white transition-colors">Contacto</a>
                </nav>

                <button id="btn-logout" class="bg-nordic-logoBlue text-white border border-white/10 px-6 py-3 text-xs uppercase tracking-widest font-bold hover:bg-nordic-logoBlueHover transition-all">
                    Cerrar Sesión
                </button>
            </div>
        </header>

        <!-- Contenido Principal -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-6 pt-36 pb-16 relative z-10">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-nordic-border/60 pb-6 mb-10 gap-4">
                <div>
                    <span class="text-nordic-logoBlue font-display font-bold text-[10px] uppercase tracking-widest block">Consola de Cliente</span>
                    <h1 class="text-2xl font-display font-bold uppercase tracking-tight">Gestión de Incidentes</h1>
                </div>
                <div class="bg-nordic-card border border-nordic-border px-4 py-2">
                    <span id="nodo-activo-text" class="text-xs font-mono text-nordic-textMuted">Cargando identidad...</span>
                </div>
            </div>

            <!-- Contenedor Global de Alertas Integradas en Interfaz -->
            <div id="portal-alert" class="hidden mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none transition-all duration-300"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                
                <!-- Formulario de Apertura -->
                <div class="lg:col-span-5 bg-nordic-card border border-nordic-border p-6 md:p-8 space-y-6">
                    <div class="border-b border-nordic-border pb-4">
                        <h2 class="text-lg font-display font-bold uppercase text-white tracking-wide">Aperturar Nuevo Ticket</h2>
                        <p class="text-xs text-nordic-textMuted font-light mt-1">Reporte fallas activas en sus sistemas de seguridad o red.</p>
                    </div>

                    <form id="form-ticket" class="space-y-5">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Título del Incidente</label>
                            <input type="text" name="titulo" required placeholder="Ej: Pérdida de enlace en cámara IP perimetral" 
                                class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 text-sm text-white rounded-none focus:outline-none focus:border-nordic-logoBlue transition-colors">
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Prioridad Solicitada</label>
                            <select name="prioridad" class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 text-sm text-white rounded-none focus:outline-none focus:border-nordic-logoBlue transition-colors cursor-pointer">
                                <option value="Baja" selected>Consulta General / Baja</option>
                                <option value="Media">Afectación Parcial / Media</option>
                                <option value="Alta">Falla Mayor / Alta</option>
                                <option value="Crítica">Fallo Total de Infraestructura / Crítica</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Descripción Técnica</label>
                            <textarea rows="5" name="descripcion" required placeholder="Detalle los síntomas del problema, equipos afectados o pruebas realizadas..." 
                                class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 text-sm text-white resize-none rounded-none focus:outline-none focus:border-nordic-logoBlue transition-colors"></textarea>
                        </div>

                        <button type="submit" class="w-full bg-nordic-logoBlue text-white px-6 py-4 text-xs font-display font-bold uppercase tracking-widest hover:bg-nordic-logoBlueHover transition-colors">
                            Enviar Reporte al Centro de Soporte
                        </button>
                    </form>
                </div>

                <!-- Historial e Incidentes Activos -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Pestañas de Filtrado -->
                    <div class="flex border border-nordic-border bg-[#080d1a] p-1 select-none">
                        <button id="tab-Abierto" class="flex-1 py-3 text-xs font-bold uppercase tracking-wider text-center transition-all bg-[#2A4094] text-white">
                            Abiertos (0)
                        </button>
                        <button id="tab-EnProceso" class="flex-1 py-3 text-xs font-bold uppercase tracking-wider text-center transition-all text-nordic-textMuted hover:text-white">
                            En Proceso (0)
                        </button>
                        <button id="tab-Cerrado" class="flex-1 py-3 text-xs font-bold uppercase tracking-wider text-center transition-all text-nordic-textMuted hover:text-white">
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
        <footer class="border-t border-nordic-border/40 py-12 text-xs text-nordic-textMuted font-light relative z-10">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex flex-col items-start opacity-70">
                    <p class="font-display font-bold tracking-wider text-white text-xs uppercase">NORDICTECH</p>
                    <p class="text-[8px] tracking-[0.2em] uppercase text-nordic-textMuted">El Salvador</p>
                </div>
                <p>&copy; 2026 NordicTech El Salvador. Consola de monitoreo y soporte.</p>
            </div>
        </footer>

    </div>

    <!-- MODAL PERSONALIZADO PARA CONFIRMACIÓN DE CERRAR TICKET (Reemplaza a confirm()) -->
    <div id="modal-confirmar-cierre" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm animate-fade-in">
        <div class="w-full max-w-sm bg-nordic-card border border-nordic-border p-6 shadow-2xl text-center space-y-5">
            <div class="space-y-2">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-950/30 border border-red-500/30">
                    <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-sm font-display font-bold uppercase tracking-wider text-white">¿Confirmar Cierre del Ticket?</h3>
                <p class="text-[11px] text-nordic-textMuted">Estás a punto de dar por solucionado este ticket de forma voluntaria. Esta acción no se puede deshacer.</p>
            </div>

            <!-- Campo oculto para almacenar el ID del ticket seleccionado -->
            <input type="hidden" id="cerrar-ticket-id">

            <div class="flex justify-center space-x-3 pt-2">
                <button type="button" id="btn-cancel-cierre" class="px-4 py-2 text-xs uppercase tracking-widest text-nordic-textMuted hover:text-white transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-cierre" class="bg-red-900 border border-red-700 hover:bg-red-800 text-white px-5 py-2 text-xs uppercase tracking-widest font-bold transition-colors">
                    Sí, Cerrar Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PERSONALIZADO PARA LOGOUT -->
    <div id="modal-logout" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="w-full max-w-sm bg-nordic-card border border-nordic-border p-6 shadow-2xl text-center space-y-5">
            <div class="space-y-2">
                <h3 class="text-sm font-display font-bold uppercase tracking-wider text-white">¿Finalizar Sesión?</h3>
                <p class="text-[11px] text-nordic-textMuted">Su sesión activa en la consola será destruida de forma segura.</p>
            </div>
            <div class="flex justify-center space-x-3 pt-2">
                <button type="button" id="btn-cancel-logout" class="px-4 py-2 text-xs uppercase tracking-widest text-nordic-textMuted hover:text-white transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-logout" class="bg-nordic-logoBlue hover:bg-nordic-logoBlueHover text-white px-5 py-2 text-xs uppercase tracking-widest font-bold transition-colors">
                    Cerrar Sesión
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/portal_clientes.js?v=1.0.7"></script>
</body>
</html>