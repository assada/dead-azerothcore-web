# Custom server data

Use this guide to rebuild `resources/data/game` from your client data. Run shell commands from the repository root.

The website reads characters and item names, quality, and item levels from AzerothCore databases. JSON files supply client metadata and viewer mappings. SQL changes do not update these files.

The importer combines WotLK **3.3.5.12340** data with **9.2.0.41462** appearance and mount data for the bundled viewer. These builds serve different purposes. A WotLK display ID is not a substitute for a viewer appearance ID.

## Prepare the source directory

[`resources/data/source`](../resources/data/source) contains the import source: 17 CSV files, `Faction.dbc`, and 20 customization files.

```sh
mkdir -p data/client-data data/generated-game
cp -R resources/data/source/. data/client-data/
```

`data/` is excluded from Git and Docker builds. Keep your source files there between imports.

Replace the relevant CSV files with exports from your patched client. Keep the filenames and column names that the importer expects.

If your server changes faction definitions, replace the bundled `Faction.dbc` with your extracted version:

```sh
cp /path/to/extracted/DBFilesClient/Faction.dbc data/client-data/Faction.dbc
```

The bundled customization metadata matches the standard model pack. If you change the viewer customization options, replace `data/client-data/meta/charactercustomization2/` with the metadata from your own pack.

The complete input directory contains:

| Source | Required filenames in `data/client-data/` |
| --- | --- |
| WotLK spells | `Spell_3.3.5_12340.csv`, `SpellIcon_3.3.5_12340.csv` |
| WotLK items | `Item_3.3.5_12340.csv`, `ItemDisplayInfo_3.3.5_12340.csv`, `SpellItemEnchantment_3.3.5_12340.csv` |
| WotLK talents | `Talent_3.3.5_12340.csv`, `TalentTab_3.3.5_12340.csv`, `GlyphProperties_3.3.5_12340.csv` |
| WotLK achievements | `Achievement_3.3.5_12340.csv`, `AchievementCategory_3.3.5_12340.csv` |
| Viewer item mappings | `Item_9.2.0_41462.csv`, `ItemAppearance_9.2.0_41462.csv`, `ItemModifiedAppearance_9.2.0_41462.csv` |
| Viewer mount mappings | `Mount_9.2.0_41462.csv`, `MountXDisplay_9.2.0_41462.csv` |
| Website CSV projections | `Skills.csv`, `Areas.csv` |
| WotLK faction table | `Faction.dbc` |
| Viewer customization | `meta/charactercustomization2/{race}_{gender}.json` |

Customization needs both genders (`0`, `1`) for races `1, 2, 3, 4, 5, 6, 7, 8, 10, 11`. The importer reads the `Options` property from each file.

The import rebuilds all JSON files. It needs the complete source directory, even for a change to one table.

## Export CSV with wow.tools.local

[wow.tools.local](https://github.com/Marlamin/wow.tools.local) supports local DBC/DB2 files and CSV export. Use it to replace the bundled data with exports from your patched client.

1. Extract the final DBC files from your client, including your patches.
2. Set `dbcFolder` in wow.tools.local to your extracted data root.
3. Arrange the files under their build directory:

   ```text
   <dbcFolder>/
     3.3.5.12340/dbfilesclient/Item.dbc
     3.3.5.12340/dbfilesclient/ItemDisplayInfo.dbc
     3.3.5.12340/dbfilesclient/SkillLine.dbc
     3.3.5.12340/dbfilesclient/AreaTable.dbc
   ```

4. Export the required tables for build `3.3.5.12340` through the DBC browser or its CSV endpoint.

   For an instance at `http://localhost:5000`, this exports `Item.dbc`:

   ```sh
   curl --fail --get 'http://localhost:5000/dbc/export/' \
     --data-urlencode 'name=Item' \
     --data-urlencode 'build=3.3.5.12340' \
     --output data/client-data/Item_3.3.5_12340.csv
   ```

5. Save each export under the filename from the input table.

The viewer CSV files use build `9.2.0.41462`. Keep the bundled copies unless you also change the viewer mappings and assets.

Use UTF-8 CSV with a header row. Preserve array suffixes such as `SpellRank[0]` and localized columns such as `Name_lang[0]`. The importer reads locale index `0` for WotLK text.

The exporter uses WoWDBDefs column names. If a definition changes, align the export headers with [ImportGameData.php](../app/Console/Commands/ImportGameData.php). Renaming a file alone does not change its columns.

`Faction.dbc` stays binary. The importer expects the WotLK `WDBC` layout with 57 fields and 228 bytes per record.

## Skills.csv and Areas.csv

The repository includes `Skills.csv` and `Areas.csv` in [`resources/data/source`](../resources/data/source). Use them unchanged if your server keeps the standard skills and areas.

For custom skills or areas, export `SkillLine` or `AreaTable` from build `3.3.5.12340`. Select and rename the columns as follows:

| Output file | Output column | Client export column |
| --- | --- | --- |
| `Skills.csv` | `ID` | `ID` |
| `Skills.csv` | `CategoryId` | `CategoryID` |
| `Skills.csv` | `Name` | `DisplayName_lang[0]` |
| `Skills.csv` | `SpellIcon` | `SpellIconID` |
| `Areas.csv` | `ID` | `ID` |
| `Areas.csv` | `ZoneName` | `AreaName_lang[0]` |
| `Areas.csv` | `MapId` | `ContinentID` |
| `Areas.csv` | `AreaId` | `ParentAreaID` |

These are the columns that the importer consumes. The bundled `Skills.csv` also has `SkillCostId`, `AltVerb`, and `CanLink`. The importer ignores these three columns.

`ZoneName` in our CSV means the localized area label. Use `AreaName_lang[0]`, not the internal `ZoneName` field from the client export.

The client column names come from the WoWDBDefs definitions for [SkillLine](https://github.com/wowdev/WoWDBDefs/blob/master/definitions/SkillLine.dbd) and [AreaTable](https://github.com/wowdev/WoWDBDefs/blob/master/definitions/AreaTable.dbd).

## Generate and deploy JSON

Build the application image as described in the [installation guide](../README.md#install). Run the importer in its PHP 8.4 container:

```sh
docker compose run --rm --no-deps \
  -v "$PWD/data/client-data:/client-data:ro" \
  -v "$PWD/data/generated-game:/var/www/html/resources/data/game" \
  app php -d memory_limit=512M artisan wow:import-data /client-data
```

The output mount preserves the generated files on the host. The importer prints a row count for each output file. It does not write to game databases.

After a successful import, copy the output into the repository:

```sh
cp -R data/generated-game/. resources/data/game/
git diff --stat -- resources/data/game
```

The output contains 11 JSON tables and 20 customization files. Keep your source changes for future imports. A rebuild replaces manual JSON edits.

Rebuild and recreate the application to deploy the new JSON:

```sh
docker compose build app
docker compose up -d --no-deps --force-recreate app
```

The application caches JSON by file modification time. Normal rebuilds load the new version. If you preserve old timestamps, use `touch resources/data/game/*.json resources/data/game/customization/*.json` before the build.

If your installation uses `docker-compose.network.yml`, include both Compose files in these commands, as described in the README.

## Custom items and viewer mappings

An item can appear in the equipment list without appearing on the 3D model. The website uses separate sources for each part:

| Part | Source | Custom server requirement |
| --- | --- | --- |
| Name, quality, item level | World database `item_template` | Your server data |
| Icon name | `item_template.displayid` through `item-icons.json` | A matching WotLK `ItemDisplayInfo` export |
| Icon image and tooltip | `WOW_TOOLTIP_URL` | An AoWoW instance with your items and icons |
| 3D appearance | Item entry through `items.json` | A matching viewer appearance and its assets |
| Enchant and gem tooltip parameters | `enchants.json` | Matching `SpellItemEnchantment` and `Item` exports |

The importer builds item appearances through this chain:

```text
WotLK Item.ID
  -> 9.2 ItemModifiedAppearance.ItemID
  -> ItemModifiedAppearance.ItemAppearanceID
  -> ItemAppearance.ID
  -> ItemAppearance.ItemDisplayInfoID
```

For a new item that reuses an existing model, add its entry to the WotLK `Item` CSV. Add a matching row to `ItemModifiedAppearance_9.2.0_41462.csv` that points to the existing viewer appearance. Use a unique row `ID`. The importer selects the first appearance row for each item entry.

If the viewer needs a different inventory type, add or update the entry in `Item_9.2.0_41462.csv`. The importer prefers this value over the WotLK inventory type.

For a new model, also supply the compatible model, texture, and metadata files in the viewer asset directory. A WotLK patch or an added SQL row alone does not provide these viewer assets.

For changed appearances on existing items, update the mapping for that entry. The current renderer does not resolve appearances from the world database `displayid`.

The equipment loader also prefers JSON `class`, `subclass`, and `inventoryType` values over the database values when metadata exists. Keep your WotLK `Item` export consistent with your server.

Set `WOW_TOOLTIP_URL` to your AoWoW base URL and recreate the application container. The default external AoWoW instance does not contain your custom items or changed item statistics.

After deployment, open a character with your changed item. Compare its equipment label, icon, tooltip, and 3D appearance separately.
