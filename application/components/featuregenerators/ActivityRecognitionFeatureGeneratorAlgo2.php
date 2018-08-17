<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 11/08/2018
 * Time: 16:47
 */

require_once 'FeatureGenerator.php';

class ActivityRecognitionFeatureGeneratorAlgo2 extends FeatureGenerator
{

    public function getData($device_id, $granularity, $from, $to)
    {
        if (!$this->checkGranularitySupport(array('/^(\d+)minutes/'), $granularity)) return;

        $data = $this->dbreader->queryDatabaseForData('plugin_google_activity_recognition', 'activity_name', $device_id, $from, $to);
        if (sizeof($data) > 0) {
            $usage_times = array(); // timestamp => milliseconds of phone use, starting at timestamp
            $last_state = -1;
            $last_change_timestamp = -1;
            $accumulated_bins = array();
            if(sizeof($data) > 1) {
                // collect usage durations at points of time
                for ($i = 0; $i < sizeof($data) - 1; $i++) {
                    $data_record_i = $data[$i];
                    // init counters
                    if ($i == 0) {
                        $last_state = $data_record_i['activity_name'];
                        $last_change_timestamp = $data_record_i['timestamp'];
                        continue;
                    }
                    // on each further item, check for changes
                    if ($last_state != $data_record_i['activity_name']){
                        array_push($usage_times, array(
                            'start_timestamp' => $last_change_timestamp,
                            'duration_millis' => $data_record_i['timestamp']-$last_change_timestamp,
                            'activity' => $last_state
                        ));
                        $last_change_timestamp = $data_record_i['timestamp'];
                        $last_state = $data_record_i['activity_name'];
                        continue;
                    }
                }
                /*
                 * $usage_times:
                 * array(37) {
                 *    [0]=> array(3) { ["start_timestamp"]=> float(1533041083731) ["duration_millis"]=> float(3843084) ["activity"]=> string(5) "still" }
                 *    [1]=> array(3) { ["start_timestamp"]=> float(1533044926815) ["duration_millis"]=> float(26) ["activity"]=> string(7) "tilting" }
                 *   ...
                 * }
                 */


                $bin_size = $this->getMillisOfGranularity($granularity);

                foreach($usage_times as $a_usage) {
                    $usage_start_time_millis = $a_usage['start_timestamp'];
                    $usage_start_time_bin = $this->getGranularityTimestampForTimestamp($granularity, $usage_start_time_millis);

                    if ($this->getGranularityTimestampForTimestamp($granularity,($usage_start_time_millis + $a_usage['duration_millis'])) == $usage_start_time_bin) {
                        // if usage stays completely within one bin, simply add it
                        if (!isset($accumulated_bins["$usage_start_time_bin"])){
                            $accumulated_bins["$usage_start_time_bin"] = array();
                        }
                        if (!isset($accumulated_bins["$usage_start_time_bin"][$a_usage['activity']])){
                            $accumulated_bins["$usage_start_time_bin"][$a_usage['activity']] = 0;
                        }
                        $accumulated_bins["$usage_start_time_bin"][$a_usage['activity']] += round($a_usage['duration_millis']/1000);
                    }
                    else {
                        // split over multiple bins
                        // - this bin:
                        if (!isset($accumulated_bins["$usage_start_time_bin"])){
                            $accumulated_bins["$usage_start_time_bin"] = array();
                        }
                        if (!isset($accumulated_bins["$usage_start_time_bin"][$a_usage['activity']])){
                            $accumulated_bins["$usage_start_time_bin"][$a_usage['activity']] = 0;
                        }
                        $millis_within_this_bin = $bin_size - ($usage_start_time_millis - $usage_start_time_bin);
                        if ($millis_within_this_bin < 0) echo "$millis_within_this_bin = $bin_size - ($usage_start_time_millis - $usage_start_time_bin)";
                        $accumulated_bins["$usage_start_time_bin"][$a_usage['activity']] += round($millis_within_this_bin/1000);

                        $remaining_millis = $a_usage['duration_millis'] - $millis_within_this_bin;

                        for($i = 0; $i<ceil($remaining_millis/$bin_size); $i++){
                            $current_bin_time = floatval($usage_start_time_bin) + (($i+1)*$bin_size);
                            $current_bin_datekey = $current_bin_time;

                            $remaining_millis -= $bin_size;
                            if ($remaining_millis < 0) {
                                break; // should no occur but who knows
                            }


                            if (!isset($accumulated_bins["$current_bin_datekey"])){
                                $accumulated_bins["$current_bin_datekey"] = array();
                            }
                            if (!isset($accumulated_bins["$current_bin_datekey"][$a_usage['activity']])){
                                $accumulated_bins["$current_bin_datekey"][$a_usage['activity']] = 0;
                            }
                            $accumulated_bins["$current_bin_datekey"][$a_usage['activity']] += round(min($bin_size, $remaining_millis)/1000);
                        }
                    }
                }
            }

            return $accumulated_bins;
        }
    }

}