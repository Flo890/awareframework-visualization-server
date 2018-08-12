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
            //echo "sync call from $sync_from to $to";
            $this->syncDataFromTo($sync_from, $to);
            $counter++;
        }
        echo "executed $counter calls";
    }

    public function syncDataFromTo($from, $to){

    }

}