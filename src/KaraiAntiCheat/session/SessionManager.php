<?php

namespace KaraiAntiCheat\session;

use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class SessionManager
{
    use SingletonTrait;

    private \WeakMap $map;

    private function __construct()
    {
        self::setInstance($this);
        $this->map = new \WeakMap();
    }

    /**
     * @param NetworkSession $networkSession
     * @return Session
     */
    public function get(NetworkSession $networkSession): Session
    {
        if(!$this->map->offsetExists($networkSession)) {
            $this->map->offsetSet($networkSession, $session = new Session($networkSession));
            return $session;
        }
        return $this->map->offsetGet($networkSession);
    }

    /**
     * @param NetworkSession $networkSession
     * @return void
     */
    public function close(NetworkSession $networkSession)
    {
        if(!$this->map->offsetExists($networkSession)) return null;
        $this->map->offsetUnset($networkSession);

        if($networkSession->isConnected()) {
            $networkSession->disconnect("Karai Session Destroyed");
        }
    }
}