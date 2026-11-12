<?php
/**
 * migu_lnss.php — LNSS 自写 PHP 源·单文件自描述版（261103 契约 v1）
 *
 * 咪咕视频直播（大陆版，develop202 算法）。一个文件＝一个完整直播源，两种入口：
 *   清单入口  端侧回环零参数直吐 m3u；公网部署需 ?list=1&token=… → 频道行自指本件
 *   播放入口  ?id=1~84&token=… → 咪咕 playurl 签名解析（时间戳双 md5＋puData→ddCalcu）→ 302 跟至终链
 * 分片直连咪咕 CDN（实测免 UA 免 Referer），不设分片代理。
 * 流地址缓存 900s（CDN 签名带时间戳，403 即自动重解析），播放器 6s 级清单轮询只打上游一次。
 * 频道表内嵌＝261103 （死台随上游轮换起落，整批重扫即更新）。
 * 部署：与 jstv_lnss.php 同规——/sdcard/LNSS/PHP/ 端侧回环，或服务器订阅 …/migu_lnss.php?token=<access_token>
 */

$access_token = '322936015478592488';
$cache_file = __DIR__ . '/.migu_lnss_cache.json';

$channels = [
    // ── 央视 ──
    '1' => ['name' => 'CCTV-1综合', 'pid' => '608807420', 'group' => '央视'],
    '2' => ['name' => 'CCTV-2财经', 'pid' => '631780532', 'group' => '央视'],
    '3' => ['name' => 'CCTV-3综艺', 'pid' => '624878271', 'group' => '央视'],
    '4' => ['name' => 'CCTV-4中文国际', 'pid' => '631780421', 'group' => '央视'],
    '5' => ['name' => 'CCTV-5体育', 'pid' => '641886683', 'group' => '央视'],
    '6' => ['name' => 'CCTV-5+体育赛事', 'pid' => '641886773', 'group' => '央视'],
    '7' => ['name' => 'CCTV-6电影', 'pid' => '624878396', 'group' => '央视'],
    '8' => ['name' => 'CCTV-7国防军事', 'pid' => '673168121', 'group' => '央视'],
    '9' => ['name' => 'CCTV-8电视剧', 'pid' => '624878356', 'group' => '央视'],
    '10' => ['name' => 'CCTV-9纪录', 'pid' => '673168140', 'group' => '央视'],
    '11' => ['name' => 'CCTV-10科教', 'pid' => '624878405', 'group' => '央视'],
    '12' => ['name' => 'CCTV-11戏曲', 'pid' => '667987558', 'group' => '央视'],
    '13' => ['name' => 'CCTV-12社会与法', 'pid' => '673168185', 'group' => '央视'],
    '14' => ['name' => 'CCTV-13新闻', 'pid' => '608807423', 'group' => '央视'],
    '15' => ['name' => 'CCTV-14少儿', 'pid' => '624878440', 'group' => '央视'],
    '16' => ['name' => 'CCTV-15音乐', 'pid' => '673168223', 'group' => '央视'],
    '17' => ['name' => 'CCTV-17农业农村', 'pid' => '673168256', 'group' => '央视'],
    '18' => ['name' => 'CCTV发现之旅', 'pid' => '624878970', 'group' => '央视'],
    '19' => ['name' => 'CCTV老故事', 'pid' => '884121956', 'group' => '央视'],
    '20' => ['name' => 'CCTV中学生', 'pid' => '708869532', 'group' => '央视'],
    '21' => ['name' => 'CGTN纪录', 'pid' => '609006487', 'group' => '央视'],
    '22' => ['name' => 'CCTV西班牙语', 'pid' => '609006450', 'group' => '央视'],
    '23' => ['name' => 'CCTV法语', 'pid' => '609006476', 'group' => '央视'],
    '24' => ['name' => 'CCTV阿拉伯语', 'pid' => '609154345', 'group' => '央视'],
    '25' => ['name' => 'CCTV俄语', 'pid' => '609006446', 'group' => '央视'],
    '26' => ['name' => 'CHC影迷电影', 'pid' => '952383261', 'group' => '央视'],
    // ── 卫视 ──
    '27' => ['name' => '东方卫视', 'pid' => '651632648', 'group' => '卫视'],
    '28' => ['name' => '江苏卫视', 'pid' => '623899368', 'group' => '卫视'],
    '29' => ['name' => '广东卫视', 'pid' => '608831231', 'group' => '卫视'],
    '30' => ['name' => '北京卫视', 'pid' => '630287636', 'group' => '卫视'],
    '31' => ['name' => '辽宁卫视', 'pid' => '630291707', 'group' => '卫视'],
    '32' => ['name' => '河北卫视', 'pid' => '962042070', 'group' => '卫视'],
    '33' => ['name' => '江西卫视', 'pid' => '783847495', 'group' => '卫视'],
    '34' => ['name' => '河南卫视', 'pid' => '790187291', 'group' => '卫视'],
    '35' => ['name' => '陕西卫视', 'pid' => '738910838', 'group' => '卫视'],
    '36' => ['name' => '大湾区卫视', 'pid' => '608917627', 'group' => '卫视'],
    '37' => ['name' => '湖北卫视', 'pid' => '947472496', 'group' => '卫视'],
    '38' => ['name' => '吉林卫视', 'pid' => '947472500', 'group' => '卫视'],
    '39' => ['name' => '青海卫视', 'pid' => '947472506', 'group' => '卫视'],
    '40' => ['name' => '东南卫视', 'pid' => '849116810', 'group' => '卫视'],
    '41' => ['name' => '海南卫视', 'pid' => '947472502', 'group' => '卫视'],
    '42' => ['name' => '海峡卫视', 'pid' => '849119120', 'group' => '卫视'],
    '43' => ['name' => '中国农林卫视', 'pid' => '956904896', 'group' => '卫视'],
    '44' => ['name' => '兵团卫视', 'pid' => '956923145', 'group' => '卫视'],
    '45' => ['name' => '宁夏卫视', 'pid' => '738910535', 'group' => '卫视'],
    '46' => ['name' => '三沙卫视', 'pid' => '961023778', 'group' => '卫视'],
    '47' => ['name' => '黑龙江卫视', 'pid' => '738906901', 'group' => '卫视'],
    // ── 地方 ──
    '48' => ['name' => '上海新闻综合', 'pid' => '651632657', 'group' => '地方'],
    '49' => ['name' => '上海第一财经', 'pid' => '608780988', 'group' => '地方'],
    '50' => ['name' => '南京新闻综合频道', 'pid' => '838109047', 'group' => '地方'],
    '51' => ['name' => '南京教科频道', 'pid' => '838153729', 'group' => '地方'],
    '52' => ['name' => '南京十八频道', 'pid' => '838151753', 'group' => '地方'],
    '53' => ['name' => '江苏城市频道', 'pid' => '626064714', 'group' => '地方'],
    '54' => ['name' => '江苏国际', 'pid' => '626064674', 'group' => '地方'],
    '55' => ['name' => '江苏教育', 'pid' => '628008321', 'group' => '地方'],
    '56' => ['name' => '江苏影视频道', 'pid' => '626064697', 'group' => '地方'],
    '57' => ['name' => '江苏综艺频道', 'pid' => '626065193', 'group' => '地方'],
    '58' => ['name' => '公共新闻频道', 'pid' => '626064693', 'group' => '地方'],
    '59' => ['name' => '优漫卡通频道', 'pid' => '626064703', 'group' => '地方'],
    '60' => ['name' => '盐城新闻综合', 'pid' => '639731825', 'group' => '地方'],
    '61' => ['name' => '淮安新闻综合', 'pid' => '639731826', 'group' => '地方'],
    '62' => ['name' => '泰州新闻综合', 'pid' => '639731818', 'group' => '地方'],
    '63' => ['name' => '连云港新闻综合', 'pid' => '639731715', 'group' => '地方'],
    '64' => ['name' => '宿迁新闻综合', 'pid' => '639731832', 'group' => '地方'],
    '65' => ['name' => '徐州新闻综合', 'pid' => '639731747', 'group' => '地方'],
    '66' => ['name' => '南通新闻综合', 'pid' => '955227985', 'group' => '地方'],
    '67' => ['name' => '宜兴新闻综合', 'pid' => '955227996', 'group' => '地方'],
    '68' => ['name' => '溧水新闻综合', 'pid' => '639737327', 'group' => '地方'],
    '69' => ['name' => '镇江新闻综合', 'pid' => '639731783', 'group' => '地方'],
    '70' => ['name' => '陕西银龄频道', 'pid' => '956909362', 'group' => '地方'],
    '71' => ['name' => '陕西都市青春频道', 'pid' => '956909358', 'group' => '地方'],
    '72' => ['name' => '陕西体育休闲频道', 'pid' => '956909356', 'group' => '地方'],
    '73' => ['name' => '陕西秦腔频道', 'pid' => '956909303', 'group' => '地方'],
    '74' => ['name' => '陕西新闻资讯频道', 'pid' => '956909289', 'group' => '地方'],
    '75' => ['name' => '财富天下', 'pid' => '956923159', 'group' => '地方'],
    '76' => ['name' => '广东珠江', 'pid' => '639731952', 'group' => '地方'],
    '77' => ['name' => '广东影视', 'pid' => '614961829', 'group' => '地方'],
    '78' => ['name' => '嘉佳卡通', 'pid' => '614952364', 'group' => '地方'],
    // ── 轮播（261103 用户精简：高清大片/经典动画大集合/赛事最经典/体坛名栏汇/金色剧场/最强综艺趴 删） ──
    '79' => ['name' => '红色轮播台', 'pid' => '713600957', 'group' => '轮播'],
    '80' => ['name' => '新片放映厅', 'pid' => '619495952', 'group' => '轮播'],
    '81' => ['name' => '经典香港电影', 'pid' => '625703337', 'group' => '轮播'],
    '82' => ['name' => '军事迷必看大片', 'pid' => '617432318', 'group' => '轮播'],
    '83' => ['name' => '咪咕24小时体育台', 'pid' => '654102378', 'group' => '轮播'],
    '84' => ['name' => '新动力量创一流', 'pid' => '713589837', 'group' => '轮播'],
];

$self_name = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'migu_lnss.php');
date_default_timezone_set('Asia/Shanghai');

function fetchUrl($url, $headers = [], $follow = true, $timeout = 15) {
    $resp_headers = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$resp_headers) {
        $len = strlen($line);
        if (preg_match('/^([\w-]+):\s*(.*?)\s*$/', trim($line), $m)) $resp_headers[strtolower($m[1])] = $m[2];
        return $len;
    });
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $err = (string) curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string) $body, 'type' => $type, 'error' => $err, 'headers' => $resp_headers];
}

// 咪咕 playurl 签名请求（develop202 算法：时间戳+contId+版本前缀 md5，再叠盐值后缀 md5）
function miguApiUrl($pid) {
    $t = (string) (intval(microtime(true) * 1000));
    $v = '2600034600';
    $cid = md5((string) (intval(microtime(true) * 1000)));
    $h = ["AppVersion: $v", 'TerminalId: android', "X-UP-CLIENT-CHANNEL-ID: {$v}-99000-201600010010028", "ClientId: $cid"];
    if ($pid !== '641886683' && $pid !== '641886773') $h[] = 'appCode: miguvideo_default_android';
    $salt = str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT) . '25';
    $sign = md5(md5($t . $pid . substr($v, 0, 8)) . '2cac4f2c6c3346a5b34e085725ef7e33migu' . substr($salt, 0, 4));
    $u = 'https://play.miguvideo.com/playurl/v1/play/playurl?sign=' . $sign . '&rateType=3&contId=' . $pid
        . '&timestamp=' . $t . '&salt=' . $salt
        . '&flvEnable=true&super4k=true&h265N=true&4kvivid=true&2Kvivid=true&vivid=2';
    return [$u, $h];
}

// puData → ddCalcu（develop202 算法，逐字符交叉拼接按日/按频道插字）
function ddCalcu($puData, $pid) {
    $keys = 'cdabyzwxkl';
    $dd = '';
    $n = strlen($puData);
    for ($i = 0; $i < $n / 2; $i++) {
        $dd .= $puData[$n - $i - 1] . $puData[$i];
        if ($i == 1) $dd .= 'v';
        if ($i == 2) $dd .= $keys[substr(date('Ymd'), 2, 1)];
        if ($i == 3) $dd .= $keys[substr($pid, 6, 1)];
        if ($i == 4) $dd .= 'a';
    }
    return $dd;
}

// 解析到最终可播清单地址：playurl 签名→puData/ddCalcu→循环跟 302（UA MiguVideo）
function miguResolve($pid) {
    list($u, $h) = miguApiUrl($pid);
    $res = fetchUrl($u, $h);
    $j = json_decode($res['body'], true);
    if (!isset($j['body']['urlInfo']['url'])) return null;
    $url = $j['body']['urlInfo']['url'];
    if (!preg_match('/&puData=([^&]+)/', $url, $mm)) return null;
    $url .= '&ddCalcu=' . ddCalcu($mm[1], $pid) . '&sv=10004&ct=android';
    for ($i = 0; $i < 6; $i++) {
        $res = fetchUrl($url, ['User-Agent: MiguVideo/6.2.30'], false);
        if ($res['code'] != 302) return $url;
        $loc = (string) ($res['headers']['location'] ?? '');
        if ($loc === '' || strpos($loc, 'http://bofang') === 0) return $url;
        $url = $loc;
    }
    return $url;
}

// 校验响应体确实是 m3u8；是则返回去 BOM 后正文，否则 null
function m3u8Body($body) {
    if (!is_string($body) || $body === '') return null;
    $t = ltrim($body, "\xEF\xBB\xBF \t\r\n");
    return (stripos($t, '#EXTM3U') === 0) ? $t : null;
}

function resolveUrl($base, $relative) {
    if (preg_match('~^https?://~i', $relative)) return $relative;
    $p = parse_url($base);
    $root = ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? '') . (isset($p['port']) ? ':' . $p['port'] : '');
    if ($relative !== '' && $relative[0] === '/') return $root . $relative;
    $path = preg_replace('~[^/]*$~', '', (string) ($p['path'] ?? '/')) . $relative;
    return $root . $path;
}

// 清单正文改写：分片/KEY/MAP 行换绝对地址（分片直连 CDN，不走代理）
function rewriteAbsolute($body, $base_url) {
    $out = '';
    foreach (explode("\n", $body) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            if (preg_match('/URI="([^"]+)"/', $line, $m)) {
                $line = str_replace($m[1], resolveUrl($base_url, $m[1]), $line);
            }
            $out .= $line . "\n";
        } else {
            $out .= resolveUrl($base_url, $line) . "\n";
        }
    }
    return $out;
}

function sendPlaylist($output) {
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Cache-Control: no-store');
    header('Access-Control-Allow-Origin: *');
    echo $output;
    exit;
}

function upstreamFail($code) {
    http_response_code($code > 0 ? $code : 502);
    header('Content-Type: text/plain; charset=utf-8');
    exit('upstream ' . ($code > 0 ? $code : 'unreachable'));
}

function cacheRead($pid, $ttl) {
    global $cache_file;
    $j = json_decode((string) @file_get_contents($cache_file), true);
    $e = is_array($j) ? ($j[$pid] ?? null) : null;
    return (is_array($e) && !empty($e['u']) && (time() - (int) ($e['t'] ?? 0)) < $ttl) ? (string) $e['u'] : null;
}

function cacheWrite($pid, $url) {
    global $cache_file;
    $j = json_decode((string) @file_get_contents($cache_file), true);
    if (!is_array($j)) $j = [];
    $j[$pid] = ['u' => $url, 't' => time()];
    @file_put_contents($cache_file, json_encode($j), LOCK_EX);
}

// ── 清单入口（契约 v1）：端侧回环零参数直通，公网需 token ──
$is_loopback = in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true);
$token_ok = isset($_GET['token']) && $_GET['token'] === $access_token;
$want_list = isset($_GET['list']) || !isset($_GET['id']);
if ($want_list && ($is_loopback || $token_ok)) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' && $_SERVER['HTTPS'] != '') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '127.0.0.1';
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ('/' . $self_name);
    $self_base = $scheme . '://' . $host . $script;
    $out = "#EXTM3U\n";
    foreach ($channels as $cid => $ch) {
        $out .= '#EXTINF:-1 tvg-id="' . htmlspecialchars($ch['name'], ENT_QUOTES, 'UTF-8')
            . '" group-title="' . $ch['group'] . '",' . $ch['name'] . "\n";
        $out .= $self_base . '?id=' . $cid . '&token=' . urlencode($access_token) . "\n";
    }
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Cache-Control: no-store');
    header('Access-Control-Allow-Origin: *');
    echo $out;
    exit;
}

// ── 门禁（播放入口恒需 token）──
if (!$token_ok) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden - 未授权访问');
}

// ── 播放入口：缓存流地址优先，失效重解析；master 清单挑最高码率变体 ──
$id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
if (!isset($channels[$id])) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    exit('404 - 频道不存在，可用ID: 1-' . count($channels));
}
$pid = $channels[$id]['pid'];

$cached = cacheRead($pid, 900);
if ($cached !== '') {
    $res = fetchUrl($cached);
    $m3u8 = m3u8Body($res['body']);
    if ($m3u8 !== null && !preg_match('/#EXT-X-STREAM-INF/i', $m3u8)) {
        sendPlaylist(rewriteAbsolute($m3u8, $cached));
    }
}

$final = miguResolve($pid);
if ($final === null) upstreamFail(502);

$res = fetchUrl($final);
$m3u8 = m3u8Body($res['body']);
if ($m3u8 === null) upstreamFail($res['code']);

// master 清单挑最高码率变体再取一层；变体后基底必须换成变体自身 URL
$playlist_base = $final;
if (preg_match('/#EXT-X-STREAM-INF/i', $m3u8)) {
    $best_url = '';
    $best_bw = 0;
    $cur_bw = 0;
    foreach (explode("\n", $m3u8) as $line) {
        $line = trim($line);
        if (preg_match('/BANDWIDTH=(\d+)/', $line, $m)) {
            $cur_bw = intval($m[1]);
        } elseif ($line !== '' && $line[0] !== '#') {
            if ($cur_bw >= $best_bw) {
                $best_bw = $cur_bw;
                $best_url = resolveUrl($final, $line);
            }
            $cur_bw = 0;
        }
    }
    if ($best_url !== '') {
        $playlist_base = $best_url;
        $res = fetchUrl($best_url);
        $m3u8 = m3u8Body($res['body']);
        if ($m3u8 === null) upstreamFail($res['code']);
    }
}
cacheWrite($pid, $playlist_base);
sendPlaylist(rewriteAbsolute($m3u8, $playlist_base));
