<?php

class MyDatabase
{
    private $conexion;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->conexion = new mysqli($hostname, $username, $password, $database);
        $this->conexion->set_charset("utf8mb4");
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $this->conexion->affected_rows;
    }

    private function bindParams($stmt, $params)
    {
        if (empty($params)) {
            return;
        }

        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    public function __destruct()
    {
        $this->conexion->close();
    }
}
