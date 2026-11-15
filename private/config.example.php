<?php
// Copiar fuera del directorio público: ../mosaico-private/config.php
return [
 'host'=>'localhost',
 'database'=>'soyservi_mosaico01',
 'user'=>'USUARIO_MYSQL',
 'password'=>'CONTRASENA_MYSQL',
 'admin_user'=>'administrador',
 // Generar con PHP: password_hash('tu contraseña', PASSWORD_DEFAULT)
 'admin_hash'=>'REEMPLAZAR_CON_HASH_GENERADO',
];
