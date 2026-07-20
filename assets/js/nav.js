/* ==========================================================================
   nav.js — Control del menú móvil (hamburguesa) y drawer del sidebar
   - Se carga en TODAS las páginas con header
   - No afecta la lógica existente; solo gestiona visibilidad visual
   - Mobile First: solo se activa el toggle cuando los elementos están
     presentes en el DOM
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    /* --------------------------------------------------------------------------
       1. Menú hamburguesa principal (todas las páginas con header)
       -------------------------------------------------------------------------- */
    const mobileToggle  = document.getElementById('mobile-toggle');
    const mobileMenu    = document.getElementById('mobile-menu');
    const mobileIcon    = document.getElementById('mobile-toggle-icon');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = mobileMenu.classList.toggle('nt-mobile-menu--visible');
            mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            // Cambia el icono (hamburguesa ↔ X)
            if (mobileIcon) {
                mobileIcon.setAttribute(
                    'd',
                    isOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'
                );
            }
        });

        // Cerrar el menú al hacer clic en un enlace
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('nt-mobile-menu--visible');
                mobileToggle.setAttribute('aria-expanded', 'false');
                if (mobileIcon) {
                    mobileIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
                }
            });
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
                mobileMenu.classList.remove('nt-mobile-menu--visible');
                mobileToggle.setAttribute('aria-expanded', 'false');
                if (mobileIcon) {
                    mobileIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
                }
            }
        });
    }

    /* --------------------------------------------------------------------------
       2. Botón "Cerrar Sesión" dentro del menú móvil (Portal Clientes / Dashboard)
       - Solo si existe, replicamos el comportamiento del botón principal
       -------------------------------------------------------------------------- */
    const btnLogoutMobile = document.getElementById('btn-logout-mobile');
    const btnLogout       = document.getElementById('btn-logout');

    if (btnLogoutMobile && btnLogout) {
        btnLogoutMobile.addEventListener('click', (e) => {
            // Cierra el menú móvil y dispara el clic del botón original
            if (mobileMenu) mobileMenu.classList.remove('nt-mobile-menu--visible');
            btnLogout.click();
        });
    }

    /* --------------------------------------------------------------------------
       3. Drawer del sidebar (solo Dashboard.php)
       -------------------------------------------------------------------------- */
    const sidebar         = document.getElementById('sidebar-clientes');
    const sidebarToggle   = document.getElementById('btn-sidebar-toggle');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');

    if (sidebar && sidebarToggle && sidebarBackdrop) {
        const openSidebar = () => {
            sidebar.classList.add('nt-sidebar--open');
            sidebarBackdrop.classList.add('nt-sidebar__backdrop--visible');
            sidebarToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        };

        const closeSidebar = () => {
            sidebar.classList.remove('nt-sidebar--open');
            sidebarBackdrop.classList.remove('nt-sidebar__backdrop--visible');
            sidebarToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        };

        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sidebar.classList.contains('nt-sidebar--open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        sidebarBackdrop.addEventListener('click', closeSidebar);

        // Cerrar el sidebar al seleccionar un cliente en móvil
        const clientesList = document.getElementById('clientes-list');
        if (clientesList) {
            clientesList.addEventListener('click', (e) => {
                if (e.target.closest('.cursor-pointer')) {
                    // Solo cerramos en móvil (< lg)
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                }
            });
        }
    }
});
