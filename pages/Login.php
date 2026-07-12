<?php
session_start();

// Si la variable de sesión existe, el usuario ya está autenticado.
// Lo redirigimos de inmediato a su portal para que no vuelva a ver el login.
if (isset($_SESSION['id_usuario'])) {
    header("Location: /pages/PortalClientes.html");
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
    <!-- Forzamos estilos globales y blindamos los inputs contra el comportamiento nativo de navegadores -->
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

    <!-- CONTENEDOR MAESTRO -->
    <div class="bg-[#060913] text-white min-h-screen w-full relative flex flex-col justify-between isolation-auto">

        <!-- Reflejo azul ambiental de fondo -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-[500px] bg-gradient-to-b from-[#2A4094]/10 via-transparent to-transparent blur-3xl pointer-events-none z-0"></div>

        <!-- Header (Idéntico a tu ejemplo) -->
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
                    <a href="#servicios" class="hover:text-white transition-colors">Servicios</a>
                    <a href="#contacto" class="hover:text-white transition-colors">Contacto</a>
                </nav>

                <div>
                    <a href="./Login.php"
                        class="bg-nordic-logoBlue text-white border border-white/10 px-6 py-3 rounded-none text-xs uppercase tracking-widest font-bold hover:bg-nordic-logoBlueHover transition-all block">
                        Portal de Clientes
                    </a>
                </div>
            </div>
        </header>

        <!-- Sección del Formulario de Login -->
        <main class="flex-grow flex items-center justify-center pt-36 pb-16 px-6 relative z-10">
            <div class="w-full max-w-md bg-nordic-card border border-nordic-border p-8 md:p-10 shadow-2xl relative">
                
                

                <!-- Encabezado del Formulario -->
                <div class="space-y-2 mb-8">
                    <span class="text-nordic-logoBlue font-display font-bold text-[10px] uppercase tracking-widest block">
                        Autenticación Corporativa
                    </span>
                    <h1 class="text-2xl font-display font-bold tracking-tight text-white uppercase">
                        Ingreso al Sistema
                    </h1>
                    <p class="text-xs text-nordic-textMuted font-light">
                        Introduce tus credenciales autorizadas por NordicTech.
                    </p>
                </div>

                <!-- Formulario sin lógica (Estructura visual pura) -->
                <form id="form-login" class="space-y-6">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold mb-2 text-slate-300">
                            Correo Electrónico
                        </label>
                        <input type="email" name="username" required autocomplete="username"
                            placeholder="tucorreo@gmail.com"
                            class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 focus:outline-none focus:border-nordic-logoBlue text-sm text-white rounded-none transition-colors">
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-[10px] uppercase tracking-widest font-bold text-slate-300">
                                Contraseña
                            </label>
                            
                        </div>
                        <input type="password" name="password" required autocomplete="current-password"
                            placeholder="••••••••••••"
                            class="w-full bg-nordic-bg border border-nordic-border px-4 py-3 focus:outline-none focus:border-nordic-logoBlue text-sm text-white rounded-none transition-colors">
                    </div>

                    

                    <div>
                        <button type="submit" 
                            class="w-full bg-nordic-logoBlue text-white px-8 py-4 text-xs font-display font-bold uppercase tracking-widest hover:bg-nordic-logoBlueHover transition-colors">
                            Iniciar sesion
                        </button>
                    </div>
                </form>

                <!-- Registrarte -->
                    <div class="flex items-center mt-4">
                        <label class="ml-2 block text-xs text-nordic-textMuted font-light select-none cursor-pointer hover:text-slate-300 transition-colors">
                            No tienes cuenta? <a href="/pages/Registro.html" class="text-white underline hover:text-nordic-logoBlueHover transition-colors">Contáctanos</a>
                        </label>
                    </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-nordic-border/40 py-12 text-xs text-nordic-textMuted font-light relative z-10">
            <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex flex-col items-start opacity-70">
                    <p class="font-display font-bold tracking-wider text-white text-xs uppercase">NORDICTECH</p>
                    <p class="text-[8px] tracking-[0.2em] uppercase text-nordic-textMuted">El Salvador S.A de C.V</p>
                </div>
                <p>&copy; 2026 NordicTech El Salvador. </p>
            </div>
        </footer>

    </div>

    <script src="/assets/js/login.js?v=1.0.4"></script>
</body>
</html>