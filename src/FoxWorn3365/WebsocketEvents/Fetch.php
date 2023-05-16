<?php

namespace FoxWorn3365\WebsocketEvents;

class Fetch {
    // A simple and static class to fetch all parts of useful components

    // Player
    public static function player(object $player) : ?object {
        return $player;
        $playerload = [
            'online' => 'isConnected',
            'display_name' => 'getDisplayName',
            'gamemode' => 'getGamemode',
            'healt' => 'getHealth',
            'id' => 'getId',
            'last_played' => 'getLastPlayed',
            'location' => 'getLocation',
            'position' => 'getPosition',
            'max_healt' => 'getMaxHealth',
            'name' => 'getName',
            'name_tag' => 'getNameTag',
            //'skin' => 'getSkin',
            'spawn' => 'getSpawn',
            'uuid' => 'getUniqueId',
            'viewers' => 'getViewers',
            'world' => 'getWorld'
        ];
        $playerClass = new \stdClass;
        $playerClass->type = 'player';
        // return $playerClass;
        if (gettype($player) != 'object') {
            return null;
        }
        foreach ($playerload as $element => $function) {
            $playerClass->{$element} = $player->$function();
        }
        $playerClass->skin = new \stdClass;
        $playerClass->skin->cape = new \stdClass;
        $playerClass->skin->cape->data = $player->getSkin()->getCapeData();
        $playerClass->skin->data = null;
        $playerClass->skin->id = $player->getSkin()->getSkinId();
        unset($playerClass->location->world);
        unset($playerClass->position->world);
        $playerClass->world = $playerClass->world->getFolderName();
        //$playerClass->uuid = @$playerClass->uuid->uuid;
        $playerClass->gamemode = $playerClass->gamemode->getEnglishName();
        return $playerClass;
    }

    // Item
    public static function item(object $item) : object {
        $itemClass = new \stdClass;
        $itemClass->type = 'item';
        $gettable = [
            'count' => 'getCount',
            'custom_name' => 'getCustomName',
            'id' => 'getId',
            'max_stack_size' => 'getMaxStackSize',
            'name' => 'getName',
            'name_tag' => 'getNamedTag',
            'vanilla_name' => 'getVanillaName',
            'null' => 'isNull'
        ];
        foreach ($gettable as $item_a => $function) {
            $itemClass->{$item_a} = @$item->$function();
        }
        return $itemClass;
    }

    // Block
    public static function block(object $block) : object {
        $takable = [
            'id' => 'getId',
            'max_stack_size' => 'getMaxStackSize',
            'name' => 'getName',
            'position' => 'getPosition',
            'solid' => 'isSolid',
            'transparent' => 'isTransparent',
            'light_level' => 'getLightLevel',
            'placable' => 'canBePlaced',
            'replacable' => 'canBeReplaced'
        ];
        $bed = new \stdClass;
        $bed->type = 'block';
        foreach ($takable as $id => $function) {
            $bed->{$id} = @$block->$function();
        }
        if ($bed->name == 'Bed') {
            $bed->occupied = $block->isOccupied();
        }
        //$bed->position->world = $bed->position->world->getFolderName();
        return $bed;
    }

    // Entity
    public static function entity(object $entity) : object {
        $newEntity = new \stdClass;
        $newEntity->type = 'entity';
        $captable = [
            'name' => 'getName',
            'healt' => 'getHealth',
            'location' => 'getLocation',
            'max_healt' => 'getMaxHealth',
            'world' => 'getWorld',
            'xp' => 'getXpDropAmount',
            'viewers' => 'getViewers',
            'id' => 'getId'
        ];

        foreach ($captable as $key => $function) {
            $newEntity->{$key} = $entity->$function();
        }

        unset($newEntity->location->world);
        $newEntity->world = $newEntity->world->getFolderName();

        return $newEntity;
    }
}