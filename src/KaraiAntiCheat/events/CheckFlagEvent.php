<?php

namespace KaraiAntiCheat\events;

use KaraiAntiCheat\session\Session;
use pocketmine\event\Event;

class CheckFlagEvent extends Event
{
    protected string $format = "";

    public function __construct(
        protected Session $session,
        protected string $name,
        protected string $type,
        protected string $debug,
        protected string $violation,
        protected string $maxViolations,
    )
    {
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->name . $this->type;
    }

    /**
     * @return string
     */
    public function getDebug(): string
    {
        return $this->debug;
    }

    /**
     * @return string
     */
    public function getViolation(): string
    {
        return $this->violation;
    }

    /**
     * @return string
     */
    public function getMaxViolations(): string
    {
        return $this->maxViolations;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }
}