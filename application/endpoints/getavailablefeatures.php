<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 15/08/2018
 * Time: 16:51
 */

header("Access-Control-Allow-Origin: *"); // TODO remove as soon as client hosted from within this project
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization');
// answer OPTIONS pre flight request immediately
if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    die('ok');
}

$participant_id = $_GET['participant_id'];

require_once('../components/DBReader.php');
$db_reader = new DBReader();

include('../components/auth.php');

// get list of available data features from the json
// TODO check whether each feature has data per user
$mappings_config = json_decode(file_get_contents("../../datamappings.json"),true);
$features = array();
foreach($mappings_config["mappings"] as $feature_key => $feature_config){

    array_push($features, array(
        'key' => $feature_key,
        'display_name' => $feature_config['display_name']
    ));
}

echo json_encode($features);