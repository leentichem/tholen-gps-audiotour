<?php
// Tholen eigen teller. Geen IP-adressen worden opgeslagen.
// POST: {event, lang, stop}
// Dashboard: stats.php?dashboard=1&key=DE_DASHBOARD_SLEUTEL
header('Content-Type: application/json; charset=utf-8');

$dashboardKey = 'w6z_msSe7hNvFnzfzWctcuUH';
$dataFile = __DIR__ . '/stats-data.json';

if (isset($_GET['dashboard'])) {
    if (!hash_equals($dashboardKey, (string)($_GET['key'] ?? ''))) {
        http_response_code(403);
        echo "Geen toegang.";
        exit;
    }
    $data = [];
    if (is_file($dataFile)) {
        $raw = file_get_contents($dataFile);
        $data = json_decode($raw, true);
    }
    if (!is_array($data)) $data = ['days'=>[]];

    $countries=[]; $languages=[]; $events=[]; $totalVisits=0; $totalAudio=0;
    foreach (($data['days'] ?? []) as $day) {
        foreach (($day['countries'] ?? []) as $c=>$n) $countries[$c]=($countries[$c]??0)+$n;
        foreach (($day['languages'] ?? []) as $l=>$n) $languages[$l]=($languages[$l]??0)+$n;
        foreach (($day['events'] ?? []) as $e=>$n) $events[$e]=($events[$e]??0)+$n;
    }
    $totalVisits = $events['visit'] ?? 0;
    $totalAudio = $events['audio_start'] ?? 0;
    arsort($countries); arsort($languages);

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="nl"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Tholen route – statistieken</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:1000px;margin:30px auto;padding:0 18px}table{border-collapse:collapse;width:100%;margin:10px 0 28px}th,td{border:1px solid #ddd;padding:7px;text-align:left}h1{margin-bottom:4px}.kaart{display:grid;grid-template-columns:1fr 1fr;gap:20px}</style>';
    echo '<h1>Tholen route – statistieken</h1>';
    echo '<p>Totaal geregistreerde bezoeken: <b>'.(int)$totalVisits.'</b><br>Audiostarts: <b>'.(int)$totalAudio.'</b></p>';
    echo '<div class="kaart"><section><h2>Bezoeken per land</h2><table><tr><th>Land</th><th>Bezoeken</th></tr>';
    foreach($countries as $c=>$n) echo '<tr><td>'.htmlspecialchars($c).'</td><td>'.(int)$n.'</td></tr>';
    echo '</table></section><section><h2>Bezoeken per taal</h2><table><tr><th>Taal</th><th>Bezoeken</th></tr>';
    foreach($languages as $l=>$n) echo '<tr><td>'.htmlspecialchars($l).'</td><td>'.(int)$n.'</td></tr>';
    echo '</table></section></div>';
    echo '<h2>Gebeurtenissen</h2><table><tr><th>Event</th><th>Aantal</th></tr>';
    foreach($events as $e=>$n) echo '<tr><td>'.htmlspecialchars($e).'</td><td>'.(int)$n.'</td></tr>';
    echo '</table></html>';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$event = preg_replace('/[^a-z_]/', '', (string)($input['event'] ?? 'visit'));
$lang  = preg_replace('/[^a-z-]/', '', strtolower((string)($input['lang'] ?? 'nl')));
if ($lang === '') $lang = 'nl';

$country = strtoupper((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
if (!preg_match('/^[A-Z]{2}$/', $country)) {
    foreach (['HTTP_X_COUNTRY_CODE','HTTP_X_GEOIP_COUNTRY'] as $header) {
        $candidate = strtoupper((string)($_SERVER[$header] ?? ''));
        if (preg_match('/^[A-Z]{2}$/', $candidate)) { $country=$candidate; break; }
    }
}
if (!preg_match('/^[A-Z]{2}$/', $country)) {
    $al = strtolower((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    $map = ['nl'=>'NL','de'=>'DE','fr'=>'FR','en'=>'GB','fi'=>'FI','sv'=>'SE','no'=>'NO','da'=>'DK','is'=>'IS','es'=>'ES','it'=>'IT','pt'=>'PT','pl'=>'PL','cs'=>'CZ','hu'=>'HU','ro'=>'RO','el'=>'GR','tr'=>'TR','ar'=>'MA','bg'=>'BG','hr'=>'HR','et'=>'EE','ga'=>'IE','lv'=>'LV','lt'=>'LT','mt'=>'MT','sk'=>'SK','sl'=>'SI'];
    $country = $map[substr($al,0,2)] ?? 'XX';
}

$today = gmdate('Y-m-d');
$fp = fopen($dataFile, 'c+');
if (!$fp) { http_response_code(500); echo json_encode(['ok'=>false]); exit; }
flock($fp, LOCK_EX);
rewind($fp);
$raw = stream_get_contents($fp);
$data = $raw ? json_decode($raw, true) : null;
if (!is_array($data)) $data = ['updated'=>$today,'days'=>[]];
if (!isset($data['days'][$today])) $data['days'][$today] = ['countries'=>[], 'languages'=>[], 'events'=>[]];
foreach ([['countries',$country],['languages',$lang],['events',$event]] as [$group,$value]) {
    if (!isset($data['days'][$today][$group][$value])) $data['days'][$today][$group][$value]=0;
    $data['days'][$today][$group][$value]++;
}
$data['updated']=$today;
rewind($fp); ftruncate($fp,0); fwrite($fp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); fflush($fp); flock($fp,LOCK_UN); fclose($fp);
echo json_encode(['ok'=>true]);
?>
