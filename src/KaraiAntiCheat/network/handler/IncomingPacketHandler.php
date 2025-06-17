<?php

namespace KaraiAntiCheat\network\handler;

use KaraiAntiCheat\events\CheckFlagEvent;
use KaraiAntiCheat\events\CheckPunishEvent;
use KaraiAntiCheat\network\protocol\packets\AlertCheckPacket;
use KaraiAntiCheat\network\protocol\packets\ProxyToServerHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\PunishCheckPacket;
use KaraiAntiCheat\network\protocol\packets\ServerToProxyHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\SessionInfoPacket;
use KaraiAntiCheat\session\Session;
use KaraiAntiCheat\session\SessionManager;
use KaraiAntiCheat\util\Reflect;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\handler\SpawnResponsePacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class IncomingPacketHandler extends PacketHandler
{
    public function __construct(
        private Session $session
    ) {}

    /**
     * @param SetLocalPlayerAsInitializedPacket $packet
     * @return bool
     */
    public function handleSetLocalPlayerAsInitialized(SetLocalPlayerAsInitializedPacket $packet): bool
    {
        $networkSession = $this->session->getNetworkSession();
        $handler = $networkSession->getHandler();

        if($handler instanceof SpawnResponsePacketHandler) {
            /** @var \Closure $callback */
            $callback = Reflect::tryGet($handler, "responseCallback");
            $networkSession->setHandler(new HandshakePacketHandler($this->session, $callback));
        }
        return true;
    }

    /**
     * @param AlertCheckPacket $packet
     * @return bool
     */
    public function handleAlertCheck(AlertCheckPacket $packet): bool
    {
        $ev = new CheckFlagEvent($this->session, $packet->name, $packet->type, $packet->debug, $packet->violation, $packet->maxViolations);
        $ev->call();

        $format = $ev->getFormat();
        if($format == "") {
            $format = "&8(&c!&8) &c{$this->session->getNetworkSession()->getDisplayName()} &7failed &6{$ev->getFullName()}";

            if($ev->getMaxViolations() != 0) {
                $format .= " &8[vl={$ev->getViolation()}/{$ev->getMaxViolations()}]&r";
            }
            if($ev->getDebug() !== "") {
                $format .= " &8[{$ev->getDebug()}&8]";
            }
        }

        ($server = Server::getInstance())->broadcastMessage(TextFormat::colorize($format), $server->getBroadcastChannelSubscribers("kirai.anticheat"));
        return true;
    }

    /**
     * @param PunishCheckPacket $packet
     * @return bool
     */
    public function handlePunishCheck(PunishCheckPacket $packet): bool
    {
        $ev = new CheckPunishEvent($this->session, $packet->name, $packet->type, $packet->debug, $packet->violation, $packet->maxViolations, $packet->checkType);
        $ev->call();
        return true;
    }

    /**
     * @param SessionInfoPacket $packet
     * @return bool
     */
    public function handleSessionInfo(SessionInfoPacket $packet): bool
    {
        $this->session->setChecks($packet->checks);
        return true;
    }
}