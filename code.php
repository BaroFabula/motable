<?php
$client = new MongoDB\Client($config->dburl);
$db = $client->{$config->dbname};

/** Get Data Collection */
$data = $db->{$config->collection};
$fieldnames = getFieldnamesOfCollection($data);
$rows = $data->find();

function getFieldnamesOfCollection($collection)
{
    $titles = Array();
    $dataset = $collection->find();
    foreach ($dataset as $ds) {
        $dstitles = array_keys(get_object_vars($ds));
        foreach ($dstitles as $dstitle) {
            if (!in_array($dstitle, $titles)) {
                array_push($titles, $dstitle);
            }
        }
    }
    return $titles;
}