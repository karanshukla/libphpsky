<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Video\StartUpload;

/**
 * object
 */
class StartUploadInput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'input';
    public const ID = 'app.bsky.video.startUpload';

    /** @var int Exact byte size of the complete upload-ready video file before it is split into parts. */
    public int $sizeBytes;

    /** @var string Declared MIME type of the video. */
    public string $mimeType;

    /** @var ?string Optional client-provided file name. */
    public ?string $name;

    /** @var ?int Advisory, non-authoritative duration used only for early failure; the authoritative probe runs asynchronously after upload. */
    public ?int $durationMs;

    /** @var ?int Advisory, non-authoritative width used only for early failure; the authoritative probe runs asynchronously after upload. */
    public ?int $width;

    /** @var ?int Advisory, non-authoritative height used only for early failure; the authoritative probe runs asynchronously after upload. */
    public ?int $height;

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
        return ['sizeBytes', 'mimeType'];
    }

    public static function new(
        int $sizeBytes,
        string $mimeType,
        ?string $name = null,
        ?int $durationMs = null,
        ?int $width = null,
        ?int $height = null,
    ): self {
        $instance = new self();
        $instance->sizeBytes = $sizeBytes;
        $instance->mimeType = $mimeType;
        if ($name !== null) {
            $instance->name = $name;
        }
        if ($durationMs !== null) {
            $instance->durationMs = $durationMs;
        }
        if ($width !== null) {
            $instance->width = $width;
        }
        if ($height !== null) {
            $instance->height = $height;
        }

        return $instance;
    }
}
