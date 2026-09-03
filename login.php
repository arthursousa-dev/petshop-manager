<?php
session_start();
if (!empty($_GET['_db_fresh'])) { $_SESSION = []; session_regenerate_id(true); }

$usuarios = [
    'cliente@petshop.com'    => ['senha' => 'cliente123',    'perfil' => 'cliente',    'nome' => 'Maria Silva'],
    'atendente@petshop.com'  => ['senha' => 'atendente123',  'perfil' => 'atendente',  'nome' => 'João Atendente'],
    'vet@petshop.com'        => ['senha' => 'vet123',        'perfil' => 'vet',        'nome' => 'Dra. Ana Vet'],
];

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (isset($usuarios[$email]) && $usuarios[$email]['senha'] === $senha) {
        $_SESSION['logado'] = true;
        $_SESSION['perfil'] = $usuarios[$email]['perfil'];
        $_SESSION['usuario'] = ['email' => $email, 'nome' => $usuarios[$email]['nome']];
        header('Location: index.php');
        exit;
    } else {
        $erro = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop Manager — Login</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter','Segoe UI',sans-serif;display:flex;height:100vh;overflow:hidden;background:#f0eff5;}
/* LEFT */
.col-left{
  flex:1.1;
  background:linear-gradient(145deg,#ff6b35 0%,#e91e8c 60%,#7c3aed 100%);
  display:flex;flex-direction:column;align-items:flex-start;justify-content:center;
  color:#fff;padding:60px;position:relative;overflow:hidden;
}
.col-left::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
}
.col-left::after{
  content:'';position:absolute;bottom:-80px;right:-80px;
  width:320px;height:320px;border-radius:50%;
  background:rgba(255,255,255,.07);
}
.login-brand{position:relative;z-index:1;}
.login-brand .emoji{font-size:3.2rem;margin-bottom:20px;display:block;}
.login-brand h1{font-size:2.2rem;font-weight:800;letter-spacing:-.5px;line-height:1.1;margin-bottom:12px;}
.login-brand p{font-size:.95rem;opacity:.88;line-height:1.65;max-width:300px;font-weight:400;}
.features{margin-top:36px;display:flex;flex-direction:column;gap:12px;position:relative;z-index:1;}
.feat{display:flex;align-items:center;gap:12px;font-size:.87rem;opacity:.95;font-weight:500;}
.feat-icon{width:28px;height:28px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;}
/* RIGHT */
.col-right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;}
.login-box{width:100%;max-width:380px;}
.login-box h2{font-size:1.65rem;font-weight:800;color:#1a1a2e;margin-bottom:6px;letter-spacing:-.4px;}
.login-box .sub{color:#888;font-size:.88rem;margin-bottom:32px;font-weight:400;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:7px;text-transform:uppercase;letter-spacing:.5px;}
.form-group input{
  width:100%;padding:11px 14px;
  border:1.5px solid #e8e5f2;border-radius:9px;
  font-size:.9rem;color:#1a1a2e;
  transition:border .2s,box-shadow .2s;background:#fff;
  font-family:inherit;
}
.form-group input:focus{outline:none;border-color:#e91e8c;box-shadow:0 0 0 3px rgba(233,30,140,.08);}
.btn-login{
  width:100%;padding:13px;
  background:linear-gradient(135deg,#ff6b35,#e91e8c);
  color:#fff;border:none;border-radius:9px;
  font-size:.95rem;font-weight:700;cursor:pointer;
  transition:opacity .2s,transform .15s,box-shadow .2s;
  font-family:inherit;
  box-shadow:0 4px 16px rgba(233,30,140,.3);
}
.btn-login:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 6px 20px rgba(233,30,140,.38);}
.erro{
  background:#fff5f5;color:#991b1b;
  padding:11px 14px;border-radius:8px;
  font-size:.83rem;margin-bottom:18px;
  border:1px solid #fecaca;
  display:flex;align-items:center;gap:8px;
}
.creds{
  margin-top:20px;padding:13px 15px;
  background:#fefce8;border:1px solid #fde68a;
  border-radius:9px;font-size:.76rem;color:#78350f;line-height:1.8;
}
.creds code{background:#fff;padding:1px 6px;border-radius:4px;font-weight:700;border:1px solid #fde68a;}
.creds-note{font-size:.69rem;opacity:.8;margin-top:6px;display:block;}
@media(max-width:820px){
  body{flex-direction:column;overflow:auto;height:auto;}
  .col-left{flex:none;padding:36px 24px 28px;min-height:38vh;}
  .col-right{flex:1;padding:28px 20px;}
  .login-brand h1{font-size:1.7rem;}
  .features{margin-top:20px;flex-direction:row;flex-wrap:wrap;gap:8px;}
}
</style>
</head>
<body>
<div class="col-left">
  <div class="login-brand">
    <span class="emoji">🐾</span>
    <h1>PetShop Manager</h1>
    <p>Sistema completo para gestão de pet shop: clientes, pets, agendamentos e muito mais.</p>
  </div>
  <div class="features">
    <div class="feat"><div class="feat-icon">📅</div>Agendamentos online</div>
    <div class="feat"><div class="feat-icon">🐶</div>Cadastro de pets</div>
    <div class="feat"><div class="feat-icon">💊</div>Controle veterinário</div>
    <div class="feat"><div class="feat-icon">📊</div>Relatórios completos</div>
  </div>
</div>
<div class="col-right">
  <div class="login-box">
    <h2>Entrar</h2>
    <p class="sub">Acesse sua conta para continuar</p>
    <?php if ($erro): ?>
    <div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" placeholder="seu@email.com" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Senha</label>
        <input type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Entrar no Sistema</button>
    </form>
    <div class="creds">
      <strong>🔑 Credenciais de teste:</strong><br>
      Cliente: <code>cliente@petshop.com</code> / <code>cliente123</code><br>
      Atendente: <code>atendente@petshop.com</code> / <code>atendente123</code><br>
      Veterinário: <code>vet@petshop.com</code> / <code>vet123</code>
      <span class="creds-note">Remova esta caixa antes de publicar em produção.</span>
    </div>
  </div>
</div>
</body>
</html>
