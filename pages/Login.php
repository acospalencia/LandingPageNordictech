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

    <!-- Hoja de estilos centralizada del proyecto -->
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body>

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

                <div>
                    <a href="./Login.php" class="nt-btn">
                        Portal de Clientes
                    </a>
                </div>
            </div>
        </header>

        <main class="nt-main nt-main--centered">
            <div class="nt-w-full nt-max-w-md nt-card nt-card--form nt-card--shadow nt-relative">

                <div class="nt-stack-2 nt-mb-6">
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs">
                        Autenticación Corporativa
                    </span>
                    <h1 class="nt-text-2xl nt-font-display nt-font-bold nt-tracking-tight nt-text-white nt-uppercase">
                        Ingreso al Sistema
                    </h1>
                    <p class="nt-text-xs nt-text-muted nt-text-light">
                        Introduce tus credenciales autorizadas por NordicTech.
                    </p>
                </div>

                <div id="login-alert" class="nt-alert"></div>

                <form id="form-login" class="nt-form">
                    <div>
                        <label class="nt-form__label nt-form__label--xs">
                            Correo Electrónico
                        </label>
                        <input type="email" name="username" required autocomplete="username" placeholder="tucorreo@gmail.com"
                            class="nt-form__input">
                    </div>

                    <div>
                        <div class="nt-flex-between--wrap nt-mb-2">
                            <label class="nt-form__label nt-form__label--xs nt-form__label--no-margin">
                                Contraseña
                            </label>
                        </div>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••••••"
                            class="nt-form__input">
                    </div>

                    <div>
                        <button type="submit" class="nt-btn nt-btn--full nt-font-display">
                            Iniciar sesion
                        </button>
                    </div>
                </form>

                <div class="nt-form--inline">
                    <label class="nt-ml-2 nt-block nt-text-xs nt-text-muted nt-text-light nt-select-none nt-cursor-pointer nt-transition-colors">
                        No tienes cuenta? <a href="/pages/SignIn.php" class="nt-link">Contáctanos</a>
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

    <script src="/assets/js/login.js?v=1.1.0"></script>
</body>
</html>
