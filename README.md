# webfont-generator
**Subset and convert one or many OTF/TTF/WOFF/WOFF2 files in EOT, SVG, WOFF and WOFF2.**

Built thanks to:

* [fontforge](http://fontforge.github.io/), for converting to ttf and svg. You only need to setup command line scripting tool.
* [ttf2eot](https://github.com/wget/ttf2eot)
* [sfnt2woff](https://github.com/kseo/sfnt2woff)
* [woff2_compress and woff2_decompress](https://github.com/google/woff2)
* [woff2sfnt](https://github.com/kseo/sfnt2woff), for decoding WOFF files
* [fonttools (pyftsubset)](https://github.com/fonttools/fonttools#other-tools)

![Webfont generator screenshot](/screenshot@2x.png)

## Usage with Docker

```bash
docker run -ti --name "webfontgen" -p 8080:80 ambroisemaupate/webfontgenerator
```

The Docker image is built from this repository `Dockerfile` (runtime configuration lives in `docker/`):

```bash
docker build -t ambroisemaupate/webfontgenerator .
```

Then open your browser on `http://localhost:8080`, upload your OTF/TTF/WOFF/WOFF2 font file and… enjoy!

## Development

Requires Docker only (Linux or macOS): the image ships PHP 8.5, Composer and every font tool. Clone this repository, then:

```bash
cp compose.override.yml.dist compose.override.yml   # exposes port 8080
docker compose up --build                           # installs vendor/ on first launch
```

Then open your browser on `http://localhost:8080`
