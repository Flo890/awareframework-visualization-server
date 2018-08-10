<?php



class DBReader
{

    public function __construct()
    {


        include_once '../config/database.php';
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
}