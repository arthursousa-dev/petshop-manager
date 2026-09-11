<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();
if (!perfil_pode(['atendente','vet'])) { header('Location: index.php'); exit; }

// DADOS BRUTOS
$agendamentos = ler_json('agendamentos.json');
$pets         = ler_json('pets.json');
$clientes     = ler_json('clientes.json');
$servicos     = ler_json('servicos.json');
$financeiro = ler_json('financeiro.json');

// FILTROS
$mes_filtro  = $_GET['mes']  ?? date('Y-m');
$aba         = $_GET['aba']  ?? 'agendamentos';

// MÊS ANTERIOR (para comparação)
$dt_atual    = DateTime::createFromFormat('Y-m', $mes_filtro);
$dt_ant      = clone $dt_atual; $dt_ant->modify('-1 month');
$mes_ant     = $dt_ant->format('Y-m');

// HELPER MAPS
$sv_map  = []; foreach ($servicos as $s) $sv_map[$s['id']]  = $s;
$pet_map = []; foreach ($pets     as $p) $pet_map[$p['id']] = $p;
$cl_map  = []; foreach ($clientes as $c) $cl_map[$c['id']]  = $c;

// FILTRAR MÊS
$ags_mes = array_values(array_filter($agendamentos,
    fn($a) => substr($a['data_hora'], 0, 7) === $mes_filtro));
$ags_ant = array_values(array_filter($agendamentos,
    fn($a) => substr($a['data_hora'], 0, 7) === $mes_ant));

// STATUS COUNTS
$status_k = ['agendado','em_atendimento','concluido','cancelado'];
$sc = array_fill_keys($status_k, 0);
foreach ($ags_mes as $a) { if (isset($sc[$a['status']])) $sc[$a['status']]++; }
$sc_ant = array_fill_keys($status_k, 0);
foreach ($ags_ant as $a) { if (isset($sc_ant[$a['status']])) $sc_ant[$a['status']]++; }

// AGEND POR DIA
$ags_por_dia = [];
foreach ($ags_mes as $a) {
    $d = substr($a['data_hora'], 0, 10);
    $ags_por_dia[$d] = ($ags_por_dia[$d] ?? 0) + 1;
}
ksort($ags_por_dia);

// RECEITA
$receita_total = 0; $receita_ant = 0;
$receita_por_dia = []; $sv_receita = []; $sv_count = [];

foreach ($ags_mes as $a) {
    $sid = $a['servico_id'];
    $sv_count[$sid] = ($sv_count[$sid] ?? 0) + 1;
    if ($a['status'] === 'concluido') {
        $preco = floatval($sv_map[$sid]['preco'] ?? 0);
        $receita_total += $preco;
        $d = substr($a['data_hora'], 0, 10);
        $receita_por_dia[$d] = ($receita_por_dia[$d] ?? 0) + $preco;
        $sv_receita[$sid] = ($sv_receita[$sid] ?? 0) + $preco;
    }
}
foreach ($ags_ant as $a) {
    if ($a['status'] === 'concluido') $receita_ant += floatval($sv_map[$a['servico_id']]['preco'] ?? 0);
}
ksort($receita_por_dia);
arsort($sv_count);


$entradas_fin=0; $saidas_fin=0;
foreach($financeiro as $f){
 if(substr($f['data'],0,7)===$mes_filtro){
   if($f['tipo']==='entrada') $entradas_fin += floatval($f['valor']);
   if($f['tipo']==='saida') $saidas_fin += floatval($f['valor']);
 }
}
$receita_total += $entradas_fin;
$lucro_liquido = $receita_total - $saidas_fin;

// MÉTRICAS
$concluidos    = $sc['concluido'];
$ticket_medio  = $concluidos > 0 ? $receita_total / $concluidos : 0;
$melhor_dia_v  = !empty($receita_por_dia) ? max($receita_por_dia) : 0;
$melhor_dia_d  = !empty($receita_por_dia) ? array_search($melhor_dia_v, $receita_por_dia) : '';
$total_mes     = count($ags_mes);
$total_ant     = count($ags_ant);

function pct_change($novo, $ant) {
    if ($ant == 0) return $novo > 0 ? '+100%' : '0%';
    $d = round(($novo - $ant) / $ant * 100);
    return ($d >= 0 ? '+' : '') . $d . '%';
}
function trend_class($novo, $ant) {
    if ($ant == 0) return $novo > 0 ? 'trend-up' : 'trend-neu';
    return $novo >= $ant ? 'trend-up' : 'trend-down';
}
function trend_icon($novo, $ant) {
    if ($ant == 0) return '●';
    return $novo >= $ant ? '▲' : '▼';
}

// DADOS PARA JS
$js_status_data   = array_values($sc);
$js_dias_labels   = array_map(fn($d) => date('d/m', strtotime($d)), array_keys($ags_por_dia));
$js_dias_data     = array_values($ags_por_dia);
$js_rec_labels    = array_map(fn($d) => date('d/m', strtotime($d)), array_keys($receita_por_dia));
$js_rec_data      = array_values($receita_por_dia);

$sv_chart_labels = []; $sv_chart_qtd = []; $sv_chart_rec = [];
foreach ($sv_count as $sid => $cnt) {
    $sv_chart_labels[] = $sv_map[$sid]['nome'] ?? $sid;
    $sv_chart_qtd[]    = $cnt;
    $sv_chart_rec[]    = $sv_receita[$sid] ?? 0;
}

// EXPORT TXT
if (isset($_GET['exportar'])) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio_' . $aba . '_' . $mes_filtro . '.txt"');
    echo "=== RELATÓRIO PETSHOP MANAGER ===\nPeríodo: $mes_filtro\nGerado: " . date('d/m/Y H:i') . "\n\n";
    if ($aba === 'agendamentos') {
        echo "AGENDAMENTOS DO MÊS\n" . str_repeat('-', 50) . "\n";
        foreach ($ags_mes as $a) {
            $pn = $pet_map[$a['pet_id']]['nome'] ?? '–';
            $sn = $sv_map[$a['servico_id']]['nome'] ?? '–';
            $cn = $cl_map[$a['cliente_id']]['nome'] ?? '–';
            echo date('d/m/Y H:i', strtotime($a['data_hora'])) . " | $pn | $sn | $cn | " . ucfirst($a['status']) . "\n";
        }
        echo "\nTotal: $total_mes | Concluídos: $concluidos\n";
    } elseif ($aba === 'receita') {
        echo "RECEITA DO MÊS\n" . str_repeat('-', 50) . "\n";
        foreach ($receita_por_dia as $dia => $val)
            echo date('d/m/Y', strtotime($dia)) . " -> " . formatar_moeda($val) . "\n";
        echo "\nTOTAL: " . formatar_moeda($receita_total) . "\n";
        echo "Ticket Médio: " . formatar_moeda($ticket_medio) . "\n";
    } elseif ($aba === 'servicos') {
        echo "SERVIÇOS REALIZADOS\n" . str_repeat('-', 50) . "\n";
        foreach ($sv_count as $sid => $cnt)
            echo ($sv_map[$sid]['nome'] ?? $sid) . " -> $cnt realizações -> " . formatar_moeda($sv_receita[$sid] ?? 0) . "\n";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PetShop – Relatórios</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">

  <!-- HEADER -->
  <div class="page-header">
    <h1>📊 Relatórios</h1>
    <div class="relat-controls">
      <form method="GET" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">
        <label style="font-size:.8rem;color:var(--text-2);font-weight:600">Período:</label>
        <input type="month" name="mes" value="<?= htmlspecialchars($mes_filtro) ?>" class="input-sm">
        <button type="submit" class="btn-secondary" style="padding:7px 14px">Filtrar</button>
      </form>
      <a href="?aba=<?= $aba ?>&mes=<?= $mes_filtro ?>&exportar=1" class="btn-primary" style="padding:7px 16px">⬇ Exportar</a>
    </div>
  </div>

  <!-- KPI CARDS -->
  <div class="cards-grid" style="margin-bottom:22px">
    <div class="card card card-relat-blue" style="--c-before:#3b82f6">
      <div class="card-icon" style="background:rgba(59,130,246,.1)">📅</div>
      <div class="card-info">
        <div class="card-value"><?= $total_mes ?></div>
        <div class="card-label">Agendamentos</div>
        <span class="trend <?= trend_class($total_mes,$total_ant) ?>">
          <?= trend_icon($total_mes,$total_ant) ?> <?= pct_change($total_mes,$total_ant) ?> vs. mês ant.
        </span>
      </div>
    </div>
    <div class="card card-relat-green" style="--c-before:#22c55e">
      <div class="card-icon" style="background:rgba(34,197,94,.1)">✅</div>
      <div class="card-info">
        <div class="card-value"><?= $concluidos ?></div>
        <div class="card-label">Concluídos</div>
        <span class="trend <?= trend_class($concluidos,$sc_ant['concluido']) ?>">
          <?= trend_icon($concluidos,$sc_ant['concluido']) ?> <?= pct_change($concluidos,$sc_ant['concluido']) ?> vs. mês ant.
        </span>
      </div>
    </div>
    <div class="card card-pink" style="--c-before:#e91e8c">
      <div class="card-icon" style="background:rgba(233,30,140,.1)">💰</div>
      <div class="card-info">
        <div class="card-value" style="font-size:1.2rem"><?= formatar_moeda($receita_total) ?></div>
        <div class="card-label">Receita do Mês</div>
        <span class="trend <?= trend_class($receita_total,$receita_ant) ?>">
          <?= trend_icon($receita_total,$receita_ant) ?> <?= pct_change($receita_total,$receita_ant) ?> vs. mês ant.
        </span>
      </div>
    </div>
    <div class="card card-relat-violet" style="--c-before:#7c3aed">
      <div class="card-icon" style="background:rgba(124,58,237,.1)">🎯</div>
      <div class="card-info">
        <div class="card-value" style="font-size:1.2rem"><?= formatar_moeda($ticket_medio) ?></div>
        <div class="card-label">Ticket Médio</div>
        <span class="trend trend-neu">● por atend. concluído</span>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <a href="?aba=agendamentos&mes=<?= $mes_filtro ?>"
       class="tab <?= $aba==='agendamentos'?'active':'' ?>">📅 Agendamentos</a>
    <a href="?aba=receita&mes=<?= $mes_filtro ?>"
       class="tab <?= $aba==='receita'?'active active-receita':'' ?>">💰 Receita</a>
    <a href="?aba=servicos&mes=<?= $mes_filtro ?>"
       class="tab <?= $aba==='servicos'?'active active-servicos':'' ?>">✂️ Serviços Top</a>
  </div>

  <!-- TAB: AGENDAMENTOS -->
  <?php if ($aba === 'agendamentos'): ?>

  <div class="charts-grid">
    <!-- Doughnut: Status -->
    <div class="chart-box">
      <div class="chart-title">🔵 Distribuição por Status</div>
      <div class="chart-canvas-wrap" style="height:260px">
        <canvas id="chart-status"></canvas>
      </div>
    </div>
    <!-- Bar: Por Dia -->
    <div class="chart-box">
      <div class="chart-title">📆 Agendamentos por Dia</div>
      <div class="chart-canvas-wrap" style="height:260px">
        <canvas id="chart-dias"></canvas>
      </div>
    </div>
  </div>

  <!-- Resumo rápido por status -->
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
    <?php
    $status_labels = ['agendado'=>'Agendado','em_atendimento'=>'Em Atend.','concluido'=>'Concluído','cancelado'=>'Cancelado'];
    $status_colors = ['agendado'=>'#3b82f6','em_atendimento'=>'#f59e0b','concluido'=>'#22c55e','cancelado'=>'#ef4444'];
    foreach ($sc as $sk => $sv_val):
    ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-left:3px solid <?= $status_colors[$sk] ?>;border-radius:8px;padding:10px 16px;flex:1;min-width:120px">
      <div style="font-size:1.3rem;font-weight:800;color:var(--text)"><?= $sv_val ?></div>
      <div style="font-size:.72rem;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px"><?= $status_labels[$sk] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabela -->
  <div class="section-box">
    <h2>📋 Lista de Agendamentos – <?= date('M/Y', strtotime($mes_filtro . '-01')) ?></h2>
    <?php if (empty($ags_mes)): ?>
      <p class="empty-msg">Nenhum agendamento neste mês.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>Data/Hora</th><th>Pet</th><th>Serviço</th><th>Cliente</th><th>Status</th><th>Obs.</th></tr></thead>
        <tbody>
        <?php foreach ($ags_mes as $a):
          $pn = htmlspecialchars($pet_map[$a['pet_id']]['nome']   ?? '–');
          $sn = htmlspecialchars($sv_map[$a['servico_id']]['nome'] ?? '–');
          $cn = htmlspecialchars($cl_map[$a['cliente_id']]['nome'] ?? '–');
        ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
          <td><strong><?= $pn ?></strong></td>
          <td><?= $sn ?></td>
          <td><?= $cn ?></td>
          <td><span class="status-badge status-<?= $a['status'] ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span></td>
          <td style="max-width:180px;word-break:break-word"><?= htmlspecialchars($a['observacoes'] ?? '–') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- TAB: RECEITA -->
  <?php elseif ($aba === 'receita'): ?>

  <!-- Area chart -->
  <div class="chart-box-full">
    <div class="chart-title">📈 Evolução da Receita – <?= date('M/Y', strtotime($mes_filtro . '-01')) ?></div>
    <div class="chart-canvas-wrap" style="height:300px">
      <canvas id="chart-receita"></canvas>
    </div>
  </div>

  <!-- Charts lado a lado: receita por serviço + mini KPIs -->
  <div class="charts-grid-2">
    <div class="chart-box">
      <div class="chart-title">🏆 Receita por Serviço</div>
      <div class="chart-canvas-wrap" style="height:250px">
        <canvas id="chart-sv-receita"></canvas>
      </div>
    </div>
    <div class="chart-box" style="display:flex;flex-direction:column;gap:12px;justify-content:center">
      <div class="chart-title">📌 Destaques do Mês</div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <div style="background:linear-gradient(135deg,rgba(34,197,94,.08),rgba(34,197,94,.02));border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:14px 16px">
          <div style="font-size:.68rem;color:#16a34a;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px">💰 Maior faturamento</div>
          <div style="font-size:1.3rem;font-weight:800;color:var(--text)"><?= formatar_moeda($melhor_dia_v) ?></div>
          <div style="font-size:.76rem;color:var(--text-3)"><?= $melhor_dia_d ? date('d \d\e F', strtotime($melhor_dia_d)) : '–' ?></div>
        </div>
        <div style="background:linear-gradient(135deg,rgba(233,30,140,.08),rgba(233,30,140,.02));border:1px solid rgba(233,30,140,.2);border-radius:10px;padding:14px 16px">
          <div style="font-size:.68rem;color:#e91e8c;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px">🎯 Ticket Médio</div>
          <div style="font-size:1.3rem;font-weight:800;color:var(--text)"><?= formatar_moeda($ticket_medio) ?></div>
          <div style="font-size:.76rem;color:var(--text-3)"><?= $concluidos ?> atendimentos concluídos</div>
        </div>
        <div style="background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(124,58,237,.02));border:1px solid rgba(124,58,237,.2);border-radius:10px;padding:14px 16px">
          <div style="font-size:.68rem;color:#7c3aed;text-transform:uppercase;font-weight:700;letter-spacing:.5px;margin-bottom:3px">📊 Receita Total</div>
          <div style="font-size:1.3rem;font-weight:800;color:var(--text)"><?= formatar_moeda($receita_total) ?></div>
          <div style="font-size:.76rem;color:var(--text-3)"><?= empty($receita_por_dia)?0:count($receita_por_dia) ?> dias com faturamento</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabela receita -->
  <div class="section-box">
    <h2>📋 Faturamento por Dia</h2>
    <?php if (empty($receita_por_dia)): ?>
      <p class="empty-msg">Sem receita registrada neste mês.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>Data</th><th>Atendimentos concluídos</th><th>Receita</th></tr></thead>
        <tbody>
        <?php foreach ($receita_por_dia as $dia => $val):
          $ags_dia = count(array_filter($ags_mes, fn($a) => substr($a['data_hora'],0,10)===$dia && $a['status']==='concluido'));
        ?>
        <tr>
          <td><?= date('d/m/Y (l)', strtotime($dia)) ?></td>
          <td><?= $ags_dia ?></td>
          <td class="preco-cell"><?= formatar_moeda($val) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:700;background:var(--surface-2)">
          <td colspan="2">Total do mês</td>
          <td class="preco-cell"><?= formatar_moeda($receita_total) ?></td>
        </tr>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <!-- TAB: SERVIÇOS TOP -->
  <?php elseif ($aba === 'servicos'): ?>

  <div class="chart-box-full">
    <div class="chart-title">🏅 Serviços mais realizados no mês</div>
    <div class="chart-canvas-wrap" style="height:300px">
      <canvas id="chart-svtop"></canvas>
    </div>
  </div>

  <div class="section-box">
    <h2>📋 Ranking de Serviços</h2>
    <?php if (empty($sv_count)): ?>
      <p class="empty-msg">Nenhum dado disponível.</p>
    <?php else:
      $rank = 0;
    ?>
      <table class="table">
        <thead><tr><th>#</th><th>Serviço</th><th>Realizações</th><th>Receita Gerada</th><th>Preço Unit.</th></tr></thead>
        <tbody>
        <?php foreach ($sv_count as $sid => $cnt):
          $rank++;
          $sv_obj = $sv_map[$sid] ?? null;
          $medals = ['🥇','🥈','🥉'];
        ?>
        <tr>
          <td style="font-size:1.1rem"><?= $medals[$rank-1] ?? $rank ?></td>
          <td><strong><?= htmlspecialchars($sv_obj['nome'] ?? '–') ?></strong><br>
            <span class="text-muted"><?= htmlspecialchars($sv_obj['descricao'] ?? '') ?></span></td>
          <td><?= $cnt ?> vez<?= $cnt !== 1 ? 'es' : '' ?></td>
          <td class="preco-cell"><?= formatar_moeda($sv_receita[$sid] ?? 0) ?></td>
          <td><?= formatar_moeda($sv_obj['preco'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <?php endif; ?>

</div><!-- /container -->

<script>
document.addEventListener('DOMContentLoaded', function() {

  // Global defaults
  Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
  Chart.defaults.color       = '#9898b0';

  const C = {
    orange : '#ff6b35',
    pink   : '#e91e8c',
    purple : '#7c3aed',
    green  : '#22c55e',
    blue   : '#3b82f6',
    yellow : '#f59e0b',
    red    : '#ef4444',
    teal   : '#0891b2'
  };

  // PLUGIN: Doughnut Center Text
  const centerText = {
    id: 'centerText',
    beforeDraw(chart) {
      if (chart.config.type !== 'doughnut') return;
      const { ctx, data, chartArea: { top, bottom, left, right } } = chart;
      const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
      if (!total) return;
      ctx.save();
      const cx = (left + right) / 2, cy = (top + bottom) / 2;
      ctx.font = 'bold 30px Inter, sans-serif';
      ctx.fillStyle = '#1a1a2e';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(total, cx, cy - 10);
      ctx.font = '600 11px Inter, sans-serif';
      ctx.fillStyle = '#9898b0';
      ctx.fillText('atendimentos', cx, cy + 12);
      ctx.restore();
    }
  };
  Chart.register(centerText);

  // CHART: STATUS DOUGHNUT
  const elStatus = document.getElementById('chart-status');
  if (elStatus) {
    new Chart(elStatus, {
      type: 'doughnut',
      data: {
        labels: ['Agendado', 'Em Atend.', 'Concluído', 'Cancelado'],
        datasets: [{
          data: <?= json_encode($js_status_data) ?>,
          backgroundColor: [C.blue, C.yellow, C.green, C.red],
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '62%',
        animation: { animateRotate: true, duration: 900 },
        plugins: {
          legend: {
            position: 'bottom',
            labels: { padding: 14, boxWidth: 11, boxHeight: 11,
                      borderRadius: 3, font: { size: 12 } }
          },
          tooltip: {
            callbacks: {
              label: ctx => {
                const tot = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const pct = tot > 0 ? Math.round(ctx.parsed / tot * 100) : 0;
                return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
              }
            }
          }
        }
      }
    });
  }

  // CHART: AGEND POR DIA
  const elDias = document.getElementById('chart-dias');
  if (elDias) {
    new Chart(elDias, {
      type: 'bar',
      data: {
        labels: <?= json_encode($js_dias_labels) ?>,
        datasets: [{
          label: 'Agendamentos',
          data: <?= json_encode($js_dias_data) ?>,
          backgroundColor: 'rgba(255,107,53,0.82)',
          hoverBackgroundColor: C.orange,
          borderRadius: 6, borderSkipped: false
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 800 },
        plugins: { legend: { display: false },
          tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} agendamento(s)` } }
        },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 },
               grid: { color: 'rgba(0,0,0,0.04)' } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // CHART: RECEITA ÁREA
  const elRec = document.getElementById('chart-receita');
  if (elRec) {
    const ctxR  = elRec.getContext('2d');
    const gradR = ctxR.createLinearGradient(0, 0, 0, 300);
    gradR.addColorStop(0, 'rgba(233,30,140,0.28)');
    gradR.addColorStop(1, 'rgba(233,30,140,0)');
    new Chart(elRec, {
      type: 'line',
      data: {
        labels: <?= json_encode($js_rec_labels) ?>,
        datasets: [{
          label: 'Receita (R$)',
          data: <?= json_encode($js_rec_data) ?>,
          borderColor: C.pink,
          backgroundColor: gradR,
          borderWidth: 2.5, fill: true, tension: 0.45,
          pointBackgroundColor: C.pink,
          pointBorderColor: '#fff', pointBorderWidth: 2,
          pointRadius: 5, pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 900 },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
            grid: { color: 'rgba(0,0,0,0.04)' }
          },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // CHART: RECEITA POR SERVIÇO (horizontal bar)
  const elSvRec = document.getElementById('chart-sv-receita');
  if (elSvRec) {
    const bkgs = [C.pink, C.orange, C.purple, C.blue, C.teal, C.green, C.yellow];
    new Chart(elSvRec, {
      type: 'bar',
      data: {
        labels: <?= json_encode($sv_chart_labels) ?>,
        datasets: [{
          label: 'Receita (R$)',
          data: <?= json_encode($sv_chart_rec) ?>,
          backgroundColor: bkgs.slice(0, <?= count($sv_chart_labels) ?>).map(c => c + 'cc'),
          hoverBackgroundColor: bkgs.slice(0, <?= count($sv_chart_labels) ?>),
          borderRadius: 6, borderSkipped: false
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 800 },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ' R$ ' + ctx.parsed.x.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            }
          }
        },
        scales: {
          x: { beginAtZero: true,
               ticks: { callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
               grid: { color: 'rgba(0,0,0,0.04)' } },
          y: { grid: { display: false } }
        }
      }
    });
  }

  // CHART: SERVIÇOS TOP (horizontal bar)
  const elSvTop = document.getElementById('chart-svtop');
  if (elSvTop) {
    const bkgs2 = [C.pink, C.orange, C.purple, C.blue, C.teal, C.green, C.yellow, C.red];
    new Chart(elSvTop, {
      type: 'bar',
      data: {
        labels: <?= json_encode($sv_chart_labels) ?>,
        datasets: [{
          label: 'Realizações',
          data: <?= json_encode($sv_chart_qtd) ?>,
          backgroundColor: bkgs2.slice(0, <?= count($sv_chart_labels) ?>).map(c => c + 'cc'),
          hoverBackgroundColor: bkgs2.slice(0, <?= count($sv_chart_labels) ?>),
          borderRadius: 6, borderSkipped: false
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        animation: { delay: (ctx) => ctx.dataIndex * 80, duration: 700 },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: { label: ctx => ` ${ctx.parsed.x} realização(ões)` }
          }
        },
        scales: {
          x: { beginAtZero: true, ticks: { stepSize: 1 },
               grid: { color: 'rgba(0,0,0,0.04)' } },
          y: { grid: { display: false } }
        }
      }
    });
  }

});
</script>
<footer class="footer">Desenvolvido por: Arthur</footer>
</body>
</html>
