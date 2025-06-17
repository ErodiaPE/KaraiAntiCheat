<?php

namespace KaraiAntiCheat\config;

class KaraiParameters
{
    public function __construct(
        private bool $useCommands = true
    )
    {
    }

    /**
     * @return bool
     */
    public function useCommands(): bool
    {
        return $this->useCommands;
    }
}