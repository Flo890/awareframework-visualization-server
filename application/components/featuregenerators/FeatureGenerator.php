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

    /**
     * @param $granularity
     * @return string
     * @deprecated formatted dates should not be used. better use a timestamp, and transform it into a binned value using getGranularityTimestampForTimestamp
     */
    protected function getDateFormatForGranularity($granularity){
        if($granularity == 'hourly' || $granularity == '60minutes') { // TODO the second case should not be necessary!
            return 'd_m_Y-H';
        }
        else if(preg_match('/^(\d+)minutes/', $granularity) == 1) {
            return 'd_m_Y-H_i';
        }
        else if ($granularity == 'daily') {
            return 'd_m_Y';
        }
    }

    protected function getParseableDateFormatForGranularity($granularity){
        if($granularity == 'hourly') {
            return 'Y-m-d H:00';
        }
        else if(preg_match('/^(\d+)minutes/', $granularity) == 1) {
            return 'Y-m-d H:i';
        }
        else if ($granularity == 'daily') {
            return 'Y-m-d';
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

    /**
     * @param $granularity
     * @param $timestamp in millis
     * @return float|int bin timestamp in millis
     */
    protected function getGranularityTimestampForTimestamp($granularity, $timestamp){
        if(preg_match('/^(\d+)minutes/',$granularity, $matches) == 1){
            $minutes = $matches[1];
            if ($minutes < 60){
                // get hour (floored)
                $timestamp_floored_hour = $this->getGranularityTimestampForTimestamp('60minutes',$timestamp);
                // calc diff of timestamp to hour
                $minutes_in_hour = $timestamp - $timestamp_floored_hour;
                // make floored div
                $amount_bins_in_hour = floor($minutes_in_hour/(60*1000*$minutes));
                // add floored-diff*minutes to hour-timestamp
                $timestamp_bin = $timestamp_floored_hour + ($amount_bins_in_hour*$minutes*60*1000);
                return $timestamp_bin;
            }
            else if ($minutes == 60) {
                $dateTime = new DateTime(date('Y-m-d H:00',intval(round($timestamp/1000))));
                return $dateTime->getTimestamp()*1000;
            }
            else {
                // multiple hours
                if ($minutes % 60 != 0) die('for granularity higher than 60 minutes, only full hours are allowed! (60,120, 180, ...)');
                $hour_granularity = $minutes/60;
                // get hour of day
                $day_timestamp_floored = $this->getGranularityTimestampForTimestamp('daily',$timestamp);
                // floored hour of day / granularity hours
                $millis_in_day = $timestamp - $day_timestamp_floored;
                // floored div
                $floored_div = floor($millis_in_day/($hour_granularity*60*60*1000));
                $timestamp_bin = $day_timestamp_floored + ($floored_div*$hour_granularity*60*60*1000);
                return $timestamp_bin;
            }
        }
        else if(preg_match('/daily/',$granularity, $matches) == 1) {
            $dateTime = new DateTime(date('Y-m-d H',intval(round($timestamp/1000))));
            return $dateTime->getTimestamp()*1000;
        }


    }

}