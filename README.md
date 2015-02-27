# webfont-generator

Web interface written in PHP to wrap zoltan-dulac/css3FontConverter script.

![Screenshot](/screenshot.png)

First install https://github.com/zoltan-dulac/css3FontConverter scripts to your webserver.
Choose a location that will be readable and executable by your PHP server.


```
# Clone current project to your webserver root
git clone https://github.com/ambroisemaupate/webfont-generator.git
# Create your own config file
cp config.default.yml config.yml
# adapt convertPath to suit your css3FontConverter bash script path
# …
# Install dependencies using Composer
composer install
```

Then you should be able to see a basic file form on your browser.
