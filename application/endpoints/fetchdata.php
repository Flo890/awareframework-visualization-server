<?php

/**
 * endpoint parameters:
 * feature_name    one of the features listed in datamappings.json (required)
 * participant_id (required)
 * granularity     e.g. 60minutes, where 60 is interchangeable
 * from            timestamp (seconds)
 * to              timestamp (seconds)
 */
header("Access-Control-Allow-Origin: *"); // TODO remove as soon as client hosted from within this project
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization');
// answer OPTIONS pre flight request immediately
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    die('ok');
}

require_once('../components/FeatureFetcher.php');

$feature_fetcher = new FeatureFetcher();

$feature_name = $_GET['feature_name']; // TODO return 400 if parameter(s) missing
$participant_id = $_GET['participant_id'];
if (isset($_GET['granularity'])) {
    $granularity = $_GET['granularity'];
} else {
    $granularity = 'hourly';
}

// input tolerance
if ($granularity == 'hourly') $granularity = '60minutes';

$from = isset($_GET['from']) ? intval($_GET['from'])*1000 : 0;
$to = isset($_GET['to']) ? intval($_GET['to'])*1000 : 9999999999999;

require_once('../components/DBReader.php');
$db_reader = new DBReader();

include('../components/auth.php');

$implemented_granularities = array('hourly'); // TODO implement more


$device_id = $db_reader->getDeviceIdForParticipantId($participant_id);
$dbresponse_assoc = $feature_fetcher->getFeature($feature_name, $device_id, $granularity, $from, $to);


echo json_encode($dbresponse_assoc);