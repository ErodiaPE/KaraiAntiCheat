<?php

namespace KaraiAntiCheat;

use KaraiAntiCheat\commands\AntiCheatCommand;
use KaraiAntiCheat\network\handler\LoginPacketHandler;
use KaraiAntiCheat\network\protocol\codec\ProtocolCodecs;
use KaraiAntiCheat\network\protocol\KaraiProtocolCodecUpdater;
use KaraiAntiCheat\network\RakLib;
use KaraiAntiCheat\session\SessionManager;
use KaraiAntiCheat\util\Reflect;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\LoginPacketHandler as LoginPacketHandlerPM;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class KaraiPlugin extends PluginBase implements Listener
{
    private ?AntiCheatCommand $command = null;

    /**
     * @return void
     */
    protected function onLoad(): void
    {
        $manager = PermissionManager::getInstance();
        $register = fn(string $permission) => DefaultPermissions::registerPermission(new Permission($permission, ":)"), [$manager->getPermission(DefaultPermissions::ROOT_OPERATOR)]);
        $register("kirai.anticheat.command");
    }

    /**
     * @return void
     */
    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @return void
     */
    public function injectCommands(): void
    {
        $this->getServer()->getCommandMap()->register("anticheat", $this->command = new AntiCheatCommand());
    }

    /**
     * @param NetworkInterfaceRegisterEvent $ev
     * @return void
     */
    public function onInterfaceRegister(NetworkInterfaceRegisterEvent $ev): void
    {
        $interface = $ev->getInterface();
        if($interface instanceof DedicatedQueryNetworkInterface) {
            $ev->cancel();
        }elseif (!$interface instanceof RakLib && $interface instanceof RakLibInterface) {
            $ev->cancel();
            ($server = Server::getInstance())->getNetwork()->registerInterface(new RakLib(
                $server,
                "127.0.0.1",
                $server->getPort(),
                false,
                Reflect::tryGet($interface, "packetBroadcaster"),
                Reflect::tryGet($interface, "entityEventBroadcaster"),
                TypeConverter::getInstance(),
            ));
        }
    }

    /**
     * @param DataPacketReceiveEvent $ev
     * @return void
     */
    public function handleIncomingPacket(DataPacketReceiveEvent $ev): void
    {
        $origin = $ev->getOrigin();
        $packet = $ev->getPacket();

        $started = Karai::getInstance()->isStarted();
        if($started && $packet instanceof LoginPacket) {
            $current = $origin->getHandler();
            if($current instanceof LoginPacketHandlerPM) {
                $origin->setHandler($handler = new LoginPacketHandler(
                    Server::getInstance(),
                    $origin,
                    Reflect::tryGet($current, "playerInfoConsumer"),
                    Reflect::tryGet($current, "authCallback")
                ));
                $handler->handleLogin($packet);
            }
            $ev->cancel();
            return;
        }

        $session = SessionManager::getInstance()->get($origin);
        $cancel = $packet->handle($session->getIncomingPacketHandler());
        if($cancel) {
            $ev->cancel();
        }
    }

    /**
     * @param DataPacketSendEvent $ev
     * @return void
     */
    public function handleOutgoingPacket(DataPacketSendEvent $ev): void
    {
        $packets = $ev->getPackets();
        $targets = $ev->getTargets();
        if(count($packets) > 1) return;
        if(count($targets) > 1) return;

        foreach ($packets as $packet) {
            foreach ($targets as $origin) {
                if($packet instanceof AvailableCommandsPacket && $this->command !== null) {
                    if(!isset($packet->commandData[$this->command->getLabel()]) || !$this->command->testPermissionSilent($origin->getPlayer())){
                        continue;
                    }

                    $lname = strtolower($this->command->getLabel());
                    $aliases = $this->command->getAliases();
                    $aliasObj = null;
                    if(count($aliases) > 0){
                        if(!in_array($lname, $aliases, true)){
                            //work around a client bug which makes the original name not show when aliases are used
                            $aliases[] = $lname;
                        }
                        $aliasObj = new CommandEnum(ucfirst($this->command->getLabel()) . "Aliases", $aliases);
                    }

                    $description = $this->command->getDescription();
                    $packet->commandData[$this->command->getLabel()] = new CommandData(
                        $lname, //TODO: commands containing uppercase letters in the name crash 1.9.0 client
                        $description instanceof Translatable ? $origin->getPlayer()->getLanguage()->translate($description) : $description,
                        0,
                        0,
                        $aliasObj,
                        $this->command->buildOverloads(),
                        chainedSubCommandData: []
                    );
                }

                $session = SessionManager::getInstance()->get($origin);
                $cancel = $packet->handle($session->getOutgoingPacketHandler());
                if($cancel) {
                    $ev->cancel();
                }
            }
        }
    }
}