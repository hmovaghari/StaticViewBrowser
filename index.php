<?php
// proxy.php  (همان منطق قبلی؛ فقط UI شبیه IE شده)
// Read-only HTML renderer.
// - Removes scripts/forms/iframes and on* attributes.
// - Rewrites links and images via ?resource= (images)
// - No JS execution on client.

function is_valid_url($u){
    $p = parse_url($u);
    if ($p === false) return false;
    if (!isset($p['scheme'])) return false;
    return in_array(strtolower($p['scheme']), ['http','https']);
}

function fetch_url_raw($url){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SimpleReadOnlyProxy/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['body'=>$body, 'err'=>$err, 'info'=>$info];
}

// Resource proxy (images only – مثل قبل)
if (isset($_GET['resource'])) {
    $resUrl = $_GET['resource'];
    if (!is_valid_url($resUrl)) { header("HTTP/1.1 400 Bad Request"); echo "Invalid resource URL."; exit; }
    $f = fetch_url_raw($resUrl);
    if ($f['err'] || !$f['body']) { header("HTTP/1.1 502 Bad Gateway"); echo "Unable to fetch resource."; exit; }
    $ct = isset($f['info']['content_type']) ? $f['info']['content_type'] : 'application/octet-stream';
    header('Content-Type: '.$ct);
    header('Cache-Control: public, max-age=300');
    echo $f['body'];
    exit;
}

$input = isset($_GET['url']) ? trim($_GET['url']) : '';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>نمایش ایستا (read-only) صفحات</title>
  <style>
    /* --- صفحه کلی --- */
    :root{
      --ie-blue-1:#0057b8; /* نوار عنوان */
      --ie-blue-2:#2a7bd1; /* گرادیان نوار ابزار */
      --ie-blue-3:#7fb3f4; /* روشن‌تر */
      --ie-gray-1:#f0f0f0;
      --ie-gray-2:#d9d9d9;
      --ie-gray-3:#bfbfbf;
      --shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    html,body{margin:0;padding:0;background:#e9eef5;font-family:Tahoma, Arial, sans-serif}
    .app{
      max-width: 980px;
      margin: 18px auto;
      background:#fff;
      border:1px solid #9cb0c5;
      box-shadow: var(--shadow);
      overflow:hidden;
      border-radius:8px;
    }

    /* --- شبیه‌سازی قاب مرورگر IE --- */
    .ie-titlebar{
      background: linear-gradient(180deg, var(--ie-blue-1), #0b62c4);
      color:#fff;
      padding:8px 12px;
      font-weight:bold;
      display:flex;
      align-items:center;
      gap:8px;
      border-bottom:1px solid #084e9b;
    }
    .ie-titlebar .logo{
      width:16px;height:16px; border-radius:3px;
      background: radial-gradient(circle at 30% 30%, #fff 0, #bfe0ff 45%, #3c8dde 46%, #0b5fb3 100%);
      border:1px solid rgba(255,255,255,.6);
      flex:0 0 16px;
    }
    .ie-titlebar .title{
      flex:1 1 auto; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .ie-window-buttons{
      display:flex; gap:6px;
    }
    .ie-btn-win{
      width:14px;height:14px; border:1px solid #e4eefb; border-radius:2px;
      background: linear-gradient(180deg, #e4eefb, #c8dbf7);
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.6);
    }

    .ie-toolbar{
      background: linear-gradient(180deg, var(--ie-blue-3), var(--ie-blue-2));
      padding:6px 8px;
      display:flex; align-items:center; gap:8px;
      border-bottom:1px solid #9cb0c5;
    }
    .ie-toolbtn{
      appearance:none; border:1px solid #7aa6d9; color:#173a5e;
      background: linear-gradient(180deg,#e9f2ff,#cde2ff);
      border-radius:4px; padding:6px 10px; cursor:default;
      box-shadow:inset 0 1px 0 #fff;
      font-size:12px;
    }
    .ie-toolbtn[disabled]{opacity:.6}

    .ie-addressbar{
      display:flex; align-items:center; gap:8px;
      background: linear-gradient(180deg, #fafafa, #f0f0f0);
      padding:10px;
      border-bottom:1px solid #cfd9e4;
    }
    .addr-label{font-size:12px;color:#333;flex:0 0 auto}
    .addr-input{
      flex:1 1 auto; display:flex; align-items:center; gap:6px;
      background:#fff; border:1px solid #9cb0c5; border-radius:6px; padding:6px 8px;
      box-shadow: inset 0 1px 2px rgba(0,0,0,.08);
    }
    .addr-input .lock{
      width:14px; height:14px; border-radius:2px; background:
        linear-gradient(180deg,#ffd966,#e0b800);
      border:1px solid #a8870f;
      flex:0 0 14px;
    }
    .addr-input input[type="text"]{
      border:none; outline:none; width:100%;
      font: 13px Tahoma, Arial, sans-serif;
      direction:ltr; /* آدرس چپ‌به‌راست */
    }
    .addr-go{
      appearance:none; border:1px solid #7aa6d9; color:#173a5e;
      background: linear-gradient(180deg,#e9f2ff,#cde2ff);
      border-radius:6px; padding:7px 14px; cursor:pointer; font-weight:bold;
      box-shadow:inset 0 1px 0 #fff;
    }
    .addr-go:hover{filter:brightness(1.05)}
    .note{
      margin: 8px 12px 0 12px; color:#555; font-size:12px;
    }

    /* --- محتوای خروجی --- */
    .out{
      margin:12px; background:#fff; border:1px solid #e1e6ec; border-radius:6px; padding:12px;
    }
    .err{color:#c00; background:#fff2f2; border:1px solid #f0b3b3; padding:8px 10px; border-radius:6px; margin:12px}
  </style>
</head>
<body>
  <div class="app">

    <!-- نوار عنوان شبیه IE -->
    <div class="ie-titlebar">
      <div class="logo" aria-hidden="true"></div>
      <div class="title">نمایش ایستا (read-only) صفحات</div>
      <div class="ie-window-buttons">
        <div class="ie-btn-win" title="کمینه‌سازی"></div>
        <div class="ie-btn-win" title="بزرگ‌نمایی"></div>
        <div class="ie-btn-win" title="بستن"></div>
      </div>
    </div>

    <!-- نوار ابزار شبیه IE -->
    <div class="ie-toolbar">
      <button type="button" class="ie-toolbtn" disabled title="فقط تزئینی">◀</button>
      <button type="button" class="ie-toolbtn" disabled title="فقط تزئینی">▶</button>
      <button type="button" class="ie-toolbtn" disabled title="فقط تزئینی">⟳ Refresh</button>
      <button type="button" class="ie-toolbtn" disabled title="فقط تزئینی">⌂ Home</button>
    </div>

    <!-- آدرس‌بار شبیه IE -->
    <form class="ie-addressbar" method="get" action="">
      <div class="addr-label">Address:</div>
      <div class="addr-input">
        <div class="lock" aria-hidden="true" title="Secure (نماد تزئینی)"></div>
        <input type="text" name="url" value="<?php echo htmlspecialchars($input ?: 'https://facebook.com'); ?>" placeholder="https://example.com" required>
      </div>
      <button class="addr-go" type="submit">Go</button>
    </form>

    <p class="note">این فقط یک «ویو ایستا» است؛ جاوااسکریپت سایت‌ها اجرا نمی‌شود. هدف: نمایش نمای کلی شبیه مرورگر IE.</p>

<?php
if ($input !== '') {
    if (!is_valid_url($input)) {
        echo "<div class='err'>آدرس معتبر نیست. با http:// یا https:// شروع شود.</div>";
    } else {
        $f = fetch_url_raw($input);
        if ($f['err'] || !$f['body']) {
            echo "<div class='err'>خطا در دریافت صفحه: " . htmlspecialchars($f['err']) . "</div>";
        } else {
            $html = $f['body'];

            // Sanitize با DOMDocument
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);

            // حذف اجزای تعاملی/خطرناک
            $tagsToRemove = ['script','iframe','form','input','button','textarea','noscript','meta','object','embed'];
            foreach ($tagsToRemove as $tag) {
                $nodes = $dom->getElementsByTagName($tag);
                for ($i = $nodes->length -1; $i >= 0; $i--) {
                    $node = $nodes->item($i);
                    if ($node) $node->parentNode->removeChild($node);
                }
            }

            // حذف on* و javascript:*
            $xpath = new DOMXPath($dom);
            $all = $xpath->query('//*[@*]');
            foreach ($all as $el) {
                $attrs = [];
                foreach ($el->attributes as $a) $attrs[] = $a->name;
                foreach ($attrs as $name) {
                    if (preg_match('/^on/i', $name)) $el->removeAttribute($name);
                    if (in_array(strtolower($name), ['href','src'])) {
                        $val = $el->getAttribute($name);
                        if (stripos($val, 'javascript:') === 0) $el->removeAttribute($name);
                    }
                }
            }

            // بازنویسی لینک‌ها/تصاویر
            $base = $input;
            $baseParts = parse_url($base);
            $baseScheme = $baseParts['scheme'];
            $baseHost = $baseParts['host'];
            $basePath = isset($baseParts['path']) ? dirname($baseParts['path']) : '/';
            function absolutize($url, $baseScheme, $baseHost, $basePath) {
                if (preg_match('/^https?:\\/\\//i', $url)) return $url;
                if (strpos($url, '//') === 0) return $baseScheme . ':' . $url;
                if (strpos($url, '/') === 0) return $baseScheme . '://' . $baseHost . $url;
                $cleanBase = rtrim($basePath, '/') . '/';
                return $baseScheme . '://' . $baseHost . $cleanBase . $url;
            }

            $aTags = $dom->getElementsByTagName('a');
            for ($i = $aTags->length-1; $i>=0; $i--) {
                $a = $aTags->item($i);
                $href = $a->getAttribute('href');
                if (!$href) continue;
                if (strpos($href, '#') === 0) { $a->removeAttribute('href'); continue; }
                $abs = absolutize($href, $baseScheme, $baseHost, $basePath);
                $a->setAttribute('href', '?url=' . urlencode($abs));
                if ($a->hasAttribute('target')) $a->removeAttribute('target');
            }

            $imgs = $dom->getElementsByTagName('img');
            for ($i = $imgs->length-1; $i>=0; $i--) {
                $img = $imgs->item($i);
                $src = $img->getAttribute('src');
                if (!$src) { $img->removeAttribute('src'); continue; }
                $abs = absolutize($src, $baseScheme, $baseHost, $basePath);
                $img->setAttribute('src', '?resource=' . urlencode($abs));
                if ($img->hasAttribute('srcset')) $img->removeAttribute('srcset');
                if ($img->hasAttribute('sizes'))  $img->removeAttribute('sizes');
            }

            // حذف لینک‌های stylesheet (همان رفتار نسخه‌ی خواسته‌شده)
            $links = $dom->getElementsByTagName('link');
            for ($i = $links->length-1; $i>=0; $i--) {
                $ln = $links->item($i);
                $rel = strtolower($ln->getAttribute('rel'));
                if ($rel === 'stylesheet' || $rel === 'preload' || $rel === 'dns-prefetch') {
                    $ln->parentNode->removeChild($ln);
                }
            }

            echo '<div class="out">';
            echo '<p style="font-size:12px;color:#444;margin-top:0">نمایش ایستا از: ' . htmlspecialchars($input) . '</p>';
            $body = $dom->saveHTML();
            $body = preg_replace('/^\\<\\?xml.*?\\?\\>/', '', $body);
            echo $body;
            echo '</div>';
        }
    }
}
?>
  </div>
</body>
</html>
