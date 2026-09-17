<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Unspecced\GetTrends;

/**
 * object
 */
class GetTrendsOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'app.bsky.unspecced.getTrends';

    /** @var array<\Aazsamir\Libphpsky\Model\App\Bsky\Unspecced\Defs\TrendView> */
    public array $trends = [];

    /** @var ?string Snowflake for this recommendation, use when submitting recommendation events. */
    public ?string $recIdStr;

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
        return ['trends'];
    }

    /**
     * @param array<\Aazsamir\Libphpsky\Model\App\Bsky\Unspecced\Defs\TrendView> $trends
     */
    public static function new(array $trends, ?string $recIdStr = null): self
    {
        $instance = new self();
        $instance->trends = $trends;
        if ($recIdStr !== null) {
            $instance->recIdStr = $recIdStr;
        }

        return $instance;
    }
}
