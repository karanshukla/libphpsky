<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Video\FinishUpload;

/**
 * object
 */
class FinishUploadOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'app.bsky.video.finishUpload';

    /** @var string The processing job to poll with getJobStatus; on deduplication this may differ from the input jobId. */
    public string $completedJobId;
    public \Aazsamir\Libphpsky\Model\App\Bsky\Video\Defs\JobStatus $jobStatus;

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
        return ['completedJobId', 'jobStatus'];
    }

    public static function new(
        string $completedJobId,
        \Aazsamir\Libphpsky\Model\App\Bsky\Video\Defs\JobStatus $jobStatus,
    ): self {
        $instance = new self();
        $instance->completedJobId = $completedJobId;
        $instance->jobStatus = $jobStatus;

        return $instance;
    }
}
