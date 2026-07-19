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
    <!-- Hoja de estilos centralizada del proyecto -->
    <link rel="stylesheet" href="/assets/css/nordictech.css">
</head>
<body>

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
                <a href="/pages/Login.php" class="nt-link-button">
                    Iniciar Sesión
                </a>
            </div>
        </header>

        <main class="nt-main nt-main--centered">
            <div class="nt-max-w-md nt-w-full nt-card nt-card--form nt-stack-6 nt-card--shadow">
                <div class="nt-divider-bottom nt-pb-4 nt-text-center">
                    <span class="nt-text-eyebrow nt-text-eyebrow--xs nt-text-eyebrow--spaced">Solicitud de Acceso</span>
                    <h1 class="nt-text-xl nt-font-display nt-font-bold nt-uppercase nt-tracking-wide">Crear cuenta corporativa</h1>
                    <p class="nt-text-xs nt-text-muted nt-text-light nt-mt-1">Tu cuenta requerirá aprobación manual del staff técnico.</p>
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

                    <button type="submit" class="nt-btn nt-btn--full nt-btn--mt-form nt-font-display">
                        Registrar Solicitud
                    </button>
                </form>
            </div>
        </main>

        <footer class="nt-footer nt-footer--compact">
            <p>&copy; 2026 NordicTech El Salvador. Registro Cifrado de Nodos.</p>
        </footer>
    </div>

    <script src="/assets/js/registro.js?v=1.1.0"></script>
</body>
</html>
