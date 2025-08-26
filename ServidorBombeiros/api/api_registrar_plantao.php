<?php
declare(strict_types=1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

function respond(array $data, int $status = 200): void {
    http_response_code($status);
    $out = json_encode($data, JSON_UNESCAPED_UNICODE);
    if (ob_get_length()) { ob_clean(); } // remove warnings/HTML antes do JSON
    header('Content-Type: application/json; charset=utf-8');
    echo $out;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Método não permitido.'], 405);
}

$bombeiro_id = isset($_POST['bombeiro_id']) ? intval($_POST['bombeiro_id']) : 0;
$data        = $_POST['data']  ?? '';
$turno       = $_POST['turno'] ?? '';

if (!$bombeiro_id || !$data || !$turno) {
    respond(['success' => false, 'message' => 'Dados incompletos.'], 400);
}

try {
    mysqli_begin_transaction($conn, MYSQLI_TRANS_START_READ_WRITE);

    // Tipo do bombeiro
    $stmt_tipo = mysqli_prepare($conn, "SELECT tipo FROM bombeiros WHERE id = ?");
    mysqli_stmt_bind_param($stmt_tipo, "i", $bombeiro_id);
    mysqli_stmt_execute($stmt_tipo);
    $bombeiro = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_tipo));
    mysqli_stmt_close($stmt_tipo);

    if (!$bombeiro) {
        mysqli_rollback($conn);
        respond(['success' => false, 'message' => 'Bombeiro não encontrado.'], 404);
    }

    // ====== Regra das vagas ======
    if ($turno === 'I_SUB') {
        // Somente quando NÃO há fixo válido (ausente ou com exceção)
        $fixo = get_fixo_de_servico($data, $conn);
        $fixo_invalido = true;
        if ($fixo) {
            $tem_excecao = verificar_excecao_fixo((int)$fixo['id'], $data, $conn);
            $fixo_invalido = ($tem_excecao === true);
        }
        if (!$fixo_invalido) {
            mysqli_rollback($conn);
            respond(['success' => false, 'message' => 'A vaga I_SUB só existe quando não há fixo válido no dia.'], 400);
        }

        // Garante 1 vaga I_SUB por dia
        $q = "SELECT id FROM plantoes WHERE data = ? AND turno = 'I_SUB' FOR UPDATE";
        $stmt = mysqli_prepare($conn, $q);
        mysqli_stmt_bind_param($stmt, "s", $data);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $ja_ocupado = mysqli_num_rows($res) > 0;
        mysqli_stmt_close($stmt);

        if ($ja_ocupado) {
            mysqli_rollback($conn);
            respond(['success' => false, 'message' => 'A vaga Integral (Substituto) já está ocupada.'], 409);
        }
    } else if ($bombeiro['tipo'] === 'BC') {
        // Vagas BC: D = 1, N = 1, I consome D e N
        $vagas_bc_d = 1;
        $vagas_bc_n = 1;

        $sql_vagas = "SELECT p.turno
                      FROM plantoes p
                      JOIN bombeiros b ON p.bombeiro_id = b.id
                      WHERE p.data = ? AND b.tipo = 'BC' FOR UPDATE";
        $stmt_vagas = mysqli_prepare($conn, $sql_vagas);
        mysqli_stmt_bind_param($stmt_vagas, "s", $data);
        mysqli_stmt_execute($stmt_vagas);
        $result_vagas = mysqli_stmt_get_result($stmt_vagas);

        while ($plantao_existente = mysqli_fetch_assoc($result_vagas)) {
            if ($plantao_existente['turno'] === 'D') $vagas_bc_d--;
            elseif ($plantao_existente['turno'] === 'N') $vagas_bc_n--;
            elseif ($plantao_existente['turno'] === 'I') { $vagas_bc_d--; $vagas_bc_n--; }
        }
        mysqli_stmt_close($stmt_vagas);

        $bloqueado =
            ($turno === 'D' && $vagas_bc_d <= 0) ||
            ($turno === 'N' && $vagas_bc_n <= 0) ||
            ($turno === 'I' && ($vagas_bc_d <= 0 || $vagas_bc_n <= 0));

        if ($bloqueado) {
            mysqli_rollback($conn);
            respond(['success' => false, 'message' => 'Não há mais vagas de BC para o turno selecionado.'], 409);
        }
    }
    // ====== Fim das regras ======

    // Inserção
    $sql_insert = "INSERT INTO plantoes (bombeiro_id, data, turno) VALUES (?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);
    mysqli_stmt_bind_param($stmt_insert, "iss", $bombeiro_id, $data, $turno);

    if (!mysqli_stmt_execute($stmt_insert)) {
        $errno = mysqli_errno($conn);
        mysqli_rollback($conn);
        if ($errno == 1062) {
            respond(['success' => false, 'message' => 'Este bombeiro já está escalado para este dia/turno.'], 409);
        }
        respond(['success' => false, 'message' => 'Erro no banco ao registrar o plantão.'], 500);
    }

    mysqli_commit($conn);
    mysqli_stmt_close($stmt_insert);
    respond(['success' => true, 'message' => 'Plantão registrado com sucesso!']);

} catch (Throwable $e) {
    if ($conn && mysqli_errno($conn) === 0) { mysqli_rollback($conn); }
    respond(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
}
