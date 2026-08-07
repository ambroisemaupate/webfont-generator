

# webfont-generator
**Crear subconjuntos y convertir uno o varios archivos OTF/TTF a EOT, SVG, WOFF y WOFF2.**

Desarrollado gracias a:

* [fontforge](http://fontforge.github.io/), para convertir a ttf y svg. Solo necesitas configurar la herramienta de scripting de línea de comandos.
* [ttf2eot](https://github.com/wget/ttf2eot)
* [sfnt2woff](https://github.com/kseo/sfnt2woff)
* [woff2_compress](https://github.com/google/woff2)
* [fonttools (pyftsubset)](https://github.com/fonttools/fonttools#other-tools)

![Captura de pantalla de Webfont generator](/screenshot@2x.png)

## Uso con Docker

```bash
docker run -ti --name "webfontgen" -p 8080:80 ambroisemaupate/webfontgenerator
```

Luego abre tu navegador en `http://localhost:8080`, sube tu archivo de fuente OTF/TTF y… ¡disfrútalo!

## Desarrollo

Clona este repositorio y luego:

```bash
cp config.docker.yml config.yml
composer install
docker-compose up
```

Luego abre tu navegador en `http://localhost:8080`
