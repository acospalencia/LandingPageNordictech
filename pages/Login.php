<?php
session_start();

// Si ya tiene sesión activa, redirigir automáticamente según su rol de la base de datos
if (isset($_SESSION['id_usuario']) && isset($_SESSION['id_rol'])) {
    if (intval($_SESSION['id_rol']) === 3) {
        header("Location: /pages/Dashboard.php");
        exit;
    } else {
        header("Location: /pages/PortalClientes.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes | NordicTech El Salvador</title>
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

        <div class="nt-ambient-glow nt-ambient-glow--medium"></div>

        <header class="nt-header">
            <div class="nt-header__inner">

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

                <a href="./Login.php" class="nt-btn hidden sm:inline-block">
                    Portal de Clientes
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
                    <a href="./Login.php" class="nt-btn">Portal de Clientes</a>
                </div>
            </div>
        </header>

        <main class="nt-main nt-main--centered">
            <div class="w-full max-w-md bg-nordic-card border border-nordic-border p-6 sm:p-8 md:p-10 shadow-2xl relative">

                <div class="space-y-2 mb-6">
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs">
                        Inicia sesion en tu cuenta
                    </span>
                    <h1 class="text-xl sm:text-2xl font-display font-bold tracking-tight text-white uppercase">
                        Ingreso al Sistema de tickets
                    </h1>
                    <p class="text-xs text-nordic-textMuted font-light">
                        Introduce tus credenciales.
                    </p>
                </div>

                <div id="login-alert" class="nt-alert"></div>

                <form id="form-login" class="nt-form">
                    <div>
                        <label class="nt-form__label nt-form__label--xs">
                            Correo Electrónico
                        </label>
                        <input type="text" name="username" required autocomplete="username" placeholder="tucorreo@gmail.com"
                            class="nt-form__input">
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="nt-form__label nt-form__label--xs nt-form__label--no-margin">
                                Contraseña
                            </label>
                        </div>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••••"
                            class="nt-form__input">
                    </div>

                    <div>
                        <button type="submit" class="nt-btn nt-btn--full font-display">
                            Iniciar sesion
                        </button>
                    </div>
                </form>

                <div class="nt-form--inline text-size-md">
                    <label class="ml-2 block text-xs text-nordic-textMuted font-light select-none cursor-pointer hover:text-slate-300 transition-colors">
                        <a href="/pages/recuperar_password.php" class="nt-link">Olvide mi contraseña</a>
                    </label>
                </div>

                <div class="nt-form--inline text-size-md">
                    <label class="ml-2 block text-xs text-nordic-textMuted font-light select-none cursor-pointer hover:text-slate-300 transition-colors">
                        No tienes cuenta? <a href="/pages/SignIn.php" class="nt-link">Crea tu cuenta</a>
                    </label>
                </div>

            </div>
        </main>

        <footer class="nt-footer">
            <div class="nt-footer__inner">
                <div class="nt-footer__brand">
                    <p class="nt-footer__brand-name">NORDICTECH</p>
                    <p class="nt-footer__brand-sub">El Salvador S.A de C.V</p>
                </div>
                <p>&copy; 2026 NordicTech El Salvador. </p>
            </div>
        </footer>

    </div>

    <script src="/assets/js/login.js?v=1.0.5"></script>
    <script src="/assets/js/nav.js?v=1.0.0"></script>
</body>
</html>
