<?php
// l13.php - Speed Wi-Fi HOME 5G L13 信号实时监控 局域网内
// 使用 cURL + Cookie 文件保持会话，解决 LD 为空问题

// ==================== 请修改这里 ====================
$router_ip = '192.168.10.1';
$password  = '明文密码';         // 明文
$cookie_file = __DIR__ . '/l13_cookie.txt';  // Cookie 临时文件（自动创建）
// ====================================================

function sha256_upper(string $text): string {
    return strtoupper(hash('sha256', $text));
}

function curl_get(string $url, string $cookie_file, string $referer = ''): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ?: '';
}

function curl_post(string $url, array $post_data, string $cookie_file, string $referer = ''): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Origin: http://' . parse_url($url, PHP_URL_HOST)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ?: '';
}

// 主逻辑
$base_url = "http://{$router_ip}";
$referer = "{$base_url}/index.html";

// 第一步：获取有效 LD（必须带 Cookie）
$ld_url = "{$base_url}/goform/goform_get_cmd_process?" . http_build_query([
    'cmd' => 'LD',
    'multi_data' => '1',
    '_' => time() * 1000
]);
$ld_response = curl_get($ld_url, $cookie_file, $referer);

$data = json_decode($ld_response, true);
if (empty($data['LD'])) {
    // LD 仍为空？可能是第一次访问，需要先访问主页激活会话
    curl_get("{$base_url}/index.html", $cookie_file);  // 激活会话
    $ld_response = curl_get($ld_url, $cookie_file, $referer);
    $data = json_decode($ld_response, true);
}

if (empty($data['LD'])) {
    die("<pre style='color:red;'>严重错误：无法获取有效 LD！响应：{$ld_response}</pre>");
}

$ld = $data['LD'];
$step1 = sha256_upper($password);
$encrypted_pwd = sha256_upper($step1 . $ld);

// 第二步：登录
$login_response = curl_post("{$base_url}/goform/goform_set_cmd_process", [
    'isTest' => 'false',
    'goformId' => 'LOGIN',
    'password' => $encrypted_pwd
], $cookie_file, $referer);

$login_result = json_decode($login_response, true);
if (!isset($login_result['result']) || !in_array($login_result['result'], ['0', 'success'])) {
    die("<pre style='color:red;'>登录失败！响应：{$login_response}</pre>");
}

// 第三步：获取信号（带 Cookie）
$signal_url = "{$base_url}/goform/goform_get_cmd_process?" . http_build_query([
    'cmd' => 'lte_rssi,lte_rsrp,Z5g_rsrp,Z5g_SINR,signalbar,network_type,wan_active_band,lte_ca_pcell_band,lte_ca_scell_band',
    'multi_data' => '1',
    '_' => time() * 1000
]);
$signal_response = curl_get($signal_url, $cookie_file, $referer);
$signal_data = json_decode($signal_response, true) ?: [];

// 输出页面
$time = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="3">
    <title>L13 实时信号</title>
    <style>
        body { font-family: "Microsoft YaHei", sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .info { font-size: 18px; line-height: 2; }
        .label { display: inline-block; width: 160px; font-weight: bold; color: #555; }
        .strong { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>Speed Wi-Fi HOME 5G L13 实时信号</h1>
    <div class="info">
        <div><span class="label">更新时间：</span><?= $time ?></div>
        <div><span class="label">网络模式：</span><?= $signal_data['network_type'] ?? '—' ?></div>
        <div><span class="label">信号格子：</span><?= $signal_data['signalbar'] ?? '—' ?> 格</div>
        <hr>
        <div><span class="label">4G 主信号：</span>RSRP <span class="strong"><?= $signal_data['lte_rsrp'] ?? '—' ?></span> dBm　RSSI <span class="strong"><?= $signal_data['lte_rssi'] ?? '—' ?></span> dBm</div>
        <div><span class="label">5G 辅信号：</span>RSRP <span class="strong"><?= $signal_data['Z5g_rsrp'] ?? '—' ?></span> dBm　SINR <span class="strong"><?= $signal_data['Z5g_SINR'] ?? '—' ?></span> dB</div>
        <div><span class="label">当前频段：</span><?= $signal_data['wan_active_band'] ?? '—' ?></div>
        <?php if (!empty($signal_data['lte_ca_pcell_band'])): ?>
        <div><span class="label">载波聚合：</span>主 B<?= $signal_data['lte_ca_pcell_band'] ?><?php if (!empty($signal_data['lte_ca_scell_band'])): ?> | 辅 B<?= $signal_data['lte_ca_scell_band'] ?><?php endif; ?></div>
        <?php endif; ?>
    </div>
    <p style="text-align:center;color:#888;margin-top:30px;">页面每3秒自动刷新 · 会话已保持</p>
</div>
</body>
</html>
