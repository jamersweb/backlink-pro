<?php

namespace App\Services;

/**
 * CaptchaSolvingService - Backward Compatibility Alias
 * 
 * @deprecated Use CaptchaService instead. This class will be removed in a future version.
 * 
 * This class extends CaptchaService to maintain backward compatibility
 * with existing code that uses CaptchaSolvingService.
 */
class CaptchaSolvingService extends CaptchaService
{
    // All functionality is now in CaptchaService
    // This class exists only for backward compatibility
}
