<?php
session_start();
require 'functions.php';
verificar_sessao();
$perfil = $_SESSION['perfil'];

$clientes = ler_json('clientes.json');
$msg = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $acao = $_POST['acao'] ?? '';

    // Cliente edita o próprio perfil
    if ($perfil === 'cliente' && $acao === 'editar') {
        $meu_email = $_SESSION['usuario']['email'];
        foreach ($clientes as &$cl) {
            if ($cl['email'] === $meu_email) {
                $novo_nome = trim($_POST['nome'] ?? '');
                if (!empty($novo_nome)) {
                    $cl['nome'] = $novo_nome;
                    $_SESSION['usuario']['nome'] = $novo_nome;
                }
                $cl['telefone'] = trim($_POST['telefone'] ?? '');
                $cl['endereco'] = trim($_POST['endereco'] ?? '');
                break;
            }
        }
        unset($cl);
        salvar_json('clientes.json', $clientes);
        $msg = 'Perfil atualizado com sucesso!';
        $clientes = ler_json('clientes.json');
    }
    // Atendente/Vet faz CRUD completo
    elseif (perfil_pode(['atendente','vet'])) {
        if ($acao === 'criar') {
            $novo = [
                'id'        => gerar_id(),
                'nome'      => trim($_POST['nome'] ?? ''),
                'email'     => trim($_POST['email'] ?? ''),
                'telefone'  => trim($_POST['telefone'] ?? ''),
                'endereco'  => trim($_POST['endereco'] ?? ''),
                'criado_em' => date('Y-m-d H:i:s')
            ];
            if (empty($novo['nome']) || empty($novo['email'])) {
                $erro = 'Nome e e-mail são obrigatórios.';
            } else {
                $clientes[] = $novo;
                salvar_json('clientes.json', $clientes);
                $msg = 'Cliente cadastrado com sucesso!';
            }
        } elseif ($acao === 'editar') {
            $id = $_POST['id'] ?? '';
            foreach ($clientes as &$cl) {
                if ($cl['id'] === $id) {
                    $cl['nome']     = trim($_POST['nome'] ?? '');
                    $cl['email']    = trim($_POST['email'] ?? '');
                    $cl['telefone'] = trim($_POST['telefone'] ?? '');
                    $cl['endereco'] = trim($_POST['endereco'] ?? '');
                    break;
                }
            }
            unset($cl);
            salvar_json('clientes.json', $clientes);
            $msg = 'Cliente atualizado!';
        } elseif ($acao === 'excluir') {
            $id = $_POST['id'] ?? '';
            $clientes = array_values(array_filter($clientes, fn($c) => $c['id'] !== $id));
            salvar_json('clientes.json', $clientes);
            $msg = 'Cliente removido.';
        }
        $clientes = ler_json('clientes.json');
    }
}

// --- Dados do cliente logado ---
$meu_cliente = null;
$meus_pets = [];
if ($perfil === 'cliente') {
    $meu_email = $_SESSION['usuario']['email'];
    foreach ($clientes as $cl) {
        if ($cl['email'] === $meu_email) { $meu_cliente = $cl; break; }
    }
    if ($meu_cliente) {
        $pets_data = ler_json('pets.json');
        $meus_pets = array_values(array_filter($pets_data, fn($p) => $p['cliente_id'] === $meu_cliente['id']));
    }
}

function pet_emoji($especie) {
    $m = ['Cão'=>'🐕','Gato'=>'🐈','Pássaro'=>'🐦','Coelho'=>'🐇','Peixe'=>'🐠'];
    return $m[$especie] ?? '🐾';
}

function pet_idade($nasc) {
    if (empty($nasc)) return '';
    $diff = (new DateTime())->diff(new DateTime($nasc));
    if ($diff->y > 0) return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '');
    return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
}

$titulo_pagina = ($perfil === 'cliente') ? 'Meu Perfil' : 'Clientes';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - <?= $titulo_pagina ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">

<?php if ($perfil === 'cliente'): ?>
  <!-- Visão do cliente: cartão de perfil -->
  <div class="page-header">
    <h1>🪪 Meu Perfil</h1>
  </div>

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if ($meu_cliente): ?>
    <?php
      $iniciais_cl = '';
      foreach (explode(' ', $meu_cliente['nome']) as $p) {
          $iniciais_cl .= mb_strtoupper(mb_substr($p,0,1));
          if (mb_strlen($iniciais_cl) >= 2) break;
      }
    ?>
    <div class="profile-hero">
      <div class="avatar-lg"><?= htmlspecialchars($iniciais_cl) ?></div>
      <div class="profile-details">
        <div class="profile-name">
          <?= htmlspecialchars($meu_cliente['nome']) ?>
          <span class="badge-perfil badge-cliente">Cliente</span>
        </div>
        <div class="profile-email">📧 <?= htmlspecialchars($meu_cliente['email']) ?></div>
        <div class="profile-meta">
          <div class="profile-meta-item">
            <span class="meta-label">📞 Telefone</span>
            <span class="meta-value"><?= htmlspecialchars($meu_cliente['telefone'] ?: '—') ?></span>
          </div>
          <div class="profile-meta-item">
            <span class="meta-label">📍 Endereço</span>
            <span class="meta-value"><?= htmlspecialchars($meu_cliente['endereco'] ?: '—') ?></span>
          </div>
          <div class="profile-meta-item">
            <span class="meta-label">📅 Cliente desde</span>
            <span class="meta-value"><?= isset($meu_cliente['criado_em']) ? date('M/Y', strtotime($meu_cliente['criado_em'])) : '—' ?></span>
          </div>
          <div class="profile-meta-item">
            <span class="meta-label">🐾 Pets</span>
            <span class="meta-value"><?= count($meus_pets) ?> cadastrado<?= count($meus_pets) !== 1 ? 's' : '' ?></span>
          </div>
        </div>
      </div>
      <div class="profile-actions">
        <button class="btn-primary" onclick="abrirModal('modal-editar-perfil')">✏️ Editar Perfil</button>
      </div>
    </div>

    <!-- Pets do cliente -->
    <div class="section-box">
      <h2>🐾 Meus Pets</h2>
      <?php if (empty($meus_pets)): ?>
        <p class="empty-msg">Você ainda não tem pets cadastrados. Fale com a recepção!</p>
      <?php else: ?>
        <div class="pets-grid">
          <?php foreach ($meus_pets as $pt): ?>
          <div class="pet-card">
            <div class="pet-emoji"><?= pet_emoji($pt['especie'] ?? '') ?></div>
            <div class="pet-card-info">
              <strong><?= htmlspecialchars($pt['nome']) ?></strong>
              <span><?= htmlspecialchars($pt['raca'] ?: ($pt['especie'] ?: '—')) ?></span>
              <span><?= !empty($pt['peso']) ? $pt['peso'].' kg' : '' ?></span>
              <span><?= pet_idade($pt['nascimento'] ?? '') ?></span>
              <?php if (!empty($pt['alergias'])): ?>
              <span style="color:#d97706">⚠️ <?= htmlspecialchars($pt['alergias']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="alert alert-info">ℹ️ Seu cadastro ainda não foi encontrado. Entre em contato com a recepção.</div>
  <?php endif; ?>

  <!-- Modal editar perfil -->
  <div class="modal-overlay" id="modal-editar-perfil">
    <div class="modal">
      <div class="modal-header">
        <h3>✏️ Editar Meu Perfil</h3>
        <button onclick="fecharModal('modal-editar-perfil')" class="modal-close">✕</button>
      </div>
      <form method="POST" action="clientes.php">
            <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="editar">
        <div class="form-group">
          <label>Nome completo</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($meu_cliente['nome'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>E-mail (não editável)</label>
          <input type="text" value="<?= htmlspecialchars($meu_cliente['email'] ?? '') ?>" disabled style="opacity:.5">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($meu_cliente['telefone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Endereço</label>
            <input type="text" name="endereco" value="<?= htmlspecialchars($meu_cliente['endereco'] ?? '') ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" onclick="fecharModal('modal-editar-perfil')" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary">Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>

<?php else: ?>
  <!-- Visão da equipe: lista de clientes -->
  <div class="page-header">
    <h1>👥 Clientes</h1>
    <?php if (perfil_pode(['atendente','vet'])): ?>
    <button class="btn-primary" onclick="abrirModal('modal-criar')">+ Novo Cliente</button>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="section-box">
    <table class="table">
      <thead>
        <tr>
          <th>Nome</th><th>E-mail</th><th>Telefone</th><th>Endereço</th><th>Cadastro</th>
          <?= perfil_pode(['atendente','vet']) ? '<th>Ações</th>' : '' ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($clientes)): ?>
        <tr><td colspan="6" class="empty-msg">Nenhum cliente cadastrado.</td></tr>
      <?php else: ?>
        <?php foreach ($clientes as $cl): ?>
        <tr>
          <td><strong><?= htmlspecialchars($cl['nome']) ?></strong></td>
          <td><?= htmlspecialchars($cl['email']) ?></td>
          <td><?= htmlspecialchars($cl['telefone'] ?? '—') ?></td>
          <td><?= htmlspecialchars($cl['endereco'] ?? '—') ?></td>
          <td><?= isset($cl['criado_em']) ? date('d/m/Y', strtotime($cl['criado_em'])) : '—' ?></td>
          <?php if (perfil_pode(['atendente','vet'])): ?>
          <td style="white-space:nowrap">
            <button class="btn-sm btn-edit" onclick="editarCliente('<?= htmlspecialchars(json_encode($cl), ENT_QUOTES) ?>')">Editar</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remover cliente?')">
            <?= csrf_campo() ?>
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $cl['id'] ?>">
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

  <?php if (perfil_pode(['atendente','vet'])): ?>
  <!-- Modal criar -->
  <div class="modal-overlay" id="modal-criar">
    <div class="modal">
      <div class="modal-header"><h3>Novo Cliente</h3><button onclick="fecharModal('modal-criar')" class="modal-close">✕</button></div>
      <form method="POST" action="clientes.php">
            <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="criar">
        <div class="form-row">
          <div class="form-group"><label>Nome *</label><input type="text" name="nome" required></div>
          <div class="form-group"><label>E-mail *</label><input type="email" name="email" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Telefone</label><input type="text" name="telefone"></div>
          <div class="form-group"><label>Endereço</label><input type="text" name="endereco"></div>
        </div>
        <div class="modal-footer">
          <button type="button" onclick="fecharModal('modal-criar')" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal editar -->
  <div class="modal-overlay" id="modal-editar">
    <div class="modal">
      <div class="modal-header"><h3>Editar Cliente</h3><button onclick="fecharModal('modal-editar')" class="modal-close">✕</button></div>
      <form method="POST" action="clientes.php">
            <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-row">
          <div class="form-group"><label>Nome *</label><input type="text" name="nome" id="edit-nome" required></div>
          <div class="form-group"><label>E-mail *</label><input type="email" name="email" id="edit-email" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Telefone</label><input type="text" name="telefone" id="edit-telefone"></div>
          <div class="form-group"><label>Endereço</label><input type="text" name="endereco" id="edit-endereco"></div>
        </div>
        <div class="modal-footer">
          <button type="button" onclick="fecharModal('modal-editar')" class="btn-secondary">Cancelar</button>
          <button type="submit" class="btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

<?php endif; ?>
</div>

<script>
function abrirModal(id){document.getElementById(id).style.display='flex';}
function fecharModal(id){document.getElementById(id).style.display='none';}
function editarCliente(json){
  var d=JSON.parse(json);
  document.getElementById('edit-id').value=d.id;
  document.getElementById('edit-nome').value=d.nome||'';
  document.getElementById('edit-email').value=d.email||'';
  document.getElementById('edit-telefone').value=d.telefone||'';
  document.getElementById('edit-endereco').value=d.endereco||'';
  abrirModal('modal-editar');
}
</script>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
