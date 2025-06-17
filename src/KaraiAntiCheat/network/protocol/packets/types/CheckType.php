<?php

namespace KaraiAntiCheat\network\protocol\packets\types;

enum CheckType
{
    case UNKNOWN;
    case NONE;
    case ALERT;
    case KICK;
    case BAN;
}