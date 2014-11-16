<?php

include './DbfDataExtract.php';

use DbfDatabase as Dbf;

$db = new Dbf\DataExtract('./sample_data/sample.dbf');

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
$result = $db->filter( array( 'COUNTRY'=>'U.s.a.'));

foreach ( $result as $row) {
    print_r($row);
}

//Sample data filter by function
$result1 = $db->filter( function ( array $record) {
    if ( $record['COUNTRY'] == "U.s.a." || $record['COUNTRY'] == "Canada" ) {
        return true;
    }
});
foreach ( $result1 as $row) {
    print_r($row);
}

//Sample data filter by U.s.a. y Ca
$result2 = $db->filter( array( 'COUNTRY'=>'U.s.a.', 'STATE_PROV' => 'Ca'));

foreach ( $result2 as $row) {
    print_r($row);
}
