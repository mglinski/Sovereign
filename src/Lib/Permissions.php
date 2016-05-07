<?php

namespace Sovereign\Lib;


class Permissions {
    protected $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function get($userID, $serverID) {
        return $this->db->queryField("SELECT permission FROM permissions WHERE userID = :userID AND serverID = :serverID", "permission", [":userID" => $userID, ":serverID" => $serverID]);
    }

    public function set($userID, $serverID, $permission) {
        $this->db->execute("INSERT INTO permissions (serverID, userID, permission) VALUE (:serverID, :userID, :permission) ON DUPLICATE KEY UPDATE permission = :permission", [":serverID" => $serverID, ":userID" => $userID, ":permission" => $permission]);
    }

}