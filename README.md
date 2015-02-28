# webfont-generator

Web interface written in PHP to wrap many font converting tools.
Install them before using webfont-generator.

* [fontforge](http://fontforge.github.io/), for converting to ttf and svg. You only need to setup command line scripting tool.
* [ttf2eot](http://code.google.com/p/ttf2eot/)
* [snft2woff](http://people.mozilla.com/~jkew/woff/)
* [woff2_compress](https://github.com/google/woff2)

![Screenshot](/screenshot.png)

```
# Clone current project to your webserver root
git clone https://github.com/ambroisemaupate/webfont-generator.git
# Create your own config file
cp config.default.yml config.yml
# adapt your binaries paths to suit your system (unices, Mac OSX)
# …
# Install dependencies using Composer
composer install
```

Then you should be able to see a basic file form on your browser.

Webfont Generator is released by Ambroise Maupate under MIT license.
