<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Support\ItemCatalog;
use App\Support\Wow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuctionController extends Controller
{
    public function index(Request $request)
    {
        $markets = config('wow.auction.shared')
            ? ['shared' => ['name' => 'Shared Auction', 'houses' => [1, 2, 3, 4, 5, 6, 7]]]
            : [
                'alliance' => ['name' => 'Alliance', 'houses' => [1, 2, 3]],
                'horde' => ['name' => 'Horde', 'houses' => [4, 5, 6]],
                'neutral' => ['name' => 'Neutral', 'houses' => [7]],
            ];
        $categories = ItemCatalog::CATEGORIES;
        $qualities = ItemCatalog::QUALITIES;
        $category = $request->query('category');
        $subclasses = is_scalar($category) ? ($categories[$category]['subclasses'] ?? []) : [];
        $filters = $request->validate([
            'market' => ['nullable', Rule::in(array_keys($markets))],
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', Rule::in(array_keys($categories))],
            'subclass' => ['nullable', 'integer', Rule::in(array_keys($subclasses))],
            'quality' => ['nullable', 'integer', Rule::in(array_keys($qualities))],
            'min_level' => ['nullable', 'integer', 'between:0,80'],
            'max_level' => ['nullable', 'integer', 'between:0,80'],
            'sort' => ['nullable', Rule::in(['name', 'level', 'bid', 'buyout', 'time'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
        $market = $filters['market'] ?? array_key_first($markets);

        $world = config('wow.world_database');
        $query = Auction::query()
            ->join('item_instance as instance', 'instance.guid', '=', 'auctionhouse.itemguid')
            ->join($world.'.item_template as item', 'item.entry', '=', 'instance.itemEntry')
            ->leftJoin('characters as seller', 'seller.guid', '=', 'auctionhouse.itemowner')
            ->whereIn('auctionhouse.houseid', $markets[$market]['houses'])
            ->where('auctionhouse.time', '>', now()->timestamp)
            ->select([
                'auctionhouse.id', 'auctionhouse.buyoutprice', 'auctionhouse.startbid',
                'auctionhouse.lastbid', 'auctionhouse.time', 'instance.itemEntry',
                'instance.count', 'instance.randomPropertyId', 'instance.enchantments',
                'item.name', 'item.Quality as quality', 'item.RequiredLevel as level',
                'item.ItemLevel as item_level', 'item.displayid', 'seller.name as seller',
            ])
            ->selectRaw('CASE WHEN auctionhouse.lastbid > 0 THEN auctionhouse.lastbid ELSE auctionhouse.startbid END as bid');

        if (isset($filters['q'])) {
            // LIKE wildcards are literal characters in player searches.
            $query->whereRaw("item.name LIKE ? ESCAPE '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']).'%']);
        }
        foreach (['category' => 'item.class', 'subclass' => 'item.subclass', 'quality' => 'item.Quality'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $query->where($column, $filters[$filter]);
            }
        }
        if (isset($filters['min_level'])) {
            $query->where('item.RequiredLevel', '>=', $filters['min_level']);
        }
        if (isset($filters['max_level'])) {
            $query->where('item.RequiredLevel', '<=', $filters['max_level']);
        }

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $columns = ['name' => 'item.name', 'level' => 'item.RequiredLevel', 'bid' => 'bid', 'buyout' => 'auctionhouse.buyoutprice', 'time' => 'auctionhouse.time'];
        if ($sort === 'buyout') {
            $query->orderByRaw('auctionhouse.buyoutprice = 0');
        }
        $auctions = $query->orderBy($columns[$sort], $direction)->orderBy('auctionhouse.id')->paginate(25)->withQueryString();
        $icons = Wow::itemIcons();
        $tooltipUrl = rtrim(config('wow.tooltip_url'), '/');

        return response()->view('auctions.index', compact(
            'auctions', 'markets', 'market', 'categories', 'qualities', 'filters', 'sort', 'direction', 'icons', 'tooltipUrl'
        ))->header('Cache-Control', 'private, no-store');
    }
}
