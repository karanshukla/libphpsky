<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\App\Bsky\Graph\MuteActor;

/**
 * Creates a mute relationship for the specified account. If a mute already exists for the account, it is updated in place: the stored scope is replaced with the scope in this request. Mutes are private in Bluesky. Requires auth.
 * procedure
 */
class MuteActor implements \Aazsamir\Libphpsky\Action
{
    use \Aazsamir\Libphpsky\Generator\Prefab\IsProcedure;

    public const NAME = 'main';
    public const ID = 'app.bsky.graph.muteActor';

    public static function id(): string
    {
        return self::ID;
    }

    public static function name(): string
    {
        return self::NAME;
    }

    public function procedure(MuteActorInput $input): void
    {
        $this->request($this->argsWithKeys(func_get_args()));
    }

    public function rawProcedure(MuteActorInput $input): mixed
    {
        return $this->request($this->argsWithKeys(func_get_args()));
    }
}
