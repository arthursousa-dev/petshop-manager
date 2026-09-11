<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();

$pdo = \App\Config\Database::getConnection();

$totalEntradas = (float) ($pdo->query("SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE tipo = 'entrada'")->fetchColumn());
$totalSaidas   = (float) ($pdo->query("SELECT COALESCE(SUM(valor),0) FROM financeiro_lancamentos WHERE tipo = 'saida'")->fetchColumn());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $stmt = $pdo->prepare(
        'INSERT INTO financeiro_lancamentos (tipo, descricao, categoria, valor, data)
         VALUES (:tipo, :descricao, :categoria, :valor, :data)'
    );
    $stmt->execute([
        ':tipo' => $_POST['tipo'], ':descricao' => $_POST['descricao'],
        ':categoria' => $_POST['categoria'], ':valor' => (float) $_POST['valor'], ':data' => $_POST['data'],
    ]);
    header('Location: financeiro.php');
    exit;
}

$dados = $pdo->query(
    "SELECT id, to_char(data,'YYYY-MM-DD') AS data, tipo, descricao, categoria, valor
     FROM financeiro_lancamentos ORDER BY data DESC, id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Financeiro</title>
<link rel="stylesheet" href="style.css">
<style>
.financeiro-wrap{max-width:1200px;margin:auto;padding:20px}
.financeiro-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;margin-bottom:20px}
.fin-card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 3px 10px rgba(0,0,0,.08)}
.fin-card h3{margin:0 0 8px 0;font-size:14px}
.fin-card .valor{font-size:28px;font-weight:bold}
.fin-form,.fin-table{background:#fff;border-radius:14px;padding:20px;box-shadow:0 3px 10px rgba(0,0,0,.08);margin-top:20px}
.fin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px}
.fin-form input,.fin-form select{padding:10px;border:1px solid #ddd;border-radius:8px}
.fin-form button{padding:12px;border:none;border-radius:8px;cursor:pointer}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
</style>
</head>
<body>
<?php if(file_exists('navbar.php')) include 'navbar.php'; ?>
<div class="financeiro-wrap">
<h1>💰 Gestão Financeira</h1>

<div class="financeiro-cards">
<div class="fin-card"><h3>Entradas</h3><div class="valor">R$ <?=number_format($totalEntradas,2,',','.')?></div></div>
<div class="fin-card"><h3>Saídas</h3><div class="valor">R$ <?=number_format($totalSaidas,2,',','.')?></div></div>
<div class="fin-card"><h3>Lucro Líquido</h3><div class="valor">R$ <?=number_format($totalEntradas-$totalSaidas,2,',','.')?></div></div>
</div>

<div class="fin-form">
<h2>Novo Lançamento</h2>
<form method="post">
            <?= csrf_campo() ?>
<div class="fin-grid">
<select name="tipo">
<option value="entrada">Entrada</option>
<option value="saida">Saída</option>
</select>
<input name="descricao" placeholder="Descrição" required>
<input name="categoria" placeholder="Categoria">
<input type="number" step="0.01" name="valor" placeholder="Valor" required>
<input type="date" name="data" required>
</div>
<br>
<button type="submit">Salvar Lançamento</button>
</form>
</div>

<div class="fin-table">
<h2>Movimentações</h2>
<table>
<tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Categoria</th><th>Valor</th></tr>
<?php foreach($dados as $r): ?>
<tr>
<td><?=$r['data']?></td>
<td><?=$r['tipo']?></td>
<td><?=$r['descricao']?></td>
<td><?=$r['categoria']?></td>
<td>R$ <?=number_format($r['valor'],2,',','.')?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
</div>
</body>
</html>