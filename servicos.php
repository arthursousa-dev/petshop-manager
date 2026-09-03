<?php
session_start();
require 'functions.php';
verificar_sessao();
$perfil = $_SESSION['perfil'];

$servicos = ler_json('servicos.json');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && perfil_pode(['vet'])) {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die('Sessão expirada. Recarregue a página e tente novamente.');
    }
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'criar') {
        $novo = [
            'id'        => gerar_id(),
            'nome'      => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'preco'     => floatval($_POST['preco'] ?? 0),
            'duracao'   => trim($_POST['duracao'] ?? ''),
            'criado_em' => date('Y-m-d H:i:s')
        ];
        $servicos[] = $novo;
        salvar_json('servicos.json', $servicos);
        $msg = 'Serviço criado!';
    } elseif ($acao === 'editar') {
        $id = $_POST['id'] ?? '';
        foreach ($servicos as &$sv) {
            if ($sv['id'] === $id) {
                $sv['nome']      = trim($_POST['nome'] ?? '');
                $sv['descricao'] = trim($_POST['descricao'] ?? '');
                $sv['preco']     = floatval($_POST['preco'] ?? 0);
                $sv['duracao']   = trim($_POST['duracao'] ?? '');
                break;
            }
        }
        unset($sv);
        salvar_json('servicos.json', $servicos);
        $msg = 'Serviço atualizado!';
    } elseif ($acao === 'excluir') {
        $id = $_POST['id'] ?? '';
        $servicos = array_values(array_filter($servicos, fn($s) => $s['id'] !== $id));
        salvar_json('servicos.json', $servicos);
        $msg = 'Serviço removido.';
    }
    $servicos = ler_json('servicos.json');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop - Serviços</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
  <div class="page-header">
    <h1>✂️ Serviços &amp; Preços</h1>
    <?php if (perfil_pode(['vet'])): ?>
    <button class="btn-primary" onclick="abrirModal('modal-criar')">+ Novo Serviço</button>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div class="section-box">
    <?php if ($perfil === 'cliente'): ?>
    <p style="font-size:.82rem;color:var(--text-3);margin-bottom:14px">Confira abaixo todos os procedimentos e valores disponíveis.</p>
    <?php endif; ?>
    <table class="table">
      <thead>
        <tr>
          <th>Serviço</th>
          <th>Descrição</th>
          <th>Preço</th>
          <th>Duração</th>
          <?= perfil_pode(['vet']) ? '<th>Ações</th>' : '' ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($servicos)): ?>
        <tr><td colspan="5" class="empty-msg">Nenhum serviço cadastrado.</td></tr>
      <?php else: ?>
        <?php foreach ($servicos as $sv): ?>
        <tr>
          <td><strong><?= htmlspecialchars($sv['nome']) ?></strong></td>
          <td><?= htmlspecialchars($sv['descricao'] ?? '—') ?></td>
          <td class="preco-cell"><?= formatar_moeda($sv['preco']) ?></td>
          <td><?= htmlspecialchars($sv['duracao'] ?? '—') ?></td>
          <?php if (perfil_pode(['vet'])): ?>
          <td style="white-space:nowrap">
            <button class="btn-sm btn-edit" onclick="editarSv('<?= htmlspecialchars(json_encode($sv), ENT_QUOTES) ?>')">Editar</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remover serviço?')">
            <?= csrf_campo() ?>
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $sv['id'] ?>">
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

<?php if (perfil_pode(['vet'])): ?>
<div class="modal-overlay" id="modal-criar">
  <div class="modal">
    <div class="modal-header"><h3>Novo Serviço</h3><button onclick="fecharModal('modal-criar')" class="modal-close">✕</button></div>
    <form method="POST" action="servicos.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="criar">
      <div class="form-row">
        <div class="form-group"><label>Nome *</label><input type="text" name="nome" required></div>
        <div class="form-group"><label>Preço (R$) *</label><input type="number" name="preco" step="0.01" min="0" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Duração</label><input type="text" name="duracao" placeholder="Ex: 1h30min"></div>
        <div class="form-group"><label>Descrição</label><input type="text" name="descricao"></div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="fecharModal('modal-criar')" class="btn-secondary">Cancelar</button>
        <button type="submit" class="btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="modal-editar">
  <div class="modal">
    <div class="modal-header"><h3>Editar Serviço</h3><button onclick="fecharModal('modal-editar')" class="modal-close">✕</button></div>
    <form method="POST" action="servicos.php">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="es-id">
      <div class="form-row">
        <div class="form-group"><label>Nome *</label><input type="text" name="nome" id="es-nome" required></div>
        <div class="form-group"><label>Preço (R$) *</label><input type="number" name="preco" id="es-preco" step="0.01" min="0" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Duração</label><input type="text" name="duracao" id="es-dur"></div>
        <div class="form-group"><label>Descrição</label><input type="text" name="descricao" id="es-desc"></div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="fecharModal('modal-editar')" class="btn-secondary">Cancelar</button>
        <button type="submit" class="btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function abrirModal(id){document.getElementById(id).style.display='flex';}
function fecharModal(id){document.getElementById(id).style.display='none';}
function editarSv(json){
  var d=JSON.parse(json);
  document.getElementById('es-id').value=d.id;
  document.getElementById('es-nome').value=d.nome||'';
  document.getElementById('es-preco').value=d.preco||'';
  document.getElementById('es-dur').value=d.duracao||'';
  document.getElementById('es-desc').value=d.descricao||'';
  abrirModal('modal-editar');
}
</script>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
