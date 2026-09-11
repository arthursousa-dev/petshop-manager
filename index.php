<?php
require_once __DIR__ . '/bootstrap_sessao.php';
if (!empty($_GET['_db_fresh'])) { $_SESSION = []; session_regenerate_id(true); }
require 'functions.php';
verificar_sessao();

$usuario = $_SESSION['usuario'];
$perfil = $_SESSION['perfil'];

$agendamentos = ler_json('agendamentos.json');
$pets = ler_json('pets.json');
$clientes = ler_json('clientes.json');
$servicos = ler_json('servicos.json');

$hoje = date('Y-m-d');
$mes = date('Y-m');

$financeiro = ler_json('financeiro.json');
$entradas_mes=0; $saidas_mes=0;
foreach($financeiro as $f){
 if(substr($f['data'],0,7)===$mes){
   if($f['tipo']==='entrada') $entradas_mes += floatval($f['valor']);
   if($f['tipo']==='saida') $saidas_mes += floatval($f['valor']);
 }
}
$receita_mes += $entradas_mes;
$lucro_mes = $receita_mes - $saidas_mes;


$ags_hoje = array_filter($agendamentos, fn($a) => substr($a['data_hora'], 0, 10) === $hoje);
$receita_mes = 0;
foreach ($agendamentos as $ag) {
    if (substr($ag['data_hora'], 0, 7) === $mes && $ag['status'] === 'concluido') {
        foreach ($servicos as $sv) {
            if ($sv['id'] === $ag['servico_id']) {
                $receita_mes += floatval($sv['preco']);
            }
        }
    }
}

if ($perfil === 'cliente') {
    $meu_cliente = null;
    foreach ($clientes as $cl) {
        if ($cl['email'] === $usuario['email']) { $meu_cliente = $cl; break; }
    }
    $meus_pets = array_filter($pets, fn($p) => $meu_cliente && $p['cliente_id'] === $meu_cliente['id']);
    $meus_ags = array_filter($agendamentos, fn($a) => $meu_cliente && $a['cliente_id'] === $meu_cliente['id']);
    $ags_hoje_exib = array_filter($meus_ags, fn($a) => substr($a['data_hora'], 0, 10) === $hoje);
    $total_pets_exib = count($meus_pets);
} else {
    $ags_hoje_exib = $ags_hoje;
    $total_pets_exib = count($pets);
    $receita_exib = $receita_mes;
    $meus_ags = $agendamentos;
}

$proximo_ag = null;
$agora = date('Y-m-d H:i');
$futuros = array_filter($meus_ags ?? $agendamentos, fn($a) => $a['data_hora'] >= $agora && $a['status'] !== 'cancelado');
usort($futuros, fn($a, $b) => strcmp($a['data_hora'], $b['data_hora']));
if (!empty($futuros)) $proximo_ag = array_values($futuros)[0];

$proximo_nome = '-';
if ($proximo_ag) {
    $pet_nome = '';
    foreach ($pets as $p) { if ($p['id'] === $proximo_ag['pet_id']) { $pet_nome = $p['nome']; break; } }
    $sv_nome = '';
    foreach ($servicos as $s) { if ($s['id'] === $proximo_ag['servico_id']) { $sv_nome = $s['nome']; break; } }
    $proximo_nome = date('d/m H:i', strtotime($proximo_ag['data_hora'])) . ' - ' . $pet_nome . ' (' . $sv_nome . ')';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="page-header">
    <h1>🐾 Dashboard</h1>
    <span class="badge-perfil badge-<?= $perfil ?>"><?= ucfirst($perfil === 'vet' ? 'Veterinário' : $perfil) ?></span>
  </div>
  <p class="welcome-msg">Olá, <strong><?= htmlspecialchars($usuario['nome']) ?></strong>! Bem-vindo ao PetShop Manager.</p>

  <div class="cards-grid">
    <div class="card card-orange">
      <div class="card-icon">📅</div>
      <div class="card-info">
        <div class="card-value"><?= count($ags_hoje_exib) ?></div>
        <div class="card-label">Agendamentos Hoje</div>
      </div>
    </div>
    <div class="card card-pink">
      <div class="card-icon">🐶</div>
      <div class="card-info">
        <div class="card-value"><?= $total_pets_exib ?></div>
        <div class="card-label"><?= $perfil === 'cliente' ? 'Meus Pets' : 'Total de Pets' ?></div>
      </div>
    </div>
    <?php if ($perfil !== 'cliente'): ?>
    <div class="card card-green">
      <div class="card-icon">💰</div>
      <div class="card-info">
        <div class="card-value"><?= formatar_moeda($receita_exib) ?></div>
        <div class="card-label">Receita do Mês</div>
      </div>
    </div></div><div class="card card-green"><div class="card-info"><div class="card-value"><?= formatar_moeda($saidas_mes ?? 0) ?></div><div class="card-label">Saídas do Mês</div></div></div><div class="card card-green"><div class="card-info"><div class="card-value"><?= formatar_moeda($lucro_mes ?? 0) ?></div><div class="card-label">Lucro Líquido</div></div></div>
    <?php endif; ?>
    <div class="card card-blue">
      <div class="card-icon">⏰</div>
      <div class="card-info">
        <div class="card-value-sm"><?= htmlspecialchars($proximo_nome) ?></div>
        <div class="card-label">Próximo Agendamento</div>
      </div>
    </div>
  </div>

  <div class="section-box">
    <h2>📋 Agendamentos Hoje</h2>
    <?php if (empty($ags_hoje_exib)): ?>
    <p class="empty-msg">Nenhum agendamento para hoje.</p>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>Horário</th><th>Pet</th><th>Serviço</th><th>Status</th><?= $perfil !== 'cliente' ? '<th>Cliente</th>' : '' ?></tr></thead>
      <tbody>
      <?php foreach ($ags_hoje_exib as $ag):
        $pet_n = ''; foreach ($pets as $p) { if ($p['id'] === $ag['pet_id']) { $pet_n = $p['nome']; break; } }
        $sv_n = ''; foreach ($servicos as $s) { if ($s['id'] === $ag['servico_id']) { $sv_n = $s['nome']; break; } }
        $cl_n = ''; foreach ($clientes as $c) { if ($c['id'] === $ag['cliente_id']) { $cl_n = $c['nome']; break; } }
      ?>
      <tr>
        <td><?= date('H:i', strtotime($ag['data_hora'])) ?></td>
        <td><?= htmlspecialchars($pet_n) ?></td>
        <td><?= htmlspecialchars($sv_n) ?></td>
        <td><span class="status-badge status-<?= $ag['status'] ?>"><?= ucfirst($ag['status']) ?></span></td>
        <?= $perfil !== 'cliente' ? '<td>' . htmlspecialchars($cl_n) . '</td>' : '' ?>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
