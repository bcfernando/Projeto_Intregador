<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/require_login.php';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funcoes.php';

// --- Lógica do Calendário ---
$mes_atual = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$ano_atual = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

if ($mes_atual < 1 || $mes_atual > 12) $mes_atual = date('m');
if ($ano_atual < 1970 || $ano_atual > 2100) $ano_atual = date('Y');

$timestamp_primeiro_dia = mktime(0, 0, 0, $mes_atual, 1, $ano_atual);
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
$nome_mes = ucfirst(strftime('%B', $timestamp_primeiro_dia));
$dias_no_mes = date('t', $timestamp_primeiro_dia);
$dia_semana_primeiro = date('w', $timestamp_primeiro_dia);

$mes_anterior = $mes_atual - 1; $ano_anterior = $ano_atual;
if ($mes_anterior < 1) { $mes_anterior = 12; $ano_anterior--; }

$mes_proximo = $mes_atual + 1; $ano_proximo = $ano_atual;
if ($mes_proximo > 12) { $mes_proximo = 1; $ano_proximo++; }

$data_inicio_mes = sprintf('%04d-%02d-01', $ano_atual, $mes_atual);
$data_fim_mes    = sprintf('%04d-%02d-%02d', $ano_atual, $mes_atual, $dias_no_mes);

// Carrega plantões do mês
$plantoes_mes = [];
$fixos_servico_mes = [];
$vagas_dia_mes = [];

$sql_plantoes_mes = "SELECT p.id as plantao_id, p.bombeiro_id, p.data, p.turno, b.nome_completo, b.tipo
                     FROM plantoes p
                     JOIN bombeiros b ON p.bombeiro_id = b.id
                     WHERE p.data BETWEEN ? AND ? AND b.ativo = 1";

if ($stmt_plantoes = mysqli_prepare($conn, $sql_plantoes_mes)) {
    mysqli_stmt_bind_param($stmt_plantoes, "ss", $data_inicio_mes, $data_fim_mes);
    mysqli_stmt_execute($stmt_plantoes);
    $result_plantoes = mysqli_stmt_get_result($stmt_plantoes);
    while ($row = mysqli_fetch_assoc($result_plantoes)) {
        if (!isset($plantoes_mes[$row['data']])) $plantoes_mes[$row['data']] = [];
        // indexa por bombeiro_id para agrupar; valor contém todos os campos
        $plantoes_mes[$row['data']][$row['bombeiro_id']] = $row;
    }
    mysqli_free_result($result_plantoes);
    mysqli_stmt_close($stmt_plantoes);
} else {
    echo "Erro ao buscar plantões: " . mysqli_error($conn);
}

// === Cálculo de vagas por dia (inclui I_SUB) ===
for ($dia = 1; $dia <= $dias_no_mes; $dia++) {
    $data_corrente = sprintf("%04d-%02d-%02d", $ano_atual, $mes_atual, $dia);

    // Fixo do dia
    $fixo_dia = get_fixo_de_servico($data_corrente, $conn);
    $fixos_servico_mes[$data_corrente] = $fixo_dia;

    // Vagas padrão de BC (1 por turno)
    $vagas_bc_d = 1;
    $vagas_bc_n = 1;

    // Detecta se o fixo é inválido (ausente ou com exceção) para liberar a vaga I_SUB
    $tem_excecao = false;
    if ($fixo_dia) {
        $tem_excecao = verificar_excecao_fixo((int)$fixo_dia['id'], $data_corrente, $conn);
    }
    $fixo_invalido = empty($fixo_dia) || $tem_excecao;

    // Vaga adicional: substituto integral do fixo (I_SUB) se não há fixo válido
    $vaga_fixo_integral = $fixo_invalido ? 1 : 0;

    // Desconta vagas conforme plantões já existentes
    if (isset($plantoes_mes[$data_corrente])) {
        foreach ($plantoes_mes[$data_corrente] as $plantao) {
            if ($plantao['tipo'] == 'BC') {
                if ($plantao['turno'] == 'D') {
                    $vagas_bc_d--;
                } elseif ($plantao['turno'] == 'N') {
                    $vagas_bc_n--;
                } elseif ($plantao['turno'] == 'I') {
                    $vagas_bc_d--;
                    $vagas_bc_n--;
                } elseif ($plantao['turno'] == 'I_SUB') {
                    $vaga_fixo_integral--;
                }
            }
        }
    }

    $vagas_dia_mes[$data_corrente] = [
        'D'     => max(0, $vagas_bc_d),
        'N'     => max(0, $vagas_bc_n),
        'I_SUB' => max(0, $vaga_fixo_integral),
    ];
}

// Ordem sugerida
$ordem_mes_ids = get_ordem_escolha_ids($conn);
$primeiro_da_ordem_nome = '(Nenhum BC ativo)';
if (!empty($ordem_mes_ids)) {
    $primeiro_nome_temp = get_bombeiro_nome($ordem_mes_ids[0], $conn);
    if ($primeiro_nome_temp) $primeiro_da_ordem_nome = $primeiro_nome_temp;
}

$proximo_sugerido_id = get_proximo_a_escolher_id($conn);
$proximo_sugerido_nome = '(Nenhum)';
if ($proximo_sugerido_id) {
    $nome_temp = get_bombeiro_nome($proximo_sugerido_id, $conn);
    if ($nome_temp) {
        $proximo_sugerido_nome = $nome_temp;
    } else {
        set_config('bc_da_vez_id', null, $conn);
        $proximo_sugerido_id = null;
    }
}

$dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escala de Plantões - <?php echo htmlspecialchars($nome_mes) . ' ' . $ano_atual; ?></title>
    <link rel="stylesheet" href="css/style.css">

    <!-- KIT FAVICON COMPLETO -->
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
</head>
<body>
    <div class="main-title-container">
        <img src="img/logo.png" alt="Logo do Sistema de Escala" class="main-logo">
        <h1>Escala de Plantões</h1>
    </div>

    <div class="controles-escala">
        <div>
            <p style="margin-bottom:8px;">
                Ordem deste mês iniciaria com:
                <select id="selectInicioOrdem" style="max-width:320px;">
                    <?php
                    if (!empty($ordem_mes_ids)) {
                        foreach ($ordem_mes_ids as $idx => $bid) {
                            $nome = get_bombeiro_nome($bid, $conn);
                            if (!$nome) continue;
                            echo '<option value="'.(int)$bid.'">'.htmlspecialchars($nome).'</option>';
                        }
                    } else {
                        echo '<option value="">(Nenhum BC ativo)</option>';
                    }
                    ?>
                </select>
                <small style="opacity:.75;margin-left:6px;"></small>
            </p>
        
            <p>
                Próximo Sugerido na Ordem:
                <strong id="displayProximoSugerido"><?php echo htmlspecialchars($proximo_sugerido_nome); ?></strong>
                <button id="btnAvancarOrdem" <?php echo !$proximo_sugerido_id ? 'disabled' : ''; ?>>
                    Avançar Ordem Sugerida
                </button>
            </p>
        </div>
        
        <script>
        // Seleciona no combo o início salvo (se houver)
        (function() {
            const sel = document.getElementById('selectInicioOrdem');
            if (!sel) return;
            <?php
                $inicio_salvo_id = get_config('bc_inicio_ordem_id', $conn);
                $inicio_js = $inicio_salvo_id ? (int)$inicio_salvo_id : 0;
            ?>
            const inicioSalvo = <?php echo json_encode($inicio_js); ?>;
            if (inicioSalvo) {
                const opt = sel.querySelector(`option[value="${inicioSalvo}"]`);
                if (opt) opt.selected = true;
            }
        })();
        </script>
        <div style="text-align: right;">
            <a href="exportar_tabela_formatada.php?month=<?php echo $mes_atual; ?>&year=<?php echo $ano_atual; ?>" class="button-link btn-secondary" target="_blank" title="Exportar escala em formato de tabela">
                <span class="turno-icon">📄</span> Exportar
            </a>
            <a href="bombeiros.php" class="button-link btn-secondary" style="margin-left: 10px;" title="Adicionar ou editar bombeiros">
                <span class="turno-icon">⚙️</span> Gerenciar
            </a>
            <!-- Botão de Sincronização Google (da sua versão) -->
            <button id="btnSyncGoogle" class="button-link btn-secondary" style="margin-left:10px" title="Sincronizar com Google">
              <span class="turno-icon">🔀</span> Sincronizar
            </button>

            <button id="theme-toggle" class="button-link btn-secondary" style="margin-left: 10px;" title="Alternar tema claro/escuro">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: -0.1em;">
                    <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311a1.464 1.464 0 0 1-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872-2.105l-.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.858 2.929 2.929 0 0 1 0 5.858z"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="calendar-nav">
        <a href="?month=<?php echo $mes_anterior; ?>&year=<?php echo $ano_anterior; ?>" class="button-link">« Mês Anterior</a>
        <h2><?php echo htmlspecialchars($nome_mes) . ' ' . $ano_atual; ?></h2>
        <a href="?month=<?php echo $mes_proximo; ?>&year=<?php echo $ano_proximo; ?>" class="button-link">Próximo Mês »</a>
    </div>

    <table class="calendar">
        <thead>
            <tr>
                <?php foreach ($dias_semana as $dia_nome): ?>
                    <th><?php echo $dia_nome; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                for ($i = 0; $i < $dia_semana_primeiro; $i++) {
                    echo '<td class="other-month"></td>';
                }
                $dia_atual_semana = $dia_semana_primeiro;

                for ($dia = 1; $dia <= $dias_no_mes; $dia++):
                    $data_corrente   = sprintf("%04d-%02d-%02d", $ano_atual, $mes_atual, $dia);
                    $fixo_do_dia     = $fixos_servico_mes[$data_corrente] ?? null;
                    $plantoes_do_dia = $plantoes_mes[$data_corrente] ?? [];
                    $vagas_do_dia    = $vagas_dia_mes[$data_corrente];

                    $is_weekend = ($dia_atual_semana == 0 || $dia_atual_semana == 6);

                    // Status geral considera vagas D/N de BC
                    $status_dot_class = ($vagas_do_dia['D'] > 0 || $vagas_do_dia['N'] > 0) ? 'green' : 'red';
                    $pode_integral    = ($vagas_do_dia['D'] > 0 && $vagas_do_dia['N'] > 0);
                ?>
                <td class="<?php echo $is_weekend ? 'weekend' : ''; ?>">
                    <span class="day-number"><?php echo $dia; ?></span>

                    <div class="cell-icons-top">
                        <button class="btn-detalhes" data-date="<?php echo $data_corrente; ?>" title="Ver detalhes e registrar plantão">👁️</button>
                        <span class="status-dot <?php echo $status_dot_class; ?>" title="Status Vagas BC (Verde=Vagas, Vermelho=Lotado)"></span>

                        <div class="cell-availability-info">
                            <span class="availability-slot availability-D <?php echo ($vagas_do_dia['D'] > 0) ? 'disponivel' : 'lotado'; ?>"
                                  title="Vagas BC Diurnas: <?php echo $vagas_do_dia['D']; ?>">
                                <span class="turno-icon turno-D">☀️</span> <?php echo $vagas_do_dia['D']; ?>
                            </span>

                            <span class="availability-slot availability-N <?php echo ($vagas_do_dia['N'] > 0) ? 'disponivel' : 'lotado'; ?>"
                                  title="Vagas BC Noturnas: <?php echo $vagas_do_dia['N']; ?>">
                                <span class="turno-icon turno-N">★</span> <?php echo $vagas_do_dia['N']; ?>
                            </span>

                            <?php if (!empty($vagas_do_dia['I_SUB']) && $vagas_do_dia['I_SUB'] > 0): ?>
                                <span class="availability-slot availability-ISUB disponivel" title="Vaga Integral (Substituto do Fixo)">
                                    <span class="turno-icon turno-I">📅</span> SUB
                                </span>
                            <?php endif; ?>

                            <?php if ($pode_integral): ?>
                                <span class="availability-slot availability-I disponivel" title="Vaga Integral BC (24h) Disponível">
                                    <span class="turno-icon turno-I">📅</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="plantoes-do-dia">
                        <?php if ($fixo_do_dia): ?>
                            <span class="plantao-item fixo">
                                <?php echo htmlspecialchars(abreviar_nome($fixo_do_dia['nome_completo'], 30)); ?>
                                <?php echo get_turno_icon(null, true); ?>
                            </span>
                        <?php endif; ?>

                        <?php foreach ($plantoes_do_dia as $plantao): ?>
                            <span class="plantao-item bc">
                                <?php echo htmlspecialchars(abreviar_nome($plantao['nome_completo'], 30)); ?>
                                <?php echo get_turno_icon($plantao['turno']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </td>
                <?php
                    $dia_atual_semana++;
                    if ($dia_atual_semana > 6) {
                        echo '</tr><tr>';
                        $dia_atual_semana = 0;
                    }
                endfor;

                while ($dia_atual_semana > 0 && $dia_atual_semana <= 6) {
                    echo '<td class="other-month"></td>';
                    $dia_atual_semana++;
                }
                ?>
            </tr>
        </tbody>
    </table>

    <!-- MODAL DE DETALHES -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modalDate">Data do Plantão</h2>

            <h3>Ocupantes do Dia:</h3>
            <ul id="modalOcupantesList"><!-- preenchido via JS --></ul>

            <div id="modalSelecao" style="display:none;">
                <h3>Registrar Novo Plantão</h3>
                <p id="modalSugestao">Sugestão: ...</p>

                <select id="modalSelectBombeiro">
                    <option value="">-- Selecione um Bombeiro --</option>
                </select>

                <div class="modal-buttons">
                    <button id="modalBtnD" disabled>
                        ☀️ Diurno <span class="vagas"></span>
                    </button>
                    <button id="modalBtnN" disabled>
                        ★ Noturno <span class="vagas"></span>
                    </button>
                    <button id="modalBtnI" disabled>
                        📅 Integral <span class="vagas"></span>
                    </button>
                    <button id="modalBtnISUB" disabled>
                        📅 Integral (Substituto) <span class="vagas"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <!-- MODAL DE CONFIRMAÇÃO -->
    <div id="confirm-modal" class="confirm-modal-overlay" style="display: none;">
        <div class="confirm-modal-box">
            <p id="confirm-modal-text">Você tem certeza?</p>
            <div class="confirm-modal-buttons">
                <button id="confirm-modal-btn-yes" class="btn-confirm-yes">Sim</button>
                <button id="confirm-modal-btn-no" class="btn-confirm-no">Não</button>
            </div>
        </div>
    </div>

    <footer>
        <p>Sistema de Escala de Plantões - Desenvolvido por Luiz Fernando Hohn</p>
    </footer>

    <!-- Mantém a sessão ativa somente se houver interação do usuário (do colega) -->
    <script>
      let active = false;
      ['mousemove','keydown','click','scroll','touchstart','touchmove'].forEach(ev=>{
        addEventListener(ev, ()=> active = true, {passive:true});
      });
      setInterval(()=>{
        if (!active) return;
        active = false;
        fetch('session_ping.php', {method:'POST', credentials:'same-origin'});
      }, 120_000); // a cada 120s, se houve atividade
    </script>

    <!-- Rotina de Sincronização com Google (da sua versão) -->
    <script>
    (function () {
      const btn = document.getElementById('btnSyncGoogle');
      if (!btn) return;

      btn.addEventListener('click', async () => {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Sincronizando...';

        const m = <?php echo (int)$mes_atual; ?>;
        const y = <?php echo (int)$ano_atual; ?>;

        try {
          const resp = await fetch(`sync_to_google.php?month=${m}&year=${y}&auto=1`, { method: 'GET' });
          const raw = await resp.text();
          let data;
          try { data = JSON.parse(raw); } catch { throw new Error('Retorno não-JSON: ' + raw.slice(0,200)); }

          const ok = Array.isArray(data) && data.some(x => x && x.ok === true);
          showToast(ok ? 'Sincronização concluída.' : 'Sincronização terminou com erro. Veja o console.');
          console.log('sync_to_google.php retorno:', data);
        } catch (e) {
          showToast('Erro na sincronização: ' + (e.message || e));
        } finally {
          btn.disabled = false;
          btn.innerHTML = original;
        }
      });
    })();
    </script>

    <script src="js/script.js"></script>
</body>
</html>
