<?php

namespace KaraiAntiCheat;

use KaraiAntiCheat\config\KaraiParameters;
use KaraiAntiCheat\network\protocol\codec\ProtocolCodecs;
use KaraiAntiCheat\network\protocol\KaraiProtocolCodecUpdater;
use KaraiAntiCheat\util\Reflect;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class Karai
{
    use SingletonTrait;

    private ?KaraiPlugin $plugin = null;
    private ?KaraiParameters $parameters = null;
    private bool $start = false;

    private function __construct(
    )
    {
        self::setInstance($this);

    }

    /**
     * @return KaraiPlugin
     */
    public function getPlugin(): KaraiPlugin
    {
        if($this->plugin == null) {
            $this->plugin = Server::getInstance()->getPluginManager()->getPlugin("KaraiAntiCheat");
        }
        return $this->plugin;
    }

    /**
     * @return bool
     */
    public function isStarted(): bool
    {
        return $this->start;
    }

    /**
     * @param bool $start
     */
    public function setStart(bool $start): void
    {
        $this->start = $start;
    }

    /**
     * @return KaraiParameters
     */
    public function getParameters(): KaraiParameters
    {
        return $this->parameters;
    }

    /**
     * @param KaraiParameters $parameters
     */
    public function setParameters(KaraiParameters $parameters): void
    {
        $this->parameters = $parameters;

        if($parameters->useCommands()) {
            $this->getPlugin()->injectCommands();
        }
    }

    /**
     * @param KaraiParameters $parameters
     * @param string $protocolCodecUpdater
     * @return Karai
     */
    public static function start(KaraiParameters $parameters, string $protocolCodecUpdater = KaraiProtocolCodecUpdater::class): Karai
    {
        $packetPool = PacketPool::getInstance();

        /** @var \SplFixedArray $pool */
        $pool = Reflect::tryGet($packetPool, "pool");
        $pool->setSize(1024);

        $instance = self::getInstance();
        $instance->setStart(true);
        $instance->setParameters($parameters);
        ProtocolCodecs::addUpdater(new $protocolCodecUpdater());
        return $instance;
    }
}