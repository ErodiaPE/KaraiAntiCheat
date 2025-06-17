<?php

namespace KaraiAntiCheat\network\protocol\codec;

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;

class ProtocolCodecs
{
    /**
     * @param ProtocolCodecUpdater $codec
     * @return void
     */
    public static function addUpdater(ProtocolCodecUpdater $codec): void
    {
        $bedrockCodec = new BedrockCodec();
        $codec->updateCodec($bedrockCodec);

        $packets = $bedrockCodec->getRegisteredPackets();
        array_walk($packets, fn(DataPacket $packet) => PacketPool::getInstance()->registerPacket($packet));
    }
}