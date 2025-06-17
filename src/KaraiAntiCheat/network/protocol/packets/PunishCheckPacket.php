<?php

namespace KaraiAntiCheat\network\protocol\packets;

use KaraiAntiCheat\network\handler\IncomingPacketHandler;
use KaraiAntiCheat\network\handler\HandshakePacketHandler;
use KaraiAntiCheat\network\protocol\codec\BedrockPacketIds;
use KaraiAntiCheat\network\protocol\packets\types\CheckType;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\PacketHandlingException;

class PunishCheckPacket extends DataPacket implements ServerboundPacket
{
    public const NETWORK_ID = BedrockPacketIds::PUNISH_CHECK_PACKET;
    public string $name;
    public string $type;
    public string $debug;
    public float $violation;
    public float $maxViolations;
    public CheckType $checkType;


    protected function decodePayload(PacketSerializer $in): void
    {
        $this->name = $in->getString();
        $this->type = $in->getString();
        $this->debug = $in->getString();
        $this->violation = $in->getLDouble();
        $this->maxViolations = $in->getLDouble();

        $typeString = strtolower($in->getString());
        $this->checkType = match($typeString) {
            "none" => CheckType::NONE,
            "alert" => CheckType::ALERT,
            "kick" => CheckType::KICK,
            "ban" => CheckType::BAN,
            default => throw new PacketHandlingException("Unknown check type: $typeString")
        };
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
        if($handler instanceof IncomingPacketHandler) $handler->handlePunishCheck($this);
        return false;
    }
}