<?php
/**
 * Copyright © QuarryTeam
 */
declare(strict_types=1);

namespace QuarryTeam\CustomerUuid\Model\Uuid;

interface GeneratorInterface
{
    /**
     * Generate an RFC 4122 v4 UUID.
     */
    public function generate(): string;
}

