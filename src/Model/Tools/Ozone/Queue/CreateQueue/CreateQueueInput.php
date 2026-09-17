<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\Tools\Ozone\Queue\CreateQueue;

/**
 * object
 */
class CreateQueueInput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'input';
    public const ID = 'tools.ozone.queue.createQueue';

    /** @var string Display name for the queue (must be unique) */
    public string $name;

    /** @var ?array<string> Subject types this queue accepts */
    public ?array $subjectTypes = [];

    /** @var ?string Collection name for record subjects. Required if subjectTypes includes 'record'. */
    public ?string $collection;

    /** @var ?array<string> Report reason types (fully qualified NSIDs) */
    public ?array $reportTypes = [];

    /** @var ?string Optional description of the queue */
    public ?string $description;

    /** @var ?array<string> Policy keys to recommend when actioning reports in this queue */
    public ?array $recommendedPolicies = [];

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
        return ['name'];
    }

    /**
     * @param array<string> $subjectTypes
     * @param array<string> $reportTypes
     * @param array<string> $recommendedPolicies
     */
    public static function new(
        string $name,
        ?array $subjectTypes = [],
        ?string $collection = null,
        ?array $reportTypes = [],
        ?string $description = null,
        ?array $recommendedPolicies = [],
    ): self {
        $instance = new self();
        $instance->name = $name;
        if ($subjectTypes !== null) {
            $instance->subjectTypes = $subjectTypes;
        }
        if ($collection !== null) {
            $instance->collection = $collection;
        }
        if ($reportTypes !== null) {
            $instance->reportTypes = $reportTypes;
        }
        if ($description !== null) {
            $instance->description = $description;
        }
        if ($recommendedPolicies !== null) {
            $instance->recommendedPolicies = $recommendedPolicies;
        }

        return $instance;
    }
}
