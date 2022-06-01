<?php

function read_sitemap($base_url){
    # read simapxml
    $url_list = array();
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $context = [ 'http' => [ 'timeout' => 2, 'method' => 'GET' ], 'ssl' => [ 'verify_peer' => false, 'allow_self_signed'=> true ] ];
    $context = stream_context_create($context);
    $html = file_get_contents($base_url . "/sitemap.xml", false, $context);

    if (!empty($html)){
        $doc->loadHTML($html);
        $items = $doc->getElementsByTagName('loc');
        if(count($items) > 0)
        {
            foreach ($items as $tag1)
            {
                array_push($url_list, $tag1->nodeValue);
            }
        }
        else
        {
            array_push($url_list, $doc->saveHTML());
        }
    }
    return $url_list;
}


function curl($url){
    $ci = curl_init();
    curl_setopt($ci , CURLOPT_URL , $url);
    $result = curl_exec($ci);
    curl_close($ci);
}

// print_r(read_sitemap('https://example.com'));
?>

