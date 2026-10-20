<?php

namespace App\Enums;

use RuntimeException;

enum StudentImportStatus: string
{
    case Uploaded = 'uploaded';
    case Analyzed = 'analyzed';
    case Mapped = 'mapped';
    case Previewed = 'previewed';
    case Processing = 'processing';
    case Partial = 'partial';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function nextStates(): array
    {
        return match ($this) {
            self::Uploaded => [self::Analyzed, self::Failed, self::Cancelled],
            self::Analyzed => [self::Mapped, self::Failed, self::Cancelled],
            self::Mapped => [self::Previewed, self::Analyzed, self::Failed, self::Cancelled],
            self::Previewed => [self::Processing, self::Mapped, self::Failed, self::Cancelled],
            self::Processing => [self::Partial, self::Completed, self::Failed],
            self::Partial => [self::Processing, self::Failed, self::Cancelled],
            self::Failed => [self::Processing, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStates(), true);
    }

    public function assertCanTransitionTo(self $status): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new RuntimeException("Transition d’import invalide : {$this->value} vers {$status->value}.");
        }
    }
}
