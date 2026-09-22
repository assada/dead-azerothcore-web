# Character pages

Character pages read AzerothCore's character and world databases. The browser viewer renders the character and equipment.

The page includes equipment tooltips, saved character stats, both talent groups and their glyphs, learned mounts, achievements, reputation, skills and arena teams. It uses AzerothCore's `talentGroupsCount`, `activeTalentGroup` and spell `specMask` columns.

## Data and caching

Character data is queried on each request. Stats come from `character_stats`, so they reflect the values saved by the game server. Static client metadata is stored in `resources/data/game` and cached by file modification time. The auction's item icons use the same data loader.

GearScore and average item level are in `app/Support/GearScore.php`. The formulas account for hunter weapon weights, two-handed weapons, heirlooms and enchantment penalties. Mount counts use known mount spell IDs. Reputation includes the race/class base value and follows the client faction hierarchy.

## Model assets

The standard pack is `data.tar.gz` in [release v1.0.0](https://github.com/assada/dead-azerothcore-web/releases/tag/v1.0.0). The [installation guide](../README.md#install) includes commands to download it and extract it into the application storage volume.

The archive has asset directories at its root. Extract it without `--strip-components`. The download is about 2 GB, with additional disk space required for the extracted files.

The resulting container paths are:

```
storage/app/modelviewer/9.2.0/
  meta/
  textures/
  mo3/
  bone/
```

Nginx serves these files at `/modelviewer/9.2.0/`. The storage volume preserves them across application rebuilds. Keep the archive outside this directory.

For an existing extracted pack, use the same layout and copy it into the running container:

```sh
docker compose cp /path/to/modelviewer/9.2.0/. app:/var/www/html/storage/app/modelviewer/9.2.0/
```

`public/vendor/modelviewer/viewer.min.js` provides the browser renderer. Its license is included beside it. The Laravel adapter supplies jQuery and the viewer's debug hook. The transparent canvas shares the page's courtyard background.

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

Game metadata lives in `resources/data/game`.

See [Custom server data](custom-server-data.md) for source files, CSV exports, the Docker import command, and custom item limitations. The guide mounts the output directory so that generated files survive container removal.

## Deployment and checks

Keep the existing database connections and authentication configuration. `DB_WORLD_DATABASE` selects the world database. `WOW_TOOLTIP_URL` selects the AoWoW tooltip host. Install model assets before opening a character page.

```sh
docker compose build app
docker compose up -d --no-deps app
docker compose exec app php artisan optimize
```

After deployment, check login, equipped characters, model rotation/zoom, talent groups, mount switching, achievement filters and mobile layout.

Run the feature and formula tests with:

```sh
docker run --rm -v "$PWD:/app" -w /app dead-azerothcore-web-php php artisan test tests/Feature/CharacterTest.php tests/Feature/AuctionTest.php tests/Unit/GearScoreTest.php tests/Unit/ReputationTest.php
```

These tests use isolated SQLite databases and do not change game data. Build the PHP development image and install development dependencies as described in the README.
