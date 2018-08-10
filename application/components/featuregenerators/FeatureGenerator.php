<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 10/08/2018
 * Time: 13:11
 */

abstract class FeatureGenerator
{

    public function __construct()
    {
        $this->dbreader = new DBReader();
    }

    public abstract function getData($device_id, $granularity);

    protected function checkGranularitySupport($supported_granularities, $granularity){
        if (!in_array($granularity, $supported_granularities)) {
            header('HTTP/1.1 400 Bad Request');
            echo "TimeInConversationFeatureGenerator only supports granularity 'max' and 'hourly'";
            return;
        }
    }

}