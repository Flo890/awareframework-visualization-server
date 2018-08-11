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
            return false;
        } else {
            return true;
        }
    }

    protected function getDateFormatForGranularity($granularity){
        switch($granularity){
            case 'hourly':
                return 'd_m_Y-H';
                break;
        }
    }

    protected function getMillisOfGranularity($granularity){
        switch($granularity){
            case 'hourly':
                return 1000*60*60;
        }
    }

    protected function getGranularityTimestampForTimestamp($granularity, $timestamp){
        switch($granularity){
            case 'hourly':
                $dateTime = new DateTime(date('Y-m-d H:00',intval(round($timestamp/1000))));
                return $dateTime->getTimestamp()*1000;
                break;
        }
    }

}