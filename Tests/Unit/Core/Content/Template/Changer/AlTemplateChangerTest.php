<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Tests\Unit\Core\Content\Template\Changer;

use AlphaLemon\AlphaLemonCmsBundle\Tests\TestCase;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Template\Changer\AlTemplateChanger;
use AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General;

/**
 * AlTemplateChangerTest
 *
 * @author AlphaLemon <webmaster@alphalemon.com>
 */
class AlTemplateChangerTest extends TestCase
{
    private $templateChanger;
    private $currentTemplateManager;
    private $newTemplateManager;
    private $currentTemplateSlots;
    private $newTemplateSlots;
    private $blockRepository;
    private $pageContentsContainer;
    private $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->currentTemplateManager = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Template\AlTemplateManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->newTemplateManager = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Template\AlTemplateManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->currentTemplateSlots = $this->getMockBuilder('AlphaLemon\Theme\BusinessWebsiteThemeBundle\Core\Slots\BusinessWebsiteThemeBundleHomeSlots')
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->blockRepository = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Propel\AlBlockRepositoryPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->currentTemplateManager->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnValue($this->dispatcher));

        $this->newTemplateSlots = $this->getMockBuilder('AlphaLemon\Theme\BusinessWebsiteThemeBundle\Core\Slots\BusinessWebsiteThemeBundleHomeSlots')
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->pageContentsContainer = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageBlocks\AlPageBlocks')
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->factory = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Block\AlBlockManagerFactoryInterface');
        $this->slotsConverterFactory = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\Factory\AlSlotsConverterFactoryInterface');

        $this->templateChanger = new AlTemplateChanger($this->factory, $this->slotsConverterFactory);
    }

    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testRefreshThrownAnExceptionWhenAnyTemplateManagerHasNotBeenSet()
    {
        $this->templateChanger->change();
    }

    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testRefreshThrownAnExceptionWhenPageHaveNotBeenSet()
    {
        $this->templateChanger
                ->setCurrentTemplateManager($this->currentTemplateManager)
                ->change();
    }

    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testRefreshThrownAnExceptionWhenLanguageHaveNotBeenSet()
    {
        $this->templateChanger
                ->setNewTemplateManager($this->currentTemplateManager)
                ->change();
    }

    public function testTemplateChangeFailsWhenAddOperationFails()
    {
        $currentSlots = array("site" => array("logo"));
        $newSlots = array("site" => array("logo", "nav_menu"));

        $this->init($currentSlots, $newSlots);

        $block = $this->setUpBlock();
        $blockManager = $this->setUpBlockManager($block);

        $blockManager->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->returnValue($blockManager));

        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');

        $this->blockRepository->expects($this->exactly(2))
            ->method('rollBack');

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertFalse($result);
    }

    public function testAddNewSlot()
    {
        $currentSlots = array("site" => array("logo"));
        $newSlots = array("site" => array("logo", "nav_menu"));

        $this->init($currentSlots, $newSlots);

        $block = $this->setUpBlock();
        $blockManager = $this->setUpBlockManager($block);

        $blockManager->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->returnValue($blockManager));

        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');

        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');

        $this->blockRepository->expects($this->never())
            ->method('rollback');

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertTrue($result);
    }

    public function testTemplateChangeFailsWhenEditOperationFails()
    {
        $currentSlots = array("site" => array("logo"));
        $newSlots = array("page" => array("logo"));

        $this->init($currentSlots, $newSlots);

        $converter = $this->getMock('\AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\AlSlotConverterInterface');
        $converter->expects($this->any())
            ->method('convert')
            ->will($this->returnValue(false));

        $this->slotsConverterFactory->expects($this->any())
            ->method('createConverter')
            ->will($this->returnValue($converter));

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('rollBack');

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertFalse($result);
    }

    public function testChangeSlot()
    {
        $currentSlots = array("site" => array("logo"));
        $newSlots = array("page" => array("logo"));

        $this->init($currentSlots, $newSlots);

        $converter = $this->getMock('\AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\AlSlotConverterInterface');
        $converter->expects($this->any())
            ->method('convert')
            ->will($this->returnValue(true));

        $this->slotsConverterFactory->expects($this->any())
            ->method('createConverter')
            ->will($this->returnValue($converter));

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('commit');

        $this->blockRepository->expects($this->never())
            ->method('rollback');

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertTrue($result);
    }

    public function testTemplateChangeFailsWhenRemoveOperationFails()
    {
        $currentSlots = array("site" => array("logo", "nav_menu"));
        $newSlots = array("site" => array("logo"));

        $this->init($currentSlots, $newSlots);

        $block = $this->setUpBlock();
        $blockManager = $this->setUpBlockManager($block);

        $blockManager->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->returnValue($blockManager));

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('rollBack');

        $this->pageContentsContainer->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block)));

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertFalse($result);
    }

    public function testRemoveSlot()
    {
        $currentSlots = array("site" => array("logo", "nav_menu"));
        $newSlots = array("site" => array("logo"));

        $this->init($currentSlots, $newSlots);

        $block = $this->setUpBlock();
        $blockManager = $this->setUpBlockManager($block);

        $blockManager->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->returnValue($blockManager));

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('commit');

        $this->blockRepository->expects($this->never())
            ->method('rollback');

        $this->pageContentsContainer->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block)));

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertTrue($result);
    }

    public function testTemplateChangeFailsWhenOneperationFails()
    {
        $currentSlots = array("site" => array("logo", "copyright_box"),
                       "language" => array("nav_menu", 'nav_menu1'),
                       "page" => array("content", "left_box"),);

        $newSlots = array("site" => array("logo"),
                       "language" => array("nav_menu", "copyright_box"),
                       "page" => array("content", "left_box", "right_box"),);

        $this->init($currentSlots, $newSlots);

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('rollBack');

        $this->blockRepository->expects($this->any())
            ->method('save')
            ->will($this->onConsecutiveCalls(true));

        $this->blockRepository->expects($this->any())
            ->method('delete')
            ->will($this->onConsecutiveCalls(false));

        $this->pageContentsContainer->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));

        $block = $this->setUpBlock();
        $blockManager1 = $this->setUpBlockManager($block);

        $blockManager1->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $block = $this->setUpBlock();
        $blockManager2 = $this->setUpBlockManager($block);

        $blockManager2->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->onConsecutiveCalls($blockManager1, $blockManager2));

        $converter = $this->getMock('\AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\AlSlotConverterInterface');
        $converter->expects($this->any())
            ->method('convert')
            ->will($this->returnValue(true));

        $this->slotsConverterFactory->expects($this->any())
            ->method('createConverter')
            ->will($this->returnValue($converter));

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertFalse($result);
    }

    public function testTemplateChange()
    {
        $currentSlots = array("site" => array("logo", "copyright_box"),
                       "language" => array("nav_menu", 'nav_menu1'),
                       "page" => array("content", "left_box"),);

        $newSlots = array("site" => array("logo"),
                       "language" => array("nav_menu", "copyright_box"),
                       "page" => array("content", "left_box", "right_box"),);

        $this->init($currentSlots, $newSlots);

        $this->blockRepository->expects($this->any())
            ->method('startTransaction');

        $this->blockRepository->expects($this->any())
            ->method('rollBack');

        $this->blockRepository->expects($this->any())
            ->method('save')
            ->will($this->onConsecutiveCalls(true));

        $this->blockRepository->expects($this->any())
            ->method('delete')
            ->will($this->onConsecutiveCalls(false));

        $this->pageContentsContainer->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));

        $block = $this->setUpBlock();
        $blockManager1 = $this->setUpBlockManager($block);

        $blockManager1->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $block = $this->setUpBlock();
        $blockManager2 = $this->setUpBlockManager($block);

        $blockManager2->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->factory->expects($this->any())
            ->method('createBlockManager')
            ->will($this->onConsecutiveCalls($blockManager1, $blockManager2));

        $converter = $this->getMock('\AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\AlSlotConverterInterface');
        $converter->expects($this->any())
            ->method('convert')
            ->will($this->returnValue(true));

        $this->slotsConverterFactory->expects($this->any())
            ->method('createConverter')
            ->will($this->returnValue($converter));

        $result = $this->templateChanger
                    ->setCurrentTemplateManager($this->currentTemplateManager)
                    ->setNewTemplateManager($this->newTemplateManager)
                    ->change();
        $this->assertTrue($result);
    }

    private function setUpBlockManager($block)
    {
        $blockManager = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Bundles\TextBundle\Core\Block\AlBlockManagerText')
                                ->disableOriginalConstructor()
                                ->getMock();

        $blockManager->expects($this->any())
            ->method('get')
            ->will($this->returnValue($block));

        return $blockManager;
    }

    private function setUpBlock()
    {
        $block = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlBlock');

        return $block;
    }

    private function init($currentSlots, $newSlots)
    {
        $this->pageContentsContainer->expects($this->any())
            ->method('getIdLanguage')
            ->will($this->returnValue(2));

        $this->pageContentsContainer->expects($this->any())
            ->method('getIdPage')
            ->will($this->returnValue(2));

        $this->currentTemplateManager->expects($this->once())
            ->method('getTemplateSlots')
            ->will($this->returnValue($this->currentTemplateSlots));

        $this->currentTemplateManager->expects($this->once())
            ->method('getBlockModel')
            ->will($this->returnValue($this->blockRepository));

        $this->currentTemplateManager->expects($this->any())
            ->method('getPageBlocks')
            ->will($this->returnValue($this->pageContentsContainer));

        $this->currentTemplateSlots->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($currentSlots));

        $this->blockRepository->expects($this->any())
            ->method('setModelObject')
            ->will($this->returnSelf());

        $this->newTemplateManager->expects($this->once())
            ->method('getTemplateSlots')
            ->will($this->returnValue($this->newTemplateSlots));

        $this->newTemplateSlots->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($newSlots));
    }
}