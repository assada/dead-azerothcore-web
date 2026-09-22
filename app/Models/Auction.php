<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auction extends Model
{
    protected $connection = 'acore_characters';

    protected $table = 'auctionhouse';

    public $timestamps = false;

    public function timeLeft(): string
    {
        $seconds = $this->time - now()->timestamp;

        return match (true) {
            $seconds < 1800 => 'Short',
            $seconds < 7200 => 'Medium',
            $seconds < 43200 => 'Long',
            default => 'Very long',
        };
    }

    public function tooltipRel(): string
    {
        $params = [];
        if ($this->randomPropertyId) {
            $params['rand'] = (int) $this->randomPropertyId;
        }
        $enchants = preg_split('/\s+/', trim($this->enchantments ?? ''));
        if (! empty($enchants[0])) {
            $params['ench'] = (int) $enchants[0];
        }

        return http_build_query($params);
    }
}
