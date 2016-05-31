<?php

namespace Sovereign\Lib;


class Permissions {
    protected $db;
    protected $config;

    public function __construct(Db $db, Config $config) {
        $this->db = $db;
        $this->config = $config;
    }

    public function get($userID, $serverID) {
        foreach ($this->config->get("admins", "permissions") as $adminID) {
                    if ($adminID == $userID) {
                                    return 2;
                    }
        }

        return $this->db->queryField("SELECT permission FROM permissions WHERE userID = :userID AND serverID = :serverID", "permission", [":userID" => $userID, ":serverID" => $serverID]);
    }

    public function set($userID, $serverID, $permission) {
        $this->db->execute("INSERT INTO permissions (serverID, userID, permission) VALUE (:serverID, :userID, :permission) ON DUPLICATE KEY UPDATE permission = :permission", [":serverID" => $serverID, ":userID" => $userID, ":permission" => $permission]);
    }

}