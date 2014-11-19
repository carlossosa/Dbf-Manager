<?php

include './DbfDataExtract.php';

use DbfDatabase as Dbf;

$db = new Dbf\DataExtract('./sample_data/POS10010.dbf');

//Print All Data
foreach ( $db as $record) {
    print_r($record);
}

//Count record
echo "\r\nRecord ".count($db)."\r\n";

//Get specific record
print_r( $db->getRecord(3));

//Print field list
print_r($db->getFields());

//Sample data filter by U.s.a.
$result = $db->filter( array( 'CIDDOCUM02'=>'35'));

foreach ( $result as $row) {
    print_r($row);
}


