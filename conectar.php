<?php
require __DIR__ . '/lib.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    if (($_POST['accion'] ?? '') === 'salir') { unset($_SESSION['c']); ir('conectar.php', 'Sesión cerrada.'); }
    $c = ['host' => trim($_POST['host'] ?? ''), 'port' => (int)($_POST['port'] ?? 3306) ?: 3306,
          'user' => trim($_POST['user'] ?? ''), 'pass' => (string)($_POST['pass'] ?? '')];
    // Opcional: DB_ALLOWED_HOSTS="host1,host2" limita a qué servidores se puede conectar
    $ok = array_filter(array_map('trim', explode(',', (string)getenv('DB_ALLOWED_HOSTS'))));
    if ($c['host'] === '' || $c['user'] === '') ir('conectar.php', 'Indica servidor y usuario.', 'error');
    if ($ok && !in_array($c['host'], $ok, true)) ir('conectar.php', 'Servidor no permitido.', 'error');
    try {
        $t = mysqli_init(); $t->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $t->real_connect($c['host'], $c['user'], $c['pass'], null, $c['port']); $t->close();
    } catch (mysqli_sql_exception $e) {
        ir('conectar.php', 'No se pudo conectar: ' . $e->getMessage(), 'error');
    }
    session_regenerate_id(true);
    $_SESSION['c'] = $c;
    ir('panel.php');
}
cabecera('Conexión');
?>
<section class="card chico">
<h1>Conectar a MySQL / MariaDB</h1>
<p class="nota">Tus datos se usan solo en esta sesión; no se guardan en archivos.</p>
<form method="post"><?= csrf_campo() ?>
<label>Servidor <input name="host" value="<?= h(getenv('DB_HOST') ?: 'localhost') ?>" required></label>
<label>Puerto <input name="port" type="number" value="3306"></label>
<label>Usuario <input name="user" autocomplete="username" required></label>
<label>Contraseña <input name="pass" type="password" autocomplete="current-password"></label>
<button class="btn">Conectar</button>
</form></section>
<?php pie();
