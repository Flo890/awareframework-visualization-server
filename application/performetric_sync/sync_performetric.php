<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 12/08/2018
 * Time: 12:15
 */

require_once '../config/performetric.php';
require_once 'PerformetricSyncer.php';

if(!isset($performetric) || !$performetric['enable']){
    die('performetric is not enabled in config/performetric.php');
}

$performetric_syncer = new PerformetricSyncer();
$performetric_syncer->syncData();