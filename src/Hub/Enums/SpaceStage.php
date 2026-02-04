<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

enum SpaceStage: string
{
    case NoAppFile = 'NO_APP_FILE';
    case ConfigError = 'CONFIG_ERROR';
    case Building = 'BUILDING';
    case BuildError = 'BUILD_ERROR';
    case Running = 'RUNNING';
    case RunningBuilding = 'RUNNING_BUILDING';
    case RuntimeError = 'RUNTIME_ERROR';
    case Deleting = 'DELETING';
    case Paused = 'PAUSED';
    case Sleeping = 'SLEEPING';
}
