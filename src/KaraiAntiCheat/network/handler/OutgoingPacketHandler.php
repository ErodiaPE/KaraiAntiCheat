<?php

namespace KaraiAntiCheat\network\handler;

use KaraiAntiCheat\session\Session;
use KaraiAntiCheat\util\Reflect;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;

class OutgoingPacketHandler extends PacketHandler
{
    public function __construct(
        private Session $session
    ) {}
}