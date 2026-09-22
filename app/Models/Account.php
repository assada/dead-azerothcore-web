<?php

namespace App\Models;

use App\Enums\AccountAction;
use App\Support\Srp6;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Account extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    use CanResetPassword, HasFactory, Notifiable;

    protected $connection = 'acore_auth';

    protected $table = 'account';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'username',
        'salt',
        'verifier',
        'email',
        'reg_mail',
        'joindate',
        'last_ip',
        'last_attempt_ip',
        'failed_logins',
        'locked',
        'lock_country',
        'last_login',
        'online',
        'expansion',
        'Flags',
        'mutetime',
        'mutereason',
        'muteby',
        'locale',
        'os',
        'recruiter',
        'totaltime',
    ];

    protected $hidden = [
        'salt',
        'verifier',
        'session_key',
        'totp_secret',
    ];

    protected function casts(): array
    {
        return ['joindate' => 'datetime', 'last_login' => 'datetime', 'mutetime' => 'integer'];
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => strtoupper(trim($value)));
    }

    public function getAuthPassword()
    {
        // Laravel uses this fingerprint to invalidate web sessions after SRP credentials change.
        return $this->verifier ? hash('sha256', $this->salt.$this->verifier) : null;
    }

    public function operations(): HasMany
    {
        return $this->hasMany(AccountOperation::class, 'account_id');
    }

    public function changePassword(#[\SensitiveParameter] string $password, AccountAction $action): void
    {
        $this->getConnection()->transaction(function () use ($password, $action) {
            $account = self::lockForUpdate()->findOrFail($this->id);
            if ($account->getAuthPassword() !== $this->getAuthPassword()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'The account changed. Please try again.']);
            }
            $salt = random_bytes(32);
            $account->forceFill([
                'salt' => $salt,
                'verifier' => Srp6::computeVerifier($account->username, $password, $salt),
            ])->save();
            $account->operations()->create(['action' => $action]);
        });
        $this->refresh();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(AccountProfile::class, 'account_id');
    }

    public function hasVerifiedEmail()
    {
        return $this->profile?->email_verified_at !== null
            && strcasecmp($this->profile->verified_email, $this->email) === 0;
    }

    public function markEmailAsVerified()
    {
        $profile = $this->profile()->updateOrCreate([], [
            'verified_email' => $this->email, 'email_verified_at' => now(),
        ]);
        $this->setRelation('profile', $profile);

        return true;
    }

    public function getRememberToken()
    {
        return $this->profile?->remember_token;
    }

    public function setRememberToken($value)
    {
        $profile = $this->profile ?? new AccountProfile;
        $profile->remember_token = $value;
        $this->setRelation('profile', $profile);
    }

    /**
     * Access entries for this account across realms.
     */
    public function accesses(): HasMany
    {
        return $this->hasMany(AccountAccess::class, 'id', 'id');
    }

    /**
     * Preferred access entry for the current/default realm.
     */
    public function realmAccess(): HasOne
    {
        $realmId = \App\Support\Wow::defaultRealmId();

        // Prefer specific realm, else fallback to global (-1)
        return $this->hasOne(AccountAccess::class, 'id', 'id')
            ->whereIn('RealmID', [$realmId, -1])
            ->orderByRaw('CASE WHEN RealmID = ? THEN 0 ELSE 1 END', [$realmId]);
    }

    /**
     * Compute security level integer. 0 if no access entry present.
     */
    public function getSecurityLevelAttribute(): int
    {
        $access = $this->realmAccess()->first();

        return $access ? (int) $access->gmlevel : 0;
    }

    /**
     * Human-readable label for security level.
     */
    public function getSecurityLabelAttribute(): string
    {
        $map = [
            0 => 'Player',
            1 => 'Moderator',
            2 => 'Gamemaster',
            3 => 'Administrator',
        ];
        $level = $this->security_level;

        return $map[$level] ?? 'Player';
    }

    /**
     * Short code for badge display (e.g., GM, MOD, ADMIN, PLAYER).
     */
    public function getSecurityCodeAttribute(): string
    {
        $map = [
            0 => 'PLAYER',
            1 => 'MOD',
            2 => 'GM',
            3 => 'ADMIN',
        ];
        $level = $this->security_level;

        return $map[$level] ?? 'PLAYER';
    }
}
