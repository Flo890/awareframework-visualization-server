<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 11/08/2018
 * Time: 12:02
 */

require_once 'FeatureGenerator.php';

class ActivityRecognitionFeatureGenerator extends FeatureGenerator
{

    public function getData($device_id, $granularity)
    {
        if(!$this->checkGranularitySupport(array('/hourly/'), $granularity)) return;

        $data = $this->dbreader->queryDatabaseForData('plugin_google_activity_recognition','activity_name', $device_id);
        if (sizeof($data) > 0){
           // Android
            $current_timestamp_millis = $this->getGranularityTimestampForTimestamp($granularity, $data[0]['timestamp']);
            $index_in_data = 0;
            $accumulated_data = array();
            while($current_timestamp_millis < $data[sizeof($data)-1]['timestamp']){
                $current_activities = array(
                    'still' => 0,
                    'tilting' => 0,
                    'on_foot' => 0,
                    'unknown' => 0,
                    'in_vehicle' => 0,
                    'on_bicycle' => 0
                );
                if (isset($rest)){
                    $current_activities[$rest['activity']] = $rest['seconds'];
                    unset($rest);
                }
                while(
                    isset($data[$index_in_data]) && isset($data[$index_in_data+1])
                    && $data[$index_in_data]['timestamp'] <= $current_timestamp_millis+$this->getMillisOfGranularity($granularity)
                ){
                    $diff_to_next_logitem = $data[$index_in_data+1]['timestamp'] - $data[$index_in_data]['timestamp'];
                    $max_milliseconds = $this->getMillisOfGranularity($granularity) - ($data[$index_in_data]['timestamp'] -  $current_timestamp_millis); // limit the amount of seconds to add by the remaining time in this hour
                    $current_activities[$data[$index_in_data]['activity_name']] += min(intval(round($diff_to_next_logitem/1000)), intval(round($max_milliseconds/1000)));
                    $index_in_data++;

                    if (isset($data[$index_in_data+1]) && $data[$index_in_data+1]['timestamp'] - $current_timestamp_millis > $this->getMillisOfGranularity($granularity)){
                        // this log entry ends in the next bin
                        $rest = array(
                            'activity' => $data[$index_in_data]['activity_name'],
                            'seconds' => min(intval(round(($data[$index_in_data+1]['timestamp'] -  $current_timestamp_millis + $this->getMillisOfGranularity($granularity))/1000)),3600)
                        );
                    }
                }
                $current_timestamp_millis += $this->getMillisOfGranularity($granularity);

                $accumulated_data[date($this->getDateFormatForGranularity($granularity),intval(round($current_timestamp_millis/1000)))] = $current_activities;
            }
            return $accumulated_data;
        }
        else {
            $data = $this->dbreader->queryDatabaseForData('plugin_ios_activity_recognition','activities', $device_id);
            if (sizeof($data) > 0) {
                // TODO implement iOS
            }
        }
    }


}