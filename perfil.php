<?php
session_start();
require 'functions.php';
verificar_sessao();

// Cliente vai para o próprio perfil no clientes.php
if ($_SESSION['perfil'] === 'cliente') {
    header('Location: clientes.php');
    exit;
}

$perfil  = $_SESSION['perfil'];
$email   = $_SESSION['usuario']['email'];
$msg     = '';

// Carrega perfis do sistema
$perfis = ler_json('perfis_sistema.json');
$idx_perfil = null;
foreach ($perfis as $i => $p) {
    if ($p['email'] === $email) { $idx_perfil = $i; break; }
}
// Se não existir, cria entrada padrão
if ($idx_perfil === null) {
    $perfis[] = ['email' => $email, 'nome' => $_SESSION['usuario']['nome'], 'telefone' => '', 'bio' => ''];
    $idx_perfil = count($perfis) - 1;
    salvar_json('perfis_sistema.json', $perfis);
}
$meu = $perfis[$idx_perfil];

// POST: salvar edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $novo_nome = trim($_POST['nome'] ?? '');
    if (!empty($novo_nome)) {
        $perfis[$idx_perfil]['nome']     = $novo_nome;
        $_SESSION['usuario']['nome']     = $novo_nome;
    }
    $perfis[$idx_perfil]['telefone'] = trim($_POST['telefone'] ?? '');
    $perfis[$idx_perfil]['bio']      = trim($_POST['bio'] ?? '');
    salvar_json('perfis_sistema.json', $perfis);
    $msg = 'Perfil atualizado!';
    $meu = $perfis[$idx_perfil];
}

// Iniciais do avatar
$iniciais = '';
foreach (explode(' ', $meu['nome']) as $p) {
    $iniciais .= mb_strtoupper(mb_substr($p, 0, 1));
    if (mb_strlen($iniciais) >= 2) break;
}

$perfil_label = $perfil === 'vet' ? 'Veterinário' : ucfirst($perfil);
$badge_class  = $perfil === 'vet' ? 'badge-vet' : 'badge-atendente';

// Estatísticas rápidas
$clientes_total = count(ler_json('clientes.json'));
$pets_total     = count(ler_json('pets.json'));
$servicos_total = count(ler_json('servicos.json'));
$ags_total      = count(ler_json('agendamentos.json'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - Meu Perfil</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="page-header">
    <h1>🪪 Meu Perfil</h1>
  </div>

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <!-- Stats rápidas -->
  <div class="stat-row">
    <div class="stat-mini">
      <div class="stat-num"><?= $clientes_total ?></div>
      <div class="stat-lbl">Clientes</div>
    </div>
    <div class="stat-mini">
      <div class="stat-num"><?= $pets_total ?></div>
      <div class="stat-lbl">Pets</div>
    </div>
    <div class="stat-mini">
      <div class="stat-num"><?= $ags_total ?></div>
      <div class="stat-lbl">Agendamentos</div>
    </div>
    <div class="stat-mini">
      <div class="stat-num"><?= $servicos_total ?></div>
      <div class="stat-lbl">Serviços</div>
    </div>
  </div>

  <!-- Profile card -->
  <div class="profile-hero">
    <div class="avatar-lg"><?= htmlspecialchars($iniciais) ?></div>
    <div class="profile-details">
      <div class="profile-name">
        <?= htmlspecialchars($meu['nome']) ?>
        <span class="badge-perfil <?= $badge_class ?>"><?= $perfil_label ?></span>
      </div>
      <div class="profile-email">📧 <?= htmlspecialchars($email) ?></div>
      <div class="profile-meta">
        <div class="profile-meta-item">
          <span class="meta-label">📞 Telefone</span>
          <span class="meta-value"><?= htmlspecialchars($meu['telefone'] ?: '—') ?></span>
        </div>
        <div class="profile-meta-item">
          <span class="meta-label">🏥 Função</span>
          <span class="meta-value"><?= $perfil_label ?></span>
        </div>
      </div>
      <?php if (!empty($meu['bio'])): ?>
      <div class="perfil-bio"><?= htmlspecialchars($meu['bio']) ?></div>
      <?php endif; ?>
    </div>
    <div class="profile-actions">
      <button class="btn-primary" onclick="abrirModal('modal-editar')">✏️ Editar Perfil</button>
    </div>
  </div>
</div>

<!-- Modal editar -->
<div class="modal-overlay" id="modal-editar">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Editar Meu Perfil</h3>
      <button onclick="fecharModal('modal-editar')" class="modal-close">✕</button>
    </div>
    <form method="POST" action="perfil.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="editar">
      <div class="form-group">
        <label>Nome de exibição</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($meu['nome']) ?>" required>
      </div>
      <div class="form-group">
        <label>E-mail (não editável)</label>
        <input type="text" value="<?= htmlspecialchars($email) ?>" disabled style="opacity:.5">
      </div>
      <div class="form-group">
        <label>Telefone</label>
        <input type="text" name="telefone" value="<?= htmlspecialchars($meu['telefone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Biografia / Observações</label>
        <textarea name="bio" rows="3"><?= htmlspecialchars($meu['bio'] ?? '') ?></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="fecharModal('modal-editar')" class="btn-secondary">Cancelar</button>
        <button type="submit" class="btn-primary">Salvar alterações</button>
      </div>
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
