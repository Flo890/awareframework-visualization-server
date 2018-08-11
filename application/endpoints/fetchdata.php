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
$device_id = $_GET['device_id'];
if (isset($_GET['granularity'])) {
    $granularity = $_GET['granularity'];
} else {
    $granularity = 'hourly';
}

$implemented_granularities = array('hourly'); // TODO implement more

$dbresponse_assoc = $feature_fetcher->getFeature($feature_name,$device_id, $granularity);


echo json_encode($dbresponse_assoc);