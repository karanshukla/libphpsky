<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Actor\ContentVisibilityDeclaration;

/**
 * object
 */
class ContentVisibilityDeclaration implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'main';
    public const ID = 'app.bsky.actor.contentVisibilityDeclaration';

    /** @var bool Whether the account requests that its posts be hidden from algorithmic recommendations. Consumers must treat a missing record as false. */
    public bool $hideFromAlgorithmicRecommendations;

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
        return ['hideFromAlgorithmicRecommendations'];
    }

    public static function new(bool $hideFromAlgorithmicRecommendations): self
    {
        $instance = new self();
        $instance->hideFromAlgorithmicRecommendations = $hideFromAlgorithmicRecommendations;

        return $instance;
    }
}
