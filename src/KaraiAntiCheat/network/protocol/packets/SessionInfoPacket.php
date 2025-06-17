<?php

namespace KaraiAntiCheat\network\protocol\packets;

use KaraiAntiCheat\network\handler\IncomingPacketHandler;
use KaraiAntiCheat\network\handler\HandshakePacketHandler;
use KaraiAntiCheat\network\protocol\codec\BedrockPacketIds;
use KaraiAntiCheat\network\protocol\packets\types\Check;
use KaraiAntiCheat\network\protocol\packets\types\CheckType;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\PacketHandlingException;

class SessionInfoPacket extends DataPacket implements ServerboundPacket
{
    public const NETWORK_ID = BedrockPacketIds::SESSION_INFO_PACKET;
    public array $checks;

    protected function decodePayload(PacketSerializer $in): void
    {
        $count = $in->getLInt();

        $this->checks = [];
        for ($i = 0; $i < $count; $i++) {
            $typeString = $in->getString();
            $checkType = match(strtolower($typeString)) {
                "none" => CheckType::NONE,
                "alert" => CheckType::ALERT,
                "kick" => CheckType::KICK,
                "ban" => CheckType::BAN,
                default => throw new PacketHandlingException("Unknown check type: $typeString")
            };

            $name = $in->getString();
            $type = $in->getString();
            $description = $in->getString();
            $maxViolations = $in->getLFloat();
            $inCreative = $in->getBool();
            $this->checks[] = new Check(
                $checkType,
                $name,
                $type,
                $description,
                $maxViolations,
                $inCreative
            );
        }
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
        if($handler instanceof IncomingPacketHandler) $handler->handleSessionInfo($this);
        return false;
    }
}