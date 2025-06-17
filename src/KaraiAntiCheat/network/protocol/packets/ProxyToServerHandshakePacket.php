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

class ProxyToServerHandshakePacket extends DataPacket implements ServerboundPacket
{
    public const NETWORK_ID = BedrockPacketIds::PROXY_TO_SERVER_HANDSHAKE_PACKET;

    protected function decodePayload(PacketSerializer $in): void
    {
    }

    protected function encodePayload(PacketSerializer $out): void
    {
    }

    /**
     * @return self
     */
    public static function create(): ProxyToServerHandshakePacket
    {
        return new self();
    }

    /**
     * @param PacketHandlerInterface $handler
     * @return bool
     */
    public function handle(PacketHandlerInterface $handler): bool
    {
        if($handler instanceof HandshakePacketHandler) $handler->handleProxyToServerHandshake($this);
        return false;
    }
}