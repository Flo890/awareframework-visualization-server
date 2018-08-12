<?php



class DBReader
{

    public function __construct()
    {
        $this->connect_aware_db();
        $this->connect_meta_db();
        $this->connect_aware_dashboard();
    }

    private function connect_aware_db(){
        require '../config/aware_database.php';
        $active_db_config = $db[$active_server];
        $this->mysqli = new mysqli(
            $active_db_config['hostname'],
            $active_db_config['username'],
            $active_db_config['password'],
            $active_db_config['database']
        );

        if ($this->mysqli->connect_errno) {
            echo "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
        }
    }

    private function connect_meta_db() {
        require '../config/meta_database.php';
        if (isset($active_server_meta)) {
            $active_db_config_meta = $db_meta[$active_server_meta];
            $this->mysqli_meta = new mysqli(
                $active_db_config_meta['hostname'],
                $active_db_config_meta['username'],
                $active_db_config_meta['password'],
                $active_db_config_meta['database']
            );

            if ($this->mysqli_meta->connect_errno) {
                echo "Failed to connect to MySQL: (" . $this->mysqli_meta->connect_errno . ") " . $this->mysqli_meta->connect_error;
            }
        }
    }

    private function connect_aware_dashboard() {
        require '../config/aware_database.php';
            $active_db_config = $db[$active_server];
            $this->mysqli_aware_dashboard = new mysqli(
                $active_db_config['hostname'],
                $active_db_config['username'],
                $active_db_config['password'],
                $active_db_config['database_aware_dashboard']
            );

            if ($this->mysqli_aware_dashboard->connect_errno) {
                echo "Failed to connect to MySQL: (" . $this->mysqli_aware_dashboard->connect_errno . ") " . $this->mysqli_aware_dashboard->connect_error;
            }
    }

    public function queryDatabaseForData($table_name, $column_name, $device_id, $from, $to){
        // TODO vulnerable to SQL injection through datamappings.json!!
        if (!($stmt = $this->mysqli->prepare("SELECT timestamp,$column_name FROM $table_name WHERE device_id=? AND timestamp>=? AND timestamp <=? ORDER BY timestamp ASC;"))){
            echo "Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
        }
        if (!$stmt->bind_param("sdd", $device_id, $from, $to)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function queryMetaDatabase($table_name, $device_id) {
        // TODO vulnerable to SQL injection!!
        if (!($stmt = $this->mysqli_meta->prepare("SELECT * FROM $table_name WHERE device_id=? ORDER BY _id ASC;"))){
            echo "Prepare failed: (" . $this->mysqli_meta->errno . ") " . $this->mysqli_meta->error;
        }
        if (!$stmt->bind_param("s", $device_id)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function getStudyConfig($study_id){
        if (!($stmt = $this->mysqli_aware_dashboard->prepare("SELECT config FROM studies_configurations WHERE study_id=?;"))){
            echo "Prepare failed: (" . $this->mysqli_aware_dashboard->errno . ") " . $this->mysqli_aware_dashboard->error;
        }
        if (!$stmt->bind_param("d", $study_id)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        $config_json = mysqli_fetch_assoc($res)['config'];
        $config = json_decode($config_json);
        return $config;
    }

    public function getDigestAnswer($device_id, $participant_id){
        if (!($stmt = $this->mysqli_meta->prepare("select md5(CONCAT(participant_id,':Geschützter Aware Bereich:',password)) as a1 from study_participants where participant_id=? and device_id=?;"))){
            echo "Prepare failed: (" . $this->mysqli_meta->errno . ") " . $this->mysqli_meta->error;
        }
        if (!$stmt->bind_param("ds", $participant_id, $device_id)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        $assoc = $res->fetch_all(MYSQLI_ASSOC);
        if(sizeof($assoc) != 1){
            return false;
        }
        return $assoc[0]['a1'];
    }

    public function runSqlScriptOnAwareStudyDb($script_path){
        // create table in aware study database
        require '../config/aware_database.php';
        $active_db_config = $db[$active_server];
        $command = 'mysql'
            . ' --host=' . $active_db_config['hostname']
            . ' --user=' . $active_db_config['username']
            . ' --password=' . $active_db_config['password']
            . ' --database=' . $active_db_config['database']
            . ' --execute="SOURCE ' . $script_path
        ;
        $output1 = shell_exec($command);
        echo $output1;
    }

    public function getLatestPerformetricSyncDate(){
        // - first try to get latest sync date from report database
        if (!($stmt = $this->mysqli->prepare("select max(`to`) as latest_to from performetric_fatigue_report;"))){
            echo "Prepare failed: (" . $this->mysqli_meta->errno . ") " . $this->mysqli_meta->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        $assoc = $res->fetch_all(MYSQLI_ASSOC);
        if(isset($assoc[0]['latest_to'])){
            echo "using last report date";
            $datetime = new DateTime($assoc[0]['latest_to']);
            return $datetime->getTimestamp();
        }

        // - otherwise, use lowest study join date from participants meta table
        if (!($stmt = $this->mysqli_meta->prepare("select min(study_join) as first_study_join from study_participants;"))){
            echo "Prepare failed: (" . $this->mysqli_meta->errno . ") " . $this->mysqli_meta->error;
        }

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $stmt->close();
        $assoc = $res->fetch_all(MYSQLI_ASSOC);
        if(isset($assoc[0]['first_study_join'])){
            echo "using join date";
            $datetime = new DateTime($assoc[0]['first_study_join']);
            return $datetime->getTimestamp();
        }

        // - as fallback, return now
        return time();
    }

    public function insertFatigueLog($from,$to,$performetric_obj){
        $db_dateformat = 'Y-m-d H:i';
        $from_formatted = date($db_dateformat,$from);
        $to_formatted = date($db_dateformat,$to);
        if (!($stmt = $this->mysqli->prepare("insert into performetric_fatigue_report (`user`,fatigue_avg,minutes_no_fatigue,minutes_moderate_fatigue,minutes_extreme_fatigue,rest_breaks,fatigue_messages,`from`,`to`) values(?,?,?,?,?,?,?,?,?);"))){
            echo "Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
        }
        foreach($performetric_obj->users as $a_users_fatigue){
            if (!$stmt->bind_param(
                "sddddddss",
                $a_users_fatigue->user,
                $a_users_fatigue->metrics->fatigueAvg,
                $a_users_fatigue->metrics->minutesNoFatigue,
                $a_users_fatigue->metrics->minutesModerateFatigue,
                $a_users_fatigue->metrics->minutesExtremeFatigue,
                $a_users_fatigue->metrics->restBreaks,
                $a_users_fatigue->metrics->fatigueMessages,
                $from_formatted,
                $to_formatted
            )) {
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if (!$stmt->execute()) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }
            if($stmt->affected_rows != 1){
                echo "insert affected rows was not 1<br/>";
            }
        }

        $stmt->close();
    }
}