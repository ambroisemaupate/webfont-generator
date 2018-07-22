<?php
/**
 * Copyright © 2015, Ambroise Maupate
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @file App.php
 * @author Ambroise Maupate
 */
namespace WebfontGenerator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;

use WebfontGenerator\Converters\ConverterInterface;

/**
 *
 */
class App
{
    protected $twig = null;
    protected $config = null;
    protected $configPath = "config.yml";
    protected $request = null;
    protected $response = null;
    protected $file = null;
    protected $assignation = [];

    public function boot()
    {
        if (!empty($this->getConfig()['converters'])) {

            $this->request = Request::createFromGlobals();

            try {
                $fs = new Filesystem();
                $zipFile = $this->handle($this->request, $fs);
                if (null !== $zipFile) {
                    $response = new Response();

                    // Set headers
                    $response->headers->set('Cache-Control', 'private');
                    $response->headers->set('Content-type', $zipFile->getMimeType());
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $zipFile->getBasename() . '";');

                    // Send headers before outputting anything
                    $response->sendHeaders();
                    $response->setContent(file_get_contents($zipFile->getRealPath()));
                    $response->send();

                    /*
                     * Delete zip from server
                     */
                    $fs->remove($zipFile->getRealPath());

                    return 0;
                }

            } catch (\Exception $e) {
                $this->assignation['error'] = $e->getMessage();
            }

            $this->response = new Response();
            $this->response->setContent($this->getTwig()->render('base.html.twig', $this->assignation));
            $this->response->setCharset('UTF-8');
            $this->response->prepare($this->request);
            $this->response->send();

            return 0;

        } else {
            throw new \Exception("You must define converters path in your “config.yml” file.", 1);
        }
    }

    public function getTwig()
    {
        if (null === $this->twig) {

            $this->twig = new \Twig_Environment(
                new \Twig_Loader_Filesystem([
                    ROOT. '/views',
                ]),
                [
                    'debug' => true,
                    'cache' => ROOT . "/cache",
                ]
            );
        }

        return $this->twig;
    }

    public function getConfig()
    {
        if (null === $this->config &&
            file_exists(ROOT . "/" . $this->configPath)) {
            $yaml = new Parser();
            $this->config = $yaml->parse(file_get_contents(ROOT . "/" . $this->configPath));
        }

        return $this->config;
    }

    public function handle(Request $request, Filesystem $fs)
    {
        if ($request->getMethod() === Request::METHOD_POST &&
            $request->files->has('file')) {
            $this->file = $request->files->get('file');

            if (null !== $this->file) {
                if ($this->file->getMimeType() == 'application/x-font-ttf' ||
                    $this->file->getMimeType() == 'application/vnd.ms-opentype') {
                    $font = new WebFont($this->file, $fs);

                    foreach ($this->config['converters'] as $key => $converter) {
                        if (!empty($converter['path']) &&
                            !empty($converter['class'])) {
                            /*
                             * Initialize file converter
                             */
                            $converterClass = $converter['class'];
                            $c = new $converterClass($converter['path']);

                            if ($c instanceof ConverterInterface) {
                                $font->addFile($c->convert($font->getOriginal()));
                            } else {
                                throw new \RuntimeException($converter['class'] . "must implement ConverterInterface.", 1);
                            }
                        }
                    }

                    return $font->getZipFile();
                } else {
                    throw new \Exception("You must only upload true-type (.ttf) or opentype (.otf) font files (your file is " . $this->file->getMimeType() . ").", 1);
                }
            } else {
                throw new \Exception("You must choose an OTF/TTF font file.", 1);
            }
        }

        return null;
    }
}
