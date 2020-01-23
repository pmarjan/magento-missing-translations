<?php

namespace Comwrap\TranslatedPhrases\Model\Filesystem\Parser;

use Comwrap\TranslatedPhrases\Helper\Configuration;
use Magento\Setup\Module\I18n\Dictionary\Options\ResolverFactory;
use Magento\Setup\Module\I18n\Parser\Parser;
use Magento\Setup\Module\I18n\Parser\Adapter\Php\Tokenizer\PhraseCollector;
use Magento\Setup\Module\I18n\Parser\Adapter\Php\Tokenizer;
use Magento\Setup\Module\I18n\Parser\Adapter\Php;
use Magento\Setup\Module\I18n\Parser\Adapter\Html;
use Magento\Setup\Module\I18n\Parser\Adapter\Js;
use Magento\Setup\Module\I18n\Parser\Adapter\Xml;

class Native implements ParserInterface
{
    /** @var ResolverFactory */
    private $resolverFactory;

    /** @var ParserInterface */
    private $parser;

    /** @var array */
    private $phrases = [];
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Native constructor.
     * @param ResolverFactory $resolverFactory
     * @param Parser $parser
     * @param Configuration $configuration
     */
    public function __construct(
        ResolverFactory $resolverFactory,
        Parser $parser,
        Configuration $configuration
    ) {
        $this->resolverFactory = $resolverFactory;
        $this->parser = $parser;
        $this->configuration = $configuration;

        foreach ($this->prepareAdapters() as $type => $adapter) {
            $this->parser->addAdapter($type, $adapter);
        }
    }

    /**
     * @param $directory
     * @param bool $withContext
     * @return string[]
     */
    public function getPhrases($directory, $withContext = false)
    {
        /** @var  $optionResolver */
        $optionResolver = $this->resolverFactory->create($directory, $withContext);

        /** @var array $options */
        $options = $optionResolver->getOptions();

        /** Parse */
        $this->parser->parse(
            $this->configuration->skipBackendScanning() ? $this->updateOptionsFileMask($options) : $options
        );

        /** Get Phrases */
        return $this->phrasesToString($this->parser->getPhrases());
    }

    /**
     * @param array $options
     * @return mixed
     */
    private function updateOptionsFileMask($options)
    {
        foreach ($options as &$option) {
            if (!isset($option['fileMask'])) {
                continue;
            }

            /** @var string $originalFileMask */
            $originalFileMask = trim($option['fileMask'], "/");
            $option['fileMask'] = "/(^((?!adminhtml).)*)(" . $originalFileMask . ")/i";
        }

        return $options;
    }

    /**
     * @return array
     */
    private function prepareAdapters()
    {
        /** @var  $phraseCollector */
        $phraseCollector = new PhraseCollector(new Tokenizer());

        return [
            'php' => new Php($phraseCollector),
            'html' => new Html(),
            'js' => new Js(),
            'xml' => new Xml(),
        ];
    }

    /**
     * @param $phrases
     * @return string[]
     */
    private function phrasesToString($phrases)
    {
        /** @var string[] $result */
        $result = [];

        foreach ($phrases as $phrase) {
            $result[] = $phrase->getPhrase();
        }

        return $result;
    }
}

