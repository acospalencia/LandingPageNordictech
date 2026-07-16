document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.querySelector('#form-login');
    const alertBox = document.querySelector('#login-alert');
    
    if (formulario && alertBox) {
        formulario.addEventListener('submit', async (event) => {
            event.preventDefault(); // Evita recarga de página
            
            // Limpiar alertas previas
            alertBox.classList.add('hidden');
            alertBox.className = "mb-6 p-4 text-xs font-semibold tracking-wide border rounded-none";

            // Seleccionar el botón y guardar su estado original
            const boton = formulario.querySelector('button[type="submit"]');
            const textoOriginal = boton.innerHTML;
            
            // Deshabilitar botón temporalmente
            boton.disabled = true;
            boton.innerHTML = `
                <span class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    PROCESANDO SOLICITUD...
                </span>
            `;

            const formData = new FormData(formulario);

            try {
                // Enviamos los datos al backend de PHP usando ruta absoluta
                const response = await fetch('/assets/php/login.php', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await response.json();

                if (resultado.status === 'success') {
                    // Pintar mensaje de éxito en el contenedor estilizado
                    alertBox.classList.add('bg-green-950/20', 'border-green-500/20', 'text-green-400');
                    alertBox.textContent = resultado.message;
                    alertBox.classList.remove('hidden');

                    formulario.reset();
                    
                    // Redireccionar de inmediato si la respuesta tiene la URL de destino
                    if (resultado.redirect) {
                        setTimeout(() => {
                            window.location.href = resultado.redirect;
                        }, 1000); // 1 segundo de retraso para que aprecien la confirmación
                    }
                } else {
                    // Pintar error en el contenedor estilizado
                    alertBox.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
                    alertBox.textContent = resultado.message;
                    alertBox.classList.remove('hidden');
                }

            } catch (error) {
                console.error('Error en la conexión:', error);
                alertBox.classList.add('bg-red-950/20', 'border-red-500/20', 'text-red-400');
                alertBox.textContent = 'Ocurrió un problema de conectividad con el servidor de NordicTech.';
                alertBox.classList.remove('hidden');
            } finally {
                // Restaurar botón a su estado original
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            }
        });
    }
});