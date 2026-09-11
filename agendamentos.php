<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();
$perfil = $_SESSION['perfil'];

$agendamentos = ler_json('agendamentos.json');
$pets = ler_json('pets.json');
$clientes = ler_json('clientes.json');
$servicos = ler_json('servicos.json');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar' && perfil_pode(['atendente','vet','cliente'])) {
        $novo = [
            'id' => gerar_id(),
            'cliente_id' => $_POST['cliente_id'] ?? '',
            'pet_id' => $_POST['pet_id'] ?? '',
            'servico_id' => $_POST['servico_id'] ?? '',
            'data_hora' => trim($_POST['data_hora'] ?? ''),
            'status' => 'agendado',
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'criado_em' => date('Y-m-d H:i:s')
        ];
        if ($perfil === 'cliente') {
            $meu_email = $_SESSION['usuario']['email'];
            $meu_cl = null;
            foreach ($clientes as $cl) { if ($cl['email'] === $meu_email) { $meu_cl = $cl; break; } }
            if ($meu_cl) $novo['cliente_id'] = $meu_cl['id'];
        }
        $agendamentos[] = $novo;
        salvar_json('agendamentos.json', $agendamentos);
        $msg = 'Agendamento criado com sucesso!';
    } elseif ($acao === 'status' && perfil_pode(['atendente','vet'])) {
        $id = $_POST['id'] ?? '';
        $ns = $_POST['novo_status'] ?? '';
        foreach ($agendamentos as &$ag) {
            if ($ag['id'] === $id) { $ag['status'] = $ns; break; }
        }
        salvar_json('agendamentos.json', $agendamentos);
        $msg = 'Status atualizado!';
    } elseif ($acao === 'excluir' && perfil_pode(['atendente','vet'])) {
        $id = $_POST['id'] ?? '';
        $agendamentos = array_values(array_filter($agendamentos, fn($a) => $a['id'] !== $id));
        salvar_json('agendamentos.json', $agendamentos);
        $msg = 'Agendamento removido.';
    }
    $agendamentos = ler_json('agendamentos.json');
}

if ($perfil === 'cliente') {
    $meu_email = $_SESSION['usuario']['email'];
    $meu_cl = null;
    foreach ($clientes as $cl) { if ($cl['email'] === $meu_email) { $meu_cl = $cl; break; } }
    if ($meu_cl) {
        $agendamentos = array_values(array_filter($agendamentos, fn($a) => $a['cliente_id'] === $meu_cl['id']));
        $meus_pets = array_values(array_filter($pets, fn($p) => $p['cliente_id'] === $meu_cl['id']));
    } else {
        $agendamentos = []; $meus_pets = [];
    }
} else {
    $meus_pets = $pets;
}

usort($agendamentos, fn($a,$b) => strcmp($b['data_hora'], $a['data_hora']));

function nome_pet($pets, $id) { foreach($pets as $p){ if($p['id']===$id) return $p['nome']; } return '-'; }
function nome_sv($servicos, $id) { foreach($servicos as $s){ if($s['id']===$id) return $s['nome']; } return '-'; }
function nome_cl($clientes, $id) { foreach($clientes as $c){ if($c['id']===$id) return $c['nome']; } return '-'; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - Agendamentos</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="page-header">
    <h1>📅 Agendamentos</h1>
    <button class="btn-primary" onclick="abrirModal('modal-criar')">+ Novo Agendamento</button>
  </div>
  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="section-box">
    <table class="table">
      <thead><tr><th>Data/Hora</th><th>Pet</th><th>Serviço</th><?= $perfil !== 'cliente' ? '<th>Cliente</th>' : '' ?><th>Status</th><th>Obs.</th><th>Ações</th></tr></thead>
      <tbody>
      <?php if (empty($agendamentos)): ?>
      <tr><td colspan="7" class="empty-msg">Nenhum agendamento encontrado.</td></tr>
      <?php else: ?>
      <?php foreach ($agendamentos as $ag): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($ag['data_hora'])) ?></td>
        <td><strong><?= htmlspecialchars(nome_pet($pets, $ag['pet_id'])) ?></strong></td>
        <td><?= htmlspecialchars(nome_sv($servicos, $ag['servico_id'])) ?></td>
        <?php if ($perfil !== 'cliente'): ?><td><?= htmlspecialchars(nome_cl($clientes, $ag['cliente_id'])) ?></td><?php endif; ?>
        <td><span class="status-badge status-<?= $ag['status'] ?>"><?= ucfirst($ag['status']) ?></span></td>
        <td><?= htmlspecialchars($ag['observacoes'] ?? '-') ?></td>
        <td style="white-space:nowrap">
          <?php if (perfil_pode(['atendente','vet'])): ?>
          <form method="POST" style="display:inline">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="status">
            <input type="hidden" name="id" value="<?= $ag['id'] ?>">
            <select name="novo_status" onchange="this.form.submit()" class="select-sm">
              <option value="agendado" <?= $ag['status']==='agendado'?'selected':'' ?>>Agendado</option>
              <option value="em_atendimento" <?= $ag['status']==='em_atendimento'?'selected':'' ?>>Em Atend.</option>
              <option value="concluido" <?= $ag['status']==='concluido'?'selected':'' ?>>Concluído</option>
              <option value="cancelado" <?= $ag['status']==='cancelado'?'selected':'' ?>>Cancelado</option>
            </select>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Excluir agendamento?')">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $ag['id'] ?>">
            <button type="submit" class="btn-sm btn-danger">✕</button>
          </form>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modal-criar">
  <div class="modal">
    <div class="modal-header"><h3>Novo Agendamento</h3><button onclick="fecharModal('modal-criar')" class="modal-close">✕</button></div>
    <form method="POST" action="agendamentos.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="criar">
      <?php if (perfil_pode(['atendente','vet'])): ?>
      <div class="form-group"><label>Cliente *</label>
        <select name="cliente_id" required>
          <option value="">Selecione...</option>
          <?php foreach ($clientes as $cl): ?>
          <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group"><label>Pet *</label>
          <select name="pet_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($meus_pets as $pt): ?>
            <option value="<?= $pt['id'] ?>"><?= htmlspecialchars($pt['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Serviço *</label>
          <select name="servico_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($servicos as $sv): ?>
            <option value="<?= $sv['id'] ?>"><?= htmlspecialchars($sv['nome']) ?> - <?= formatar_moeda($sv['preco']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group"><label>Data e Hora *</label><input type="datetime-local" name="data_hora" required></div>
      <div class="form-group"><label>Observações</label><textarea name="observacoes" rows="2"></textarea></div>
      <div class="modal-footer"><button type="button" onclick="fecharModal('modal-criar')" class="btn-secondary">Cancelar</button><button type="submit" class="btn-primary">Agendar</button></div>
    </form>
  </div>
</div>

<script>
function abrirModal(id){document.getElementById(id).style.display='flex';}
function fecharModal(id){document.getElementById(id).style.display='none';}
</script>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
