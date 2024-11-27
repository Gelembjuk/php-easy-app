<?php 

namespace Gelembjuk\WebApp\Resources;
/**
 * This class allows to create HTML page based on the template.
 * The class is used by HTMLPresenter to generate HTML output
 * The class must to localize a page template, extract subject/title info for it, insert data into the template.
 * 
 * Pages are html files in format:
 * 
 * META:title:: Page title
 * META:keywords:: keywords
 * 
 * ...contents...
 * 
 * There can be 0 or many META lines. 
 * The folder with page resources is provided as a setting in the context.
 * The folder structure is:
 * 
 * /
 * /locale1_optional
 * ../page1.htm
 * ../page2.htm
 * /locale2_optional
 * ../page1.htm
 * ../page2.htm
 * page1.htm
 * page2.htm
 * 
 * The class will search for a page in the locale folder first. If not found it will search in the root folder.
 */

 use \Gelembjuk\WebApp\Context as Context;
 use \Gelembjuk\WebApp\Response\DataResponse as DataResponse;
 use \Gelembjuk\WebApp\Present\HTMLPresenter as HTMLPresenter;

class HTMLPage {
    use \Gelembjuk\WebApp\ContextTrait;

    protected string $pagesFolder;
    /**
     * When there are additional folders then we will check first in pagesFolder and then in additional
     */
    protected array $additionalPagesFolders = [];
    protected string $templateExtension;

    protected string $outTemplate = '';
    /**
     * This is custom requested locale
     */
    protected string $locale;
    /**
     * This is default locale. If a file is not found on requested we will look there
     */
    protected string $defaultLocale;

    public function __construct(Context $context, string $pagesFolder, string $locale = '', string $templateExtension = 'htm') 
    {
        $this->withContext($context);
        $this->pagesFolder = $pagesFolder;
        $this->templateExtension = $templateExtension;
        $this->locale = $locale;
        $this->afterConstruct();
    }
    public function withLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }
    public function withDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
        return $this;
    }
    public function withOutTemplate($outTemplate)
    {
        $this->outTemplate = $outTemplate;
        return $this;
    }
    public function withTemplateExtension($templateExtension) 
    {
        $this->templateExtension = $templateExtension;
        return $this;
    }
    public function withAdditionalPagesFolders(array $list)
    {
        $this->additionalPagesFolders = $list;
        return $this;
    }
    public function buildPage(string $template, DataResponse $data, string $locale = ''): array
    {
        $templateFileInfo = $this->getTemplateFile($template, $locale);

        $data->withTemplate($templateFileInfo[1]);

        if (!empty($this->outTemplate)) {
            $data->withBaseTemplate($this->outTemplate);
        }

        $presenter = new HTMLPresenter($this->context, $data);
        $presenter->
            withTemplatesPath($templateFileInfo[0])->
            withTemplatesExtension($this->templateExtension);

        $page = $presenter->buildOutput()->getContent();

        return $this->parseMetaData($page);
    }
    public function buildPageByValues(string $template, array $data = [], string $locale = ''): array
    {
        return $this->buildPage($template, new DataResponse($data), $locale);
    }
    private function parseMetaData($page):array
    {
        $metadata = [];
        do {
            $oneLine = explode("\n", $page, 2);
            $line = $oneLine[0];

            if (strpos($line, 'META:') !== 0) {
                break;
            }
            $page = $oneLine[1];

            $line = str_replace('META:', '', $line);
            $parts = explode('::', $line, 2);
            $metadata[$parts[0]] = trim($parts[1]);
            
        } while(true);
        return [$metadata, $page];
    }
    /**
     * This throws exception if file is not found
     */
    protected function getTemplateFile($template, $locale): array 
    {
        if (empty($locale)) {
            $locale = $this->locale;
        }
        if (!empty($locale)) {
            $templateInfo = $this->getTemplateIfFileExists($template, $locale);

            if ($templateInfo) {
                return $templateInfo;
            }
        }
        if (!empty($this->defaultLocale)) {
            $templateInfo = $this->getTemplateIfFileExists($template, $this->defaultLocale);

            if ($templateInfo) {
                return $templateInfo;
            }
        }
        // check in root of pages folder
        $templateInfo = $this->getTemplateIfFileExists($template);

        if ($templateInfo) {
            return $templateInfo;
        }
        throw new \Gelembjuk\WebApp\Exceptions\NotFoundException("Requested template file is not found");
    }

    protected function getTemplateIfFileExists(string $template, string $locale = '') : ?array
    {
        $paths = [];
        
        if (!empty($this->pagesFolder)) {
            $paths[] = $this->pagesFolder;
        }

        $paths = array_merge($paths, $this->additionalPagesFolders);
        
        foreach ($paths as $tmplpath) {
            $path = $tmplpath.'/';
            if (!empty($locale)) {
                $path .= $locale.'/';
            }
            $path .= $template.'.'.$this->templateExtension;
            
            if (file_exists($path)) {
                if (!empty($locale)) {
                    return [$tmplpath, $locale.'/'.$template];
                }
                return [$tmplpath, $template];
            }
        }
        return null;
    }
}