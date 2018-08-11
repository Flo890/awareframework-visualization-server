<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 10/08/2018
 * Time: 13:11
 */

require_once 'FeatureGenerator.php';

class TimeInConversationFeatureGenerator extends FeatureGenerator
{


    /**
     * @param $device_id
     * @param $granularity  currently only max is supported, which is implemented as hourly
     * @return array
     *         "2018_08_15-17" => array(
     *              "silence" => 1442,  // number of seconds between 17:00 and 18:00 at 15.08.2018 the user spent in a silent environment
     *              "noise" => 1120,    // ... in a noisy environment
     *              "voice" => 1038,    // ... talking / near somebody talking
     *              "unknown" => 0      // amount of seconds that could not be classified
     *         )
     *     - ideally the sum of all 4 categories should be 3600, but that is practically not the case TODO could normalize it to a sum of 3600
     *     - data granularity is hourly
     */
    public function getData($device_id, $granularity)
    {
        if(!$this->checkGranularitySupport(array('/hourly/'), $granularity)) return;

        $data = $this->dbreader->queryDatabaseForData('plugin_studentlife_audio_android','inference', $device_id);

        $accumulatedHourly = array();
        if(sizeof($data) > 1) {
            for ($i = 0; $i < sizeof($data) - 1; $i++) {
                $data_record_i = $data[$i];

                // the difference in seconds between log-record i and i+1
                // so we do assume that the classified state lasted for this amount of seconds, starting at the timestamp
                $millis_timestamp_i = $data_record_i['timestamp'];
                $unix_timestamp_i = intval(round($millis_timestamp_i / 1000));
                $millis_timestamp_iplusone = $data[$i + 1]['timestamp'];
                $diff_millis = $millis_timestamp_iplusone - $millis_timestamp_i;
                $diff_secs = intval(round($diff_millis / 1000));
                // if this is the last datapoint within an clock-hour, limit the diff (so that it not becomes e.g. 9000 if there are no data points in the following 2 hours)
                $day_hour = date($this->getDateFormatForGranularity($granularity), $unix_timestamp_i);
                $date_of_hour = new DateTime(date('Y-m-d H:00', $unix_timestamp_i));
                $secs_to_next_full_hour = 3600 - ($unix_timestamp_i - $date_of_hour->getTimestamp());
                $limited_diff_secs = min($diff_secs, $secs_to_next_full_hour);


                if (!isset($accumulatedHourly[$day_hour])) {
                    $accumulatedHourly[$day_hour] = array(
                        'silence' => 0,
                        'noise' => 0,
                        'voice' => 0,
                        'unknown' => 0
                    );
                }
                switch ($data_record_i['inference']) {
                    case 0:
                        $accumulatedHourly[$day_hour]['silence'] += $limited_diff_secs;
                        break;
                    case 1:
                        $accumulatedHourly[$day_hour]['noise'] += $limited_diff_secs;
                        break;
                    case 2:
                        $accumulatedHourly[$day_hour]['voice'] += $limited_diff_secs;
                        break;
                    default:
                        $accumulatedHourly[$day_hour]['unknown'] += $limited_diff_secs;
                        break;
                }
            }
        }

        return $accumulatedHourly;
    }
}