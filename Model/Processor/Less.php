<?php
/**
 * Copyright © 2016 Studio Raz. All rights reserved.
 * For more information contact us at dev@studioraz.co.il
 * See COPYING_STUIDORAZ.txt for license details.
 */
namespace SR\Rtlcss\Model\Processor;

use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\Css\PreProcessor\File\Temporary;
use Magento\Framework\View\Asset\ContentProcessorException;
use Magento\Framework\View\Asset\ContentProcessorInterface;

use MoodleHQ\RTLCSS;
use Sabberworm\CSS\Parser;

/**
 * Class Less
 */
class Less implements ContentProcessorInterface {

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Source
     */
    private $assetSource;

    /**
     * @var Temporary
     */
    private $temporaryFile;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param State $appState
     * @param Source $assetSource
     * @param Temporary $temporaryFile
     */
    public function __construct(
        LoggerInterface $logger,
        State $appState,
        Source $assetSource,
        Temporary $temporaryFile
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
        $this->assetSource = $assetSource;
        $this->temporaryFile = $temporaryFile;
    }

    /**
     * @inheritdoc
     * @throws ContentProcessorException
     */
    public function processContent(File $asset)
    {
        $path = $asset->getPath();
        try {
            $parser = new \Less_Parser(
                [
                    'relativeUrls' => false,
                    'compress' => false
                ]
            );

            $content = $this->assetSource->getContent($asset);

            if (trim($content) === '') {
                return '';
            }

            $tmpFilePath = $this->temporaryFile->createFile($path, $content);

            gc_disable();
            $parser->parseFile($tmpFilePath, '');
            $content = $parser->getCss();
            gc_enable();

            if (trim($content) === '') {
                $errorMessage = PHP_EOL . self::ERROR_MESSAGE_PREFIX . PHP_EOL . $path;
                $this->logger->critical($errorMessage);

                throw new ContentProcessorException(new Phrase($errorMessage));
            }

            return $this->processRTL($asset, $content);

        } catch (\Exception $e) {
            $errorMessage = PHP_EOL . self::ERROR_MESSAGE_PREFIX . PHP_EOL . $path . PHP_EOL . $e->getMessage();
            $this->logger->critical($errorMessage);

            throw new ContentProcessorException(new Phrase($errorMessage));
        }
    }

    /**
     * @inheritdoc
     * @throws ContentProcessorException
     */
    public function processRTL(File $asset, $content)
    {

        if ($asset->getContext()->getAreaCode() == "frontend"
            && $asset->getContext()->getLocale() == "he_IL"
            && in_array($asset->getFilePath(), array('mage/gallery/gallery.less'))
        ) {
            
            $cssTreeParser = new Parser($content);
            $tree = $cssTreeParser->parse();
            $rtlcss = new RTLCSS\RTLCSS($tree);
            $rtlcss->flip();

            $format = $this->appState->getMode() !== State::MODE_DEVELOPER ?
                \Sabberworm\CSS\OutputFormat::createCompact() : \Sabberworm\CSS\OutputFormat::createPretty();

            $content = $tree->render($format);
        }

        return $content;

    }
}
