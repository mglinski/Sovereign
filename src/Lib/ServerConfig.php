<?php
namespace Sovereign\Lib;

/**
 * Class ServerConfig
 * @package Sovereign\Lib
 */
class ServerConfig
{
    /**
     * @var Db
     */
    protected $db;

    /**
     * ServerConfig constructor.
     * @param Db $db
     */
    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $key
     */
    public function get($guildID, $key)
    {
        $data = json_decode($this->db->queryField("SELECT settings FROM settings WHERE serverID = :serverID", "settings", array(":serverID" => $guildID)));
        return isset($data->{$key}) ? $data->{$key} : null;
    }

    /**
     * @param string $key
     */
    public function set($guildID, $key, $value)
    {
        $json = $this->getAll($guildID);
        $json->{$key} = $value;
        $data = json_encode($json);
        $this->db->execute("INSERT INTO settings (serverID, settings) VALUES (:serverID, :settings) ON DUPLICATE KEY UPDATE settings = :settings", array(":serverID" => $guildID, ":settings" => $data));
    }

    /**
     * @param $guildID
     * @return mixed
     */
    public function getAll($guildID)
    {
        return json_decode($this->db->queryField("SELECT settings FROM settings WHERE serverID = :serverID", "settings", array(":serverID" => $guildID)));
    }

}