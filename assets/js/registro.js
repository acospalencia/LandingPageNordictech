document.querySelector('#form-registro').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('/assets/php/signin.php', {
                    method: 'POST',
                    body: formData
                });
                const resultado = await response.json();

                if (resultado.status === 'success') {
                    alert('🎉 ' + resultado.message);
                    window.location.replace('/pages/Login.php');
                } else {
                    alert('❌ Error: ' + resultado.message);
                }
            } catch (error) {
                alert('❌ Error de conectividad con el servidor.');
            }
        });