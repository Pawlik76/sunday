<?php
require __DIR__ . '/lib.php';
$bd = $_GET['bd'] ?? null;
$m = db();
try {
    if ($bd !== null) {
        ident($bd);
        if (in_array(strtolower($bd), SISTEMA, true)) throw new InvalidArgumentException('Base de datos del sistema: no disponible.');
        $m->select_db($bd);
    }
} catch (Throwable $e) { ir('panel.php', $e->getMessage(), 'error'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $a = $_POST['accion'] ?? '';
    try {
        if ($a === 'crear_bd' || $a === 'borrar_bd') {
            $n = trim((string)($_POST['nombre'] ?? '')); $q = ident($n);
            if (in_array(strtolower($n), SISTEMA, true)) throw new InvalidArgumentException('Nombre reservado.');
            if ($a === 'crear_bd') { $m->query("CREATE DATABASE $q CHARACTER SET utf8mb4"); ir('panel.php?bd=' . urlencode($n), "Base de datos «$n» creada."); }
            $m->query("DROP DATABASE $q"); ir('panel.php', "Base de datos «$n» eliminada.");
        }
        if ($bd !== null && $a === 'crear_tabla') {
            $n = trim((string)($_POST['nombre'] ?? '')); $q = ident($n);
            $defs = ['`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY'];
            foreach (preg_split('/\R/', trim((string)($_POST['columnas'] ?? ''))) as $l) {
                $l = trim($l); if ($l === '') continue;
                if (!preg_match('/^([A-Za-z0-9_]{1,64})\s+(INT|BIGINT|TEXT|DATE|DATETIME|TIMESTAMP|BOOLEAN|VARCHAR\(\d{1,4}\)|DECIMAL\(\d{1,2},\d{1,2}\))(\s+NOT\s+NULL)?$/i', $l, $x))
                    throw new InvalidArgumentException("Columna no válida: $l");
                $tipo = strtoupper($x[2]); $d = '`' . $x[1] . '` ' . $tipo;
                if ($tipo === 'TIMESTAMP') $d .= ' NULL DEFAULT CURRENT_TIMESTAMP'; elseif (!empty($x[3])) $d .= ' NOT NULL';
                $defs[] = $d;
            }
            $m->query("CREATE TABLE $q (" . implode(', ', $defs) . ") CHARACTER SET utf8mb4");
            ir('tabla.php?bd=' . urlencode($bd) . '&t=' . urlencode($n), "Tabla «$n» creada.");
        }
        if ($bd !== null && $a === 'borrar_tabla') {
            $n = (string)($_POST['nombre'] ?? ''); $m->query('DROP TABLE ' . ident($n));
            ir('panel.php?bd=' . urlencode($bd), "Tabla «$n» eliminada.");
        }
    } catch (Throwable $e) { ir($_SERVER['REQUEST_URI'], $e->getMessage(), 'error'); }
}

cabecera('Bases de datos');
$conf = "onsubmit=\"return confirm('¿Seguro? Esta acción no se puede deshacer.')\"";
if ($bd === null):
    $lista = array_filter(array_column($m->query('SHOW DATABASES')->fetch_all(), 0),
        fn($d) => !in_array(strtolower($d), SISTEMA, true) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $d));
?>
<h1>Bases de datos</h1>
<section class="card"><h2>Crear base de datos</h2>
<form method="post" class="fila"><?= csrf_campo() ?><input type="hidden" name="accion" value="crear_bd">
<input name="nombre" placeholder="nombre_de_la_base" required><button class="btn">Crear</button></form></section>
<section class="card"><h2>Existentes</h2>
<?php if (!$lista): ?><p class="nota">No hay bases de datos todavía.</p><?php endif; ?>
<ul class="lista"><?php foreach ($lista as $d): ?>
<li><a href="panel.php?bd=<?= urlencode($d) ?>"><?= h($d) ?></a>
<form method="post" <?= $conf ?>><?= csrf_campo() ?><input type="hidden" name="accion" value="borrar_bd"><input type="hidden" name="nombre" value="<?= h($d) ?>"><button class="btn peligro">Eliminar</button></form></li>
<?php endforeach; ?></ul></section>
<?php else:
    $tablas = array_filter(array_column($m->query('SHOW TABLES')->fetch_all(), 0), fn($t) => preg_match('/^[A-Za-z0-9_]{1,64}$/', $t));
?>
<p class="mig"><a href="panel.php">Bases</a> / <?= h($bd) ?></p>
<h1><?= h($bd) ?></h1>
<section class="card"><h2>Tablas</h2>
<?php if (!$tablas): ?><p class="nota">Esta base aún no tiene tablas.</p><?php endif; ?>
<ul class="lista"><?php foreach ($tablas as $t): ?>
<li><a href="tabla.php?bd=<?= urlencode($bd) ?>&t=<?= urlencode($t) ?>"><?= h($t) ?></a>
<form method="post" <?= $conf ?>><?= csrf_campo() ?><input type="hidden" name="accion" value="borrar_tabla"><input type="hidden" name="nombre" value="<?= h($t) ?>"><button class="btn peligro">Eliminar</button></form></li>
<?php endforeach; ?></ul></section>
<section class="card"><h2>Crear tabla</h2>
<form method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="crear_tabla">
<label>Nombre de la tabla <input name="nombre" required></label>
<label>Columnas (una por línea: nombre tipo). Se agrega <code>id</code> automático.
<textarea name="columnas" rows="7">nombre VARCHAR(30) NOT NULL
apellido VARCHAR(30) NOT NULL
email VARCHAR(50)
edad INT
sexo VARCHAR(20)
reg_date TIMESTAMP</textarea></label>
<p class="nota">Tipos: INT, BIGINT, VARCHAR(n), TEXT, DATE, DATETIME, TIMESTAMP, BOOLEAN, DECIMAL(p,s)</p>
<button class="btn">Crear tabla</button></form></section>
<?php endif; pie();
