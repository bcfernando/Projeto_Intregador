<?php
// sync_to_google.php
// Lê seu export JSON e envia para a planilha do Google via Apps Script.

// 1) URL do seu Web App (precisa ser a /exec da implantação pública)
$WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbzZNeAmLQH5BH_llQusDCsMsHp2_iZHkVB9DOrTKAr-oKEo_e8TKnb47CPDiR0hqGrx/exec';

// 2) Token deve bater com o SYNC_TOKEN no Código.gs
$SYNC_TOKEN = 'W!reless';

// Se não passar month/year, usa o mês atual:
$month = isset($_GET['month']) ? preg_replace('/\D/', '', $_GET['month']) : date('m');
$year  = isset($_GET['year'])  ? preg_replace('/\D/', '', $_GET['year'])  : date('Y');

// auto=1 → sincroniza mês atual + próximo
$auto  = isset($_GET['auto']) ? (int)$_GET['auto'] : 0;

// ---------- helpers ----------
function post_to_webapp($url, array $payload) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,   // segue 301/302 do Google
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
  ]);
  $resp = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  return [$http, $resp, $err];
}

function syncMes($year, $month, $WEBAPP_URL, $SYNC_TOKEN) {
  // URL do seu export local (ajuste se o caminho for diferente)
  $base    = 'http://localhost/escala_bombeiros/exportar_tabela_formatada.php';
  $urlJson = "{$base}?month={$month}&year={$year}&format=json";

  $json = @file_get_contents($urlJson);
  if ($json === false) {
    return ["ok"=>false, "mes"=>sprintf('%04d-%02d',$year,$month), "erro"=>"Falha ao ler JSON em {$urlJson}"];
  }

  $data = json_decode($json, true);
  if (!$data || empty($data['ok'])) {
    return ["ok"=>false, "mes"=>sprintf('%04d-%02d',$year,$month), "erro"=>"JSON inválido retornado pelo export"];
  }

  if (!isset($data['events']) || !is_array($data['events'])) {
    return ["ok"=>false, "mes"=>sprintf('%04d-%02d',$year,$month), "erro"=>"Campo 'events' ausente no JSON do export"];
  }

  // payload para o Apps Script
  $payload = [
    'token'   => $SYNC_TOKEN,
    'modo'    => 'replace_mes',
    'mes'     => sprintf('%04d-%02d', $year, $month),
    'eventos' => $data['events']   // mantém o que o export gerou
  ];

  // Envia
  list($http, $resp, $err) = post_to_webapp($WEBAPP_URL, $payload);

  // Tenta decodificar a resposta do Apps Script (quando OK vem JSON)
  $respJson = json_decode($resp, true);
  return [
    "ok"     => ($http === 200) && is_array($respJson) ? true : false,
    "http"   => $http,
    "mes"    => $payload['mes'],
    "resp"   => $resp,
    "resp_j" => $respJson,
    "curl_error" => $err
  ];
}

// ---------- fluxo principal ----------
$out = [];

if ($auto) {
  // Mês atual
  $dt = new DateTime('first day of this month');
  $y1 = (int)$dt->format('Y');
  $m1 = (int)$dt->format('m');
  $out[] = syncMes($y1, $m1, $WEBAPP_URL, $SYNC_TOKEN);

  // Próximo mês
  $dt->modify('+1 month');
  $y2 = (int)$dt->format('Y');
  $m2 = (int)$dt->format('m');
  $out[] = syncMes($y2, $m2, $WEBAPP_URL, $SYNC_TOKEN);
} else {
  $out[] = syncMes((int)$year, (int)$month, $WEBAPP_URL, $SYNC_TOKEN);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
