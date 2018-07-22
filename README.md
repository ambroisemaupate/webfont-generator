# webfont-generator

Web interface written in PHP to wrap many font converting tools.

* [fontforge](http://fontforge.github.io/), for converting to ttf and svg. You only need to setup command line scripting tool.
* [ttf2eot](http://code.google.com/p/ttf2eot/)
* [sfnt2woff](http://people.mozilla.com/~jkew/woff/)
* [woff2_compress](https://github.com/google/woff2)

![Webfont generator screenshot](/screenshot.jpg)

## Usage with Docker

```bash
docker run -ti --name "webfontgen" -p 8080:80 ambroisemaupate/webfontgenerator
```

Then open your browser on `http://localhost:8080`, upload your OTF/TTF font file and… enjoy!

## Development

Clone this repository, then:

```bash
cp config.docker.yml config.yml
composer install
docker-compose up
```

Then open your browser on `http://localhost:8080`