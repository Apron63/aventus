<?php

require_once 'db.php';
require_once('./lib/simple_html_dom.php');

if (null === $dbcon) {
    exit(1);
}

define('TAIL', 'public_list_anchor');
define('LIMIT', 'limit_1');

$result = [];
$sql = "SELECT * FROM category";
$stmt = $dbcon->query($sql);
while ($row = $stmt->fetch()) {
    $page = 1;
    while (1) {
        if ($page === 1) {
            $limit = '';
        } else {
            $limit = LIMIT . '?=' . 50 * ($page - 1);
        }
        $url = $row['url'] . $limit;
        if (!empty($row['anchor'])) {
            $url .= '?' . TAIL . '=' . $row['anchor'];
        }
        var_dump($url);

        if (empty(parseUrl($url))) {
            break;
        };
        break;
    }
}

function parseUrl(string $url): array
{
    $headers = [
        'Connection: keep-alive',
        'Pragma: no-cache',
        'Cache-Control: no-cache',
        'Accept: */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36 Edg/87.0.664.52',
        //'Content-Type: text/html; charset=windows-1251',
        'Accept-Encoding: gzip, deflate',
        'Accept-Language: ru,en;q=0.9,en-GB;q=0.8,en-US;q=0.7',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $html = curl_exec($ch);

    if ($html === false) {
        echo 'Ошибка curl: ' . curl_error($ch);
        die();
    } else {
        $ch_info = curl_getinfo($ch);
        $html = mb_substr($html, $ch_info['header_size']);
        $isWinCharset = mb_check_encoding($html, "windows-1251");
    }

    curl_close($ch);
    $content = str_get_html(
        html_entity_decode(
            iconv("windows-1251", "utf-8", $html)
        )
    );
    $result = parseContent($content);
    return $result;
}

//function parseContent(string $content): int
function parseContent(simple_html_dom $content): array
{
    $result = [];
    $row = $content->find('a.review');
    for ($cnt = 0, $cntMax = count($row); $cnt < $cntMax; $cnt += 2) {
        $element = $row[$cnt]->parent->parent->children;

        $result[] = [
            'nom' => $element[0]->children[0]->innertext(),
            'name' => $element[1]->children[0]->innertext(),
            'countable' => $element[2]->attr('id'),
            'votes' => $element[3]->innertext(),
            'average' => $element[4]->innertext(),

        ];
    }
//    foreach ($row as $r) {
//        var_dump($r->innertext);
//    }
//    die();
//
//    $tbl = $content->getElementByTagName('table');
//    foreach ($tbl as $t) {
//        var_dump($t->innertext);
//    }
    //$tbl = preg_match('|<table*</table>|', $content);
    //$tbl = preg_match('|<table>*</table>|', $content->getElementByTagName('table'));
    return $result;
}
