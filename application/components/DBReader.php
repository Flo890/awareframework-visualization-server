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

    public function queryDatabaseForData($table_name, $column_name, $device_id){
        // TODO vulnerable to SQL injection through datamappings.json!!
        if (!($stmt = $this->mysqli->prepare("SELECT timestamp,$column_name FROM $table_name WHERE device_id=? ORDER BY timestamp ASC;"))){
            echo "Prepare failed: (" . $this->mysqli->errno . ") " . $this->mysqli->error;
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
}