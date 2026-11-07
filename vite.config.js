import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],

    // Define el entorno de ejecución de React en el navegador.
    define: {
        'process.env.NODE_ENV': JSON.stringify('production')
    },

    build: {
        lib: {
            // Punto de entrada del módulo de Ventas.
            entry: 'react/main.jsx',

            // Nombre global de la biblioteca.
            name: 'ConfypimVentas',

            // Nombre base del archivo generado.
            fileName: 'ventas',

            // Formato compatible con la carga mediante una etiqueta script.
            formats: ['iife']
        },

        // Directorio donde se guardará el código compilado.
        outDir: 'assets/react/ventas',

        // Limpia los archivos anteriores antes de compilar.
        emptyOutDir: true
    }
});