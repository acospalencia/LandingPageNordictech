/* ==========================================================================
   nav.js — Correcto manejo de capas Z-Index y eventos de toque en móvil
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {

    /* 1. Control del Menú Superior Hamburguesa (Navegación General) */
    const mobileToggle = document.getElementById('mobile-toggle');
    const mobileMenu   = document.getElementById('mobile-menu');
    const mobileIcon   = document.getElementById('mobile-toggle-icon');

    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = mobileMenu.classList.toggle('nt-mobile-menu--visible');
            mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (mobileIcon) {
                mobileIcon.setAttribute(
                    'd',
                    isOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'
                );
            }
        });

        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
                mobileMenu.classList.remove('nt-mobile-menu--visible');
                if (mobileToggle) mobileToggle.setAttribute('aria-expanded', 'false');
                if (mobileIcon) mobileIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
            }
        });
    }

    /* 2. Control del Sidebar Móvil de Clientes */
    const btnSidebarToggle = document.getElementById('btn-sidebar-toggle');
    const sidebarClientes  = document.getElementById('sidebar-clientes');
    const sidebarBackdrop  = document.getElementById('sidebar-backdrop');

    if (btnSidebarToggle && sidebarClientes) {
        
        // Abrir / Cerrar Sidebar
        btnSidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const isOpen = sidebarClientes.classList.contains('translate-x-0') || 
                           sidebarClientes.classList.contains('nt-sidebar--open');

            if (isOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        function openSidebar() {
            sidebarClientes.classList.remove('-translate-x-full');
            sidebarClientes.classList.add('translate-x-0', 'nt-sidebar--open');
            
            if (sidebarBackdrop) {
                sidebarBackdrop.classList.remove('hidden');
                sidebarBackdrop.style.setProperty('display', 'block', 'important');
                sidebarBackdrop.style.setProperty('z-index', '40', 'important');
            }
            
            // Forzar z-index superior para el panel de clientes
            sidebarClientes.style.setProperty('z-index', '50', 'important');
        }

        function closeSidebar() {
            sidebarClientes.classList.remove('translate-x-0', 'nt-sidebar--open');
            sidebarClientes.classList.add('-translate-x-full');
            
            if (sidebarBackdrop) {
                sidebarBackdrop.classList.add('hidden');
                sidebarBackdrop.style.setProperty('display', 'none', 'important');
            }
        }

        // EVITAR que los clics DENTRO del menú de clientes cierren el panel
        sidebarClientes.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Solo cerrar cuando se toque explícitamente el Backdrop fuera del panel
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', (e) => {
                e.stopPropagation();
                closeSidebar();
            });
        }
    }
});