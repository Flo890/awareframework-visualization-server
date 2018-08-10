<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 10/08/2018
 * Time: 13:22
 */

class FeatureFetcher
{

    /**
     * FeatureFetcher constructor.
     */
    public function __construct()
    {
        require_once 'DBReader.php';
        $this->mappings_config = json_decode(file_get_contents("../../datamappings.json"),true);
    }

    public function getFeature($feature_name, $device_id, $granularity)
    {

        if (isset($this->mappings_config["mappings"][$feature_name]["sources"])) {
            // the usual case with a direct mappable data column

            $sources = $this->mappings_config["mappings"][$feature_name]["sources"];
            // TODO helpful message if no matching mapping is set

            $db_reader = new DBReader();
            foreach($sources as $aSource){
                $table_name = $aSource["source_table"];
                $column_name = $aSource["source_column"];

                $maybeResultData = $db_reader->queryDatabaseForData($table_name, $column_name, $device_id);
                // TODO accumulate by granularity
                if (sizeof($maybeResultData) > 0) {
                    // return result of the first source which has any data for the given device_id (works because one device_id is never split over multiple tables)
                    return $maybeResultData;
                }
            }

            return array();
        }

        else if (isset($this->mappings_config["mappings"][$feature_name]["feature_generator"])){
            // the more complex version, where a class is specified for handling the data
            // TODO ensure class with given name is implemented an extends FeatureGenerator

            $classname = $this->mappings_config["mappings"][$feature_name]["feature_generator"];
            require_once "featuregenerators/$classname.php";
            $feature_generator = new $classname;

            return $feature_generator->getData($device_id, $granularity);
        }

    }

}