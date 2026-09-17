<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Video\AbortUpload;

/**
 * object
 */
class AbortUploadOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'app.bsky.video.abortUpload';

    public string $state;

    /** @var ?string Present only when state is completed. */
    public ?string $completedJobId;

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
        return ['state'];
    }

    public static function new(string $state, ?string $completedJobId = null, ?string $failureReason = null): self
    {
        $instance = new self();
        $instance->state = $state;
        if ($completedJobId !== null) {
            $instance->completedJobId = $completedJobId;
        }
        if ($failureReason !== null) {
            $instance->failureReason = $failureReason;
        }

        return $instance;
    }
}
