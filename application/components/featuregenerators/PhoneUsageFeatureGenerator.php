<?php
/**
 * Created by PhpStorm.
 * User: flobe
 * Date: 10/08/2018
 * Time: 15:19
 */

class PhoneUsageFeatureGenerator extends FeatureGenerator
{

    public function getData($device_id, $granularity)
    {
        $this->checkGranularitySupport(array('max'), $granularity);
        // TODO count seconds between 1 (on) and 0 (off) events
    }
}