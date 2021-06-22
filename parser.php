<?php

require_once 'db.php';
require_once('./lib/simple_html_dom.php');

if (null === $dbcon) {
    exit(1);
}

define('TAIL', 'public_list_anchor');
define('MAX_PAGE_LIMIT', 5);
define('BASE_URL', 'http://www.world-art.ru/cinema/');
define('IMAGE_DIR', 'image');

$date = getLastParsingDate($dbcon);

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

        $tmp = [
            'nom' => (int)$element[0]->children[0]->innertext(),
            'name' => $element[1]->children[0]->innertext(),
            'year' => (int)preg_replace('|\D|', '', $element[1]->nodes[1]->innertext()),
            'id' => (int)preg_replace('|\D|', '', $element[1]->children[0]->attr['href']),
            'countable' => (float)$element[2]->innertext(),
            'votes' => (int)$element[3]->children[0]->innertext(),
            'votesUrl' => $element[3]->children[0]->attr['href'],
            'average' => (float)$element[4]->innertext(),
        ];

        $result[] = $tmp;
    }

    return $result;
}

function parseDetailContent(simple_html_dom $detailContent): array
{
    $result = [];
    $row = $detailContent->find('p.review');
    $result['description'] = $row[0]->innertext();

    $row = $detailContent->find('img');
    $imageUrl = BASE_URL . $row[1]->attr['src'];
    $imageExt = pathinfo($imageUrl, PATHINFO_EXTENSION);
    $savedImageName = uniqid() . '.' . $imageExt;

    /**
     * Check if image url exist.
     * In Windows, warning be shown.
     * @see https://stackoverflow.com/questions/61635366/openssl-error-messages-error14095126-unexpected-eof-while-reading
     **/
    try {
        $imageContent = file_get_contents($imageUrl);
    } catch (Exception $e) {
        $result['image'] = null;
        return $result;
    }

    // Save the image.
    if (file_put_contents(IMAGE_DIR . '/' . $savedImageName, $imageContent)) {
        $result['image'] = $savedImageName;
    } else {
        $result['image'] = null;
    }

    return $result;
}

function saveToDb($connection, $data, $parsingDate, $categoryId)
{
    foreach ($data as $item) {
        $stmt = $connection->prepare('SELECT count(*) FROM `movie` WHERE `movie_id` = :movieId');
        $stmt->bindParam(':movieId', $item['id']);
        if ($res = $stmt->execute()) {
            $cnt = (int)$stmt->fetchColumn();
            if ($cnt === 0) {

                $detailUrl = BASE_URL . 'cinema.php?id=' . $item['id'];
                $detailContent = str_get_html(
                    html_entity_decode(
                        iconv("windows-1251", "utf-8", getContent($detailUrl))
                    )
                );
                $detailResult = parseDetailContent($detailContent);

                $stmt = $connection->prepare("INSERT INTO `movie` (
                    `movie_id`,
                    `category_id`,
                    `nom`,
                    `name`,
                    `year`,
                    `image`,
                    `description`
                ) VALUES (
                    :movieId,
                    :categoryId,
                    :nom,
                    :name,
                    :year,
                    :image,
                    :description
                )");
                $stmt->bindParam(':movieId', $item['id']);
                $stmt->bindParam(':categoryId', $categoryId);
                $stmt->bindParam(':nom', $item['nom']);
                $stmt->bindParam(':name', $item['name']);
                $stmt->bindParam(':year', $item['year']);
                $stmt->bindParam(':image', $detailResult['image']);
                $stmt->bindParam(':description', $detailResult['description']);

                if ($res = $stmt->execute()) {
                    echo 'Добавлено : ' . $item['name'] . PHP_EOL;
                }
            }
        }
    }
}

function getLastParsingDate($connection): DateTime
{
    $sql = "SELECT * FROM config";
    $stmt = $connection->query($sql);
    $row = $stmt->fetchAll();
    if ($row) {
        return new DateTime($row[0][0]);
    } else {
        return new DateTime('0000-00-00');
    }
}
