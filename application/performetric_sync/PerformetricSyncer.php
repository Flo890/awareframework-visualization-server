<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 12/08/2018
 * Time: 12:16
 */

require_once '../components/DBReader.php';

class PerformetricSyncer
{

    /**
     * PerformetricSyncer constructor.
     */
    public function __construct()
    {
        $this->db_reader = new DBReader();
        $this->initDatabase();
        sleep(1);
    }

    public function initDatabase(){
        $this->db_reader->runSqlScriptOnAwareStudyDb('../../database_schemas/performetric_database_schema.sql');
    }

    public function syncData(){
        include '../config/performetric.php';
        // start date
        $sync_from = $this->db_reader->getLatestPerformetricSyncDate(); // TODO we assume this is a seconds timestamp. true?
        $sync_to = time();
        $sync_interval_minutes = $performetric['data_frequency'];
        if (!is_numeric($sync_from)) die('fetched sync from timestamp is not numeric');
        if (!is_numeric($sync_to)) die('fetched sync to timestamp is not numeric');
        echo "starting performetric sync from $sync_from to $sync_to";

        $counter = 0;
        for(; $sync_from < $sync_to; $sync_from += $sync_interval_minutes*60){
            $to = $sync_from+($sync_interval_minutes*60);

            if (!isset($access_info) || !isset($create_time) || time() > $create_time + $access_info->expires_in - 60) {
                // refresh 60 seconds before the access token expires
                $access_info = $this->requestAccessToken();
                $create_time = time();
            }

            $this->syncDataFromTo($sync_from, $to, $access_info);
            $counter++;
        }
        echo "executed $counter calls";
    }

    public function syncDataFromTo($from, $to, $access_info){
        $company_id = key($access_info->additional_info->organizations);
        $api_date_format = 'Y-m-d\TH:i:s.v';
        $from_formatted = date( $api_date_format, $from);
        $to_formatted = date( $api_date_format, $to);
        echo "sync call from $from_formatted to $to_formatted<br/>";

        $body = "{\"timeperiods\":[{\"from\":\"$from_formatted\",\"to\":\"$to_formatted\"}]}";
        $ch = curl_init("https://alphaapi.performetric.net/api/reports/$company_id/fatigue");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',"Authorization: Bearer {$access_info->access_token}"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        //echo $server_output;
        $response_raw = json_decode($server_output);
        if(sizeof($response_raw) > 0 && sizeof($response_raw[0]) > 0) {
            $response_json = $response_raw[0][0];
            if (isset($response_json->users)) {
                $user_mapping = $this->db_reader->getPerformetricUserMapping();
                $this->db_reader->insertFatigueLog($from, $to, $response_json, $user_mapping);
            } else {
                echo "no data available between $from_formatted and $to_formatted. sever returned: $server_output<br/>";
            }
        } else {
            echo "no data available between $from_formatted and $to_formatted. sever returned: $server_output<br/>";
        }

    }

    private function requestAccessToken() {
        echo 'will query new access token<br/>';
        include '../config/performetric.php';
        $params = "client_id={$performetric['client_id']}&client_secret={$performetric['client_secret']}&scope={$performetric['scope']}".
            "&grant_type={$performetric['grant_type']}&password={$performetric['password']}&username={$performetric['username']}";
        $ch = curl_init('https://alphaapi.performetric.net/api/oauth/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $response_json = json_decode($server_output);

        if(!isset($response_json->access_token)){
            echo $server_output;
            die('requesting access token failed');
        }

        return $response_json;
    }



}