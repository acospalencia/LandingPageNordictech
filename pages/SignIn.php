<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header("Location: /pages/PortalClientes.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cuenta | NordicTech El Salvador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;800&family=Space+Grotesk:wght@400;700&display=swap" rel="stylesheet">
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
    <style>
        html, body {
            background-color: #060913 !important;
            color: #ffffff !important;
            margin: 0;
            padding: 0;
        }
        input {
            background-color: #060913 !important;
            color: #ffffff !important;
            border-color: #1E293B !important;
        }
        input:focus {
            border-color: #2A4094 !important;
        }
        input::placeholder {
            color: #64748B !important;
        }
    </style>
</head>
<body class="bg-[#060913] text-white font-sans antialiased selection:bg-nordic-logoBlue selection:text-white">

    <div class="bg-[#060913] text-white min-h-screen w-full relative flex flex-col justify-between isolation-auto">
        
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[400px] bg-gradient-to-b from-[#2A4094]/10 via-transparent to-transparent blur-3xl pointer-events-none z-0"></div>

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
                <a href="/pages/Login.php" class="text-xs uppercase tracking-widest font-semibold text-nordic-textMuted hover:text-white transition-colors">
                    Iniciar Sesión
                </a>
            </div>
        </header>

        <main class="flex-grow flex items-center justify-center px-6 pt-36 pb-16 relative z-10">
            <div class="max-w-md w-full bg-nordic-card border border-nordic-border p-8 space-y-6 shadow-2xl">
                <div class="border-b border-nordic-border pb-4 text-center">
                    <span class="text-nordic-logoBlue font-display font-bold text-[10px] uppercase tracking-widest block mb-1">Solicitud de Acceso</span>
                    <h1 class="text-xl font-display font-bold uppercase tracking-wide">Crear cuenta corporativa</h1>
                    <p class="text-xs text-nordic-textMuted font-light mt-1">Tu cuenta requerirá aprobación manual del staff técnico.</p>
                </div>

                <form id="form-registro" class="space-y-4">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Nombre Completo</label>
                        <input type="text" name="nombre" required placeholder="Ej: Juan Pérez" 
                            class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 focus:outline-none focus:border-nordic-logoBlue text-sm text-white rounded-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Correo Electrónico</label>
                        <input type="email" name="email" required placeholder="correo@empresa.com" 
                            class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 focus:outline-none focus:border-nordic-logoBlue text-sm text-white rounded-none transition-colors">
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••" 
                            class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 focus:outline-none focus:border-nordic-logoBlue text-sm text-white rounded-none transition-colors">
                    </div>

                    <button type="submit" class="w-full bg-nordic-logoBlue text-white px-6 py-4 text-xs font-display font-bold uppercase tracking-widest hover:bg-nordic-logoBlueHover transition-colors mt-6">
                        Registrar Solicitud
                    </button>
                </form>
            </div>
        </main>

        <footer class="border-t border-nordic-border/40 py-8 text-xs text-nordic-textMuted font-light text-center relative z-10">
            <p>&copy; 2026 NordicTech El Salvador. Registro Cifrado de Nodos.</p>
        </footer>
    </div>

    <script src="/assets/js/registro.js?v=1.0.5"></script>
</body>
</html>