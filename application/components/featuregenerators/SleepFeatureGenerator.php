<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 12/08/2018
 * Time: 08:34
 */

require_once 'FeatureGenerator.php';
require_once 'PhoneUsageFeatureGenerator.php';

class SleepFeatureGenerator extends FeatureGenerator
{

    public function getData($device_id, $granularity, $from, $to)
    {
        if (!$this->checkGranularitySupport(array('/daily/'), $granularity)) return;

        $phone_usage_feature_generator = new PhoneUsageFeatureGenerator();
        $phone_usage_data = $phone_usage_feature_generator->getData($device_id, '5minutes', $from, $to);

        $parseable_5m_dateformat = $this->getDateFormatForGranularity('5minutes');

        // timestamps
        $usage_times = array_keys($phone_usage_data);

        $sleeptimes = array();
        for($i = 0; $i<sizeof($usage_times)-2; $i++){
            $current_usage_item_datetime = date_create_from_format($parseable_5m_dateformat, $usage_times[$i]);
            $current_usage_item_timestamp = $current_usage_item_datetime->getTimestamp();
            $current_usage_secs = $phone_usage_data[$usage_times[$i]];
            // the the next item
            $next_item_datetime = date_create_from_format($parseable_5m_dateformat, $usage_times[$i+1]);
            $next_item_timestamp = $next_item_datetime->getTimestamp();

            // estimate whether this could be a night
            // - went to sleep between 6pm and 5am
            // - got up between 4am and 12am
            // - slept at least 2 hours
            $time_diff_secs = $next_item_timestamp - $current_usage_item_timestamp - $current_usage_secs;
            $current_hours = date_format($current_usage_item_datetime, 'H');
            $next_hours = date_format($next_item_datetime, 'H');
            if(($current_hours >= 18 || $current_hours < 4) && ($next_hours >= 4 && $next_hours <= 12) && $time_diff_secs >= 60 * 2){
                $sleeptimes[date_format($next_item_datetime, $this->getDateFormatForGranularity('daily'))] = array(
                    'sleep_secs' => $time_diff_secs,
                    'got_up_timestamp' => $next_item_timestamp,
                    'went_to_bed' => $current_usage_item_timestamp
                );
            }
        }

        return $sleeptimes;
    }
}