<?php

namespace KaraiAntiCheat\network\protocol\packets;

use KaraiAntiCheat\network\handler\IncomingPacketHandler;
use KaraiAntiCheat\network\handler\HandshakePacketHandler;
use KaraiAntiCheat\network\protocol\codec\BedrockPacketIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class AlertCheckPacket extends DataPacket implements ServerboundPacket
{
    public const NETWORK_ID = BedrockPacketIds::ALERT_CHECK_PACKET;
    public string $name;
    public string $type;
    public string $debug;
    public float $violation;
    public float $maxViolations;


    protected function decodePayload(PacketSerializer $in): void
    {
        $this->name = $in->getString();
        $this->type = $in->getString();
        $this->debug = $in->getString();
        $this->violation = $in->getLDouble();
        $this->maxViolations = $in->getLDouble();
    }

    protected function encodePayload(PacketSerializer $out): void
    {
    }

    /**
     * @param PacketHandlerInterface $handler
     * @return bool
     */
    public function handle(PacketHandlerInterface $handler): bool
    {
        if($handler instanceof IncomingPacketHandler) $handler->handleAlertCheck($this);
        return false;
    }
}