<?php

namespace FoxWorn3365\WebsocketEvents;

class PermissionManager {
    protected array $local;
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
        ],
        "events" => [
            'player_move',
            'player_hit',
            'entity_hit',
            'player_hurt', // @outdated
            'entity_hurt',
            'player_item_use',
            'block_break',
            'block_place',
            'block_update',
            'player_join',
            'player_login',
            'player_bed_enter',
            'player_bed_leave',
            'player_block_pick',
            'player_chat',
            'player_drop_item',
            'player_jump',
            'player_kick',
            'player_respawn',
            'player_death'
        ]
    ];

    public function getPermissions(array $permissions) : array {
        // Fast foreach for permissions by server and player
        if (in_array('*', $permissions)) {
            return $this->vocabulary_permissions;
        }
        $return = [
            "player" => [],
            "server" => [],
            "events" => []
        ];
        $server = false;
        foreach ($permissions as $perm) {
            $element = explode('.', $perm);
            if ($element[0] == 'server') {
                if ($element[1] == '*') {
                    $return['server'] = $this->vocabulary_permissions['server'];
                } else {
                    if (in_array($element[1], $this->vocabulary_permissions['server'])) {
                        $return['server'][] = $element[1];
                    }
                }
            } elseif ($element[0] == 'player') {
                if ($element[1] == '*') {
                    $return['player'] = $this->vocabulary_permissions['player'];
                } else {
                    if (in_array($element[1], $this->vocabulary_permissions['player'])) {
                        $return['player'][] = $element[1];
                    }
                }
            } elseif ($element[0] == 'exec') {
                if ($element[1] == 'player') {
                    $return['exec']['player'] = true;
                } elseif ($element[1] == 'server') {
                    $return['exec']['server'] = true;
                }
            } elseif ($element[0] == 'event') {
                if ($element[1] == '*') {
                    $return['events'] = $this->vocabulary_permissions['events'];
                } else {
                    if (in_array($element[1], $this->vocabulary_permissions['events'])) {
                        $return['events'][] = $element[1];
                    }
                }
            }
        }
        return $return;
    }
}