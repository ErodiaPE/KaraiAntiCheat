<?php

namespace KaraiAntiCheat\network\protocol;

use KaraiAntiCheat\network\protocol\codec\BedrockCodec;
use KaraiAntiCheat\network\protocol\codec\exceptions\AlreadyRegisteredPacketException;
use KaraiAntiCheat\network\protocol\codec\ProtocolCodecUpdater;
use KaraiAntiCheat\network\protocol\packets\AlertCheckPacket;
use KaraiAntiCheat\network\protocol\packets\ProxyToServerHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\PunishCheckPacket;
use KaraiAntiCheat\network\protocol\packets\ServerToProxyHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\SessionInfoPacket;

class KaraiProtocolCodecUpdater extends ProtocolCodecUpdater
{
    /**
     * @param BedrockCodec $codec
     * @return void
     * @throws AlreadyRegisteredPacketException
     */
    public function updateCodec(BedrockCodec $codec): void
    {
        $codec->register(new ProxyToServerHandshakePacket());
        $codec->register(new ServerToProxyHandshakePacket());
        $codec->register(new AlertCheckPacket());
        $codec->register(new PunishCheckPacket());
        $codec->register(new SessionInfoPacket());
    }
}