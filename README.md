# WebSocket Events - Manage events at another level
**WebSocket Events** is a plugin for PocketMine-MP that allows you to be able to manage your server via a simple WebSocket connection.<br>
Receive events, load players and send actions to be performed all with a library in PHP **already developed and working**!

## Why you should use __WebSocket Events__
This plugin allows you to do almost anything as it broadcasts most of the in-game events externally!<br>
You can make a counter of blocks placed by players in real time on your PocketMine-MP server website, for example!

## Warning!
This plugin is still in alpha (in fact it hasn't been released on Poggit yet) and ~~may contain~~ it almost certainly contains bugs that haven't been discovered yet but hey, this is a public alpha so feel free to open an issue in case you run into a crash or if you know how to work well in PHP then you are free to contribute!

## Installation
You can install this plugin by downloading the .phar found in the latest (and only :sad:) relase.

## Configuration
The default configuration should be the similar:
```yaml
---
#########################################
# \      \ /      / +------     +-----  #
#  \      X      /  |           |       #
#   \    / \    /   +-----+     +---    #
#    \  /   \  /          |     |       #
#     \/     \/     ------+     +-----  #
#########################################
# WebSocket Events v0.8@indev BETA
# "Receive, handle and execute action from and to your PocketMine-MP server via WebSockets!"
#########################################
# (C) 2023-now Federico Cosma (FoxWorn3365) and contributors
# MIT License
# Require php8 or newer
#########################################
#        CONTACTS
# Email: foxworn3365@gmail.com
# Discord: FoxWorn#0001
# GitHub https://github.com/FoxWorn3365/WebsocketEvents
# GitHub (author): https://github.com/FoxWorn3365
##########################################

enabled: true  ## Is the plugin enabled?

#-----------------------------------
# > WebSocket Server Configuration
#-----------------------------------
# Default settings
server-ip: localhost   # The host of the internal WebSocket Server. use 0.0.0.0 to open to others
server-port: 1991      # The port
timeout: 2             # Timeout (in seconds) of client message listener
max-connections: 10    # The number of max simultaneous WebSocket Connections for this server
allow-doubleauth: false # Allows transmission of the token after accepting the connection temporarily with a timeout of 2s

# Auth settings
# - This array contains all allowed WebSockets Keys
tokens:
  - myTestToken1
  - myTestToke2
  
# Permissions settings
# - This array contains all permissions for every token.
# - Use * to allow all permissions
# - Permissions is like: [part].[name], for example player.name
# - As the global permission you can use player.* to give access to the entire player class
# - Command and player execution is under "exec.[player|server]"
# - Permission for receive event: "event.[event_name]"
permissions:
  myTestToken1:
    - '*'
  myTestToken2:
    - player.*
    - server.playerList
    - exec.server
    
# Event settings
event-socket-token: myEventTestToken  # This toke will be used by the event websocket client to connect. Il will have * as permission
event-close-token: myCloseToken         # This token will be used by the server manager to prompt a shutdown command to all connected clients

# Utils
waiting-connection-time: 1 # The time the server waits before establishing internal WebSocket connections
full_logs: false                   # Should the plugin share the WSS server logs with the console?

# Enable or disable some event listeners
on_player_move: false
on_block_update: false
...
```

### Authentication
To ensure server security, the plugin includes a token-based authentication system.
The various authentication systems are outlined in the [plugin connectivity guide](https://foxworn3365.github.io/Websocket-Events-Lib/docs/connectivity)

### Permissions
The plugin includes an internal permission management system closely tied to tokens, so each token can be assigned different permissions.

#### GET Permissions
Permissions for GET requests are the most comprehensive in that you can restrict the output, cancel it, or send it whole with a single line of code.<br>
**Example of all permissions granted:**
```yaml
permissions:
  myTestToken1:
    - player.*
    - server.*
```
**Example of limited permissions:**
```yaml
permissions:
  myTestToken1:
    - player.healt
    - player.name
    - player.location
    - player.world
```
In this case the object "player" will not be complete but only the above values will be contained

#### EXECUTE Permissions
The execution permission is divided only into `player` and `server` and has absolute value, so assigning the permission `exec.player` gives the token access to all possible actions toward the player.
> **Note**<br>
> There is no `exec.*` permission, so to give access to both you need to define them

#### EVENT Permissions
Events also recently underwent a restoration that implemented permits.<br>
Unlike player permissions these are easier as it is simply a matter of specifying the event in snake_case:
```yaml
permissions:
  myTestToken1:
    - player.*
    - event.player_login
    - event.player_join
    - event.player_jump
```
It's possible here to use `event.*`!

### `allow-doubleauth`
This parameter is precisely stated in the [plugin connectivity guide](https://foxworn3365.github.io/Websocket-Events-Lib/docs/connectivity)

## F.A.Q.
### "If I try to restart the server it says the address is already in use!"
> This can happen when the WebSocket server working in a different process than the main one has not shut down properly.<br>
> To fix this problem, terminate all open versions of Pocketmine with `pkill -f -9 Pocket`

### "I cannot access the WebSocket server from outside the VPS/VDS."
> There may be various reasons so check that:
> - WebSocket Server's IP is `0.0.0.0`.
> - The port used is open.
> - The server is running.<br>
> If all this does not work then you should use NGINX or Apache2 to open the WS server to the "whole world."
> If you need help with this please contact me :D

## Contacts
**Email:** `foxworn3365@gmail.com`<br>
**Discord:** `FoxWorn#0001`

## Contribution guidelines
Contributing to the project is an incredibly beautiful act, but it must be done by following simple rules that make it possible for us to integrate without too many problems:
- Put the correct spaces in the code.
- Leave free lines between several different parts of code.
- Always comment on the code.
- Indicate in the pull request **precisely** what you changed.
- Avoid adding files unless strictly necessary
- Avoid changes of the configuration

&copy; 2023-now FoxWrn3365 | MIT License
