<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Graph\MuteActor;

/**
 * object
 */
class MuteActorInput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'input';
    public const ID = 'app.bsky.graph.muteActor';

    public string $actor;

    /** @var ?bool Restrict the mute to the account's reposts. When any 'only' scope is set, just the scoped content is muted; when none are set, the account is fully muted. Repeat calls replace the stored scope rather than adding to it. */
    public ?bool $onlyReposts;

    /** @var ?bool Restrict the mute to the account's quote posts. See onlyReposts. */
    public ?bool $onlyQuoteposts;

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
        return ['actor'];
    }

    public static function new(string $actor, ?bool $onlyReposts = null, ?bool $onlyQuoteposts = null): self
    {
        $instance = new self();
        $instance->actor = $actor;
        if ($onlyReposts !== null) {
            $instance->onlyReposts = $onlyReposts;
        }
        if ($onlyQuoteposts !== null) {
            $instance->onlyQuoteposts = $onlyQuoteposts;
        }

        return $instance;
    }
}
