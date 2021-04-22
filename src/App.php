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

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Yaml\Parser;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use WebfontGenerator\Converters\ConverterInterface;
use WebfontGenerator\Form\FontType;
use WebfontGenerator\Subsetters\PythonFontSubset;

/**
 * Class App
 *
 * @package WebfontGenerator
 */
class App
{
    protected $twig = null;
    protected $config = null;
    protected $configPath = "config.yml";
    protected $file = null;
    protected $assignation = [];

    /**
     * @return \Symfony\Component\Form\FormFactoryInterface
     */
    protected function createFormFactory()
    {
        $vendorDirectory = realpath(__DIR__.'/../vendor');
        $vendorFormDirectory = $vendorDirectory.'/symfony/form';
        $vendorValidatorDirectory = $vendorDirectory.'/symfony/validator';
        $translator = new Translator('en');
        $translator->addLoader('xlf', new XliffFileLoader());
        $this->getTwig()->addExtension(new TranslationExtension($translator));
        $validator = Validation::createValidatorBuilder()
            ->setTranslationDomain(null)
            ->setTranslator($translator)
            ->getValidator();
        // there are built-in translations for the core error messages
        $translator->addResource(
            'xlf',
            $vendorFormDirectory.'/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );
        $translator->addResource(
            'xlf',
            $vendorValidatorDirectory.'/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );

        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Exception
     */
    public function handle(Request $request)
    {
        if (!empty($this->getConfig()['converters'])) 
		{
            $formFactory = $this->createFormFactory();
            $form = $formFactory->createNamed('fonts', FontType::class, [
                'subset_latin' => true,
                'subset_ranges' => PythonFontSubset::getBaseSet()
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) 
			{
                try {
                    $fs = new Filesystem();
                    $font = new WebFont(
                        $fs,
                        $this->getFontConverters(),
                        $this->getFontSubsetter(),
                        $form->get('subset_ranges')->getData()
                    );
                    /** @var UploadedFile $file */
                    foreach ($form->get('files')->getData() as $file) 
					{
                        $font->addFontFile($file);
                    }
                    if ($form->get('subset_latin')->getData() === true) 
					{
                        $font->subsetAndConvert();
                    } 
					else 
					{
                        $font->convert();
                    }

                    $zipFile = $font->getZipFile();
                    $response = new BinaryFileResponse($zipFile);
                    $response->setContentDisposition(
                        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                        $zipFile->getBasename()
                    );
                    $response->prepare($request);
                    return $response;
                } 
				catch (\RuntimeException $exception) 
				{
					$form->addError(new FormError($exception->getMessage()));
                }
            }
            $this->assignation['form'] = $form->createView();

            $response = new Response($this->getTwig()->render('base.html.twig', $this->assignation));
            $response->setCharset('UTF-8');
            $response->prepare($request);

			return $response;
        } 
		else 
		{
            throw new \Exception("You must define converters path in your “config.yml” file.", 1);
        }
	}

    /**
     * @return null|Environment
     */
	public function getTwig()
	{
        if (null === $this->twig) 
		{
            $defaultFormTheme = 'form_div_layout.html.twig';
            $appVariableReflection = new \ReflectionClass('\Symfony\Bridge\Twig\AppVariable');
            $vendorTwigBridgeDirectory = dirname($appVariableReflection->getFileName());

            $this->twig = new Environment(
                new FilesystemLoader([
                    ROOT. '/views',
                    $vendorTwigBridgeDirectory.'/Resources/views/Form',
                ]),
                [
                    'debug' => isset($_ENV['DEBUG']) && $_ENV['DEBUG'] == true ? true : false,
                    'cache' => ROOT . "/cache",
                ]
            );

            $formEngine = new TwigRendererEngine([$defaultFormTheme], $this->twig);
            $this->twig->addRuntimeLoader(new FactoryRuntimeLoader([ FormRenderer::class => function () use ($formEngine) { return new FormRenderer($formEngine); }, ]));
            $this->twig->addExtension(new FormExtension());
        }

        return $this->twig;
    }

    /**
     * @return mixed|null
     */
    public function getConfig()
    {
        if (null === $this->config && file_exists(ROOT . "/" . $this->configPath)) 
		{
            $yaml = new Parser();
            $this->config = $yaml->parse(file_get_contents(ROOT . "/" . $this->configPath));
        }

        return $this->config;
    }

    /**
     * @param $class
     * @param $path
     *
     * @return ConverterInterface
     */
    protected function getFontConverter($class, $path)
    {
        $converterClass = $class;
        $c = new $converterClass($path);
        if ($c instanceof ConverterInterface) 
		{
            return $c;
        } 
		else 
		{
            throw new \RuntimeException($class . "must implement ConverterInterface.");
        }
    }

    /**
     * @return ConverterInterface[]
     */
    protected function getFontConverters()
    {
        $converters = [];
        foreach ($this->config['converters'] as $converter) 
		{
            if (!empty($converter['path']) && !empty($converter['class'])) 
			{
                $converters[] = $this->getFontConverter($converter['class'], $converter['path']);
            }
        }

        return $converters;
    }

    /**
     * @return null|PythonFontSubset
     */
    private function getFontSubsetter()
    {
        if (!empty($this->config['pyftsubset'])) 
		{
            return new PythonFontSubset($this->config['pyftsubset']);
        }
        return null;
    }
}
