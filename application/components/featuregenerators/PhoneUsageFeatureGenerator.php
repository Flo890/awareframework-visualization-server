<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 10/08/2018
 * Time: 15:19
 */

require_once 'FeatureGenerator.php';

class PhoneUsageFeatureGenerator extends FeatureGenerator
{

    public function getData($device_id, $granularity)
    {
        $this->checkGranularitySupport(array('max','hourly'), $granularity);

        $data = $this->dbreader->queryDatabaseForData('screen','screen_status', $device_id);

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
                    $last_state = in_array($data_record_i['screen_status'],[0,2]) ? 'NOT_USING' : 'USING';
                    $last_change_timestamp = $data_record_i['timestamp'];
                    continue;
                }
                // on each further item, check for changes
                if ($last_state == 'NOT_USING' && in_array($data_record_i['screen_status'],[1,3])){
                    $last_change_timestamp = $data_record_i['timestamp'];
                    $last_state = 'USING';
                    continue;
                }
                if ($last_state == 'USING' && in_array($data_record_i['screen_status'],[0,2])){
                    array_push($usage_times, array(
                        'start_timestamp' => $last_change_timestamp,
                        'duration_millis' => $data_record_i['timestamp']-$last_change_timestamp)
                    );
                    $last_state = 'NOT_USING';
                    continue;
                }
            }



            if ($granularity == 'hourly' || $granularity == 'max') {
                $bin_size = 1000 * 60 * 60;
                $accumulator_data_format = 'd_m_Y-H';
                $bin_parse_data_format = 'Y-m-d H:00';
            } else {
                echo "granularity $granularity not supported";
                return;
            }

            foreach($usage_times as $a_usage) {
                $usage_start_time_millis = $a_usage['start_timestamp'];
                $usage_start_time_bin = date($accumulator_data_format, intval(round($usage_start_time_millis/1000)));
                $bin_time = new DateTime(date($bin_parse_data_format, intval(round($usage_start_time_millis/1000))));

                $duration = $a_usage['duration_millis'];

                if (date($accumulator_data_format,intval(round(($usage_start_time_millis + $a_usage['duration_millis'])/1000))) == $usage_start_time_bin) {
                    // if usage stays completely within one bin, simply add it
                    if (!isset($accumulated_bins[$usage_start_time_bin])){
                        $accumulated_bins[$usage_start_time_bin] = 0;
                    }
                    $accumulated_bins[$usage_start_time_bin] += intval(round($a_usage['duration_millis']/1000));
                }
                else {
                    // split over multiple bins
                    // - this bin:
                    if (!isset($accumulated_bins[$usage_start_time_bin])){
                        $accumulated_bins[$usage_start_time_bin] = 0;
                    }
                    $millis_within_this_bin = $bin_size - ($usage_start_time_millis - ($bin_time->getTimestamp()*1000));
                    $accumulated_bins[$usage_start_time_bin] += intval(round($millis_within_this_bin/1000));

                    $remaining_millis = $a_usage['duration_millis'] - $millis_within_this_bin;

                    for($i = 0; $i<ceil($remaining_millis/$bin_size); $i++){
                        $current_bin_time = ($bin_time->getTimestamp()*1000) + (($i+1)*$bin_size);
                        $current_bin_datekey = date($accumulator_data_format, intval(round($current_bin_time/1000)));

                        if (!isset($accumulated_bins[$current_bin_datekey])){
                            $accumulated_bins[$current_bin_datekey] = 0;
                        }
                        $accumulated_bins[$current_bin_datekey] += intval(round(min($bin_size, $remaining_millis - ($i*$bin_size))/1000));
                    }
                }
            }
        }
        return $accumulated_bins;

    }
}