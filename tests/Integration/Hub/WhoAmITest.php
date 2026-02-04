<?php

declare(strict_types=1);

use Codewithkyrian\HuggingFace\Hub\DTOs\WhoAmIUser;
use Codewithkyrian\HuggingFace\Hub\HubClient;

it('fetches identity info from the Hub CI environment', function (): void {
    $client = hf_test_client()->hub();

    expect($client)->toBeInstanceOf(HubClient::class);

    $info = $client->whoami();

    expect($info)->toBeInstanceOf(WhoAmIUser::class);
    expect($info->type->value)->toBe('user');

    expect($info->name)->toBeString();
    expect($info->name)->not()->toBe('');
    expect($info->emailVerified)->toBeBool();
    expect($info->avatarUrl)->not()->toBe('');
});
