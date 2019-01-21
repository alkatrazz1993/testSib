<?php
    function getContent($url, $postdata)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($http_code == 200)
        {
            $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
            curl_close($ch);

            $tmpHeaders = substr($output, 0, $header_size);
            $postResult = substr($output, $header_size);

            $headers = array();
            foreach(explode("\n",$tmpHeaders) as $header)
            {
                $tmp = explode(":",trim($header),2);
                if (count($tmp)>1)
                {
                    $headers[strtolower($tmp[0])] = trim(strtolower($tmp[1]));
                }
            }

            $encoding="utf-8";
            if (isset($headers['content-type']))
            {
                $tmp = explode("=", $headers['content-type']);
                if (count($tmp)>1)
                {
                    $encoding = $tmp[1];
                }
            }
            if ($encoding != "utf-8")
            {
                $postResult = iconv($encoding, "UTF-8", $postResult);
            }
            return $postResult;

        } else {
            return $http_code;
        }

    }



    function parseContent()
    {
        if (isset($_POST['isin'])) {

            require_once 'helpers/simple_html_dom.php';

            $isin = trim(strip_tags($_POST['isin']));
            $arrayIsin = explode(',', $isin);
            $isins = [];

            foreach ($arrayIsin as $key => $value) {

                $targetUrl = "https://www.isin.ru/ru/foreign_isin/db/index.php";

                $content = getContent($targetUrl, array(
                    'whatseek' => trim($value),
                    'seekkind' => 'isin',
                    'search' => '1'
                ));

                $isins[$key] = array();

                if ($content) {
                    $html = str_get_html($content);

                    if ($html->innertext != '' and count($html->find("table.bordered"))) {

                        foreach ($html->find('table.bordered td') as $keyTd => $tr) {

                            if ($keyTd == 0 || $keyTd == 3) {

                                array_push($isins[$key], $tr->plaintext);

                            }
                        }

                    }
                }
                sleep(5);
            }
        }

        return $isins;
    }

    $isins = parseContent();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Поиск по коду ISIN</title>

    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" media="all">
    <script type="text/javascript" src="js/bootstrap.js"></script>

</head>
<body>
    <div class="container bg-faded content-center">
        <div class="row">
            <div class="col-8 mx-auto mb-5">
            </div>
            <div class="col-8 mx-auto text-left">
                <?php if( !empty( $isins )) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>ISIN</th>
                                <th>CFI</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? foreach ( $isins as $key => $result ) {
                                if( !empty( $result )) { ?>
                                <tr>
                                    <? foreach ( $result as $value ) { ?>
                                        <td><? echo $value ?></td>
                                    <?}?>
                                </tr>
                                <?} else { ?> <p>Результатов не найдено.</p><?
                            }}?>
                            </tbody>
                        </table>
                    </div>

                <? } ?>

            </div>
            <div class="col-8 mx-auto text-left">
                <form method="post" action="index.php">
                    <label for="isinTextArea">Введите номера ISIN через запятую</label>
                    <p><textarea class="form-control" id="isinTextArea" autofocus required name="isin"></textarea></p>
                    <p><button class="btn btn-group" type="submit" name="submit">Отправить</button></p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>