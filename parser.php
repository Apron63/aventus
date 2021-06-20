<?php

require_once 'db.php';
require_once('./lib/simple_html_dom.php');

if (null === $dbcon) {
    exit(1);
}

define('TAIL', 'public_list_anchor');
define('MAX_PAGE_LIMIT', 5);

$date =

$sql = "SELECT * FROM category";
$stmt = $dbcon->query($sql);
while ($row = $stmt->fetch()) {
    $page = 1;
    while (1) {
        $query = [];

        if ($page !== 1) {
            $query['limit_1'] = (50 * ($page - 1) + 1);
            $query['limit_2'] = 50 * $page;
        }

        if (!empty($row['anchor'])) {
            if (isset($query['limit_1'])) {
                $query['limit_1'] = 50 * ($page - 1);
            }
            unset($query['limit_2']);
            $query['anchor'] = $row['anchor'];
        }

        $url = $row['url'];
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        var_dump($url);

        $result = parseUrl($url);
        if (empty($result)) {
            break;
        }

        saveToDb($dbcon, $result, $date, $row['id']);

        if ($page++ >= MAX_PAGE_LIMIT) {
            break;
        }
    }
}

function parseUrl(string $url): ?array
{
    if (!empty($html = getContent($url))) {
        $content = str_get_html(
            html_entity_decode(
                iconv("windows-1251", "utf-8", $html)
            )
        );
        return parseContent($content);
    }

    return null;
}

function getContent(string $url): ?string
{
    $headers = [
        'Connection: keep-alive',
        'Pragma: no-cache',
        'Cache-Control: no-cache',
        'Accept: */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36 Edg/87.0.664.52',
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
    }

    curl_close($ch);
    return $html;
}

function parseContent(simple_html_dom $content): array
{
    $result = [];
    $row = $content->find('a.review');
    for ($cnt = 0, $cntMax = count($row); $cnt < $cntMax; $cnt += 2) {

        // Check if element outer from table.
        $attr = $row[$cnt]->attr;
        if (isset($attr) && false !== strpos($attr['href'], 'rating_tv_top')) {
            continue;
        }
        $element = $row[$cnt]->parent->parent->children;

        $result[] = [
            'nom' => (int)$element[0]->children[0]->innertext(),
            'name' => $element[1]->children[0]->innertext(),
            'year' => (int)preg_replace('|\D|', '', $element[1]->nodes[1]->innertext()),
            'url' => $element[1]->children[0]->attr['href'],
            'countable' => (float)$element[2]->innertext(),
            'votes' => (int)$element[3]->children[0]->innertext(),
            'votesUrl' => $element[3]->children[0]->attr['href'],
            'average' => (float)$element[4]->innertext(),
        ];


    }

    return $result;
}

function saveToDb($connection, $data, $parsingDate, $categoryId)
{
    $values = '';
    $columns = '`categoryId`, `nom`, `name`, `year`, `image`';
    foreach ($data as $row) {
        $values .= "({$categoryId}, {$row['nom']}, \"{$row['name']}\", {$row['year']}, \"{$row['url']}\"),";
    }
    $values = substr($values, 0, -1);

    $sql = "INSERT INTO `movie`({$columns}) VALUES {$values}";
    $connection->exec($sql);
}
