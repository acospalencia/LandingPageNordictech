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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2family=Montserrat:wght@300;400;600;800&family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">
    
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
    
    <link rel="stylesheet" href="/assets/css/dashboard_admin.css">
</head>
<body class="bg-[#060913] text-white font-sans antialiased">

    <header class="fixed top-0 w-full bg-[#101729]/80 backdrop-blur-md z-50 border-b border-nordic-border/40">
            <div class="max-w-7xl bg-[#101729]/80 mx-auto px-6 h-24 flex items-center justify-between">

                <!-- LOGO VECTORIAL IDENTICO A LA IMAGEN -->
                <div class="flex flex-col items-center pt-2">
                    
                    <div
                        style='font-family: "Space Grotesk", "Segoe UI", sans-serif; font-weight: bold; font-size: 24px; letter-spacing: -0.02em; color: #ffffff; text-transform: uppercase; line-height: 24px;'>
                        <a href="/">
                            <img src="/assets/img/Marca de agua black.png" alt="Logo"
                                style='display: inline-block; height: 84px; width: auto; vertical-align: middle; margin-left: 6px; border: 0;'>
                        </a>
                    </div>
                </div>

                <nav
                    class="hidden md:flex space-x-8 text-xs uppercase tracking-widest font-semibold text-nordic-textMuted">
                    <a href="/" class="hover:text-white transition-colors">Inicio</a>
                    <a href="/#servicios" class="hover:text-white transition-colors">Servicios</a>
                    <a href="/#contacto" class="hover:text-white transition-colors">Contacto</a>
                </nav>

                <div>
                    <button id="btn-logout" class="bg-nordic-logoBlue text-white border border-white/10 px-6 py-3 rounded-none text-xs uppercase tracking-widest font-bold hover:bg-nordic-logoBlueHover transition-all block">
                        cerrar sesión
                    </button>
                </div>
            </div>
        </header>

    <div class="flex pt-24 min-h-screen overflow-hidden">
        
        <aside class="w-80 bg-nordic-card border-r border-nordic-border flex flex-col fixed left-0 top-24 bottom-0 z-30">
            <div class="p-4 border-b border-nordic-border">
                <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-nordic-textMuted">Filtrar Clientes</label>
                <input type="text" id="search-input" placeholder="Nombre, correo o empresa..." 
                    class="w-full bg-nordic-bg border border-nordic-border px-3 py-2 text-sm text-white focus:outline-none focus:border-nordic-logoBlue rounded-none transition-colors">
            </div>
            
            <div id="clientes-list" class="flex-1 overflow-y-auto custom-scroll divide-y divide-nordic-border/30">
                <div class="p-4 text-center text-xs text-nordic-textMuted font-light">Sincronizando operadores...</div>
            </div>
        </aside>

        <main class="flex-grow pl-80 min-h-screen bg-[#060913]">
            <div class="max-w-5xl mx-auto p-8">
                
                <div id="alert-container" class="hidden mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none"></div>

                <div class="border-b border-nordic-border pb-6 mb-8 flex items-center justify-between">
                    <div>
                        <span class="text-nordic-logoBlue font-display font-bold text-[10px] uppercase tracking-widest block mb-1 font-semibold">Consola de Soporte</span>
                        <h1 id="active-client-name" class="text-2xl font-display font-bold uppercase tracking-wide">Selecciona un cliente</h1>
                        <p id="active-client-meta" class="text-xs text-nordic-textMuted font-light mt-1">Selecciona un elemento de la lista para auditar sus tickets de soporte.</p>
                    </div>
                </div>

                <div id="tickets-container" class="hidden space-y-6">
                    
                    <div class="flex border border-nordic-border bg-[#080d1a] select-none">
                        <button onclick="switchTab('Abierto')" id="tab-Abierto" class="flex-1 py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border bg-nordic-logoBlue text-white">
                            Abiertos (<span id="count-Abierto">0</span>)
                        </button>
                        <button onclick="switchTab('En Proceso')" id="tab-EnProceso" class="flex-1 py-4 text-xs font-bold uppercase tracking-wider text-center transition-all border-r border-nordic-border text-nordic-textMuted hover:text-white">
                            En Proceso (<span id="count-EnProceso">0</span>)
                        </button>
                        <button onclick="switchTab('Cerrado')" id="tab-Cerrado" class="flex-1 py-4 text-xs font-bold uppercase tracking-wider text-center transition-all text-nordic-textMuted hover:text-white">
                            Cerrados / Resueltos (<span id="count-Cerrado">0</span>)
                        </button>
                    </div>

                    <div id="tickets-list" class="space-y-4">
                        </div>
                </div>

                <div id="no-client-selected" class="text-center py-24 border border-dashed border-nordic-border">
                    <p class="text-xs uppercase tracking-widest text-nordic-textMuted">Debes seleccionar un cliente del menú lateral para cargar su flujo de soporte.</p>
                </div>

            </div>
        </main>
    </div>

    <div id="close-ticket-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/85 backdrop-blur-sm">
        <div class="w-full max-w-md bg-nordic-card border border-nordic-border p-6 shadow-2xl space-y-4">
            <div class="border-b border-nordic-border pb-3">
                <h3 id="modal-title-text" class="text-sm font-display font-bold uppercase tracking-wider text-white">Actualizar Ticket</h3>
                <p id="modal-subtitle-text" class="text-[11px] text-nordic-textMuted mt-1">Suministra la información técnica correspondiente.</p>
            </div>
            
            <div id="modal-alert" class="hidden p-3 text-[11px] font-semibold border text-red-400 border-red-500/20 bg-red-950/20"></div>

            <input type="hidden" id="modal-ticket-id">
            <input type="hidden" id="modal-target-status">
            
            <div>
                <label id="modal-label-text" class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Comentarios</label>
                <textarea id="modal-resolucion-text" rows="4" class="w-full bg-nordic-bg border border-nordic-border px-3 py-2 text-sm text-white focus:outline-none focus:border-nordic-logoBlue rounded-none resize-none transition-colors"></textarea>
            </div>

            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" id="btn-cancel-modal" class="px-4 py-2 text-xs uppercase tracking-widest text-nordic-textMuted hover:text-white transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btn-confirm-modal" class="bg-nordic-logoBlue border border-white/10 hover:bg-nordic-logoBlueHover text-white px-5 py-2 text-xs uppercase tracking-widest font-bold transition-colors">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/dashboard_admin.js?v=1.0.5"></script>
</body>
</html>