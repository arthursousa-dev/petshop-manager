<?php
// ler_json/salvar_json: adaptador de persistência (interface JSON
// original, backend PostgreSQL via PDO com prepared statements).

use App\Config\Database;

require_once __DIR__ . '/app/Config/Database.php';

function ler_json($arquivo) {
    $pdo = Database::getConnection();

    switch ($arquivo) {
        case 'clientes.json':
            return $pdo->query('SELECT * FROM clientes ORDER BY nome')->fetchAll();

        case 'pets.json':
            return $pdo->query('SELECT * FROM pets ORDER BY nome')->fetchAll();

        case 'servicos.json':
            return $pdo->query('SELECT * FROM servicos ORDER BY nome')->fetchAll();

        case 'agendamentos.json':
            $stmt = $pdo->query(
                "SELECT id, cliente_id, pet_id, servico_id,
                        to_char(data_hora, 'YYYY-MM-DD\"T\"HH24:MI') AS data_hora,
                        status, observacoes, criado_em
                 FROM agendamentos ORDER BY data_hora DESC"
            );
            return $stmt->fetchAll();

        case 'perfis_sistema.json':
            return $pdo->query('SELECT * FROM perfis_bio ORDER BY email')->fetchAll();

        default:
            return [];
    }
}

function salvar_json($arquivo, $dados) {
    $pdo = Database::getConnection();

    try {
        $pdo->beginTransaction();

        switch ($arquivo) {
            case 'clientes.json':
                $idsAtuais = $pdo->query('SELECT id FROM clientes')->fetchAll(\PDO::FETCH_COLUMN);
                $upsert = $pdo->prepare(
                    'INSERT INTO clientes (id, nome, email, telefone, endereco)
                     VALUES (:id, :nome, :email, :telefone, :endereco)
                     ON CONFLICT (id) DO UPDATE SET
                        nome=EXCLUDED.nome, email=EXCLUDED.email, telefone=EXCLUDED.telefone, endereco=EXCLUDED.endereco'
                );
                foreach ($dados as $c) {
                    $upsert->execute([
                        ':id' => $c['id'], ':nome' => $c['nome'], ':email' => $c['email'] ?? null,
                        ':telefone' => $c['telefone'] ?? null, ':endereco' => $c['endereco'] ?? null,
                    ]);
                }
                $remover = array_diff($idsAtuais, array_column($dados, 'id'));
                if ($remover) {
                    $pdo->prepare('DELETE FROM clientes WHERE id = ANY(:ids)')->execute([':ids' => '{' . implode(',', $remover) . '}']);
                }
                break;

            case 'pets.json':
                $idsAtuais = $pdo->query('SELECT id FROM pets')->fetchAll(\PDO::FETCH_COLUMN);
                $upsert = $pdo->prepare(
                    'INSERT INTO pets (id, nome, cliente_id, especie, raca, peso, alergias, nascimento)
                     VALUES (:id, :nome, :cliente_id, :especie, :raca, :peso, :alergias, NULLIF(:nascimento, \'\')::date)
                     ON CONFLICT (id) DO UPDATE SET
                        nome=EXCLUDED.nome, cliente_id=EXCLUDED.cliente_id, especie=EXCLUDED.especie,
                        raca=EXCLUDED.raca, peso=EXCLUDED.peso, alergias=EXCLUDED.alergias, nascimento=EXCLUDED.nascimento'
                );
                foreach ($dados as $p) {
                    $upsert->execute([
                        ':id' => $p['id'], ':nome' => $p['nome'], ':cliente_id' => $p['cliente_id'],
                        ':especie' => $p['especie'], ':raca' => $p['raca'] ?? null,
                        ':peso' => $p['peso'] !== '' ? ($p['peso'] ?? null) : null,
                        ':alergias' => $p['alergias'] ?? null, ':nascimento' => $p['nascimento'] ?? '',
                    ]);
                }
                $remover = array_diff($idsAtuais, array_column($dados, 'id'));
                if ($remover) {
                    $pdo->prepare('DELETE FROM pets WHERE id = ANY(:ids)')->execute([':ids' => '{' . implode(',', $remover) . '}']);
                }
                break;

            case 'servicos.json':
                $idsAtuais = $pdo->query('SELECT id FROM servicos')->fetchAll(\PDO::FETCH_COLUMN);
                $upsert = $pdo->prepare(
                    'INSERT INTO servicos (id, nome, descricao, preco, duracao)
                     VALUES (:id, :nome, :descricao, :preco, :duracao)
                     ON CONFLICT (id) DO UPDATE SET
                        nome=EXCLUDED.nome, descricao=EXCLUDED.descricao, preco=EXCLUDED.preco, duracao=EXCLUDED.duracao'
                );
                foreach ($dados as $s) {
                    $upsert->execute([
                        ':id' => $s['id'], ':nome' => $s['nome'], ':descricao' => $s['descricao'] ?? null,
                        ':preco' => $s['preco'], ':duracao' => $s['duracao'] ?? null,
                    ]);
                }
                $remover = array_diff($idsAtuais, array_column($dados, 'id'));
                if ($remover) {
                    $pdo->prepare('DELETE FROM servicos WHERE id = ANY(:ids)')->execute([':ids' => '{' . implode(',', $remover) . '}']);
                }
                break;

            case 'agendamentos.json':
                $idsAtuais = $pdo->query('SELECT id FROM agendamentos')->fetchAll(\PDO::FETCH_COLUMN);
                $upsert = $pdo->prepare(
                    'INSERT INTO agendamentos (id, cliente_id, pet_id, servico_id, data_hora, status, observacoes)
                     VALUES (:id, :cliente_id, :pet_id, :servico_id, :data_hora, :status, :observacoes)
                     ON CONFLICT (id) DO UPDATE SET
                        cliente_id=EXCLUDED.cliente_id, pet_id=EXCLUDED.pet_id, servico_id=EXCLUDED.servico_id,
                        data_hora=EXCLUDED.data_hora, status=EXCLUDED.status, observacoes=EXCLUDED.observacoes'
                );
                foreach ($dados as $a) {
                    $upsert->execute([
                        ':id' => $a['id'], ':cliente_id' => $a['cliente_id'], ':pet_id' => $a['pet_id'],
                        ':servico_id' => $a['servico_id'], ':data_hora' => str_replace('T', ' ', $a['data_hora']),
                        ':status' => $a['status'], ':observacoes' => $a['observacoes'] ?? null,
                    ]);
                }
                $remover = array_diff($idsAtuais, array_column($dados, 'id'));
                if ($remover) {
                    $pdo->prepare('DELETE FROM agendamentos WHERE id = ANY(:ids)')->execute([':ids' => '{' . implode(',', $remover) . '}']);
                }
                break;

            case 'perfis_sistema.json':
                $upsert = $pdo->prepare(
                    'INSERT INTO perfis_bio (email, nome, telefone, bio)
                     VALUES (:email, :nome, :telefone, :bio)
                     ON CONFLICT (email) DO UPDATE SET
                        nome=EXCLUDED.nome, telefone=EXCLUDED.telefone, bio=EXCLUDED.bio'
                );
                foreach ($dados as $p) {
                    $upsert->execute([
                        ':email' => $p['email'], ':nome' => $p['nome'] ?? null,
                        ':telefone' => $p['telefone'] ?? null, ':bio' => $p['bio'] ?? null,
                    ]);
                }
                break;
        }

        $pdo->commit();
        return true;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('salvar_json falhou (' . $arquivo . '): ' . $e->getMessage());
        return false;
    }
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
    $pdo = Database::getConnection();
    $email = strtolower(trim($email));

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if (!$u) {
        return false;
    }

    if ($u['bloqueado_ate'] !== null && strtotime($u['bloqueado_ate']) > time()) {
        return false;
    }

    if (!password_verify($senha, $u['senha_hash'])) {
        $tentativas = (int) $u['tentativas_login'] + 1;
        $bloqueio = $tentativas >= 5 ? "now() + interval '15 minutes'" : 'NULL';
        $pdo->prepare("UPDATE usuarios SET tentativas_login = :t, bloqueado_ate = {$bloqueio} WHERE email = :email")
            ->execute([':t' => $tentativas, ':email' => $email]);
        return false;
    }

    $pdo->prepare('UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE email = :email')
        ->execute([':email' => $email]);

    $u['senha'] = $u['senha_hash']; // compatibilidade
    return $u;
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

/**
 * Fatia uma lista já filtrada/ordenada em páginas, lendo a página
 * atual de $_GET['pagina'].
 */
function paginar(array $itens, int $porPagina = 15): array {
    $total = count($itens);
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $paginaAtual = max(1, min($totalPaginas, (int) ($_GET['pagina'] ?? 1)));
    $offset = ($paginaAtual - 1) * $porPagina;

    return [
        'itens' => array_slice($itens, $offset, $porPagina),
        'pagina_atual' => $paginaAtual,
        'total_paginas' => $totalPaginas,
        'total_itens' => $total,
    ];
}

/**
 * Monta os controles de paginação, preservando os filtros já
 * aplicados na querystring.
 */
function controlesPaginacao(array $paginacao): string {
    if ($paginacao['total_paginas'] <= 1) {
        return '';
    }

    $paramsBase = $_GET;
    unset($paramsBase['pagina']);
    $linkPara = fn($p) => '?' . http_build_query(array_merge($paramsBase, ['pagina' => $p]));

    $atual = $paginacao['pagina_atual'];
    $totalPag = $paginacao['total_paginas'];

    $html = '<nav class="paginacao" aria-label="Navegação de páginas" style="display:flex;gap:6px;align-items:center;justify-content:center;margin-top:16px;flex-wrap:wrap">';
    $html .= $atual > 1 ? '<a href="' . $linkPara($atual - 1) . '">‹ Anterior</a>' : '<span style="opacity:.4">‹ Anterior</span>';
    for ($p = 1; $p <= $totalPag; $p++) {
        $html .= $p === $atual
            ? '<span aria-current="page" style="padding:6px 12px;font-weight:700">' . $p . '</span>'
            : '<a href="' . $linkPara($p) . '" style="padding:6px 12px">' . $p . '</a>';
    }
    $html .= $atual < $totalPag ? '<a href="' . $linkPara($atual + 1) . '">Próxima ›</a>' : '<span style="opacity:.4">Próxima ›</span>';
    $html .= '</nav>';

    return $html;
}
