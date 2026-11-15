<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','samesite'=>'Strict','path'=>'/']);
session_start();
function respond(array $data, int $status=200): never {http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;}
function token(): string {return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));}
$action = $_GET['action'] ?? 'tiles';
try {
    // Must remain outside the domain's public document root.
    $path = getenv('MOSAICO_CONFIG') ?: dirname(__DIR__, 2) . '/mosaico-private/config.php';
    if (!is_file($path)) respond(['error'=>'CONFIG_NOT_FOUND: No se encontró el archivo de configuración. Ruta buscada: '.$path],503);
    if (!is_readable($path)) respond(['error'=>'CONFIG_NOT_READABLE: El archivo existe pero PHP no tiene permiso de lectura.'],503);
    $config = require $path;
    if (!is_array($config)) respond(['error'=>'CONFIG_INVALID: config.php debe devolver un arreglo PHP.'],503);
    foreach (['host','database','user','password','admin_user','admin_hash'] as $key) {
        if (!isset($config[$key]) || !is_string($config[$key])) respond(['error'=>'CONFIG_INVALID: Falta el campo '.$key.' o su formato es incorrecto.'],503);
    }
    if (!extension_loaded('pdo_mysql')) respond(['error'=>'DRIVER_MISSING: PHP no tiene habilitada la extensión pdo_mysql.'],503);
    $db = new PDO('mysql:host='.$config['host'].';dbname='.$config['database'].';charset=utf8mb4', $config['user'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $settings=$db->query('SELECT theme,admin_hash,auth_version FROM mosaic_settings WHERE id=1')->fetch();
    if (!$settings) respond(['error'=>'Falta la configuración inicial. Importa migracion-disenos.sql.'],503);
    $adminHash=$settings['admin_hash'] ?: $config['admin_hash'];
    if (!empty($_SESSION['admin']) && (int)($_SESSION['auth_version'] ?? 0)!==(int)$settings['auth_version']) {unset($_SESSION['admin']);}
    if ($action === 'session') respond(['authenticated'=>!empty($_SESSION['admin']), 'csrf'=>token()]);
    if ($action === 'settings' && $_SERVER['REQUEST_METHOD']==='GET') respond(['theme'=>$settings['theme']]);
    if ($action === 'diagnostics' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $db->query('SELECT id,title,url,image,category,position FROM mosaic_links LIMIT 0');
        $db->query('SELECT ip_hash,attempted_at FROM mosaic_login_attempts LIMIT 0');
        respond(['message'=>'Conexión MySQL correcta. Archivo privado legible y tablas disponibles.']);
    }
    if ($action === 'tiles' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows=$db->query('SELECT id,title,url,image,category,position FROM mosaic_links ORDER BY position,id DESC')->fetchAll();
        foreach($rows as &$row){$row['id']=(int)$row['id'];$row['position']=(int)$row['position'];}
        respond(['tiles'=>$rows]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['error'=>'Método no permitido.'],405);
    if (!hash_equals(token(), $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) respond(['error'=>'Sesión vencida. Vuelve a ingresar.'],403);
    if ($action === 'login') {
        $ip=hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $s=$db->prepare('SELECT COUNT(*) FROM mosaic_login_attempts WHERE ip_hash=? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');$s->execute([$ip]);
        if ((int)$s->fetchColumn() >= 10) respond(['error'=>'Demasiados intentos. Espera 15 minutos.'],429);
        $s=$db->prepare('INSERT INTO mosaic_login_attempts (ip_hash) VALUES (?)');$s->execute([$ip]);
        if (!hash_equals($config['admin_user'], (string)($_POST['username'] ?? '')) || !password_verify((string)($_POST['password'] ?? ''),$adminHash)) respond(['error'=>'Usuario o contraseña incorrectos.'],401);
        session_regenerate_id(true); $_SESSION['admin']=true; $_SESSION['csrf']=bin2hex(random_bytes(32));
        $_SESSION['auth_version']=(int)$settings['auth_version'];
        $s=$db->prepare('DELETE FROM mosaic_login_attempts WHERE ip_hash=? OR attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');$s->execute([$ip]);
        respond(['csrf'=>token()]);
    }
    if (empty($_SESSION['admin'])) respond(['error'=>'Inicia sesión para continuar.'],401);
    if ($action === 'theme') {
        $theme=(string)($_POST['theme'] ?? '');
        if (!in_array($theme,['nocturno','orbita','cristal','claro'],true)) respond(['error'=>'Diseño no válido.'],422);
        $s=$db->prepare('UPDATE mosaic_settings SET theme=? WHERE id=1');$s->execute([$theme]);respond(['ok'=>true]);
    }
    if ($action === 'password') {
        $ip=hash('sha256',$_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $s=$db->prepare('SELECT COUNT(*) FROM mosaic_login_attempts WHERE ip_hash=? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');$s->execute([$ip]);
        if((int)$s->fetchColumn()>=10)respond(['error'=>'Demasiados intentos. Espera 15 minutos.'],429);
        $s=$db->prepare('INSERT INTO mosaic_login_attempts(ip_hash) VALUES(?)');$s->execute([$ip]);
        if (!password_verify((string)($_POST['current'] ?? ''),$adminHash)) respond(['error'=>'La contraseña actual es incorrecta.'],422);
        $new=(string)($_POST['new'] ?? '');
        if (strlen($new)<12 || strlen($new)>72) respond(['error'=>'Usa entre 12 y 72 bytes para la nueva contraseña.'],422);
        if ($new!==(string)($_POST['confirmation'] ?? '')) respond(['error'=>'La confirmación no coincide.'],422);
        if (password_verify($new,$adminHash)) respond(['error'=>'Elige una contraseña diferente a la actual.'],422);
        $s=$db->prepare('UPDATE mosaic_settings SET admin_hash=?,auth_version=auth_version+1 WHERE id=1 AND auth_version=?');
        $s->execute([password_hash($new,PASSWORD_BCRYPT),(int)$settings['auth_version']]);
        if(!$s->rowCount())respond(['error'=>'La contraseña fue modificada en otra sesión. Vuelve a ingresar.'],409);
        session_regenerate_id(true);$_SESSION['auth_version']=(int)$settings['auth_version']+1;$_SESSION['csrf']=bin2hex(random_bytes(32));
        respond(['ok'=>true,'csrf'=>token()]);
    }
    if ($action === 'logout') {$_SESSION=[];session_destroy();respond(['ok'=>true]);}
    if ($action === 'delete') {$s=$db->prepare('DELETE FROM mosaic_links WHERE id=?');$s->execute([(int)($_POST['id'] ?? 0)]);respond(['ok'=>true]);}
    if ($action !== 'save') respond(['error'=>'Acción no encontrada.'],404);
    $title=trim((string)($_POST['title'] ?? ''));$url=trim((string)($_POST['url'] ?? ''));$category=trim((string)($_POST['category'] ?? 'General'));
    if ($title==='' || strlen($title)>480 || $category==='' || strlen($category)>240 || strlen($url)>2048 || !filter_var($url,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)) respond(['error'=>'Revisa el título, la categoría y una URL http o https válida.'],422);
    $id=(int)($_POST['id'] ?? 0);$image='';
    if ($id) {$s=$db->prepare('SELECT image FROM mosaic_links WHERE id=?');$s->execute([$id]);$old=$s->fetch();if(!$old)respond(['error'=>'El acceso ya no existe.'],404);$image=$old['image'];}
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f=$_FILES['image'];
        if ($f['error']!==UPLOAD_ERR_OK || $f['size']>3*1024*1024 || !getimagesize($f['tmp_name'])) respond(['error'=>'Usa una imagen válida de hasta 3 MB.'],422);
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($extensions[$mime])) respond(['error'=>'Solo se permiten imágenes JPG, PNG o WebP.'],422);
        $dir=dirname(__DIR__).'/uploads';if(!is_dir($dir))mkdir($dir,0755,true);
        $image='/uploads/'.bin2hex(random_bytes(20)).'.'.$extensions[$mime];
        if(!move_uploaded_file($f['tmp_name'],dirname(__DIR__).$image)) throw new RuntimeException('Upload failed');
    }
    $position=max(0,(int)($_POST['position'] ?? 0));
    if($id){$s=$db->prepare('UPDATE mosaic_links SET title=?,url=?,image=?,category=?,position=? WHERE id=?');$s->execute([$title,$url,$image,$category,$position,$id]);}
    else{$s=$db->prepare('INSERT INTO mosaic_links(title,url,image,category,position) VALUES(?,?,?,?,?)');$s->execute([$title,$url,$image,$category,$position]);}
    respond(['ok'=>true]);
} catch(Throwable $e) {
    $reference=bin2hex(random_bytes(6));
    error_log('Mosaico ['.$reference.']: '.$e->getMessage());
    if ($e instanceof PDOException) {
        $code=(int)($e->errorInfo[1] ?? 0);
        $reasons=[1045=>'MySQL rechazó el usuario o la contraseña.',1044=>'El usuario no tiene permiso para acceder a la base de datos.',1049=>'La base de datos indicada no existe.',1146=>'Falta una tabla: importa database/schema.sql.',1054=>'La estructura de las tablas no coincide con el esquema.',2002=>'No fue posible contactar al servidor MySQL. Revisa el host.',2005=>'No se pudo resolver el nombre del servidor MySQL.'];
        respond(['error'=>'MYSQL_'.$code.' (SQLSTATE '.$e->getCode().'): '.($reasons[$code] ?? 'Error de MySQL. Consulta el registro PHP.').' Referencia: '.$reference],500);
    }
    respond(['error'=>'SERVER_ERROR: Error de configuración o ejecución PHP. Consulta el registro PHP con referencia '.$reference],500);
}
