<?php
namespace Sovereign\Lib;

/**
 * Class Settings
 * @package Sovereign\Lib
 */
class Settings
{
    /**
     * @var Db
     */
    protected $db;

    /**
     * Settings constructor.
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param $key
     * @param $value
     * @param $serverID
     */
    public function set($key, $value, $serverID)
    {
        $json = $this->get($serverID);
        $json[$key] = $value;
        $this->db->execute("INSERT INTO settings (settings) VALUES (:settings) ON DUPLICATE KEY UPDATE settings = :settings", [":settings" => json_encode($json)]);
    }

    /**
     * @param $serverID
     * @return mixed
     */
    public function get($serverID)
    {
        return json_decode($this->db->queryField("SELECT settings FROM settings WHERE serverID = :serverID", "settings", [":serverID" => $serverID]));
    }
}