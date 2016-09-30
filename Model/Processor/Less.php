<?php
/**
 * Copyright © 2016 Studio Raz. All rights reserved.
 * For more information contact us at dev@studioraz.co.il
 * See COPYING_STUIDORAZ.txt for license details.
 */
namespace SR\Rtlcss\Model\Processor;
use Magento\Framework\View\Asset\File;
use MoodleHQ\RTLCSS;
use Sabberworm\CSS\Parser;

/**
 * Class Less
 */
class Less extends \Magento\Framework\Css\PreProcessor\Adapter\Less\Processor {

    /**
     * @inheritdoc
     * @throws ContentProcessorException
     */
    public function processContent(File $asset)
    {
        $content = parent::processContent($asset);
        
         if ($asset->getContext()->getAreaCode() == "frontend" && $asset->getContext()->getLocale() == "he_IL") {

                $altParser = new Parser($content);
                $tree = $altParser->parse();
                $rtlcss = new RTLCSS\RTLCSS($tree);
                $rtlcss->flip();
                $content = $tree->render();
            }
        
        return $content;

    }
}
