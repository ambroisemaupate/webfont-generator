# webfont-generator

Web interface written in PHP to wrap many font converting tools.
Install them before using webfont-generator.

* [fontforge](http://fontforge.github.io/), for converting to ttf and svg. You only need to setup command line scripting tool.
* [ttf2eot](http://code.google.com/p/ttf2eot/)
* [sfnt2woff](http://people.mozilla.com/~jkew/woff/)
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

## Mac OS X

To install FontForge, you wont need to setup XQuartz as we only need the command line tool.
Copy FontForge.app to your Application folder, so fontforge unix binary
should be located here `/Applications/FontForge.app/Contents/Resources/opt/local/bin/fontforge`.

For other converters, just download sources and compile them. For example with ttf2eot:

```bash
# Untar downloaded sources and open folder in your Terminal app
cd /Users/ambroisemaupate/Downloads/ttf2eot-0.0.2-2
# Compile sources
make
# Install binary globally
sudo cp ttf2eot /usr/bin/ttf2eot
# It should ask your computer account password
# Then verify that converter binary is available
whereis ttf2eot
# this should print "/usr/bin/ttf2eot"
```

Follow the same process with other converters.
