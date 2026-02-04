<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\DTOs;

use Codewithkyrian\HuggingFace\Hub\Enums\SpaceHardwareFlavor;
use Codewithkyrian\HuggingFace\Hub\Enums\SpaceStage;

/**
 * Represents the runtime information of a Space.
 */
final readonly class SpaceRuntime
{
    public function __construct(
        public SpaceStage $stage,
        public ?SpaceHardwareFlavor $hardware = null,
        public ?SpaceHardwareFlavor $requestedHardware = null,
        public ?int $gcTimeout = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $hardware = null;
        $requestedHardware = null;

        if (isset($data['hardware'])) {
            if (isset($data['hardware']['current']) && \is_string($data['hardware']['current'])) {
                $hardware = SpaceHardwareFlavor::tryFrom($data['hardware']['current']);
            }
            if (isset($data['hardware']['requested']) && \is_string($data['hardware']['requested'])) {
                $requestedHardware = SpaceHardwareFlavor::tryFrom($data['hardware']['requested']);
            }
        }

        return new self(
            stage: SpaceStage::tryFrom($data['stage'] ?? '') ?? SpaceStage::Building,
            hardware: $hardware,
            requestedHardware: $requestedHardware,
            gcTimeout: $data['gcTimeout'] ?? null,
        );
    }
}
