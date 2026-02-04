<?php

declare(strict_types=1);

namespace Codewithkyrian\HuggingFace\Hub\Enums;

enum SpaceSdk: string
{
    case Gradio = 'gradio';
    case Streamlit = 'streamlit';
    case Docker = 'docker';
    case Static = 'static';
}
