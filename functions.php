<?php
function ler_json($arquivo) {
    if (!file_exists($arquivo)) return [];
    $fp = fopen($arquivo, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $conteudo = fread($fp, max(1, filesize($arquivo)));
    flock($fp, LOCK_UN);
    fclose($fp);
    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : [];
}

function salvar_json($arquivo, $dados) {
    $fp = fopen($arquivo, 'c');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function verificar_sessao() {
    if (empty($_SESSION['logado'])) {
        header('Location: login.php');
        exit;
    }
}

function formatar_moeda($valor) {
    return 'R$ ' . number_format(floatval($valor), 2, ',', '.');
}

function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function gerar_id() {
    return uniqid('', true);
}

function perfil_pode($perfis_permitidos) {
    $perfil = $_SESSION['perfil'] ?? '';
    return in_array($perfil, $perfis_permitidos);
}

/**
 * Autentica contra usuarios.json (senha em hash bcrypt).
 * Retorna o registro do usuário ou false.
 */
function autenticar($email, $senha) {
    $usuarios = ler_json('usuarios.json');
    foreach ($usuarios as $u) {
        if (strtolower($u['email']) === strtolower($email) && password_verify($senha, $u['senha'] ?? '')) {
            return $u;
        }
    }
    return false;
}

/**
 * Token CSRF por sessão.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_valido($token) {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}
