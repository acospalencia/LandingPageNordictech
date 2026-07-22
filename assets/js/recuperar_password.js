/* ==========================================================================
   recuperar_password.js — Lógica de Recuperación de Contraseña
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    const formPaso1 = document.getElementById('form-paso-1');
    const formPaso2 = document.getElementById('form-paso-2');
    const alertBox = document.getElementById('alert-box');

    // Mostrar mensajes de alerta dinámicos
    function showAlert(type, text) {
        if (!alertBox) return;
        alertBox.className = "mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none transition-all";
        if (type === 'success') {
            alertBox.classList.add('bg-green-950/20', 'border-green-500/20', 'text-green-400');
        } else {
            alertBox.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
        }
        alertBox.textContent = text;
        alertBox.classList.remove('hidden');
    }

    // PASO 1: Solicitar envío de código por correo
    if (formPaso1) {
        formPaso1.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-paso-1');
            const emailInput = document.getElementById('email').value.trim();

            btn.disabled = true;
            btn.textContent = 'PROCESANDO SOLICITUD...';

            try {
                const res = await fetch('/assets/php/solicitar_codigo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `email=${encodeURIComponent(emailInput)}`
                });

                const text = await res.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no-JSON del servidor:', text);
                    showAlert('error', 'El servidor devolvió una respuesta no válida. Revisa la consola.');
                    return;
                }

                if (data.status === 'success') {
                    showAlert('success', data.message);
                    document.getElementById('email-confirmado').value = emailInput;

                    formPaso1.classList.add('hidden');
                    formPaso2.classList.remove('hidden');
                } else {
                    showAlert('error', data.message);
                }
            } catch (err) {
                showAlert('error', 'Error en el enlace de red al solicitar el código.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Código de Verificación';
            }
        });
    }

    // PASO 2: Verificar código y guardar nueva contraseña
    if (formPaso2) {
        formPaso2.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-paso-2');
            const formData = new FormData(formPaso2);

            btn.disabled = true;
            btn.textContent = 'VERIFICANDO...';

            try {
                const res = await fetch('/assets/php/cambiar_password.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });
                const data = await res.json();

                if (data.status === 'success') {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.replace('/pages/Login.php');
                    }, 2500);
                } else {
                    showAlert('error', data.message);
                }
            } catch (err) {
                showAlert('error', 'Fallo al procesar el cambio de credenciales.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Actualizar Contraseña';
            }
        });
    }
});