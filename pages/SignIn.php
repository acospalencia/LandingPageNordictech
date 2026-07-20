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
    <!-- Hoja de estilos centralizada del proyecto -->
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body class="bg-[#060913] text-white font-sans antialiased selection:bg-nordic-logoBlue selection:text-white">

    <div class="nt-app-container">

        <div class="nt-ambient-glow nt-ambient-glow--short"></div>

        <header class="nt-header">
            <div class="nt-header__inner">

                <!-- LOGO VECTORIAL IDENTICO A LA IMAGEN -->
                <div class="nt-logo-wrap">
                    <div class="nt-logo-title">
                        <a href="/">
                            <img src="/assets/img/Marca de agua black.png" alt="Logo" class="nt-logo">
                        </a>
                    </div>
                </div>

                <nav class="nt-nav">
                    <a href="/" class="nt-nav__link">Inicio</a>
                    <a href="/#servicios" class="nt-nav__link">Servicios</a>
                    <a href="/#contacto" class="nt-nav__link">Contacto</a>
                </nav>
                <a href="/pages/Login.php" class="nt-link-button hidden sm:inline-block">
                    Iniciar Sesión
                </a>

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
                    <a href="/pages/Login.php" class="nt-btn">Iniciar Sesión</a>
                </div>
            </div>
        </header>

        <main class="nt-main nt-main--centered">
            <div class="max-w-md w-full bg-nordic-card border border-nordic-border p-6 sm:p-8 space-y-6 shadow-2xl">
                <div class="border-b border-nordic-border pb-4 text-center">
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs nt-text-eyebrow--spaced">Solicitud de Acceso</span>
                    <h1 class="text-lg sm:text-xl font-display font-bold uppercase tracking-wide">Crear cuenta corporativa</h1>
                    <p class="text-xs text-nordic-textMuted font-light mt-1">Tu cuenta requerirá aprobación manual del staff técnico.</p>
                </div>

                <form id="form-registro" class="nt-form nt-form--tight">
                    <div>
                        <label class="nt-form__label nt-form__label--xs">Nombre Completo</label>
                        <input type="text" name="nombre" required placeholder="Ej: Juan Pérez"
                            class="nt-form__input">
                    </div>

                    <div>
                        <label class="nt-form__label nt-form__label--xs">Correo Electrónico</label>
                        <input type="email" name="email" required placeholder="correo@empresa.com"
                            class="nt-form__input">
                    </div>

                    <div>
                        <label class="nt-form__label nt-form__label--xs">Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="nt-form__input">
                    </div>

                    <button type="submit" class="nt-btn nt-btn--full nt-btn--mt-form font-display">
                        Registrar Solicitud
                    </button>
                </form>
            </div>
        </main>

        <footer class="nt-footer nt-footer--compact">
            <p>&copy; 2026 NordicTech El Salvador. Registro Cifrado de Nodos.</p>
        </footer>
    </div>

    <script src="/assets/js/registro.js?v=1.0.5"></script>
    <script src="/assets/js/nav.js?v=1.0.0"></script>
</body>
</html>
