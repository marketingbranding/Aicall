<?php

namespace App\Services\LiveVoice;

use RuntimeException;

class LiveCredentialProvisioningException extends RuntimeException
{
    public static function missingConfiguration(): self
    {
        return new self('Gemini Live credentials are not configured.');
    }

    public static function providerRejectedRequest(): self
    {
        return new self('Gemini Live credential provisioning failed.');
    }
}
