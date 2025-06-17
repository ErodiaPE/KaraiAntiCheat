<?php

namespace KaraiAntiCheat\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\GamemodeCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandEnumConstraint;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class AntiCheatCommand extends Command
{
    public function __construct()
    {
        parent::__construct(
            "anticheat",
            "AntiCheat's Command",
            "/anticheat <on|off>",
            ["ac"]
        );
        $this->setPermission("kirai.anticheat.command");
    }

    /**
     * @return CommandOverload[]
     */
    public function buildOverloads() : ?array{
        $values = new CommandEnum("state", ["on", "off"]);
        return [
            new CommandOverload(false, [
                CommandParameter::enum("state", $values, 0, false),
            ])
        ];
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (count($args) === 0) {
            throw new InvalidCommandSyntaxException();
        }

        $value = match (strtolower($args[0])) {
            "on", "yes", "y", "true" => true,
            "off", "no", "n", "false" => false,
            default => throw new InvalidCommandSyntaxException()
        };

        $server = Server::getInstance();
        $channelId = "kirai.anticheat";

        if ($value) {
            $server->subscribeToBroadcastChannel($channelId, $sender);
            $sender->sendMessage(TextFormat::colorize("&8(&c!&8) &fYou are §anow viewing §flogs."));
        } else {
            $server->unsubscribeFromBroadcastChannel($channelId, $sender);
            $sender->sendMessage(TextFormat::colorize("&8(&c!&8) &fYou are §cno longer viewing §flogs."));
        }
    }
}