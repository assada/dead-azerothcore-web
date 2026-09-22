<?php

namespace App\Support;

use App\Models\Character;

class CharacterAppearance
{
    public function __construct(private GameData $data) {}

    public function options(Character $character): array
    {
        $definitions = array_column($this->data->table("customization/{$character->race}_{$character->gender}"), null, 'Name');
        $options = [];
        $select = function (string $name, int|string|null $value, string $field) use ($definitions, &$options): void {
            $option = $definitions[$name] ?? null;
            if (! $option || $value === null) {
                return;
            }
            if ($field === 'Id') {
                $options[] = ['optionId' => $option['Id'], 'choiceId' => $value];

                return;
            }
            foreach ($option['Choices'] as $choice) {
                if ($choice[$field] === $value) {
                    $options[] = ['optionId' => $option['Id'], 'choiceId' => $choice['Id']];
                    break;
                }
            }
        };
        foreach (['Face' => 'face', 'Skin Color' => 'skin', 'Hair Style' => 'hairStyle', 'Hair Color' => 'hairColor'] as $name => $field) {
            $select($name, $character->$field, 'OrderIndex');
        }
        // WotLK appearance values mapped to the existing HD viewer.
        switch ($character->race) {
            case 1: // Human
                if ($character->gender === 0) {
                    $select(
                        'Mustache',
                        [0 => 'Horseshoe', 1 => 'Brush', 2 => 'Horseshoe', 3 => 'None', 4 => 'Brush', 5 => 'Brush', 6 => 'Horseshoe', 7 => 'Brush', 8 => 'None'][
                            $character->facialStyle
                        ] ?? null, 'Name');
                    $select(
                        'Beard',
                        [0 => 'Short', 1 => 'Chin Puff', 2 => 'Soul Patch', 3 => 'Goatee', 4 => 'Goatee', 5 => 'None', 6 => 'Goatee', 7 => 'None', 8 => 'None'][
                            $character->facialStyle
                        ] ?? null, 'Name');
                    $select(
                        'Sideburns',
                        [0 => 'Medium', 1 => 'None', 2 => 'None', 3 => 'Medium', 4 => 'Long', 5 => 'Long', 6 => 'None', 8 => 'None', 7 => 'None'][$character->facialStyle] ?? null, 'Name');
                    $select('Eyebrows', 'Natural', 'Name');
                    $select('Face Shape', 'Narrow', 'Name');
                    $select(
                        'Eye Color',
                        [0 => 4138, 1 => 4140, 2 => 4130, 3 => 4136, 4 => 4141, 5 => 4134, 6 => 4130, 7 => 4138, 8 => 4144, 9 => 4135, 10 => 4126, 11 => 4136][$character->face] ?? null, 'Id');
                } else {
                    $select('Piercings', $character->facialStyle, 'OrderIndex');
                    $select('Eyebrows', 'Natural', 'Name');
                    $select('Face Shape', 'Narrow', 'Name');
                    $select('Makeup', 'None', 'Name');
                    $select('Necklace', 'None', 'Name');
                    $select(
                        'Eye Color',
                        [
                            0 => 4162,
                            1 => 4153,
                            2 => 4161,
                            3 => 4164,
                            4 => 4154,
                            5 => 4160,
                            6 => 4160,
                            7 => 4157,
                            8 => 4152,
                            9 => 4154,
                            10 => 4155,
                            11 => 4165,
                            12 => 4163,
                            13 => 4155,
                            14 => 4151,
                        ][$character->face] ?? null, 'Id');
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 4534 : 4535, 'Id');
                }
                break;
            case 3: // Dwarf
                $select('Tattoo', 'None', 'Name');
                $select('Tattoo Color', 0, 'OrderIndex');
                $select('Eyebrows', 0, 'OrderIndex');
                if ($character->gender === 0) {
                    $select(
                        'Mustache',
                        [
                            0 => 'Trimmed',
                            1 => 'Bushy',
                            2 => 'Grand',
                            3 => 'Thin Braids',
                            4 => 'Wise',
                            5 => 'Thick Braids',
                            6 => 'Fancy',
                            7 => 'Bold',
                            8 => 'Tied',
                            9 => 'None',
                            10 => 'None',
                        ][$character->facialStyle] ?? null, 'Name');
                    $select('Beard', $character->facialStyle, 'OrderIndex');
                    $select('Earrings', 'None', 'Name');
                    $select('Nose Ring', 'None', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                } else {
                    $select('Earrings', [0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 0, 5 => 4][$character->facialStyle] ?? null, 'OrderIndex');
                    $select(
                        'Piercings',
                        [0 => 'None', 1 => 'None', 2 => 'None', 3 => 'None', 4 => 'Right Nostril', 5 => 'None'][$character->facialStyle] ?? null, 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 5559 : 5587, 'Id');
                }
                break;
            case 7: // Gnome
                if ($character->gender === 0) {
                    $select('Mustache', $character->facialStyle > 1 ? $character->facialStyle - 1 : 0, 'OrderIndex');
                    $select('Beard', $character->facialStyle < 7 ? $character->facialStyle : 0, 'OrderIndex');
                    $select('Eyebrows', $character->facialStyle < 6 ? $character->facialStyle : 1, 'OrderIndex');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                } else {
                    $select('Earrings', $character->facialStyle, 'OrderIndex');
                    $select('Earring Color', 8796, 'Id');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 5629 : 5643, 'Id');
                }
                break;
            case 4: // Night Elf
                $select('Vines', 'None', 'Name');
                $select('Vine Color', 0, 'OrderIndex');
                $select('Ears', 'Thin', 'Name');
                $select('Scars', 'None', 'Name');
                if ($character->gender === 0) {
                    $select(
                        'Sideburns',
                        [0 => 'None', 1 => 'Groomed', 2 => 'None', 3 => 'Short', 4 => 'Medium', 5 => 'Groomed'][$character->facialStyle] ?? null, 'Name');
                    $select('Mustache', [0 => 'None', 1 => 'Groomed', 2 => 'None', 3 => 'Thin', 4 => 'None', 5 => 'None'][$character->facialStyle] ?? null, 'Name');
                    $select('Beard', [0 => 'None', 1 => 'Trimmed', 2 => 'Full', 3 => 'None', 4 => 'Short', 5 => 'Long'][$character->facialStyle] ?? null, 'Name');
                    $select('Eyebrows', [0 => 'Shaved', 1 => 'Short', 2 => 'Long', 3 => 'Flat', 4 => 'Short', 5 => 'Owl'][$character->facialStyle] ?? null, 'Name');
                } else {
                    $select('Eyebrows', 'Long', 'Name');
                    $select('Markings', $character->facialStyle + 1, 'OrderIndex');
                    $select('Markings Color', [0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 3, 6 => 6, 7 => 7][$character->hairColor] ?? null, 'OrderIndex');
                }
                $select('Blindfold', '', 'Name');
                $select('Headdress', 'None', 'Name');
                $select('Earrings', 'None', 'Name');
                $select('Nose Ring', 'None', 'Name');
                $select('Necklace', 'None', 'Name');
                $select('Horns', 'None', 'Name');
                $select('Tattoo', 'None', 'Name');
                $select('Tattoo Color', 'None', 'Name');
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 7618 : 7634, 'Id');
                } else {
                    $select('Eye Color', $character->gender === 0 ? 7610 : 7619, 'Id');
                }
                break;
            case 11: // Draenei
                $select('Circlet', 'None', 'Name');
                $select('Jewelry Color', $character->gender === 0 ? 8707 : 8646, 'Id');
                $select('Horn Decoration', 'None', 'Name');
                $select('Tail', $character->gender === 0 ? 'Long' : 'Short', 'Name');
                if ($character->gender === 0) {
                    $select(
                        'Facial Hair',
                        [0 => 'Bare', 1 => 'Bare', 2 => 'Burns', 3 => 'Chops', 4 => 'Mustache', 5 => 'Soul Patch', 6 => 'Handlebar', 7 => 'Bare'][
                            $character->facialStyle
                        ] ?? null, 'Name');
                    $select(
                        'Tendrils',
                        [0 => 'None', 1 => 'Splayed', 2 => 'Double', 3 => 'Fanned', 4 => 'Single', 5 => 'Paired', 6 => 'Uniform', 7 => 'Twin'][$character->facialStyle] ?? null, 'Name');
                } else {
                    $select(
                        'Horns',
                        [0 => 'Sweeping', 1 => 'Curled', 2 => 'Curved', 3 => 'Thick', 4 => 'Wide', 5 => 'Grand', 6 => 'Short'][$character->facialStyle] ?? null, 'Name');
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 6977 : 6979, 'Id');
                } else {
                    $select('Eye Color', $character->gender === 0 ? 6976 : 6978, 'Id');
                }
                break;
            case 2: // Orc
                $select('Scars', 'None', 'Name');
                $select('Grime', 'None', 'Name');
                $select('Tattoo', 'None', 'Name');
                $select('War Paint', 'None', 'Name');
                $select('War Paint Color', 'None', 'Name');
                if ($character->gender === 0) {
                    $select(
                        'Beard',
                        [
                            0 => 'None',
                            1 => 'Stubble',
                            2 => 'Thick',
                            3 => 'Full',
                            4 => 'Tied',
                            5 => 'Braid',
                            6 => 'Twin Braids',
                            7 => 'None',
                            8 => 'Ringed',
                            9 => 'Split',
                            10 => 'Goatee',
                        ][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Sideburns',
                        [0 => 'None', 1 => 'None', 2 => 'Full', 3 => 'Low', 4 => 'Full', 5 => 'None', 6 => 'None', 7 => 'Braids', 8 => 'None', 9 => 'Full', 10 => 'Thick'][
                            $character->facialStyle
                        ] ?? null, 'Name');
                    $select('Earrings', 'None', 'Name');
                    $select('Nose Ring', 'None', 'Name');
                    $select('Tusks', 'Natural', 'Name');
                    $select('Upright', 'Hunched', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                } else {
                    $select('Earrings', [0 => 0, 1 => 1, 2 => 2, 3 => 0, 4 => 1, 5 => 2, 6 => 4][$character->facialStyle] ?? null, 'OrderIndex');
                    $select('Nose Ring', [0 => 0, 1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1, 6 => 0][$character->facialStyle] ?? null, 'OrderIndex');
                    $select('Necklace', 'None', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 9289 : 9313, 'Id');
                }
                break;
            case 5: // Undead
                $select('Skin Type', 'Bony', 'Name');
                if ($character->gender === 0) {
                    $select(
                        'Jaw Features',
                        [
                            0 => 'Intact',
                            1 => 'Rot-Kissed',
                            2 => 'Intact',
                            3 => 'Slackjawed',
                            4 => 'Drooler',
                            5 => 'Intact',
                            6 => 'Slackjawed',
                            7 => 'Drooler',
                            8 => 'Bonejawed',
                            9 => 'Jawsome',
                            10 => 'Toothy',
                            11 => 'Unhinged',
                            12 => 'Cheeky',
                            13 => 'Loose',
                            14 => 'Intact',
                            15 => 'Slackjawed',
                            16 => 'Slobber',
                        ][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Face Features',
                        [0 => 0, 1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 2, 6 => 3, 7 => 3, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0, 13 => 0, 14 => 4, 15 => 4, 16 => 0][
                            $character->facialStyle
                        ] ?? null, 'OrderIndex');
                    $select(
                        'Eye Color',
                        [
                            0 => 5330,
                            1 => 5330,
                            2 => 6304,
                            3 => 6304,
                            4 => 6304,
                            5 => 5330,
                            6 => 5330,
                            7 => 5330,
                            8 => 5330,
                            9 => 5330,
                            10 => 6304,
                            11 => 6304,
                            12 => 5330,
                            13 => 5330,
                            14 => 5330,
                            15 => 5330,
                            16 => 5330,
                        ][$character->facialStyle] ?? null, 'Id');
                } else {
                    $select(
                        'Face Features',
                        [0 => 'None', 1 => 'None', 2 => 'Strapped', 3 => 'Rotting', 4 => 'None', 5 => 'None', 6 => 'None', 7 => 'Putrid'][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Jaw Features',
                        [0 => 'Intact', 1 => 'Stitched', 2 => 'Intact', 3 => 'Intact', 4 => 'Bonejawed', 5 => 'Toothy', 6 => 'Cheeky', 7 => 'Intact'][
                            $character->facialStyle
                        ] ?? null, 'Name');
                    $select(
                        'Eye Color',
                        [0 => 5337, 1 => 5337, 2 => 6305, 3 => 5337, 4 => 5337, 5 => 6305, 6 => 5337, 7 => 5337][$character->facialStyle] ?? null, 'Id');
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 5344 : 5345, 'Id');
                }
                break;
            case 6: // Tauren
                $select('Horn Style', $character->hairStyle, 'OrderIndex');
                $select('Horn Color', $character->hairColor, 'OrderIndex');
                $select('Foremane', 'Short', 'Name');
                $select('Face Paint', 'None', 'Name');
                $select('Headdress', 'None', 'Name');
                $select('Necklace', 'None', 'Name');
                $select('Jewelry Color', 0, 'OrderIndex');
                $select('Flower', 'None', 'Name');
                $select('Body Paint', 'None', 'Name');
                $select('Paint Color', 0, 'OrderIndex');
                if ($character->gender === 0) {
                    $select(
                        'Hair',
                        [0 => 'Mane', 1 => 'Braids', 2 => 'Chops', 3 => 'Sideburns', 4 => 'Mane', 5 => 'Wrapped', 6 => 'Braids'][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Facial Hair',
                        [0 => 'Clean', 1 => 'Braid', 2 => 'Beard', 3 => 'Wrapped', 4 => 'Curtain', 5 => 'Clean', 6 => 'Split'][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Nose Ring',
                        [0 => 'None', 1 => 'Small', 2 => 'Open', 3 => 'None', 4 => 'None', 5 => 'Bead', 6 => 'Open'][$character->facialStyle] ?? null, 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                } else {
                    $select('Hair', $character->facialStyle, 'OrderIndex');
                    $select('Earrings', 'None', 'Name');
                    $select('Nose Ring', 'None', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 7281 : 7289, 'Id');
                }
                break;
            case 8: // Troll
                $select('Body Paint', 'None', 'Name');
                $select('Body Paint Color', 'None', 'Name');
                $select('Piercing', 'None', 'Name');
                if ($character->gender === 0) {
                    $select(
                        'Tusks',
                        [
                            0 => 'Tusked',
                            1 => 'Gougers',
                            2 => 'Mammoth',
                            3 => 'Spears',
                            4 => 'Bridle',
                            5 => 'Tusked',
                            6 => 'Gougers',
                            7 => 'Mammoth',
                            8 => 'Spears',
                            9 => 'Bridle',
                            10 => 'Gougers',
                        ][$character->facialStyle] ?? null, 'Name');
                    $select(
                        'Face Paint',
                        [
                            0 => 'None',
                            1 => 'None',
                            2 => 'None',
                            3 => 'None',
                            4 => 'None',
                            5 => 'Berserker',
                            6 => 'Fangs',
                            7 => 'Mask',
                            8 => 'Oni',
                            9 => 'Prophet',
                            10 => 'War',
                        ][$character->facialStyle] ?? null, 'Name');
                    $select('Face Paint Color', $character->hairColor + 1, 'OrderIndex');
                    $select('Earrings', 'None', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                } else {
                    $select('Tusks', $character->facialStyle, 'OrderIndex');
                    $select('Face Paint', 'None', 'Name');
                    $select('Face Paint Color', 0, 'OrderIndex');
                    $select('Earrings', 'Hoops', 'Name');
                    $select('Eye Color', 0, 'OrderIndex'); // TODO
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 8451 : 8468, 'Id');
                }
                break;
            case 10: // Blood Elf
                $select('Ears', 'Long', 'Name');
                $select('Horns', 'None', 'Name');
                $select('Blindfold', 'None', 'Name');
                $select('Tattoo', 'None', 'Name');
                $select('Tattoo Color', 0, 'OrderIndex');
                if ($character->gender === 0) {
                    $select('Facial Hair', $character->facialStyle, 'OrderIndex');
                } else {
                    $select('Earrings', $character->facialStyle, 'OrderIndex');
                    $select('Jewelry Color', 0, 'OrderIndex');
                    $select('Necklace', 'None', 'Name');
                    $select('Armbands', 'None', 'Name');
                    $select('Bracelets', 'None', 'Name');
                }
                if ($character->class === 6) {
                    // Death Knight
                    $select('Eye Color', $character->gender === 0 ? 6586 : 6605, 'Id');
                } else {
                    $select('Eye Color', $character->gender === 0 ? 6570 : 6589, 'Id');
                }
                break;
        }
        if (in_array($character->race, [4, 6], true)) {
            // Races that can choose the druid class
            $select('Bear Form', 0, 'OrderIndex');
            $select('Cat Form', 0, 'OrderIndex');
            $select('Aquatic Form', 0, 'OrderIndex');
            $select('Travel Form', 0, 'OrderIndex');
            $select('Flight Form', 0, 'OrderIndex');
            $select('Moonkin Form', 0, 'OrderIndex');
        }

        return $options;
    }
}
