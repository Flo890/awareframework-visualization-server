<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 11/08/2018
 * Time: 10:30
 */

require_once 'FeatureGenerator.php';

class HomeWorkTimeFeatureGenerator extends FeatureGenerator
{

    /**
     * HomeWorkTimeFeatureGenerator constructor.
     */
    public function __construct()
    {
        parent::__construct();
        require_once '../config/config.php';
        $study_config = $this->dbreader->getStudyConfig($study_id);
        foreach($study_config->sensors as $sensor){
            if($sensor->setting == 'frequency_wifi'){
                $this->wifi_sensor_frequency = $sensor->value;
                return;
            }
        }
    }

    public function getData($device_id, $granularity)
    {
        if(!$this->checkGranularitySupport(array('hourly'), $granularity)) return;

        $data = $this->dbreader->queryDatabaseForData('wifi','ssid', $device_id);
        $wifi_mappings = $this->getWifiMapping($device_id);

        $current_timestamp_millis = $this->getGranularityTimestampForTimestamp($granularity, $data[0]['timestamp']);
        $index_in_data = 0;
        $accumulated_data = array();
        while($current_timestamp_millis < $data[sizeof($data)-1]['timestamp']){
            $home_count = 0;
            $work_count = 0;
            $other_count = 0;
            while(isset($data[$index_in_data]) && $data[$index_in_data]['timestamp'] > $current_timestamp_millis && $data[$index_in_data]['timestamp'] <= $current_timestamp_millis+$this->getMillisOfGranularity($granularity)){
                if (!empty($data[$index_in_data]['ssid']) && isset($wifi_mappings[$data[$index_in_data]['ssid']])){
                    if ($wifi_mappings[$data[$index_in_data]['ssid']] == 'home'){
                        $home_count++;
                    }
                    else if ($wifi_mappings[$data[$index_in_data]['ssid']] == 'work'){
                        $work_count++;
                    }
                    else {
                        $other_count++;
                    }
                } else {
                    $other_count++;
                }
                $index_in_data++;
            }
            $current_timestamp_millis += $this->getMillisOfGranularity($granularity);

            $accumulated_data[date($this->getDateFormatForGranularity($granularity),intval(round($current_timestamp_millis/1000)))] = array(
                'home' => $home_count*$this->wifi_sensor_frequency,
                'work' => $work_count*$this->wifi_sensor_frequency,
                'other' => $other_count*$this->wifi_sensor_frequency
            );
        }
        return $accumulated_data;
    }



    private function getWifiMapping($device_id){
        $meta_data = $this->dbreader->queryMetaDatabase('wifi_location', $device_id);
        $mappings = array();
        foreach($meta_data as $a_mapping){
            $mappings[$a_mapping['wifi_ssid']] = $a_mapping['location'];
        }
        return $mappings;
    }
}