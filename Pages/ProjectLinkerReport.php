<?php
/**
 * Created by IntelliJ IDEA.
 * User: eloh
 * Date: 2020-04-23
 * Time: 16:24
 */

namespace Stanford\ProjectLinker;

/** @var \Stanford\ProjectLinker\ProjectLinker $module */

use PHPSQLParser\Test\Creator\functionTest;
use REDCap;
$url = $module->getUrl("Pages/ProjectLinkerDownload.php");

?>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- jquery, popper and bootstrap are already loaded through redcap,
    if you try to include again, collapse will not work-->

    <style>
        body {
            width: 90%;
            height: 100px;
            padding: 5px;

        }
    </style>

    <script type="text/javascript">


      function download( pid, type, recordids) {

        var url = '<?php echo $url; ?>';

        $.ajax({
          type: "POST",
          url: url,
          data: {
            "type": type,
            "proj_id": pid,
            "recordids" : recordids
          },
          success: function (data, textStatus, jqXHR) {
            console.log(data);
            try {
              var data_array = JSON.parse(data);

              // Check return status to see what we should do.
              // Status = 0 - Found some type of error
              // Status = 1 - MRN already in project - go to that record
              // Status = 2 - Found Records, display demographics
              if (data_array.status === 0) {
                document.getElementById('messages').style.color = 'red';
                document.getElementById('messages').innerHTML = data_array.message;
                document.getElementById('records').innerHTML = data_array.unableToSend;

              } else if (data_array.status === 2) {
                document.getElementById('messages').style.color = 'black';

                  document.getElementById('messages').innerHTML = data_array.message;
                  var iframe = document.createElement("iframe");
                  iframe.setAttribute("src", data_array.file);
                  iframe.setAttribute("style", "display: none");
                  document.body.appendChild(iframe);
                }

            } catch (error) {
              document.getElementById('messages').style.color = 'red';
              document.getElementById('messages').innerHTML = data;
            }
          },
          error: function (hqXHR, textStatus, errorThrown) {
          }
        });

      }

    </script>

</head>
<body>
<div  class="form-row justify-content-md-center" id="messages"></div>

<table class="table">
    <theader>
        <tr>
            <th>
                Project Name
            </th>
            <th>
                Contact Information
            </th>
            <th>
                Access
            </th>
            <th>
                MRNs
            </th>
            <th>
                Downloads
            </th>
        </tr>
    </theader>
    <tbody>
<?php

$parentPid = $module->getProjectSetting("parent-pid");
$currentPid = PROJECT_ID;
$allProjects = json_decode(REDCap::getData($parentPid, 'json', null, array('redcap_pid',
    'recordid_field', 'mrn_field', 'project_name',
    'access'),
    null, null, false, false, false, null, true, false), true);

// set the key to be the pid
foreach ($allProjects as $key =>$project)  {
    $allProjects[$project['redcap_pid']] = $project;
    unset($allProjects[$key]);
}

$thisProject = $allProjects[$currentPid];
if (!$thisProject) {
    echo 'This project has not been registered in the parent project.';
    exit;
}
$mrnmatches = [];



// make sure mrns are in same format
$standardize_mrn = function ($mrn) {
    $tmp = preg_replace('/-/', '', $mrn);
    return str_pad($tmp, 8, '0', STR_PAD_LEFT);
};

function standardizemrns($mrn) {
    $tmp = preg_replace('/-/', '', $mrn);
    return str_pad($tmp, 8, '0', STR_PAD_LEFT);
}

// get the mrns of the current project
$tmparray = preg_split('/\s+/', REDCap::getData($currentPid, 'csv', null, $module->getProjectSetting('mrn-field')));
array_shift($tmparray); // get rid of "mrn" column header
$mrns = array_map($standardize_mrn, $tmparray);


foreach ($allProjects as $pid => $fields) {
    if ($pid != $currentPid) {

        $tmparray = REDCap::getData($fields['redcap_pid'], 'array', null, $fields['mrn_field']);
        /*print_array($tmparray);
            [11452] => Array
        (
            [42] => Array
                (
                    [mrn] => 05181235
                )

        )*/

        //$tmparray = preg_split('/\s+/',
        //    REDCap::getData($fields['redcap_pid'], 'csv', null, array($fields['recordid_field'],
        //        $fields['mrn_field'])));

        //array_shift($tmparray); // get rid of column headers

        // map the record_id of the other project to it's standardized mrn
        // this is because using standardized mrn to download data will not match correctly
        // also mrn match is slow and hopefully record_id match should be faster

        $othermrns = [];
        //array_map($standardize_mrn, $tmparray);
        foreach($tmparray as $recordid => $otherprojrecord) {
            if (!$otherprojrecord[$fields['mrn_field']]) {
                $otherprojrecord = array_pop($otherprojrecord);
            }
            $othermrns[$recordid]
                = standardizemrns($otherprojrecord[$fields['mrn_field']]);
        }

        $tmparray = array_intersect($othermrns, $mrns);
        //print_array($tmparray);

        $key = array_search("00000000", $tmparray);
        if ($key !== false) {
            unset($tmparray[$key]);
        }
        if ($tmparray) {
            $mrnmatches[$pid] = $tmparray;
        }
    }

}

// table
foreach ($allProjects as $pid => $fields) {

//foreach ($matchedpids as $pid) {
    if ($pid != $currentPid) {
        $project = $allProjects[$pid];
        $projdetails = $module->getProjectDetails($pid);

        echo '<tr><td>' . $projdetails['app_title'] . '</td><td>';
        echo 'PI Name: ' . $projdetails['project_pi_firstname'] . ' ' . $projdetails['project_pi_lastname']
            . '<br>';
        echo 'PI Email: ' . $projdetails['project_pi_email'] . '<br>';
        echo 'Contact Name: ' . $projdetails['project_contact_name'] . '<br>';;
        echo 'Contact Email: ' . $projdetails['project_contact_email'] . '<br>';;
        echo '</td>';

        echo '<td>' . $project['access'] . '</td>';
        echo '<td># of MRNs: ' . count($mrnmatches[$pid]);
        if (strpos(strtolower($project['access']), 'dictionary') === false ) {
            if (count($mrnmatches[$pid]) <= 20) {
                echo '<br>' . implode(', ', $mrnmatches[$pid]) . '</td>';
            } else {
                echo '<br><input class="btn my-1" type="button" data-toggle="collapse" 
                    data-target="#mrnCollapse" aria-expanded="false"
                    aria-controls="mrnCollapse" value="Show MRNS"/>';
                echo '<div class="collapse" id="mrnCollapse"><div class="card card-body">';
                echo implode(', ', $mrnmatches[$pid]);
                echo '</div></div>';
            }
        } else {
            echo '</td>';
        }
        echo '<td><input type="button" class="m-1 btn" onclick="download(' . $pid .
            ',\'dictionary\',null); return false;" 
            value="Data Dictionary"/>';
        if (count($mrnmatches[$pid]) &&
            strtolower($project['access']) === 'data') {

            echo '<input type="button" class="m-1 btn" onclick="download(' . $pid .
                ',\'labelleddata\', \''
                .implode(',', array_keys($mrnmatches[$pid]))
                .'\'); return false;" value="Labelled Data"/>';
            echo '<input type="button" class="m-1 btn" onclick="download(' . $pid .
                ',\'rawdata\', \''
                .implode(',', array_keys($mrnmatches[$pid])).
                '\'); return false;" value="Raw Data"/></td></tr>';
        } else {
            echo '</td></tr>';
        }
    }
}
?>
    </tbody>
</table>
</body>
</html>

