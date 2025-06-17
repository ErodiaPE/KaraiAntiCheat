<?php

namespace KaraiAntiCheat\network\handler;

use KaraiAntiCheat\network\protocol\packets\types\KaraiClientData;
use KaraiAntiCheat\util\Reflect;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\auth\ProcessLoginTask;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
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
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;
use Ramsey\Uuid\Uuid;

class LoginPacketHandler extends PacketHandler
{
    /**
     * @phpstan-param \Closure(PlayerInfo) : void $playerInfoConsumer
     * @phpstan-param \Closure(bool $isAuthenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void $authCallback
     */
    public function __construct(
        private Server         $server,
        private NetworkSession $session,
        private \Closure       $playerInfoConsumer,
        private \Closure       $authCallback
    )
    {
    }

    public function handleLogin(LoginPacket $packet): bool
    {
        $extraData = $this->fetchAuthData($packet->chainDataJwt);

        if (!Player::isValidUserName($extraData->displayName)) {
            $this->session->disconnectWithError(KnownTranslationFactory::disconnectionScreen_invalidName());

            return true;
        }

        $clientData = $this->parseClientData($packet->clientDataJwt);
        try {
            $skin = $this->session->getTypeConverter()->getSkinAdapter()->fromSkinData(self::fromClientData($clientData));
        } catch (\InvalidArgumentException|InvalidSkinException $e) {
            $this->session->disconnectWithError(
                reason: "Invalid skin: " . $e->getMessage(),
                disconnectScreenMessage: KnownTranslationFactory::disconnectionScreen_invalidSkin()
            );

            return true;
        }

        Reflect::put($this->session, "ip", $clientData->Waterdog_IP);

        if (!Uuid::isValid($extraData->identity)) {
            throw new PacketHandlingException("Invalid login UUID");
        }
        $uuid = Uuid::fromString($extraData->identity);
        $arrClientData = (array)$clientData;
        $arrClientData["TitleID"] = $extraData->titleId;

        $playerInfo = new XboxLivePlayerInfo(
            $clientData->Waterdog_XUID,
            $extraData->displayName,
            $uuid,
            $skin,
            $clientData->LanguageCode,
            $arrClientData
        );

        ($this->playerInfoConsumer)($playerInfo);

        $ev = new PlayerPreLoginEvent(
            $playerInfo,
            $this->session->getIp(),
            $this->session->getPort(),
            $this->server->requiresAuthentication()
        );
        if ($this->server->getNetwork()->getValidConnectionCount() > $this->server->getMaxPlayers()) {
            $ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, KnownTranslationFactory::disconnectionScreen_serverFull());
        }
        if (!$this->server->isWhitelisted($playerInfo->getUsername())) {
            $ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, KnownTranslationFactory::pocketmine_disconnect_whitelisted());
        }

        $ev->call();
        if (!$ev->isAllowed()) {
            $this->session->disconnect($ev->getFinalDisconnectReason(), $ev->getFinalDisconnectScreenMessage());
            return true;
        }

        ($this->authCallback)(true, true, null, "");

        return true;
    }

    /**
     * @throws PacketHandlingException
     */
    protected function fetchAuthData(JwtChain $chain): AuthenticationData
    {
        /** @var AuthenticationData|null $extraData */
        $extraData = null;
        foreach ($chain->chain as $jwt) {
            //validate every chain element
            try {
                [, $claims,] = JwtUtils::parse($jwt);
            } catch (JwtException $e) {
                throw PacketHandlingException::wrap($e);
            }
            if (isset($claims["extraData"])) {
                if ($extraData !== null) {
                    throw new PacketHandlingException("Found 'extraData' more than once in chainData");
                }

                if (!is_array($claims["extraData"])) {
                    throw new PacketHandlingException("'extraData' key should be an array");
                }
                $mapper = new \JsonMapper();
                $mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
                $mapper->bExceptionOnMissingData = true;
                $mapper->bExceptionOnUndefinedProperty = true;
                $mapper->bStrictObjectTypeChecking = true;
                try {
                    /** @var AuthenticationData $extraData */
                    $extraData = $mapper->map($claims["extraData"], new AuthenticationData());
                } catch (\JsonMapper_Exception $e) {
                    throw PacketHandlingException::wrap($e);
                }
            }
        }
        if ($extraData === null) {
            throw new PacketHandlingException("'extraData' not found in chain data");
        }
        return $extraData;
    }

    /**
     * @throws PacketHandlingException
     */
    protected function parseClientData(string $clientDataJwt): KaraiClientData
    {
        try {
            [, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
        } catch (JwtException $e) {
            throw PacketHandlingException::wrap($e);
        }

        $mapper = new \JsonMapper();
        $mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
        $mapper->bExceptionOnMissingData = true;
        $mapper->bExceptionOnUndefinedProperty = true;
        $mapper->bStrictObjectTypeChecking = true;
        try {
            $clientData = $mapper->map($clientDataClaims, new KaraiClientData());
        } catch (\JsonMapper_Exception $e) {
            throw new PacketHandlingException($e->getMessage());
        }
        return $clientData;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function fromClientData(ClientData|KaraiClientData $clientData): SkinData
    {
        return new SkinData(
            $clientData->SkinId,
            $clientData->PlayFabId,
            self::safeB64Decode($clientData->SkinResourcePatch, "SkinResourcePatch"),
            new SkinImage($clientData->SkinImageHeight, $clientData->SkinImageWidth, self::safeB64Decode($clientData->SkinData, "SkinData")),
            [],
            new SkinImage($clientData->CapeImageHeight, $clientData->CapeImageWidth, self::safeB64Decode($clientData->CapeData, "CapeData")),
            self::safeB64Decode($clientData->SkinGeometryData, "SkinGeometryData"),
            self::safeB64Decode($clientData->SkinGeometryDataEngineVersion, "SkinGeometryDataEngineVersion"), //yes, they actually base64'd the version!
            self::safeB64Decode($clientData->SkinAnimationData, "SkinAnimationData"),
            $clientData->CapeId,
            null,
            $clientData->ArmSize,
            $clientData->SkinColor,
            [],
            [],
            true,
            $clientData->PremiumSkin,
            $clientData->PersonaSkin,
            $clientData->CapeOnClassicSkin,
            true, //assume this is true? there's no field for it ...
            $clientData->OverrideSkin ?? true,
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    private static function safeB64Decode(string $base64, string $context): string
    {
        $result = base64_decode($base64, true);
        if ($result === false) {
            throw new \InvalidArgumentException("$context: Malformed base64, cannot be decoded");
        }
        return $result;
    }
}