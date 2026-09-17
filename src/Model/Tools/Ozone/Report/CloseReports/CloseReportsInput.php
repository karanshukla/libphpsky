<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Model\Tools\Ozone\Report\CloseReports;

/**
 * object
 */
class CloseReportsInput implements \Aazsamir\Libphpsky\ATProtoObject
{
    use \Aazsamir\Libphpsky\Generator\Prefab\FromArray;
    use \Aazsamir\Libphpsky\Generator\Prefab\ToArray;

    public const NAME = 'input';
    public const ID = 'tools.ozone.report.closeReports';

    /** @var string Subject DID (account-level reports) or AT-URI (record-level reports) whose reports should be closed. */
    public string $subject;

    /** @var ?array<string> If specified, only reports of the given report types (fully qualified reason NSIDs) are closed. When omitted, all non-closed reports on the subject are targeted. */
    public ?array $reportTypes = [];

    /** @var ?string Optional moderator-only note recorded on each close activity. Not visible to reporters. */
    public ?string $internalNote;

    /** @var ?bool Set true when this action is triggered by an automated process. Defaults to false. */
    public ?bool $isAutomated;

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
        return ['subject'];
    }

    /**
     * @param array<string> $reportTypes
     */
    public static function new(
        string $subject,
        ?array $reportTypes = [],
        ?string $internalNote = null,
        ?bool $isAutomated = null,
    ): self {
        $instance = new self();
        $instance->subject = $subject;
        if ($reportTypes !== null) {
            $instance->reportTypes = $reportTypes;
        }
        if ($internalNote !== null) {
            $instance->internalNote = $internalNote;
        }
        if ($isAutomated !== null) {
            $instance->isAutomated = $isAutomated;
        }

        return $instance;
    }
}
