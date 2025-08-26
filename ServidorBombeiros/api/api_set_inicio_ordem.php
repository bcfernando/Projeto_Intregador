<?php
// NÃO imprimir nada antes do header
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funcoes.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $inicio_id = isset($_POST['inicio_id']) ? (int)$_POST['inicio_id'] : 0;
    if ($inicio_id <= 0) throw new Exception('ID inválido.');

    // valida se o bombeiro existe e é BC ativo
    $sql = "SELECT id, nome_completo FROM bombeiros WHERE id=? AND tipo='BC' AND ativo=1";
    $st  = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, "i", $inicio_id);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($st);
    if (!$row) throw new Exception('BC não encontrado/ativo.');

    // salva config simples com o novo "início da ordem"
    if (!function_exists('set_config')) {
        throw new Exception('Função set_config não encontrada.');
    }
    set_config('bc_inicio_ordem_id', $inicio_id, $conn);

    // recalcula “próximo sugerido” com base no início escolhido
    // regra: pegue a lista da ordem e retorne o que vem depois do início
    $ids = get_ordem_escolha_ids($conn);
    $proximo_nome = '(Nenhum)';
    if (!empty($ids)) {
        $idx = array_search($inicio_id, $ids);
        if ($idx === false) {
            // se o início escolhido não está na ordem (improvável), tenta o primeiro
            $p = $ids[0];
        } else {
            $p = $ids[(($idx + 1) % count($ids))];
        }
        $n = get_bombeiro_nome($p, $conn);
        if ($n) $proximo_nome = $n;
        // atualiza também o bc_da_vez_id para manter o sistema coerente (opcional)
        set_config('bc_da_vez_id', $p, $conn);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Início atualizado.',
        'proximo_nome' => $proximo_nome
    ]);
    exit;

} catch (Throwable $e) {
    error_log('api_set_inicio_ordem: ' . $e->getMessage());
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
    exit;
}
