<?php

namespace FoxWorn3365\WebsocketEvents;

class PermissionManager {
    protected array $vocabulary_permissions = [
        "server" => [
            "difficulty",
            "data_path",
            "players",
            "hardcore",
            "version",
            "tps",
            "tick",
            "port",
            "pocketmine_version",
            "online_mode",
            "ops",
            "name",
            "motd",
            "max_players",
            "ipv6",
            "ip_bans",
            "ip",
            "force_gamemode",
            "file_path"
        ],
        "player" => [
            'online',
            'display_name',
            'gamemode',
            'healt',
            'id',
            'last_played',
            'location',
            'max_healt',
            'name',
            'name_tag',
            'position',
            'skin',
            'spawn',
            'uuid',
            'viewers',
            'world'
        ]
    ];

    public function getPermissions(array $permissions) : array {
        // Fast foreach for permissions by server and player
        if (in_array('*', $permissions)) {
            return $this->vocabulary_permissions;
        }
        $return = [];
        foreach ($permissions as $perm) {
            $element = explode('.', $perm);
            if ($element[0] == 'server') {
                if (in_array($element[1], $this->vocabulary_permissions['server'])) {
                    $return['server'] = $element[1];
                }
            } elseif ($element[0] == 'player') {
                if (in_array($element[1], $this->vocabulary_permissions['server'])) {
                    $return['player'] = $element[1];
                }
            } elseif ($element[0] == 'exec') {
                if ($element[1] == 'player') {
                    $return['exec']['player'] = true;
                } elseif ($element[1] == 'server') {
                    $return['exec']['server'] = true;
                }
            }
        }
        return $return;
    }
}