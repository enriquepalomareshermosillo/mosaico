# Publicación en mosaico.soyservicios.com

La raíz indicada en cPanel es `/mosaico.soyservicios.com`. Normalmente es relativa a la carpeta de la cuenta; no es necesario conocer el prefijo `/home/...` si se conserva esta estructura:

```text
Carpeta de la cuenta/
├── mosaico.soyservicios.com/  ← extraer mosaico-publico.zip aquí
│   ├── index.html
│   ├── .htaccess
│   ├── api/index.php
│   └── uploads/.htaccess
└── mosaico-private/          ← fuera de la raíz pública
    └── config.php           ← archivo local private/config.php
```

1. En Administrador de archivos de cPanel, abre la carpeta del subdominio. Respalda su contenido existente si lo hay.
2. Sube `release/mosaico-publico.zip` y extráelo dentro de esa carpeta. `index.html` debe quedar directamente en la raíz del subdominio. Elimina el ZIP del servidor después de extraerlo.
3. Un nivel arriba de esa carpeta, crea `mosaico-private`. Sube allí el archivo local `private/config.php` como `config.php`. No lo coloques dentro de la raíz del subdominio ni dentro de public_html. Confirma que esa carpeta tampoco sea raíz de otro dominio.
4. En phpMyAdmin selecciona `soyservi_mosaico01` e importa `database/schema.sql`.
5. Con HTTPS activo, abre `https://mosaico.soyservicios.com/api/index.php?action=tiles`. Si la configuración y las tablas funcionan, la respuesta inicial será `{"tiles":[]}`.

La API ya busca la configuración en esa carpeta hermana. `localhost` sigue pendiente de confirmación como servidor MySQL. El acceso administrativo requiere configurar `admin_hash` con un hash generado por PHP antes de poder iniciar sesión; la contraseña de MySQL no es una contraseña administrativa.

El ZIP público no contiene credenciales ni el archivo SQL. La publicación y la conexión real aún no se han ejecutado.
