<?php
$ch = curl_init('https://www.bcv.org.ve/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
$html = curl_exec($ch);
curl_close($ch);

if (preg_match('/<div id="dolar".*?>.*?<strong>(.*?)<\/strong>.*?<\/div>/is', $html, $matches)) {
    $rate = trim(str_replace(',', '.', $matches[1]));
    echo "RATE FOUND: " . $rate . "\n";
} else {
    echo "RATE NOT FOUND\n";
    // Let's print out what we found for 'dolar'
    if (preg_match('/dolar/i', $html)) {
        echo "Found the word dolar but regex failed\n";
    }
}
