<?php

namespace KaraiAntiCheat\network\protocol\packets\types;

class Check
{
    public function __construct(
        private CheckType $checkType,
        private string $name,
        private string $type,
        private string $description,
        private string $maxViolations,
        private bool $inCreative,
    ) {}

    /**
     * @return CheckType
     */
    public function getCheckType(): CheckType
    {
        return $this->checkType;
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
        return $this->getName() . $this->getType();
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getMaxViolations(): string
    {
        return $this->maxViolations;
    }

    /**
     * @return bool
     */
    public function isInCreative(): bool
    {
        return $this->inCreative;
    }
}