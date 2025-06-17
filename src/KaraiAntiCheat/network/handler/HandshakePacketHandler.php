<?php

namespace KaraiAntiCheat\network\handler;

use KaraiAntiCheat\Karai;
use KaraiAntiCheat\network\protocol\packets\ProxyToServerHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\ServerToProxyHandshakePacket;
use KaraiAntiCheat\network\protocol\packets\types\KaraiClientData;
use KaraiAntiCheat\session\Session;
use KaraiAntiCheat\util\Reflect;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\auth\ProcessLoginTask;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\login\AuthenticationData;
use pocketmine\network\mcpe\protocol\types\login\ClientData;
use pocketmine\network\mcpe\protocol\types\login\ClientDataPersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\login\ClientDataPersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\login\ClientDataToSkinDataHelper;
use pocketmine\network\mcpe\protocol\types\login\JwtChain;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;
use Ramsey\Uuid\Uuid;

class HandshakePacketHandler extends PacketHandler
{
    private bool $handshakeCompleted = false;

    public function __construct(
        private Session $session,
        private \Closure $callback
    )
    {
        $this->session->getNetworkSession()->sendDataPacket(ServerToProxyHandshakePacket::create(), true);
        Karai::getInstance()->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() {
            if(!$this->session->getNetworkSession()->isConnected()) return;
            if (!$this->handshakeCompleted) {
                $this->session->getLogger()->warning("Handshake timeout: session was not synchronized within 5 seconds.");
                $this->session->getNetworkSession()->disconnect("Handshake timeout", "§cHandshake timeout. Please try again.", );
            }
        }), 20 * 5);
    }

    /**
     * @param ProxyToServerHandshakePacket $packet
     * @return bool
     */
    public function handleProxyToServerHandshake(ProxyToServerHandshakePacket $packet): bool
    {
        $this->session->getLogger()->debug("Session is now synchronized.");
        $this->handshakeCompleted = true;
        $this->session->setSync(true);

        ($this->callback)();
        return false;
    }
}