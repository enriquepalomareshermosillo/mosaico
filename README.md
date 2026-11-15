# Mosaico SOYSERVICIOS

Angular + API PHP 8.1 o superior + MySQL. Vista pública de accesos y panel de administración con sesión, protección CSRF, límite de intentos y carga de imágenes.

## Local

Instala Node.js 22.12 o superior compatible con Angular 21. Ejecuta `npm install` y `npm start`. Abre http://127.0.0.1:4200. Sin PHP muestra cuatro tarjetas de ejemplo claramente marcadas; estas no se guardan en MySQL. Administración necesita la API real y no simula guardados.

Para probar la integración local: compila con `npm run build`, configura una base MySQL local, importa `database/schema.sql` y prepara el archivo privado. Con PHP disponible, define MOSAICO_CONFIG con la ruta absoluta al archivo y sirve `dist/mosaico/browser` con PHP. No uses el servidor integrado de PHP en producción.

## HostGator

1. Confirma en cPanel la raíz real de mosaico.soyservicios.com y activa HTTPS. Respalda archivos existentes y la base antes de cargar.
2. En phpMyAdmin selecciona `soyservi_mosaico01` e importa `database/schema.sql`. Solo añade tablas con prefijo mosaic_; no requiere borrar información.
3. Crea `mosaico-private` como carpeta hermana de la raíz pública del subdominio. Copia allí `private/config.example.php` con nombre `config.php`. Si la estructura difiere, ajusta la ruta de configuración en `public/api/index.php` antes de compilar. El archivo nunca debe estar accesible por URL.
4. Completa los datos MySQL y genera un hash de contraseña con `password_hash` en PHP; pega el hash en `admin_hash`. No publiques contraseñas ni subas el ejemplo sin configurar como credencial real.
5. Ejecuta `npm run build`. Sube **el contenido** de `dist/mosaico/browser/` a la raíz del subdominio, incluidos `.htaccess`, `api/` y `uploads/.htaccess`. No subas node_modules ni private.
6. Comprueba que PHP tenga PDO MySQL y fileinfo. Da permiso de escritura a uploads con permisos mínimos que permita el hosting, normalmente 755 (sin usar 777). Verifica que el límite PHP de subida admita 3 MB.
7. Visita `/api/index.php?action=tiles`: debe devolver JSON. Entra por Administración y prueba crear, editar, buscar y eliminar un acceso. Comprueba que el destino abra una pestaña nueva y que la imagen se vea en móvil.

Los archivos de imagen antiguos se conservan al reemplazar o eliminar tarjetas para evitar borrar recursos por accidente; se pueden depurar manualmente. No hay aún recuperación de contraseña ni múltiples administradores. La API no se ha validado en HostGator hasta disponer de acceso al hosting.
