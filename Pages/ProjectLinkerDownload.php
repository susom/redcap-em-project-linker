<?php
/**
 * Created by IntelliJ IDEA.
 * User: eloh
 * Date: 2020-05-04
 * Time: 19:18
 */
namespace Stanford\ProjectLinker;

/** @var \Stanford\ProjectLinker\ProjectLinker $module */

use REDCap;
$type = isset($_POST['type']) && !empty($_POST['type']) ? $_POST['type'] : 'dictionary';
$proj_id = isset($_POST['proj_id']) && !empty($_POST['proj_id']) ? $_POST['proj_id'] : null;
$recordids = isset($_POST['recordids']) && !empty($_POST['recordids']) ? $_POST['recordids'] : null;


if (!empty($proj_id)) {

    if ($type == 'dictionary') {
        $projdatadictionary = REDCap::getDataDictionary($proj_id, 'csv');
        if (empty($projdatadictionary)) {
            print json_encode(array(
                    "status" => 0,
                    "proj_id" => $proj_id,
                    "message" => "Unable to download data dictionary for PID " . $proj_id
                )
            );
            return;

        }
        download_csv($projdatadictionary, 'dictionary', 'proj'.$proj_id.'_datadictionary.csv');

    }
    if ($type == 'data') {
        if (empty($recordids)) {
            print json_encode(array(
                    "status" => 0,
                    "proj_id" => $proj_id,
                    "message" => "Invalid data request"
                )
            );
            return;

        }

        $projdata = REDCap::getData($proj_id, 'csv', explode(',', $recordids));

        if (empty($projdata)) {
            print json_encode(array(
                    "status" => 0,
                    "proj_id" => $proj_id,
                    "message" => "No data found for matching mrns in PID " . $proj_id
                )
            );
            return;
        }
        download_csv($projdata, 'data','proj'.$proj_id.'_data.csv');

    }
}

function download_csv($csv, $type, $filename) {
    global $module;
    $filename = date("YmdHis") . '_'. $filename;
    $fh = fopen(APP_PATH_TEMP.$filename, 'w') or
    die(json_encode(array(
        "status"=> 0,
        "type" => $type,
        'message' => 'Unable to write to file'))
    );
    ;
    fwrite($fh, $csv);
    fclose($fh);


    print json_encode(array(
        "status" => 2,
        "type" => $type,
        "message" =>  'CSV file downloaded',
        "file" => $module->getUrl('../../temp/'.$filename)
    ));
}
