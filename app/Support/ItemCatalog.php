<?php

namespace App\Support;

class ItemCatalog
{
    public const QUALITIES = [
        0 => 'Poor', 1 => 'Common', 2 => 'Uncommon', 3 => 'Rare',
        4 => 'Epic', 5 => 'Legendary', 6 => 'Artifact', 7 => 'Heirloom',
    ];

    public const CATEGORIES = [
        2 => ['name' => 'Weapon', 'icon' => 'inv_sword_04', 'subclasses' => [
            0 => 'One-Handed Axes', 1 => 'Two-Handed Axes', 2 => 'Bows', 3 => 'Guns',
            4 => 'One-Handed Maces', 5 => 'Two-Handed Maces', 6 => 'Polearms',
            7 => 'One-Handed Swords', 8 => 'Two-Handed Swords', 10 => 'Staves',
            13 => 'Fist Weapons', 14 => 'Miscellaneous', 15 => 'Daggers',
            16 => 'Thrown', 18 => 'Crossbows', 19 => 'Wands', 20 => 'Fishing Poles',
        ]],
        4 => ['name' => 'Armor', 'icon' => 'inv_chest_plate04', 'subclasses' => [
            0 => 'Miscellaneous', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate',
            6 => 'Shields', 7 => 'Librams', 8 => 'Idols', 9 => 'Totems', 10 => 'Sigils',
        ]],
        1 => ['name' => 'Container', 'icon' => 'inv_misc_bag_08', 'subclasses' => [
            0 => 'Bag', 1 => 'Soul Bag', 2 => 'Herb Bag', 3 => 'Enchanting Bag',
            4 => 'Engineering Bag', 5 => 'Gem Bag', 6 => 'Mining Bag', 7 => 'Leatherworking Bag', 8 => 'Inscription Bag',
        ]],
        0 => ['name' => 'Consumable', 'icon' => 'inv_potion_51', 'subclasses' => [
            0 => 'Consumable', 1 => 'Potion', 2 => 'Elixir', 3 => 'Flask', 4 => 'Scroll',
            5 => 'Food & Drink', 6 => 'Item Enhancement', 7 => 'Bandage', 8 => 'Other',
        ]],
        16 => ['name' => 'Glyph', 'icon' => 'inv_inscription_tradeskill01', 'subclasses' => [
            1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
            6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid',
        ]],
        7 => ['name' => 'Trade Goods', 'icon' => 'inv_fabric_linen_01', 'subclasses' => [
            10 => 'Elemental', 5 => 'Cloth', 6 => 'Leather', 7 => 'Metal & Stone',
            8 => 'Meat', 9 => 'Herb', 12 => 'Enchanting', 4 => 'Jewelcrafting',
            1 => 'Parts', 3 => 'Devices', 2 => 'Explosives', 13 => 'Materials',
            14 => 'Armor Enchantment', 15 => 'Weapon Enchantment', 0 => 'Trade Goods', 11 => 'Other',
        ]],
        9 => ['name' => 'Recipe', 'icon' => 'inv_scroll_03', 'subclasses' => [
            0 => 'Book', 1 => 'Leatherworking', 2 => 'Tailoring', 3 => 'Engineering',
            4 => 'Blacksmithing', 5 => 'Cooking', 6 => 'Alchemy', 7 => 'First Aid',
            8 => 'Enchanting', 9 => 'Fishing', 10 => 'Jewelcrafting',
        ]],
        3 => ['name' => 'Gem', 'icon' => 'inv_jewelcrafting_gem_01', 'subclasses' => [
            0 => 'Red', 1 => 'Blue', 2 => 'Yellow', 3 => 'Purple', 4 => 'Green',
            5 => 'Orange', 6 => 'Meta', 7 => 'Simple', 8 => 'Prismatic',
        ]],
        6 => ['name' => 'Projectile', 'icon' => 'inv_ammo_arrow_02', 'subclasses' => [2 => 'Arrow', 3 => 'Bullet']],
        11 => ['name' => 'Quiver', 'icon' => 'inv_misc_quiver_01', 'subclasses' => [2 => 'Quiver', 3 => 'Ammo Pouch']],
        12 => ['name' => 'Quest', 'icon' => 'inv_misc_book_09', 'subclasses' => []],
        15 => ['name' => 'Miscellaneous', 'icon' => 'inv_misc_gift_01', 'subclasses' => [
            0 => 'Junk', 1 => 'Reagent', 2 => 'Companion Pets', 3 => 'Holiday', 4 => 'Other', 5 => 'Mount',
        ]],
    ];
}
