document.addEventListener('DOMContentLoaded', () => {
    const formulario = document.querySelector('#form-login');
    
    if (formulario) {
        formulario.addEventListener('submit', async (event) => {
            event.preventDefault(); // Evita que la página se recargue
            
            // Seleccionar el botón y guardar su estado original
            const boton = formulario.querySelector('button[type="submit"]');
            const textoOriginal = boton.innerHTML;
            
            // Cambiar el estado del botón a "Enviando"
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

            // Recolectar los datos del formulario de manera dinámica
            const formData = new FormData(formulario);

            try {
                // Enviamos los datos al backend de PHP
                const response = await fetch('/assets/php/login.php', {
                    method: 'POST',
                    body: formData
                });

                const resultado = await response.json();

                if (resultado.status === 'success') {
                    alert('✔ ' + resultado.message);
                    formulario.reset(); // Limpia los campos del formulario
                    
                    // Si el login es exitoso, aquí puedes redirigir al dashboard interno:
                    if (resultado.redirect) {
                        window.location.href = resultado.redirect;
                    }
                } else {
                    alert('❌ Error: ' + resultado.message);
                }

            } catch (error) {
                console.error('Error en la conexión:', error);
                alert('❌ Ocurrió un problema de conectividad con el servidor de NordicTech.');
            } finally {
                // Restaurar el estado original del botón
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
            }
        });
    }
});