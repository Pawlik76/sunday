<?php
require __DIR__ . '/lib.php';
$bd = (string)($_GET['bd'] ?? ''); $t = (string)($_GET['t'] ?? '');
try {
    ident($bd); $qt = ident($t);
    if (in_array(strtolower($bd), SISTEMA, true)) throw new InvalidArgumentException('Base de datos del sistema: no disponible.');
    $m = db($bd);
    $cols = $m->query("SHOW COLUMNS FROM $qt")->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) { ir('panel.php', $e->getMessage(), 'error'); }

$pk = [];
foreach ($cols as $c) if ($c['Key'] === 'PRI') $pk[] = $c['Field'];
$volver = 'tabla.php?bd=' . urlencode($bd) . '&t=' . urlencode($t);
$auto = fn($c) => stripos($c['Extra'], 'auto_increment') !== false;

function donde(array $pk, array $vals): array {
    $p = []; $v = [];
    foreach ($pk as $c) { $p[] = col($c) . ' <=> ?'; $v[] = isset($vals[$c]) && is_string($vals[$c]) ? $vals[$c] : null; }
    return [implode(' AND ', $p), $v];
}
function ejecutar(mysqli $m, string $sql, array $v): mysqli_stmt {
    $s = $m->prepare($sql);
    if ($v) $s->bind_param(str_repeat('s', count($v)), ...$v);
    $s->execute(); return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_post();
    $a = $_POST['accion'] ?? '';
    $v = is_array($_POST['v'] ?? null) ? $_POST['v'] : [];
    $k = is_array($_POST['pk'] ?? null) ? $_POST['pk'] : [];
    try {
        if ($a === 'insertar') {
            $n = []; $val = [];
            foreach ($cols as $c) {
                if ($auto($c)) continue;
                $x = $v[$c['Field']] ?? '';
                if (!is_string($x) || $x === '') continue;   // vacío = valor por defecto de la BD
                $n[] = col($c['Field']); $val[] = $x;
            }
            $sql = $n ? "INSERT INTO $qt (" . implode(',', $n) . ') VALUES (' . implode(',', array_fill(0, count($n), '?')) . ')'
                      : "INSERT INTO $qt () VALUES ()";
            ejecutar($m, $sql, $val); ir($volver, 'Registro insertado.');
        }
        if ($a === 'actualizar' || $a === 'borrar') {
            if (!$pk) throw new InvalidArgumentException('La tabla no tiene clave primaria: es de solo lectura.');
            [$w, $wv] = donde($pk, $k);
            if ($a === 'borrar') { ejecutar($m, "DELETE FROM $qt WHERE $w LIMIT 1", $wv); ir($volver, 'Registro eliminado.'); }
            $s = []; $sv = [];
            foreach ($cols as $c) {
                if ($auto($c)) continue;
                $x = $v[$c['Field']] ?? null; if (!is_string($x)) continue;
                $s[] = col($c['Field']) . '=?'; $sv[] = ($x === '' && $c['Null'] === 'YES') ? null : $x;
            }
            if (!$s) throw new InvalidArgumentException('No hay campos que actualizar.');
            ejecutar($m, "UPDATE $qt SET " . implode(',', $s) . " WHERE $w LIMIT 1", array_merge($sv, $wv));
            ir($volver, 'Registro actualizado.');
        }
    } catch (Throwable $e) { ir($volver, $e->getMessage(), 'error'); }
}

$fila = null;
if ($pk && isset($_GET['edit']) && is_array($_GET['edit'])) {
    [$w, $wv] = donde($pk, $_GET['edit']);
    $fila = ejecutar($m, "SELECT * FROM $qt WHERE $w LIMIT 1", $wv)->get_result()->fetch_assoc();
}
$por = 50; $p = max(1, (int)($_GET['p'] ?? 1));
$total = (int)$m->query("SELECT COUNT(*) FROM $qt")->fetch_row()[0];
$orden = $pk ? ' ORDER BY ' . col($pk[0]) : '';
$res = $m->query("SELECT * FROM $qt$orden LIMIT $por OFFSET " . (($p - 1) * $por));

cabecera($t);
?>
<p class="mig"><a href="panel.php">Bases</a> / <a href="panel.php?bd=<?= urlencode($bd) ?>"><?= h($bd) ?></a> / <?= h($t) ?></p>
<h1><?= h($t) ?> <small>(<?= $total ?> registros)</small></h1>

<section class="card"><h2><?= $fila ? 'Editar registro' : 'Insertar registro' ?></h2>
<form method="post" action="<?= h($volver) ?>"><?= csrf_campo() ?>
<input type="hidden" name="accion" value="<?= $fila ? 'actualizar' : 'insertar' ?>">
<?php if ($fila) foreach ($pk as $c): ?><input type="hidden" name="pk[<?= h($c) ?>]" value="<?= h($fila[$c]) ?>"><?php endforeach; ?>
<div class="grid">
<?php foreach ($cols as $c): if ($auto($c)) continue; $f = $c['Field']; ?>
<label><?= h($f) ?> <small><?= h($c['Type']) ?></small>
<?php if (preg_match('/text|blob/i', $c['Type'])): ?><textarea name="v[<?= h($f) ?>]" rows="2"><?= h($fila[$f] ?? '') ?></textarea>
<?php else: ?><input name="v[<?= h($f) ?>]" value="<?= h($fila[$f] ?? '') ?>"><?php endif; ?></label>
<?php endforeach; ?>
</div>
<button class="btn"><?= $fila ? 'Guardar cambios' : 'Insertar' ?></button>
<?php if ($fila): ?><a class="btn sec" href="<?= h($volver) ?>">Cancelar</a><?php endif; ?>
</form></section>

<?php if (!$pk): ?><p class="err">Esta tabla no tiene clave primaria: no se puede editar ni eliminar, solo insertar y ver.</p><?php endif; ?>
<div class="tabla"><table><thead><tr>
<?php foreach ($cols as $c): ?><th><?= h($c['Field']) ?></th><?php endforeach; ?><?php if ($pk): ?><th>Acciones</th><?php endif; ?>
</tr></thead><tbody>
<?php while ($r = $res->fetch_assoc()): ?><tr>
<?php foreach ($cols as $c): $x = $r[$c['Field']]; ?>
<td><?= $x === null ? '<em>NULL</em>' : h(mb_strimwidth((string)$x, 0, 200, '…')) ?></td>
<?php endforeach; ?>
<?php if ($pk): $pv = []; foreach ($pk as $c) $pv[$c] = $r[$c]; ?>
<td class="acc"><a class="btn sec" href="<?= h($volver . '&' . http_build_query(['edit' => $pv])) ?>">Editar</a>
<form method="post" action="<?= h($volver) ?>" onsubmit="return confirm('¿Eliminar este registro?')"><?= csrf_campo() ?>
<input type="hidden" name="accion" value="borrar">
<?php foreach ($pk as $c): ?><input type="hidden" name="pk[<?= h($c) ?>]" value="<?= h($r[$c]) ?>"><?php endforeach; ?>
<button class="btn peligro">Eliminar</button></form></td>
<?php endif; ?>
</tr><?php endwhile; ?>
</tbody></table></div>
<p class="pag">
<?php if ($p > 1): ?><a class="btn sec" href="<?= h($volver . '&p=' . ($p - 1)) ?>">← Anterior</a><?php endif; ?>
<?php if ($p * $por < $total): ?><a class="btn sec" href="<?= h($volver . '&p=' . ($p + 1)) ?>">Siguiente →</a><?php endif; ?>
</p>
<?php pie();
