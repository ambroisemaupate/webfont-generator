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
 * @file index.php
 * @author Ambroise Maupate
 */
define('ROOT', dirname(__FILE__));
require("vendor/autoload.php");

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

$fontConverter = new \FontConverterGUI();
$fontConverter->boot();

/**
 *
 */
class FontConverterGUI
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
        if (!empty($this->getConfig()['converterPath']) &&
            file_exists($this->getConfig()['converterPath'])) {

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
            throw new \Exception("You must set css3FontConverter script path in your “config.yml” file.", 1);
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
                    'cache' => ROOT."/cache",
                ]
            );
        }

        return $this->twig;
    }

    public function getConfig()
    {
        if (null === $this->config &&
            file_exists(ROOT."/".$this->configPath)) {
            $yaml = new Parser();
            $this->config = $yaml->parse(file_get_contents(ROOT."/".$this->configPath));
        }

        return $this->config;
    }

    public function handle(Request $request, Filesystem $fs)
    {
        if ($request->getMethod() === Request::METHOD_POST &&
            $request->files->has('file')) {
            $this->file = $request->files->get('file');

            if ($this->file->getClientOriginalExtension() == "ttf") {
                $font = new Font($this->file, $fs);
                return $font->handleConvert($this->getConfig()['converterPath']);
            } else {
                throw new \Exception("You must only upload TTF font files (your file is ".$this->file->getClientOriginalExtension().").", 1);
            }
        }

        return null;
    }
}


class Font
{
    protected $output = [];
    protected $originalFile = null;
    protected $zipFile = null;
    protected $buildDir = null;
    protected $distDir = null;
    protected $fs = null;

    public function __construct(UploadedFile $tmpFile, Filesystem $fs)
    {
        $this->fs = $fs;
        $this->buildDir = ROOT."/build";
        $this->distDir = ROOT."/dist";

        if (!$this->fs->exists($this->buildDir)) {
            $this->fs->mkdir($this->buildDir);
        }
        if (!$this->fs->exists($this->distDir)) {
            $this->fs->mkdir($this->distDir);
        }

        $this->originalFile = $tmpFile->move($this->buildDir, $tmpFile->getClientOriginalName());
    }

    public function handleConvert($exec)
    {
        $stylesheetPath = str_replace(".".$this->originalFile->getExtension(), ".css", $this->originalFile->getRealPath());

        /*
         * Execute convertFont.sh
         */
        exec($exec." ".$this->originalFile->getRealPath()." --output=\"".$stylesheetPath."\"", $this->output, $return);

        if ($return === 0) {
            $zipPath = $this->distDir."/".str_replace(".", "-", $this->originalFile->getBasename()).".zip";

            $zip = new \ZipArchive();
            $zip->open($zipPath, \ZipArchive::CREATE);

            $finder = new Finder();
            $finder->files()->in($this->buildDir);

            foreach ($finder as $file) {
                $zip->addFile($file->getRealpath(), $file->getBasename());
            }
            if (!$zip->close()) {
                throw new \Exception("Impossible to create ZIP archive", 1);
            }

            $this->zipFile = new File($zipPath);

            $this->fs->remove($this->buildDir);

            return $this->zipFile;
        } else {
            throw new \Exception("Impossible to convert TTF font", 1);
        }
    }
}

