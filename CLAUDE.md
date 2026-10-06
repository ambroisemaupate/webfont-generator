# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What it is

Single-page PHP web app: upload one or more OTF/TTF/WOFF/WOFF2 files, optionally subset them with `pyftsubset`, convert each to TTF, SVG, WOFF, WOFF2 and EOT, and download everything as a ZIP. No framework — plain Symfony 7.4 components (Form, HttpFoundation, Mime, Yaml…) wired by hand, rendered with Twig 3. PHP 8.5. Everything runs in Docker (Linux and macOS); there is no bare-metal setup.

## Commands

```bash
cp compose.override.yml.dist compose.override.yml   # port 8080
docker compose up --build                           # http://localhost:8080; docker/before_launch.sh runs composer install if vendor/ is missing
docker compose exec app composer …                  # composer.lock is committed
docker compose run --rm app vendor/bin/phpcs src    # only dev tool (no ruleset committed); no test suite
```

The conversion binaries live in the Docker image (`php:8.5-fpm-trixie`): fontforge, sfnt2woff/woff2sfnt (`woff-tools`), woff2_compress/woff2_decompress (`woff2`) and pyftsubset (`fonttools`) are Debian packages; only ttf2eot is built from source. `config.yml` (committed) holds their paths inside the image.

## Architecture

- `index.php` defines `ROOT` (used everywhere for paths) and hands the request to `App::handle()`.
- `src/App.php` reads `config.yml`, builds the Twig env + form factory, and on valid submit drives `WebFont`. Any `\RuntimeException` thrown during conversion is turned into a form error shown in the page.
- `src/WebFont.php` owns one job: uploads go to `build/<uniqid>/` under a slug made unique per job (`font`, `font-2`…) since every generated file is named after it; each original (or its subset) is passed through every converter, then everything is zipped into `dist/` and the build dir is removed. `dist/` and `cache/` (Twig) are never cleaned by the app.
- **Converters are config-driven**: `config.yml` → `converters.<name>.{path,class}`; `App` instantiates each `class` with its binary `path` and requires it to implement `Converters\ConverterInterface::convert(File): File`. Adding a format = new class + config entry. Converters shell out with `exec()` and write output next to the input file.
- WOFF/WOFF2 uploads are first decoded to TTF/OTF by `src/Decoders/WebFontDecoder.php` (binaries in `config.yml` → `decoders`) into a `source/` sub-folder, so converter outputs don't overwrite the upload. Upload validation (`FontType::validateFontFile`) checks extension + file signature, not MIME type.
- TTF/SVG conversion runs fontforge scripts in `assets/scripts/*.pe`.
- `src/Subsetters/PythonFontSubset.php` holds the named Unicode ranges (`$ranges`) offered in the form and the default set (`getBaseSet()`); the form (`src/Form/FontType.php`) passes selected ranges straight to `--unicodes=`.
- Front-end: UIkit (vendored minified in `assets/`), templates in `views/`.
