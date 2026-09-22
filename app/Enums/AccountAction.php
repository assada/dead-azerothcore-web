<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

enum AccountAction: string
{
    case Email = 'email';
    case Password = 'password';
    case PasswordReset = 'password_reset';
    case Sessions = 'sessions';
    case Unstuck = 'unstuck';
    case Rename = 'rename';
    case Customize = 'customize';
    case Deactivate = 'deactivate';
    case Restore = 'restore';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Login email changed',
            self::Deactivate => 'Account deactivated',
            self::Restore => 'Account restored',
            self::Password => 'Password changed',
            self::PasswordReset => 'Password reset',
            self::Sessions => 'Other sessions ended',
            self::Unstuck => 'Unstuck',
            self::Rename => 'Rename',
            self::Customize => 'Change appearance',
        };
    }

    public function loginFlag(): int
    {
        return match ($this) {
            self::Rename => 1,
            self::Customize => 8,
            default => 0,
        };
    }

    public function availableAfter(CarbonImmutable $completedAt): CarbonImmutable
    {
        $cooldown = config("wow.actions.{$this->value}.cooldown");
        if ($cooldown === 'calendar_quarter') {
            return $completedAt->setTimezone(config('app.timezone'))->startOfQuarter()->addMonths(3);
        }

        $interval = new CarbonInterval($cooldown);

        // Keep February 29 and month-end requests within the target month.
        return $completedAt->addYearsNoOverflow($interval->y)
            ->addMonthsNoOverflow($interval->m)
            ->add($interval->years(0)->months(0));
    }

    public function enabled(): bool
    {
        return (bool) config("wow.actions.{$this->value}.enabled", false);
    }

    public function cooldownDescription(): string
    {
        $cooldown = config("wow.actions.{$this->value}.cooldown");
        if ($cooldown === 'calendar_quarter') {
            return 'Available once per calendar quarter ('.config('app.timezone').').';
        }

        $interval = new CarbonInterval($cooldown);

        return $interval->isEmpty() ? '' : 'Available once every '.$interval->forHumans().'.';
    }

    public function command(int $guid): string
    {
        return match ($this) {
            self::Unstuck => "unstuck $guid inn",
            self::Rename => "character rename $guid",
            self::Customize => "character customize $guid",
            default => throw new \LogicException('Not a character action.'),
        };
    }
}
