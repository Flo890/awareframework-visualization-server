<?php

/**
 * endpoint parameters:
 * feature_name    one of the features listed in datamappings.json (required)
 * device_id       (required)
 * granularity     one of [max] (optional, default: max)
 */

require_once('../components/FeatureFetcher.php');

$feature_fetcher = new FeatureFetcher();

$feature_name = $_GET['feature_name']; // TODO return 400 if parameter(s) missing
$participant_id = $_GET['participant_id'];
if (isset($_GET['granularity'])) {
    $granularity = $_GET['granularity'];
} else {
    $granularity = 'hourly';
}
$from = isset($_GET['from']) ? $_GET['from']*1000 : 0;
$to = isset($_GET['to']) ? $_GET['to']*1000 : 9999999999999;

require_once('../components/DBReader.php');
$db_reader = new DBReader();

include('../components/auth.php');

$implemented_granularities = array('hourly'); // TODO implement more


$device_id = $db_reader->getDeviceIdForParticipantId($participant_id);
$dbresponse_assoc = $feature_fetcher->getFeature($feature_name, $device_id, $granularity, $from, $to);


echo json_encode($dbresponse_assoc);