<?php

require_once 'lib\simple_html_dom.php';
require_once 'db.php';

class parser
{
    private const IMAGE_DIR = 'image';
    private const BASE_URL = 'http://www.world-art.ru/cinema/';
    private const MAX_PAGE_LIMIT = 5;

    private \DateTime $parseDate;
    private db $connection;

    /**
     * Parser1 constructor.
     * @param \DateTime|null $parseDate
     */
    public function __construct(\DateTime $parseDate = null)
    {
        $this->parseDate = $parseDate ?? new \DateTime('0000-00-00');
    }

    /**
     *
     *
     */
    public function parse(): void
    {
        $this->connection = new db();
        if (null === $this->connection->dbcon) {
            echo 'Cannot connect to DB' . PHP_EOL;
            exit(1);
        }

        $sql = "SELECT * FROM category";
        $stmt = $this->connection->dbcon->query($sql);
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

                $result = $this->parseUrl($url);
                if (empty($result)) {
                    break;
                }

                $this->saveToDb($result, $row['id']);

                if ($page++ >= self::MAX_PAGE_LIMIT) {
                    break;
                }
            }
        }
    }

    /**
     * @param string $url
     * @return array|null
     */
    private function parseUrl(string $url): ?array
    {
        if (!empty($html = $this->getContent($url))) {
            $content = str_get_html(
                html_entity_decode(
                    iconv("windows-1251", "utf-8", $html)
                )
            );
            if (empty($content)) {
                return null;
            }
            return $this->parseContent($content);
        }

        return null;
    }

    /**
     * @param string $url
     * @return string|null
     */
    private function getContent(string $url): ?string
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
        $ch_info = curl_getinfo($ch);

        if (false === $html) {
            echo 'Curl error : ' . curl_error($ch) . PHP_EOL;
            $result = null;
        } else {
            $result = mb_substr($html, $ch_info['header_size']);
        }

        curl_close($ch);

        return $result;
    }

    /**
     * @param simple_html_dom $content
     * @return array
     */
    private function parseContent(simple_html_dom $content): array
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
                'votesTotal' => (int)$element[3]->children[0]->innertext(),
                'votesUrl' => $element[3]->children[0]->attr['href'],
                'average' => (float)$element[4]->innertext(),
            ];

            $tmp['votes'] = $this->parseVotes($element[3]->children[0]->attr['href']);

            $result[] = $tmp;
        }

        return $result;
    }

    /**
     * @param string $url
     * @return array
     */
    private function parseVotes(string $url): array
    {
        $result = [];
        /** @var simple_html_dom $content */
        $content = str_get_html(
            iconv("windows-1251", "utf-8", $this->getContent(self::BASE_URL . $url))
        );
        $row = $content->find('a.review');
        $last = count($row);

        unset($row[$last - 1]);
        unset($row[0]);
        unset($row[1]);

        $oneOrOther = true;
        foreach ($row as $item) {
            $oneOrOther = !$oneOrOther;
            if ($oneOrOther) {
                continue;
            }

            $el = $item->parent();
            $text = $el->parent->parent->parent->parent->children[0]->innertext();
            preg_match_all('|\d{4}\.\d{2}\.\d{2}|', $text, $arr);
            if (!empty($arr[0])) {
                $date = DateTime::createFromFormat('Y.m.d', $arr[0][0]);
                $rate = (float)$el->parent->parent->parent->parent->children[3]->innertext();

                if (!isset($compareDate)) {
                    $compareDate = $date;
                    $agregatedRating = 0.0;
                    $ratingCnt = 0;
                }

                if ($compareDate != $date) {

                    echo $compareDate->format('d.m.Y') . ' ' . $agregatedRating / $ratingCnt . PHP_EOL;

                    $result[] = [
                        'date' => $compareDate->format('Y-m-d'),
                        'rate' => (float)($agregatedRating / $ratingCnt),
                    ];

                    $compareDate = $date;
                    $agregatedRating = 0.0;
                    $ratingCnt = 0;
                }

                $agregatedRating += $rate;
                $ratingCnt++;
            }
        }

        if (!empty($agregatedRating)) {
            $result[] = [
                'date' => $compareDate->format('Y-m-d'),
                'rate' => (float)($agregatedRating / $ratingCnt),
            ];
        }

        return $result;
    }

    /**
     * @param simple_html_dom $detailContent
     * @return array
     */
    private function parseDetailContent(simple_html_dom $detailContent): array
    {
        $result = [];
        $row = $detailContent->find('p.review');
        $result['description'] = $row[0]->innertext();

        $row = $detailContent->find('img');
        $imageUrl = self::BASE_URL . $row[1]->attr['src'];
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
        if (file_put_contents(self::IMAGE_DIR . '/' . $savedImageName, $imageContent)) {
            $result['image'] = $savedImageName;
        } else {
            $result['image'] = null;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param int $categoryId
     */
    private function saveToDb(array $data, int $categoryId)
    {
        foreach ($data as $item) {
            $stmt = $this->connection->dbcon->prepare('SELECT count(*) FROM `movie` WHERE `movie_id` = :movieId');
            $stmt->bindParam(':movieId', $item['id']);
            if ($res = $stmt->execute()) {
                $cnt = (int)$stmt->fetchColumn();
                if ($cnt === 0) {

                    $detailUrl = self::BASE_URL . 'cinema.php?id=' . $item['id'];
                    $detailContent = str_get_html(
                        html_entity_decode(
                            iconv("windows-1251", "utf-8", $this->getContent($detailUrl))
                        )
                    );
                    $detailResult = $this->parseDetailContent($detailContent);

                    $stmt = $this->connection->dbcon->prepare("INSERT INTO `movie` (
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
                        echo 'Cinema add : ' . $item['name'] . PHP_EOL;
                    }
                }
            }

            $this->saveRateToDb($item);
        }
    }

    /**
     * @param array $item
     */
    private function saveRateToDb(array $item)
    {
        foreach ($item['votes'] as $el) {

            $sql = "INSERT INTO `rating` (`movie_id`, `vote_date`, `rate`) VALUES (:movieId, :voteDate, :rate)";
            $stmt = $this->connection->dbcon->prepare($sql);
            $stmt->bindParam(':movieId', $item['id']);
            $stmt->bindParam(':voteDate', $el['date']);
            $stmt->bindParam(':rate', $el['rate']);

            $res = $stmt->execute();
        }
    }

    /**
     * @return DateTime
     * @throws Exception
     */
    private function getLastParsingDate(): DateTime
    {
        $sql = "SELECT * FROM config";
        $stmt = $this->connection->dbcon->query($sql);
        $row = $stmt->fetchAll();
        if ($row) {
            return new DateTime($row[0][0]);
        }
        return new DateTime('0000-00-00');
    }
}