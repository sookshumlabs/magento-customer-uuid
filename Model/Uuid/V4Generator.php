<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Model\Uuid;

use Magento\Framework\Exception\LocalizedException;
use Ramsey\Uuid\Uuid;

class V4Generator implements GeneratorInterface
{
    public function generate(): string
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Throwable $e) {
            throw new LocalizedException(__('Unable to generate UUID'), $e);
        }
    }
}

