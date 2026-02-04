<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Exceptions;

/**
 * Thrown when provider or task routing fails.
 *
 * This can occur when:
 * - A provider is not supported
 * - A task is not supported for a given provider
 * - Auto-routing fails to find an available provider
 * - Model ID is required but not provided
 */
class RoutingException extends InferenceException {}
