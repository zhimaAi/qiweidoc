<?php

declare(strict_types=1);

namespace RoadRunner\Logger;

enum LogLevel
{
    case Error;
    case Warning;
    case Info;
    case Debug;
    case Log;
}
