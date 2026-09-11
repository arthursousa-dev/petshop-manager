<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();
$perfil = $_SESSION['perfil'];

$pets = ler_json('pets.json');
$clientes = ler_json('clientes.json');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && perfil_pode(['atendente','vet'])) {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar') {
        $novo = [
            'id' => gerar_id(),
            'nome' => trim($_POST['nome'] ?? ''),
            'cliente_id' => $_POST['cliente_id'] ?? '',
            'especie' => trim($_POST['especie'] ?? ''),
            'raca' => trim($_POST['raca'] ?? ''),
            'peso' => trim($_POST['peso'] ?? ''),
            'alergias' => trim($_POST['alergias'] ?? ''),
            'nascimento' => $_POST['nascimento'] ?? '',
            'criado_em' => date('Y-m-d H:i:s')
        ];
        $pets[] = $novo;
        salvar_json('pets.json', $pets);
        $msg = 'Pet cadastrado com sucesso!';
    } elseif ($acao === 'editar') {
        $id = $_POST['id'] ?? '';
        foreach ($pets as &$pt) {
            if ($pt['id'] === $id) {
                $pt['nome'] = trim($_POST['nome'] ?? '');
                $pt['cliente_id'] = $_POST['cliente_id'] ?? '';
                $pt['especie'] = trim($_POST['especie'] ?? '');
                $pt['raca'] = trim($_POST['raca'] ?? '');
                $pt['peso'] = trim($_POST['peso'] ?? '');
                $pt['alergias'] = trim($_POST['alergias'] ?? '');
                $pt['nascimento'] = $_POST['nascimento'] ?? '';
                break;
            }
        }
        salvar_json('pets.json', $pets);
        $msg = 'Pet atualizado!';
    } elseif ($acao === 'excluir') {
        $id = $_POST['id'] ?? '';
        $pets = array_values(array_filter($pets, fn($p) => $p['id'] !== $id));
        salvar_json('pets.json', $pets);
        $msg = 'Pet removido.';
    }
    $pets = ler_json('pets.json');
}

if ($perfil === 'cliente') {
    $meu_email = $_SESSION['usuario']['email'];
    $meu_cl = null;
    foreach ($clientes as $cl) { if ($cl['email'] === $meu_email) { $meu_cl = $cl; break; } }
    if ($meu_cl) {
        $pets = array_values(array_filter($pets, fn($p) => $p['cliente_id'] === $meu_cl['id']));
    } else {
        $pets = [];
    }
}

function get_cliente_nome($clientes, $id) {
    foreach ($clientes as $c) { if ($c['id'] === $id) return $c['nome']; }
    return '-';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - Pets</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="page-header">
    <h1>🐶 Pets</h1>
    <?php if (perfil_pode(['atendente','vet'])): ?>
    <button class="btn-primary" onclick="abrirModal('modal-criar')">+ Novo Pet</button>
    <?php endif; ?>
  </div>
  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="section-box">
    <table class="table">
      <thead><tr><th>Nome</th><th>Proprietário</th><th>Espécie</th><th>Raça</th><th>Peso</th><th>Alergias / Obs.</th><?= perfil_pode(['atendente','vet']) ? '<th>Ações</th>' : '' ?></tr></thead>
      <tbody>
      <?php if (empty($pets)): ?>
      <tr><td colspan="7" class="empty-msg">Nenhum pet cadastrado.</td></tr>
      <?php else: ?>
      <?php foreach ($pets as $pt): ?>
      <tr>
        <td><strong><?= htmlspecialchars($pt['nome']) ?></strong></td>
        <td><?= htmlspecialchars(get_cliente_nome($clientes, $pt['cliente_id'])) ?></td>
        <td><?= htmlspecialchars($pt['especie'] ?? '-') ?></td>
        <td><?= htmlspecialchars($pt['raca'] ?? '-') ?></td>
        <td><?= htmlspecialchars($pt['peso'] ?? '-') ?> <?= !empty($pt['peso']) ? 'kg' : '' ?></td>
        <td><?= htmlspecialchars($pt['alergias'] ?? '-') ?></td>
        <?php if (perfil_pode(['atendente','vet'])): ?>
        <td style="white-space:nowrap">
          <button class="btn-sm btn-edit" onclick="editarPet('<?= htmlspecialchars(json_encode($pt), ENT_QUOTES) ?>')">Editar</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Remover pet?')">
            <?= csrf_campo() ?>
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" name="id" value="<?= $pt['id'] ?>">
            <button type="submit" class="btn-sm btn-danger">Excluir</button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (perfil_pode(['atendente','vet'])): ?>
<div class="modal-overlay" id="modal-criar">
  <div class="modal">
    <div class="modal-header"><h3>Novo Pet</h3><button onclick="fecharModal('modal-criar')" class="modal-close">✕</button></div>
    <form method="POST" action="pets.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="criar">
      <div class="form-row">
        <div class="form-group"><label>Nome do Pet *</label><input type="text" name="nome" required></div>
        <div class="form-group"><label>Proprietário *</label>
          <select name="cliente_id" required>
            <option value="">Selecione...</option>
            <?php foreach ($clientes as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Espécie</label>
          <select name="especie">
            <option value="">Selecione...</option>
            <option>Cão</option><option>Gato</option><option>Pássaro</option><option>Coelho</option><option>Outro</option>
          </select>
        </div>
        <div class="form-group"><label>Raça</label><input type="text" name="raca"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Peso (kg)</label><input type="number" name="peso" step="0.1" min="0"></div>
        <div class="form-group"><label>Nascimento</label><input type="date" name="nascimento"></div>
      </div>
      <div class="form-group"><label>Alergias / Observações</label><textarea name="alergias" rows="2"></textarea></div>
      <div class="modal-footer"><button type="button" onclick="fecharModal('modal-criar')" class="btn-secondary">Cancelar</button><button type="submit" class="btn-primary">Salvar</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-editar">
  <div class="modal">
    <div class="modal-header"><h3>Editar Pet</h3><button onclick="fecharModal('modal-editar')" class="modal-close">✕</button></div>
    <form method="POST" action="pets.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="ep-id">
      <div class="form-row">
        <div class="form-group"><label>Nome do Pet *</label><input type="text" name="nome" id="ep-nome" required></div>
        <div class="form-group"><label>Proprietário *</label>
          <select name="cliente_id" id="ep-cliente" required>
            <option value="">Selecione...</option>
            <?php foreach ($clientes as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Espécie</label>
          <select name="especie" id="ep-especie">
            <option value="">Selecione...</option>
            <option>Cão</option><option>Gato</option><option>Pássaro</option><option>Coelho</option><option>Outro</option>
          </select>
        </div>
        <div class="form-group"><label>Raça</label><input type="text" name="raca" id="ep-raca"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Peso (kg)</label><input type="number" name="peso" id="ep-peso" step="0.1" min="0"></div>
        <div class="form-group"><label>Nascimento</label><input type="date" name="nascimento" id="ep-nasc"></div>
      </div>
      <div class="form-group"><label>Alergias / Observações</label><textarea name="alergias" id="ep-alergias" rows="2"></textarea></div>
      <div class="modal-footer"><button type="button" onclick="fecharModal('modal-editar')" class="btn-secondary">Cancelar</button><button type="submit" class="btn-primary">Salvar</button></div>
    </form>
  </div>
</div>

<script>
function abrirModal(id){document.getElementById(id).style.display='flex';}
function fecharModal(id){document.getElementById(id).style.display='none';}
function editarPet(json){
  var d=JSON.parse(json);
  document.getElementById('ep-id').value=d.id;
  document.getElementById('ep-nome').value=d.nome||'';
  document.getElementById('ep-cliente').value=d.cliente_id||'';
  document.getElementById('ep-especie').value=d.especie||'';
  document.getElementById('ep-raca').value=d.raca||'';
  document.getElementById('ep-peso').value=d.peso||'';
  document.getElementById('ep-nasc').value=d.nascimento||'';
  document.getElementById('ep-alergias').value=d.alergias||'';
  abrirModal('modal-editar');
}
</script>
<?php endif; ?>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
