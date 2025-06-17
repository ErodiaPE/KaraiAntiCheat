<?php

namespace KaraiAntiCheat\network\protocol\codec;

abstract class ProtocolCodecUpdater
{
    abstract public function updateCodec(BedrockCodec $codec): void;
}