<?php
namespace Sovereign\Lib;

class Settings
{
    protected $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function set($key, $value, $serverID)
    {
        $json = $this->get($serverID);
        $json[$key] = $value;
        $this->db->execute("INSERT INTO settings (settings) VALUES (:settings) ON DUPLICATE KEY UPDATE settings = :settings", [":settings" => json_encode($json)]);
    }

    public function get($serverID)
    {
        return json_decode($this->db->queryField("SELECT settings FROM settings WHERE serverID = :serverID", "settings", [":serverID" => $serverID]));
    }
}