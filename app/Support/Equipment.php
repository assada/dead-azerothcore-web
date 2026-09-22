<?php

namespace App\Support;

use App\Models\Character;

class Equipment
{
    public const SLOTS = [
        'Head', 'Neck', 'Shoulder', 'Shirt', 'Chest', 'Waist', 'Legs', 'Feet', 'Wrist',
        'Hands', 'Finger', 'Finger', 'Trinket', 'Trinket', 'Back', 'Main hand', 'Off hand', 'Ranged', 'Tabard',
    ];

    public function __construct(private GameData $data) {}

    public function forCharacter(Character $character): array
    {
        $world = config('wow.world_database');
        $rows = $character->inventoryItems()
            ->join('item_instance as instance', 'instance.guid', '=', 'character_inventory.item')
            ->join($world.'.item_template as item', 'item.entry', '=', 'instance.itemEntry')
            ->where('character_inventory.bag', 0)
            ->whereBetween('character_inventory.slot', [0, 18])
            ->orderBy('character_inventory.slot')
            ->get([
                'character_inventory.slot', 'instance.itemEntry', 'instance.enchantments', 'instance.randomPropertyId',
                'item.name', 'item.Quality as quality', 'item.ItemLevel as itemLevel', 'item.class as classId',
                'item.subclass as subclassId', 'item.InventoryType as inventoryType', 'item.displayid', 'item.socketBonus',
            ]);
        $metadata = $this->data->table('items');
        $enchants = $this->data->table('enchants');
        $icons = $this->data->table('item-icons');
        $pieces = $rows->pluck('itemEntry')->implode(':');
        $equipment = [];
        foreach ($rows as $row) {
            $item = $row->getAttributes();
            foreach (['slot', 'itemEntry', 'randomPropertyId', 'quality', 'itemLevel', 'classId', 'subclassId', 'inventoryType', 'displayid', 'socketBonus'] as $field) {
                $item[$field] = (int) $item[$field];
            }
            $meta = $metadata[$item['itemEntry']] ?? null;
            if ($meta) {
                $item['classId'] = $meta['class'];
                $item['subclassId'] = $meta['subclass'];
                $item['inventoryType'] = $meta['inventoryType'];
            }
            $item['appearance'] = $meta['appearance'] ?? 0;
            $item['icon'] = $icons[$item['displayid']] ?? 'inv_misc_questionmark';
            $item['slotName'] = self::SLOTS[$item['slot']];
            $gems = [];
            $enchantIds = [];
            foreach (array_chunk(array_map('intval', explode(' ', trim($item['enchantments']))), 3) as $enchantment) {
                $id = $enchantment[0];
                if (! isset($enchants[$id])) {
                    continue;
                }
                if ($enchants[$id]['gem']) {
                    $gems[] = $enchants[$id]['item'];
                } elseif ($id !== $item['socketBonus']) {
                    $enchantIds[] = $id;
                }
            }
            $params = ['pcs' => $pieces];
            if ($gems) {
                $params['gems'] = implode(':', $gems);
            }
            if ($enchantIds) {
                $params['ench'] = implode(':', $enchantIds);
            }
            if ($item['randomPropertyId']) {
                $params['rand'] = $item['randomPropertyId'];
            }
            $item['tooltip'] = http_build_query($params);
            $equipment[] = $item;
        }

        return $equipment;
    }

    public function modelItems(array $equipment, int $class): array
    {
        $items = [];
        foreach ($equipment as $item) {
            if (! in_array($item['slot'], [0, 2, 3, 4, 5, 6, 7, 8, 9, 14, 15, 16, 17, 18], true)
                || ($item['slot'] === 17 && $class !== 3) || $item['itemEntry'] === 5976 || ! $item['appearance']) {
                continue;
            }
            $items[] = [$item['inventoryType'], $item['appearance']];
        }

        return $items;
    }
}
