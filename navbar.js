document.addEventListener("DOMContentLoaded", function () {
    const navbarContainer = document.getElementById("navbar-container");

    // HTML de la Barra de Navegación
    // Nota: Usamos rutas absolutas (index.html#seccion) para que funcionen desde cualquier archivo
    const navbarHTML = `
    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md shadow-sm transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo Area -->
                <a href="index.html" class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
                    <img src="https://i.imgur.com/92XM6XB.png" alt="neuroLux Logo" class="h-12 w-auto hover:scale-105 transition-transform">
                    <span class="font-bold text-2xl tracking-tight text-neuro-dark">neuro<span class="text-neuro-blue">Lux</span></span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="index.html#inicio" class="text-gray-600 hover:text-neuro-blue font-medium transition-colors">Inicio</a>
                    <a href="index.html#nosotros" class="text-gray-600 hover:text-neuro-purple font-medium transition-colors">¿Qué es?</a>
                    <a href="index.html#modulos" class="text-gray-600 hover:text-neuro-red font-medium transition-colors">Módulos</a>
                    
                    <!-- Enlace especial al Foro -->
                    <a href="foro.html" class="text-neuro-blue font-bold hover:text-neuro-dark transition-colors border-b-2 border-transparent hover:border-neuro-blue">Foro</a>
                    
                    <a href="index.html#contacto" class="bg-neuro-blue text-white px-5 py-2.5 rounded-full font-medium hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                        Contáctanos
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-600 hover:text-neuro-blue focus:outline-none">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Panel -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 shadow-lg">
                <a href="index.html#inicio" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Inicio</a>
                <a href="index.html#nosotros" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">¿Qué es?</a>
                <a href="index.html#modulos" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Módulos</a>
                <a href="foro.html" class="block px-3 py-2 rounded-md text-base font-medium text-neuro-blue font-bold hover:bg-gray-50">Foro</a>
                <a href="index.html#contacto" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Contacto</a>
            </div>
        </div>
    </nav>
    `;

    // Insertar el HTML
    navbarContainer.innerHTML = navbarHTML;

    // Lógica del Menú Móvil
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');

    if (btn && menu) {
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    }

    // Lógica de cambio de fondo al hacer scroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 10) {
            navbar.classList.add('shadow-md');
            navbar.classList.replace('bg-white/90', 'bg-white/95');
        } else {
            navbar.classList.remove('shadow-md');
            navbar.classList.replace('bg-white/95', 'bg-white/90');
        }
    });
});