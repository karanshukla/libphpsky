<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\Tools\Ozone\Report\CloseReports;

/**
 * object
 */
class CloseReportsOutput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'output';
    public const ID = 'tools.ozone.report.closeReports';

    /** @var int Number of reports that were transitioned to closed. */
    public int $closedCount;

    /** @var array<int> IDs of the reports that were closed. */
    public array $reportIds = [];

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
        return ['closedCount', 'reportIds'];
    }

    /**
     * @param array<int> $reportIds
     */
    public static function new(int $closedCount, array $reportIds): self
    {
        $instance = new self();
        $instance->closedCount = $closedCount;
        $instance->reportIds = $reportIds;

        return $instance;
    }
}
