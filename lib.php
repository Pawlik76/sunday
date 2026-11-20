<?php
// Funciones compartidas. NO contiene credenciales: se piden en conectar.php
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
const SISTEMA = ['information_schema', 'mysql', 'performance_schema', 'sys'];

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_campo(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function exigir_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400); die('Solicitud no válida.');
    }
}
// Nombre de BD/tabla ingresado por el usuario: solo letras, números y _
function ident(string $n): string {
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $n)) {
        throw new InvalidArgumentException('Nombre no válido: usa solo letras, números y guion bajo (máx. 64).');
    }
    return '`' . $n . '`';
}
// Nombre de columna que viene del propio servidor
function col(string $n): string { return '`' . str_replace('`', '``', $n) . '`'; }
function ir(string $url, string $msg = '', string $tipo = 'msg'): void {
    if ($msg !== '') $_SESSION[$tipo] = $msg;
    header('Location: ' . $url); exit;
}
function db(?string $bd = null): mysqli {
    if (empty($_SESSION['c'])) ir('conectar.php');
    $c = $_SESSION['c'];
    $m = mysqli_init();
    $m->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    try {
        $m->real_connect($c['host'], $c['user'], $c['pass'], null, $c['port']);
    } catch (mysqli_sql_exception $e) {
        unset($_SESSION['c']);
        ir('conectar.php', 'No se pudo conectar: ' . $e->getMessage(), 'error');
    }
    $m->set_charset('utf8mb4');
    if ($bd !== null) $m->select_db($bd);
    return $m;
}
function cabecera(string $titulo): void { ?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($titulo) ?> - DataSync Sunday</title><link rel="stylesheet" href="app.css"></head>
<body>
<header><div class="b1"></div><div class="b2"></div><div class="b3"></div></header>
<nav class="nav"><a href="Inicio.html">Inicio</a>
<?php if (!empty($_SESSION['c'])): ?>
<a href="panel.php">Bases de datos</a><span class="sp"></span>
<span class="quien"><?= h($_SESSION['c']['user'] . '@' . $_SESSION['c']['host']) ?></span>
<form method="post" action="conectar.php"><?= csrf_campo() ?><input type="hidden" name="accion" value="salir"><button class="btn sec">Desconectar</button></form>
<?php endif; ?></nav>
<main class="wrap">
<?php
    foreach (['error' => 'err', 'msg' => 'ok'] as $k => $cl) {
        if (!empty($_SESSION[$k])) { echo '<p class="' . $cl . '">' . h($_SESSION[$k]) . '</p>'; unset($_SESSION[$k]); }
    }
}
function pie(): void { echo '</main></body></html>'; }
