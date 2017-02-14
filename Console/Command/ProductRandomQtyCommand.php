<?php
/**
 * @category   Lillik_ProductRandomQty
 * @package    Lillik_ProductRandomQty
 * @copyright  Copyright (c) 2017 Lilian Codreanu (https://twitter.com/clipro)
 * @author     Lilian Codreanu <lilian.codreanu@gmail.com>
 */

namespace Lillik\ProductRandomQty\Console\Command;


use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;

class ProductRandomQtyCommand extends Command
{

    CONST COMMAND_NAME = 'catalog:product:random_qty';
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    protected $stockRegistry;

    /**
     * RandomChangeQtyCommand constructor.
     *
     * @param State                                                $state
     * @param CollectionFactory                                    $collectionFactory
     * @param ObjectManagerInterface                               $manager
     * @param ProductRepository                                    $productRepository
     * @param StockRegistryInterface $stockRegistry
     *
     * @internal param ProgressBar $progressBar
     */
    public function __construct(
        State $state,
        CollectionFactory $collectionFactory,
        ObjectManagerInterface $manager,
        ProductRepository $productRepository,
        StockRegistryInterface $stockRegistry
    ) {

        try {
            $state->setAreaCode('adminhtml');

            $this->collectionFactory = $collectionFactory;
            $this->objectManager = $manager;
            $this->productRepository = $productRepository;
            $this->stockRegistry = $stockRegistry;

        } catch (LocalizedException $e) {
            // Intentionaly left empty
        }

        parent::__construct(self::COMMAND_NAME);
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Change product QTY with random value in range [1...100]');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start to processing!');
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addFieldToFilter('type_id', \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        $progressBar = $this->getProgressBar($output, $collection->count());

        $progressBar->start($collection->count());
        $progressBar->display();

        foreach ($collection as $product) {
            $stockItem=$this->stockRegistry->getStockItem($product->getId());
            $stockItem->setQty(rand(0,100))
                ->save();
            $progressBar->advance(1);
        }

        $progressBar->finish();
    }

    /**
     * @param OutputInterface $output
     * @param                 $max
     *
     * @return ProgressBar
     */
    protected function getProgressBar(OutputInterface $output, $max)
    {
        /** @var ProgressBar $progressBar */
        $progressBar = $this->objectManager->create(
            'Symfony\Component\Console\Helper\ProgressBar',
            [
                'output' => $output,
                'max' => $max
            ]
        );

        $progressBar->setMessage('Simple Products:');
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );

        return $progressBar;
    }

}