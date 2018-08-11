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

    public abstract function getData($device_id, $granularity, $from, $to);

    protected function checkGranularitySupport($supported_granularities, $granularity){
        $found = false;
        foreach($supported_granularities as $granularity_regex){
            if(preg_match($granularity_regex, $granularity) == 1){
                $found = true;
            }
        }
        if (!$found) {
            header('HTTP/1.1 400 Bad Request');
            echo "granularity $granularity not supported!!!!";
            return false;
        } else {
            return true;
        }
    }

    protected function getDateFormatForGranularity($granularity){
        if($granularity == 'hourly') {
            return 'd_m_Y-H';
        }
        else if(preg_match('/^(\d+)minutes/', $granularity) == 1) {
            return 'd_m_Y-H_i';
        }
    }

    protected function getParseableDateFormatForGranularity($granularity){
        if($granularity == 'hourly') {
            return 'Y-m-d H:00';
        }
        else if(preg_match('/^(\d+)minutes/', $granularity) == 1) {
            return 'Y-m-d H:i';
        }
    }

    protected function getMillisOfGranularity($granularity){
        if($granularity == 'hourly'){
            return 1000*60*60;
        }
        else if(preg_match('/^(\d+)minutes/',$granularity, $matches) == 1){
            return $matches[1]*60*1000;
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