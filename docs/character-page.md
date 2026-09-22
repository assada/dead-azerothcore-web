# Character pages

Character pages run in Laravel. They read AzerothCore's character and world databases and use the existing browser model viewer. No armory backend or iframe is needed.

The page includes equipment tooltips, saved character stats, both talent groups and their glyphs, learned mounts, achievements, reputation, skills and arena teams. It uses AzerothCore's `talentGroupsCount`, `activeTalentGroup` and spell `specMask` columns.

## Data and caching

Character data is queried on each request. Stats come from `character_stats`, so they reflect the values saved by the game server. Static client metadata is stored in `resources/data/game` and cached by file modification time. The auction's item icons use the same data loader.

GearScore and average item level are in `app/Support/GearScore.php`. They preserve the previous server formulas, including hunter weapon weights, two-handed weapons, heirlooms and enchantment penalties. Mount counts use known mount spell IDs. Reputation includes the race/class base value and follows the client faction hierarchy.

## Model assets

Place the model data directories here:

```
storage/app/modelviewer/9.2.0/
  meta/
  textures/
  mo3/
  bone/
```

Nginx serves them at `/modelviewer/9.2.0/`. The storage volume keeps these files outside the application image. For an existing asset pack, copy these directories into the application container with `docker compose cp /path/to/modelviewer/9.2.0/. app:/var/www/html/storage/app/modelviewer/9.2.0/`. The repository does not provide an asset download or extraction workflow yet. Do not copy its `data.tar.gz` archive into the serving directory.

`public/vendor/modelviewer/viewer.min.js` is the server's existing customized viewer from [azerothcore-armory](https://github.com/r-o-b-o-t-o/azerothcore-armory). Its source repository's license is included beside it. The Laravel adapter supplies jQuery and the viewer's debug hook. The transparent canvas shares the page's courtyard background.

### Viewer options

`resources/js/model-viewer` extends the existing viewer and WebGL renderer. `character-model.js` sets these options for character pages:

| Option | Value | Effect |
| --- | --- | --- |
| `rotationEnabled` | `true` | Enable drag rotation. |
| `verticalRotationEnabled` | `false` | Keep the camera level. |
| `panningEnabled` | `false` | Disable right-button and Ctrl-drag movement. |
| `snowEnabled` | `true` | Enable snow particles. |
| `snowDensity` | `0.00055` | Particles per canvas pixel, capped at 600. |
| `snowSize` | `1.75` | Scale flake size. |
| `snowSpeed` | `1.1` | Scale simulation time. |
| `snowWind` | `1.3` | Scale wind and gusts. |
| `shadowEnabled` | `true` | Show a soft shadow beneath the model. |
| `shadowOpacity` | `0.45` | Set shadow opacity. |
| `shadowSize` | `1` | Scale shadow width. |

Snow particles have individual fall speeds, drag, opacity and spin. A shared wind field changes their velocities; depth controls scale, blur and occlusion by the character. Snow stays in camera space during rotation. It does not collide with the model.

Both effects use the viewer's render loop. Snow pauses outside the viewport and in hidden tabs; reduced-motion preferences disable it. Resetting the view, changing mounts or leaving the page disposes effect buffers, shaders and observers with the renderer.

## Rebuilding metadata

The generated JSON is included with the application. To rebuild it, run:

```sh
docker compose run --rm -v /path/to/client-data:/client-data:ro app php -d memory_limit=512M artisan wow:import-data /client-data
```

The importer expects the original 3.3.5 client CSV files for spells, spell icons, items, item display information, enchantments, talents, talent tabs, glyphs, achievements and achievement categories. It also uses `Skills.csv`, `Areas.csv`, WotLK `Faction.dbc`, the 9.2.0 item/appearance and mount CSV files, and the viewer's `meta/charactercustomization2` directory. Filenames are listed in `ImportGameData.php`.

## Deployment and checks

Keep the existing database connections and authentication configuration. `DB_WORLD_DATABASE` selects the world database; `WOW_TOOLTIP_URL` selects the AoWoW tooltip host. Copy model assets before starting the new image.

```sh
docker compose build app
docker compose up -d --no-deps app
docker compose exec app php artisan optimize
```

Verify login, a character with equipment, model rotation/zoom, talent groups, mount switching, achievement filters and a narrow mobile viewport before stopping the old armory service.

Run the feature and formula tests with:

```sh
docker run --rm -v "$PWD:/app" -w /app dead-azerothcore-web-php php artisan test tests/Feature/CharacterTest.php tests/Feature/AuctionTest.php tests/Unit/GearScoreTest.php tests/Unit/ReputationTest.php
```

These tests use isolated SQLite databases and do not change game data. Build the PHP development image and install development dependencies as described in the README.
