<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Video\StartUpload;

/**
 * object
 */
class StartUploadOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'app.bsky.video.startUpload';

    public string $jobId;
    public int $partSizeBytes;
    public int $partCount;
    public \DateTimeInterface $expiresAt;

    public static function id(): string
    {
        return self::ID;
    }

    public static function name(): string
    {
        return self::NAME;
    }

    public static function nullable(): array
    {
        return [];
    }

    public static function required(): array
    {
        return ['jobId', 'partSizeBytes', 'partCount', 'expiresAt'];
    }

    public static function new(string $jobId, int $partSizeBytes, int $partCount, \DateTimeInterface $expiresAt): self
    {
        $instance = new self();
        $instance->jobId = $jobId;
        $instance->partSizeBytes = $partSizeBytes;
        $instance->partCount = $partCount;
        $instance->expiresAt = $expiresAt;

        return $instance;
    }
}
