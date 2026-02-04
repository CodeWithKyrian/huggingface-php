<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Inference\Exceptions;

/**
 * Thrown when input parameters are invalid.
 *
 * This can occur when:
 * - Required parameters are missing (e.g., model ID)
 * - Parameter values are out of range
 * - Incompatible parameter combinations are used
 */
class InputException extends InferenceException {}
