<?php
/**
 * Created by PhpStorm.
 * User: micha
 * Date: 09-05-2016
 * Time: 02:50
 */

namespace Sovereign\Lib;


class ServerConfig
{
    protected $db;

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

    public function getAll($guildID)
    {
        return json_decode($this->db->queryField("SELECT settings FROM settings WHERE serverID = :serverID", "settings", array(":serverID" => $guildID)));
    }

}