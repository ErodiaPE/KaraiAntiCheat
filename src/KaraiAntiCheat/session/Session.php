<?php

namespace KaraiAntiCheat\session;

use KaraiAntiCheat\network\handler\IncomingPacketHandler;
use KaraiAntiCheat\network\handler\OutgoingPacketHandler;
use KaraiAntiCheat\network\protocol\packets\types\Check;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\player\Player;

class Session
{
    private bool $sync = false;
    private IncomingPacketHandler $incomingPacketHandler;
    private OutgoingPacketHandler $outgoingPacketHandler;
    private ?\Logger $logger = null;

    /**
     * @var Check[]
     */
    private array $checks = [];

    public function __construct(
        private NetworkSession $networkSession,
    )
    {
        $this->incomingPacketHandler = new IncomingPacketHandler($this);
        $this->outgoingPacketHandler = new OutgoingPacketHandler($this);
    }

    /**
     * @return NetworkSession
     */
    public function getNetworkSession(): NetworkSession
    {
        return $this->networkSession;
    }

    /**
     * @return \Logger
     */
    public function getLogger(): \Logger
    {
        if($this->logger == null) {
            $this->logger = $this->networkSession->getLogger();
        }

        return $this->logger;
    }

    /**
     * @return bool
     */
    public function isSync(): bool
    {
        return $this->sync;
    }

    /**
     * @param bool $sync
     */
    public function setSync(bool $sync): void
    {
        $this->sync = $sync;
    }

    /**
     * @return array
     */
    public function getChecks(): array
    {
        return $this->checks;
    }

    /**
     * @param array $checks
     */
    public function setChecks(array $checks): void
    {
        $this->checks = $checks;
        $this->getLogger()->info("Loaded " . count($checks) . " checks.");
    }

    /**
     * @return IncomingPacketHandler
     */
    public function getIncomingPacketHandler(): IncomingPacketHandler
    {
        return $this->incomingPacketHandler;
    }

    /**
     * @return OutgoingPacketHandler
     */
    public function getOutgoingPacketHandler(): OutgoingPacketHandler
    {
        return $this->outgoingPacketHandler;
    }
}