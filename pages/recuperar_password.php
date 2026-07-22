<!DOCTYPE html>
<html lang="es" class="h-full bg-[#060a14]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | Nordic Tech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        nordic: {
                            card: '#0B132B',
                            border: '#1C2541',
                            textMuted: '#5C6B73',
                            logoBlue: '#2A4094'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center p-4 font-sans text-slate-200">

    <div class="w-full max-w-md bg-nordic-card border border-nordic-border p-8 shadow-2xl">
        
        <!-- Encabezado -->
        <div class="mb-6 text-center">
            <h1 class="text-sm font-bold tracking-widest text-white uppercase">Recuperación de Credenciales</h1>
            <p class="text-[10px] text-nordic-textMuted uppercase tracking-wider mt-1">Verificación por Código de Seguridad</p>
        </div>

        <!-- Alerta de Estado -->
        <div id="alert-box" class="hidden mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none"></div>

        <!-- PASO 1: Solicitar Código -->
        <form id="form-paso-1" class="space-y-5">
            <div>
                <label class="block text-[9px] font-bold uppercase tracking-widest text-nordic-textMuted mb-2">Correo Electrónico Registrado</label>
                <input type="email" id="email" name="email" required placeholder="usuario@dominio.com"
                    class="w-full bg-[#060a14] border border-nordic-border px-4 py-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-nordic-logoBlue transition-colors">
            </div>

            <button type="submit" id="btn-paso-1" 
                class="w-full bg-[#2A4094] hover:bg-[#1C2541] text-white text-xs font-bold uppercase tracking-widest py-3 border border-blue-500/30 transition-all">
                Enviar Código de Verificación
            </button>
            
            <div class="text-center pt-2">
                <a href="/pages/Login.php" class="text-[10px] text-nordic-textMuted hover:text-white uppercase tracking-wider transition-colors">
                    &larr; Volver al Inicio de Sesión
                </a>
            </div>
        </form>

        <!-- PASO 2: Verificar Código y Nueva Contraseña (Oculto por defecto) -->
        <form id="form-paso-2" class="hidden space-y-5">
            <input type="hidden" id="email-confirmado" name="email">

            <div>
                <label class="block text-[9px] font-bold uppercase tracking-widest text-nordic-textMuted mb-2">Código de 6 Dígitos</label>
                <input type="text" name="codigo" required maxlength="6" placeholder="123456"
                    class="w-full bg-[#060a14] border border-nordic-border px-4 py-3 text-center text-lg font-mono tracking-widest text-white placeholder-slate-700 focus:outline-none focus:border-nordic-logoBlue transition-colors">
            </div>

            <div>
                <label class="block text-[9px] font-bold uppercase tracking-widest text-nordic-textMuted mb-2">Nueva Contraseña</label>
                <input type="password" name="nueva_password" required minlength="6" placeholder="••••••••"
                    class="w-full bg-[#060a14] border border-nordic-border px-4 py-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-nordic-logoBlue transition-colors">
            </div>

            <button type="submit" id="btn-paso-2"
                class="w-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold uppercase tracking-widest py-3 border border-emerald-500/30 transition-all">
                Actualizar Contraseña
            </button>
        </form>

    </div>

    <!-- Vinculación del script independiente -->
    <script src="/assets/js/recuperar_password.js?v=1.0.0"></script>
</body>
</html>