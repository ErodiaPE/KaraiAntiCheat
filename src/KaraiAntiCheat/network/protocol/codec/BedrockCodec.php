<?php

namespace KaraiAntiCheat\network\protocol\codec;

use KaraiAntiCheat\network\protocol\codec\exceptions\AlreadyRegisteredPacketException;
use KaraiAntiCheat\network\protocol\codec\exceptions\UnknownPacketException;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;

class BedrockCodec
{
    /** @var array<int, DataPacket> */
    private array $registeredPackets = [];

    /**
     * @param DataPacket $packet
     * @return void
     * @throws AlreadyRegisteredPacketException
     */
    public function register(DataPacket $packet): void
    {
        $pid = $packet->pid();

        if (PacketPool::getInstance()->getPacketById($pid) !== null) {
            throw new AlreadyRegisteredPacketException("Packet ID $pid is already registered in PacketPool.");
        }

        $this->registeredPackets[$pid] = clone $packet;
    }

    /**
     * @param DataPacket $packet
     * @return void
     * @throws UnknownPacketException
     */
    public function unregister(DataPacket $packet): void
    {
        $pid = $packet->pid();
        $pool = PacketPool::getInstance();

        if ($pool->getPacketById($pid) === null) {
            throw new UnknownPacketException("Packet ID $pid is not registered in PacketPool.");
        }

        try {
            $reflection = new \ReflectionClass($pool);
            $property = $reflection->getProperty("pool");
            $property->setAccessible(true);

            $packets = $property->getValue($pool);
            unset($packets[$pid]);
            $property->setValue($pool, $packets);
        } catch (\ReflectionException $e) {
            throw new UnknownPacketException("Failed to unregister packet ID $pid due to internal error: " . $e->getMessage());
        }

        unset($this->registeredPackets[$pid]);
    }

    /**
     * @return array<int, DataPacket>
     */
    public function getRegisteredPackets(): array
    {
        return $this->registeredPackets;
    }
}