<?php

namespace KaraiAntiCheat\network\protocol\packets;

use KaraiAntiCheat\network\protocol\codec\BedrockPacketIds;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class ServerToProxyHandshakePacket extends DataPacket implements ClientboundPacket
{
    public const NETWORK_ID = BedrockPacketIds::SERVER_TO_PROXY_HANDSHAKE_PACKET;

    protected function decodePayload(PacketSerializer $in): void
    {
    }

    protected function encodePayload(PacketSerializer $out): void
    {
    }

    /**
     * @return ServerToProxyHandshakePacket
     */
    public static function create(): ServerToProxyHandshakePacket
    {
        return new self();
    }

    /**
     * @param PacketHandlerInterface $handler
     * @return bool
     */
    public function handle(PacketHandlerInterface $handler): bool
    {
        return false;
    }
}