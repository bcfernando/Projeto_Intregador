<?php
// ARQUIVO DE TESTES AUTOMATIZADOS (teste_automatizado.php)
// ATENÇÃO: Este script irá apagar e recriar dados. Execute em um ambiente de desenvolvimento.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre style='font-family: monospace; background-color: #111; color: #eee; padding: 20px; border-radius: 5px;'>";
echo "========================================\n";
echo " INICIANDO TESTES AUTOMATIZADOS DO SISTEMA\n";
echo "========================================\n\n";

$tests_run = 0;
$failures = 0;

function test_assert($condition, $message) {
    global $tests_run, $failures;
    $tests_run++;
    if ($condition) {
        echo "<span style='color: #2ecc71;'>[SUCESSO]</span> $message\n";
    } else {
        echo "<span style='color: #e74c3c;'>[FALHA]</span>   $message\n";
        $failures++;
    }
}

function test_assert_equals($expected, $actual, $message) {
    global $tests_run, $failures;
    $tests_run++;
    $expected_export = var_export($expected, true);
    $actual_export = var_export($actual, true);
    if ($expected == $actual) { // Usamos '==' para arrays, '===' para outros tipos
        if ($expected === $actual) {
            echo "<span style='color: #2ecc71;'>[SUCESSO]</span> $message\n";
        } else {
            echo "<span style='color: #2ecc71;'>[SUCESSO]</span> $message (Esperado: $expected_export, Recebido: $actual_export)\n";
        }
    } else {
        echo "<span style='color: #e74c3c;'>[FALHA]</span>   $message (Esperado: $expected_export, Recebido: $actual_export)\n";
        $failures++;
    }
}

// --- Funções de Simulação de API ---
function call_api_post($script_path, $post_data) {
    global $conn; // CORREÇÃO: Torna a conexão global visível dentro da função.

    $original_post = $_POST;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = $post_data;

    ob_start();
    include $script_path;
    $output = ob_get_clean();

    $_POST = $original_post;
    unset($_SERVER['REQUEST_METHOD']);

    $decoded = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Resposta API inválida (não-JSON)', 'raw_output' => $output];
    }
    return $decoded;
}

function call_api_get($script_path, $get_data) {
    global $conn; // CORREÇÃO: Torna a conexão global visível dentro da função.

    $original_get = $_GET;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $get_data;

    ob_start();
    include $script_path;
    $output = ob_get_clean();

    $_GET = $original_get;
    unset($_SERVER['REQUEST_METHOD']);

    $decoded = json_decode($output, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Resposta API inválida (não-JSON)', 'raw_output' => $output];
    }
    return $decoded;
}


echo "--- Configurando Ambiente de Teste ---\n";
require_once __DIR__ . '/includes/db.php';
if (!$conn || $conn->connect_error) {
    die("[ERRO CRÍTICO] Falha ao conectar ao banco de dados: " . ($conn ? $conn->connect_error : mysqli_connect_error()) . "\n</pre>");
}
require_once __DIR__ . '/includes/funcoes.php';

function reset_database(mysqli $conn_param) {
    echo "Limpando tabelas de dados...\n";
    $conn_param->query("SET FOREIGN_KEY_CHECKS = 0;");
    $conn_param->query("TRUNCATE TABLE plantoes;");
    $conn_param->query("TRUNCATE TABLE excecoes_ciclo_fixo;");
    $conn_param->query("TRUNCATE TABLE configuracoes;");
    $conn_param->query("TRUNCATE TABLE bombeiros;");
    $conn_param->query("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Inserindo dados de teste...\n";
    $sql_insert = "
    INSERT INTO `bombeiros` (`id`, `nome_completo`, `tipo`, `ativo`, `fixo_ref_data`, `fixo_ref_dia_ciclo`) VALUES
    (1, 'ANDREA ZART', 'BC', 1, NULL, NULL),
    (2, 'ANELI MIOTTO TERNUS', 'BC', 1, NULL, NULL),
    (3, 'ANGELICA BOETTCHER', 'BC', 1, NULL, NULL),
    (4, 'BRIAN DEIV HENRICH COSMAN', 'Fixo', 1, '2025-08-01', 1),
    (5, 'CLEIMAR BOETTCHER', 'Fixo', 1, '2025-08-01', 3),
    (6, 'CLEIDIVAN IVAN BENEDIX', 'BC', 1, NULL, NULL),
    (7, 'CRISTIAN KONCZIKOSKI', 'Fixo', 1, '2025-08-01', 2),
    (8, 'CRISTIANE BOETTCHER', 'BC', 1, NULL, NULL),
    (12, 'KELVIN KERKHOFF', 'Fixo', 1, '2025-08-01', 4);
    ";
    
    if (!$conn_param->multi_query($sql_insert)) {
        echo "Erro ao inserir dados: " . $conn_param->error . "\n";
        return false;
    }
    while ($conn_param->more_results() && $conn_param->next_result()) {;}

    set_config('ultimo_bc_iniciou_mes', '2', $conn_param); // Aneli
    set_config('bc_da_vez_id', '3', $conn_param); // Angelica
    return true;
}

if (!reset_database($conn)) {
    die("[ERRO CRÍTICO] Falha ao resetar o banco de dados.\n</pre>");
}
echo "Ambiente de teste configurado.\n\n";

// --- Testes de Funções PHP Diretas ---
echo "--- Testes: Funções de Ciclo Fixo ---\n";
test_assert_equals('BRIAN DEIV HENRICH COSMAN', get_fixo_de_servico('2025-08-01', $conn)['nome_completo'], "get_fixo_de_servico: Brian em 01/08");
test_assert_equals('KELVIN KERKHOFF', get_fixo_de_servico('2025-08-02', $conn)['nome_completo'], "get_fixo_de_servico: Kelvin em 02/08");
test_assert_equals(null, get_fixo_de_servico('2025-08-01', $conn)['tem_excecao'] ?? null, "get_fixo_de_servico: Brian sem exceção inicial");
echo "\n";

echo "--- Testes: Ordem de Escolha BC ---\n";
test_assert_equals('3', get_proximo_a_escolher_id($conn), "Próximo a escolher é Angelica (ID 3)");
test_assert_equals('6', avancar_e_salvar_proximo_id($conn), "Avançar ordem leva para Cleidivan (ID 6)");
test_assert_equals('6', get_proximo_a_escolher_id($conn), "Próximo agora é Cleidivan (ID 6)");
reset_database($conn); 
echo "Ordem resetada para testes de API.\n\n";

// --- Testes: Simulação de APIs ---
echo "--- Testes: Simulação de APIs (Nova Lógica de Vagas) ---\n";
$data_teste = '2025-08-01'; // Dia em que Brian (ID 4) está de serviço
define('ID_BC_ANDREA', 1);
define('ID_FIXO_BRIAN', 4);

// 1. GetDetails (Inicial): Brian (Fixo) está de serviço. Deve haver 1 vaga de BC disponível.
echo "1. Testando GetDetails (estado inicial)...\n";
$detalhes1 = call_api_get(__DIR__ . '/api/api_get_details.php', ['date' => $data_teste]);
test_assert($detalhes1['success'] ?? false, "API GetDetails (inicial): success para $data_teste");
test_assert_equals(ID_FIXO_BRIAN, $detalhes1['fixo_calculado']['id'] ?? null, "API GetDetails (inicial): Brian (Fixo) está presente");
test_assert_equals(1, $detalhes1['vagas']['D'] ?? -1, "API GetDetails (inicial): Vaga BC Diurna é 1 (mesmo com Fixo)");
test_assert_equals(1, $detalhes1['vagas']['N'] ?? -1, "API GetDetails (inicial): Vaga BC Noturna é 1 (mesmo com Fixo)");
echo "\n";

// 2. RegistrarPlantao (BC): Adicionando Andrea (BC) no turno diurno.
echo "2. Testando RegistrarPlantao para um BC...\n";
$reg_plantao_bc = call_api_post(__DIR__ . '/api/api_registrar_plantao.php', ['bombeiro_id' => ID_BC_ANDREA, 'data' => $data_teste, 'turno' => 'D']);
test_assert($reg_plantao_bc['success'] ?? false, "API RegistrarPlantao: Andrea (BC) no turno Diurno");
echo "\n";

// 3. GetDetails (Após BC): Vaga BC diurna deve ser 0.
echo "3. Testando GetDetails (após adicionar BC)...\n";
$detalhes2 = call_api_get(__DIR__ . '/api/api_get_details.php', ['date' => $data_teste]);
test_assert(ID_FIXO_BRIAN, $detalhes2['fixo_calculado']['id'] ?? null, "API GetDetails (após BC): Brian (Fixo) ainda está presente");
test_assert_equals(0, $detalhes2['vagas']['D'] ?? -1, "API GetDetails (após BC): Vaga BC Diurna agora é 0");
test_assert_equals(1, $detalhes2['vagas']['N'] ?? -1, "API GetDetails (após BC): Vaga BC Noturna continua 1");
echo "\n";

// 4. RegistrarExcecao (Fixo): Removendo Brian do ciclo neste dia.
echo "4. Testando RegistrarExcecao para o Fixo...\n";
$reg_exc = call_api_post(__DIR__ . '/api/api_registrar_excecao_fixo.php', ['bombeiro_id' => ID_FIXO_BRIAN, 'data' => $data_teste]);
test_assert($reg_exc['success'] ?? false, "API RegistrarExcecao: Removendo Brian (Fixo)");
echo "\n";

// 5. GetDetails (Após Exceção): Brian deve ter 'tem_excecao'=true. Vagas de BC não devem mudar.
echo "5. Testando GetDetails (após exceção do Fixo)...\n";
$detalhes3 = call_api_get(__DIR__ . '/api/api_get_details.php', ['date' => $data_teste]);
test_assert(true, $detalhes3['fixo_calculado']['tem_excecao'] ?? false, "API GetDetails (após exceção): Brian agora tem 'tem_excecao'");
test_assert_equals(0, $detalhes3['vagas']['D'] ?? -1, "API GetDetails (após exceção): Vaga BC Diurna continua 0 (Andrea ainda lá)");
echo "\n";

// 6. Teste de Falha: Tentar adicionar outro BC no turno lotado.
echo "6. Testando falha ao adicionar segundo BC no mesmo turno...\n";
define('ID_BC_ANELI', 2);
$reg_plantao_falha = call_api_post(__DIR__ . '/api/api_registrar_plantao.php', ['bombeiro_id' => ID_BC_ANELI, 'data' => $data_teste, 'turno' => 'D']);
test_assert(!($reg_plantao_falha['success'] ?? true), "API RegistrarPlantao (falha): Não deve ser possível adicionar Aneli (BC) no turno Diurno já ocupado");
echo "\n";


echo "========================================\n";
if ($failures > 0) {
    echo "<span style='color: #e74c3c; font-weight: bold;'>TESTES CONCLUÍDOS COM $failures FALHA(S) EM $tests_run EXECUÇÕES.</span>\n";
} else {
    echo "<span style='color: #2ecc71; font-weight: bold;'>TODOS OS $tests_run TESTES PASSARAM COM SUCESSO!</span>\n";
}
echo "========================================\n";
echo "</pre>";

mysqli_close($conn);
?>