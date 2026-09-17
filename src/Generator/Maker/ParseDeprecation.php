<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Generator\Maker;

use Aazsamir\Libphpsky\Generator\Lexicon\Def\Def;
use Nette\PhpGenerator\ClassType;

trait ParseDeprecation
{
    private function addClassDeprecated(ClassType $class, Def $def): void
    {
        if ($this->isDeprecated($def->description())) {
            $deprecatedComment = $this->extractDeprecatedComment($def->description());
            $class->addComment('@deprecated ' . ($deprecatedComment ?? ''));
        }
    }

    private function isDeprecated(?string $description): bool
    {
        if ($description === null) {
            return false;
        }

        return str_contains(strtolower($description), 'deprecated');
    }

    private function extractDeprecatedComment(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = strtolower($description);
        $deprecatedPos = strpos($description, 'deprecated');

        if ($deprecatedPos === false) {
            return null;
        }

        // find until `. ` or until the end of the string
        $afterDeprecated = substr($description, $deprecatedPos + \strlen('deprecated'));
        $afterDeprecated = trim($afterDeprecated, "\n\r\t\v\0 :.,-");
        $endingPos = strpos($afterDeprecated, '. ');
        $endingPos2 = strpos($afterDeprecated, '--');
        $endingPos = min(
            $endingPos !== false ? $endingPos : \PHP_INT_MAX,
            $endingPos2 !== false ? $endingPos2 : \PHP_INT_MAX,
        );

        if ($endingPos !== \PHP_INT_MAX) {
            $afterDeprecated = substr($afterDeprecated, 0, $endingPos);
        }

        return trim($afterDeprecated, "\n\r\t\v\0 :.,-");
    }
}
