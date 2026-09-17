<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Video\GetUploadStatus;

/**
 * object
 */
class GetUploadStatusOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'app.bsky.video.getUploadStatus';

    public string $jobId;
    public int $partSizeBytes;
    public int $partCount;

    /** @var array<int> */
    public array $receivedParts = [];
    public \DateTimeInterface $expiresAt;
    public string $state;

    /** @var ?string Present only when state is completed; may differ from jobId on deduplication. */
    public ?string $completedJobId;

    /** @var ?\Aazsamir\Libphpsky\Model\App\Bsky\Video\Defs\JobStatus Present only when state is completed. */
    public ?\Aazsamir\Libphpsky\Model\App\Bsky\Video\Defs\JobStatus $jobStatus;

    /** @var ?string Present only when state is failed. */
    public ?string $failureReason;

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
        return ['jobId', 'partSizeBytes', 'partCount', 'receivedParts', 'expiresAt', 'state'];
    }

    /**
     * @param array<int> $receivedParts
     */
    public static function new(
        string $jobId,
        int $partSizeBytes,
        int $partCount,
        array $receivedParts,
        \DateTimeInterface $expiresAt,
        string $state,
        ?string $completedJobId = null,
        ?\Aazsamir\Libphpsky\Model\App\Bsky\Video\Defs\JobStatus $jobStatus = null,
        ?string $failureReason = null,
    ): self {
        $instance = new self();
        $instance->jobId = $jobId;
        $instance->partSizeBytes = $partSizeBytes;
        $instance->partCount = $partCount;
        $instance->receivedParts = $receivedParts;
        $instance->expiresAt = $expiresAt;
        $instance->state = $state;
        if ($completedJobId !== null) {
            $instance->completedJobId = $completedJobId;
        }
        if ($jobStatus !== null) {
            $instance->jobStatus = $jobStatus;
        }
        if ($failureReason !== null) {
            $instance->failureReason = $failureReason;
        }

        return $instance;
    }
}
