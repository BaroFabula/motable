<?php
if($config->dbtype=="mongodb") {
    $client = new MongoDB\Client($config->dburl);
    $db = $client->{$config->dbname};

    /** Get Data Collection */
    $data = $db->{$config->collection};
    $rows = $data->find();
    $titlerows = $data->find();
}

if($config->dbtype == "api"){
    $api = json_decode(file_get_contents('./config-api.json'));

    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$api->user:$api->password")
        )
    ));

    $h = get_headers($api->dburl);
    $prerows = json_decode(file_get_contents($api->dburl, false, $context))->results;
    $rows = array();
    foreach ($prerows as $pr){
        $pr->_id = ($pr->_id->{'$oid'});
        //$pr = (array)$pr;
        //print_r($pr);
        array_push($rows, $pr);
    }
    $titlerows = $prerows;
}


$fieldnames = Array();
foreach ($titlerows as $rt) {
    $titles = array_keys(get_object_vars($rt));
    foreach ($titles as $title) {
        if (!in_array($title, $fieldnames)) {
            array_push($fieldnames, $title);
        }
    }
}
