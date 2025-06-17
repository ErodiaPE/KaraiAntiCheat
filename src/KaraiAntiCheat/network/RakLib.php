<?php

namespace KaraiAntiCheat\network;

use Erodia\Util\Reflect;
use KaraiAntiCheat\network\protocol\compressor\NoneCompressor;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\PacketRateLimiter;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\PacketHandlingException;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class RakLib extends RakLibInterface
{
    public function onPacketReceive(int $sessionId, string $packet) : void{
        $sessions = Reflect::tryGet($this, "sessions", RakLibInterface::class);
        
        if(isset($sessions[$sessionId])){
            if($packet === "" || $packet[0] !== "\xfe"){
                $sessions[$sessionId]->getLogger()->debug("Non-FE packet received: " . base64_encode($packet));
                return;
            }
            //get this now for blocking in case the player was closed before the exception was raised
            /** @var NetworkSession $session */
            $session = $sessions[$sessionId];
            $buf = substr($packet, 1);
            $name = $session->getDisplayName();
            try{
                $session->handleEncoded($buf);
            }catch(PacketHandlingException $e){
                $logger = $session->getLogger();

                $session->disconnect(TextFormat::RED . $e->getMessage());

                //intentionally doesn't use logException, we don't want spammy packet error traces to appear in release mode
                $logger->error(implode("\n", Utils::printableExceptionInfo($e)));
            }catch(\Throwable $e) {
                $logger = $session->getLogger();

                $player = $session->getPlayer();
                if($player === null) {
                    $logger->emergency("Crash occurred while handling a packet from session: $name");
                    throw $e;
                }

                $logger->error(implode("\n", Utils::printableExceptionInfo($e)));
                $player->disconnect(TextFormat::RED . "Internal Error");
            }
        }
    }

    /**
     * @param int $sessionId
     * @param string $address
     * @param int $port
     * @param int $clientID
     * @return void
     */
    public function onClientConnect(int $sessionId, string $address, int $port, int $clientID): void
    {
        parent::onClientConnect($sessionId, $address, $port, $clientID);
        $session = Reflect::tryGet($this, "sessions", RakLibInterface::class)[$sessionId];
        Reflect::put($session, "gamePacketLimiter", new PacketRateLimiter("Game Packets", 100, 200));
    }
}