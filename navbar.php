<?php
$perfil = $_SESSION['perfil'] ?? '';
$atual  = basename($_SERVER['PHP_SELF']);
$nome_usuario = $_SESSION['usuario']['nome'] ?? '';
$iniciais = '';
foreach (explode(' ', $nome_usuario) as $parte) {
    $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
    if (mb_strlen($iniciais) >= 2) break;
}
$link_perfil = ($perfil === 'cliente') ? 'clientes.php' : 'perfil.php';
?>
<nav class="navbar">
  <div class="nav-brand">🐾 PetShop Manager</div>
  <div class="nav-links">
    <a href="index.php"        class="<?= $atual==='index.php'        ?'active':''?>">🏠 Dashboard</a>
    <?php if ($perfil !== 'cliente'): ?>
    <a href="clientes.php"     class="<?= $atual==='clientes.php'     ?'active':''?>">👥 Clientes</a>
    <?php endif; ?>
    <a href="pets.php"         class="<?= $atual==='pets.php'         ?'active':''?>">🐶 Pets</a>
    <a href="agendamentos.php" class="<?= $atual==='agendamentos.php' ?'active':''?>">📅 Agendamentos</a>
    <a href="servicos.php"     class="<?= $atual==='servicos.php'     ?'active':''?>">✂️ Serviços</a>
    <?php if ($perfil !== 'cliente'): ?>
    <a href="financeiro.php" class="<?= $atual==='financeiro.php' ?'active':''?>">💰 Financeiro</a>
    <a href="relatorios.php"   class="<?= $atual==='relatorios.php'   ?'active':''?>">📊 Relatórios</a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <a href="<?= $link_perfil ?>" class="nav-user-link" title="Meu Perfil">
      <div class="user-avatar"><?= htmlspecialchars($iniciais) ?></div>
      <span class="user-info"><?= htmlspecialchars($nome_usuario) ?></span>
    </a>
    <a href="logout.php" class="btn-logout">Sair</a>
  </div>
</nav>
