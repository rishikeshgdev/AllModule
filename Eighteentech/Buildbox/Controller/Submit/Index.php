<?php
/**
 * @author 18th DigiTech Team
 * @copyright Copyright (c) 2022 18th DigiTech (https://www.18thdigitech.com)
 * @package Eighteentech_Buildbox
 */
namespace Eighteentech\Buildbox\Controller\Submit;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Index Controller
 */
class Index extends Action
{
    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var _productCollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var jsonResultFactory
     */
    protected $jsonResultFactory;

    /**
     * @var jsonResultFactory
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var StockItemRepository $_stockItemRepository
     */
    protected $_stockItemRepository;

    /**
     * @var \Magento\Catalog\Model\Product\Option $customOptions
     */
    protected $customOptions;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param FormKey $formKey
     * @param Cart $cart
     * @param CollectionFactory $productCollectionFactory
     * @param Product $product
     * @param JsonFactory $jsonResultFactory
     * @param ResourceConnection $resource
     * @param Session $checkoutSession
     * @param Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param ProductFactory $productFactory
     * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
     * @param \Magento\Catalog\Model\Product\Option $customOptions
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        Cart $cart,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        Product $product,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository,
        \Magento\Catalog\Model\Product\Option $customOptions
    ) {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->product = $product;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->resource = $resource;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->productFactory = $productFactory;
        $this->_stockItemRepository = $stockItemRepository;
        $this->_customOptions = $customOptions;
        parent::__construct($context);
    }

    /**
     * Execute build box functionality
     */
    public function execute()
    {
		
        $post = $this->getRequest()->getPostValue();
        $selectKitProId='';
        // print_r($post);
        // die;

        if(isset($post['kitConfChildQtyId'])){
        $selectKitProId = unserialize(base64_decode($post['selectKitProId'][0]));

        $kitConfChildQtyId = json_decode($post['kitConfChildQtyId'],true);
        $selectkitParentId = $post['selectkitParentId'];
        $configProSize = unserialize(base64_decode($post['configProSize']));
        $ProSize = implode(",",$configProSize);

        $cart = $this->cart;
        $quote = $cart->getQuote();  

        $cartItems = $cart->getQuote()->getAllItems();
        foreach($post['getItem'] as $selectedId){            
            foreach ($cartItems as $item) {
                if($item->getItemId() == $selectedId){
                    if($item->getProductType() != 'configurable'){
                        $quoteItem = $quote->getItemById($item->getItemId());                    
                        $quoteItem->setQty($post['totalQty']);
                        $quoteItem->save();
                    }
                }
            }
        }

        $params = [];
        $options = [];
         
        $removePro = 1; 
        $cartItems = $cart->getQuote()->getAllItems();
        foreach($kitConfChildQtyId as $kitProId => $qty){
            if($removePro ==1){
                
                foreach ($cartItems as $item) {
                    if($item->getProductId() == $selectkitParentId){
                            $qouteItem = $quote->getItemById($item->getItemId());                    
                            $qouteItem->delete();
                            $qouteItem->save();
                            continue;
                    }
                }
            $removePro=0;
        }
        
        $parent = $this->productFactory->create()->load($selectkitParentId);
        $child = $this->productFactory->create()->load($kitProId);
        
        $params['product'] = $parent->getId();
        $params['qty'] = $qty;

        $productAttributeOptions = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);

        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $child->getData($option['attribute_code']);
            
        }
        $params['super_attribute'] = $options;

        /*Add product to cart */
      
    
        $cart->addProduct($parent, $params);
        $cart->save();

        $cartItems = $cart->getQuote()->getAllItems();
        foreach ($cartItems as $item) {
            if($item->getProductId() == $kitProId){
                $quoteItem = $quote->getItemById($item->getParentItemId());                    
                $quoteItem->setBoxType("yes");
                $quoteItem->setProductQtyEachBox(1);
                $quoteItem->setKitProductSize($ProSize);
                $quoteItem->save();
            }
        }
    }


        }














        // if(($post['SelectedStoreQty'][0]) != ''){
        //     $selectKitProId = unserialize(base64_decode($post['selectKitProId'][0]));

        //     $configProSize = unserialize(base64_decode($post['configProSize']));
        //     $ProSize = implode(",",$configProSize);

        //     $configProSize = unserialize(base64_decode($post['configProSize']));
        //     $ProSize = implode(",",$configProSize);
            
        //     $SelectedStoreQty = explode(",",$post['SelectedStoreQty'][0]);
        
        //     if (!empty($selectKitProId)) {
        //         $avbQty = '';
        //         $itemNum = count($selectKitProId);
        //         $itemProId = '';
        //         for ($i = 0; $i < $itemNum; $i++) {
        //             if($post['SelectedStoreQty'][0][$i] != ''){
        //                 if(!empty($selectKitProId[$i])){
        //                     $quote = $this->cart->getQuote();
        //                     $item = $quote->getItemById($selectKitProId[$i]);
        //                     $item->setQty($SelectedStoreQty[$i]);
        //                     $item->setBoxType("yes");
        //                     $item->setProductQtyEachBox(1);
        //                     $item->setKitProductSize($ProSize);
        //                     $item->save();
        //                 }
        //             }
        //         }
        //     }
        // } 
        
        $productId = $post['parentProductId'];
        $cart = $this->cart;
 
        $itemInBox = 0;
        if (!empty($post['itemInBox'])) {
            $itemInBox = 1;
        } else {
            $itemInBox = 0;
        }

        $sum = 0;
        $proDim=[];
        foreach ($post['prodDim'] as $key => $value) {
            $proDim[] = $value;
            $sum += $value;
        }

        /**
         * @var $childWCId
         * store products child id
         */
        $childWCId = '' ;
        if (!isset($post["optionProId"])) {
            $childWCId = $post['choose-buildbox'];
        } else {
            $childWCId = $post["optionProId"];
        }

        /**
         * @var $childProduct
         * Load product by product id
         */
        $childProduct = $this->productFactory->create()->load($childWCId);
        $parent = $this->productFactory->create()->load($productId);

        /**
         * @var $params
         * Store product parameters
         */
        $params = [];
        $options = [];
        /**
         * @var $prodId
         * Store product id
         */
        $prodId=[];

        /**
         * @var $itemNum
         * get store item Number
         */
        $itemNum = '';
   
        $params['product'] = $parent->getId();
        // $params['qty'] = $post['boxQty'];
        $params['qty'] = 1;

        $productAttributeOptions = $parent->getTypeInstance(true)->getConfigurableAttributesAsArray($parent);
        $customOptions = $this->_customOptions->getProductOptionCollection($parent);
        $optionId = '';

        foreach ($customOptions as $option) {
            $optionId = $option->getId();
        }

        foreach ($productAttributeOptions as $option) {
            $options[$option['attribute_id']] = $childProduct->getData($option['attribute_code']);
        }

        $params['super_attribute'] = $options;

        // add custom column value in cart product with box
        if (!empty($post['getItem'])) {
            $avbQty = '';
            $prodId = $post['getItem'];
            $itemNum = count($post['getItem']);
            $itemProId = '';
            for ($i = 0; $i < $itemNum; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($prodId[$i]);
                
                $quote1 = $this->quoteRepository->get($item->getQuoteId());
                $quote1->setData('esdc_enable', $itemInBox); // Fill data
                $this->quoteRepository->save($quote1);
                 $item->setEsdcPricing($itemInBox);
                $item->setBoxType("yes"); //don't change
                // $item->setQty($post['totalQty']);
                // $item->setQty($post['prodQtyForBox']);
                $item->setBoxProductId($productId);

                if ($parent->getId()==$item->getBoxProductId()) {
                    $item->setProductDim($proDim[$i]);
                }
             
                $option = [
                    $optionId  => $parent->getName().'_'. $item->getItemId()
                ];
                $item->save();
            }
        }
        
        $params['options'] = $option;
        $params['options_'.$childWCId[0].'_file_action'] = 'save_new';

        $cart->addProduct($parent, $params);      
        $cart->save();
       
       //message for response
        $data = ['success' => 'true', 'msg' => 'Product added to cart successfully!'];
        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        /** 
         * ** After Add To cart save Same value in table **
         * ------------------------------------------------
         */
        $quoteId = $this->cart->getQuote()->getId();
        $itemsArray = $this->cart->getQuote()->getAllItems();
       
        /**
         * @var $itemId
         * Store Cart Itemid when productid == exist product id in cart
         */
        $itemId ='';
        $parentId = '';
        $ItemProId = '';
        $getBoxItems = [];
        foreach ($itemsArray as $items) {
            if ($items->getProductId() == $productId) {
                $itemId = $items->getItemId();
                $parentId=$itemId;
                $quotebox = $this->cart->getQuote();
                $setBoxId = $quotebox->getItemById($itemId);
                $getBoxItems[] = $setBoxId->getItemId();
                $setBoxId->setBoxId(1);  
                // $setBoxId->setQty($post['totalQty']);          
                $setBoxId->setProductQtyEachBox(1);
                if ($setBoxId->getBoxId()=='1') {
                    $ItemProId = $setBoxId->getItemId();
                }
                $this->CheckProduct($itemId);
                
                $setBoxId->setProductDim($sum);
                $setBoxId->save();
            }
            if (!empty($parentId) && ($parentId == $items->getParentItemId())) {
               
                    $additionalOptions = $items->getOptionByCode('info_buyRequest');
                    $quotebox = $this->cart->getQuote();
                    $parentItem = $quotebox->getItemById($parentId);
                    $buyRequest =$additionalOptions->getValue();
                    $parentItem->getOptionByCode('info_buyRequest')->setValue($buyRequest);
                    $parentItem->saveItemOptions();
                    $parentItem->save();
            }
        }
        if (!empty($post['getItem'])) {
            $prodId = $post['getItem'];
            $itemNum = count($post['getItem']);
            for ($i = 0; $i < $itemNum; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($prodId[$i]);
                $item->setBoxItemId($ItemProId);
                $item->save();
            }
        }
        if (!empty($getBoxItems)) {
            $itemNumco = count($getBoxItems);
            for ($i = 0; $i < $itemNumco; $i++) {
                $quote = $this->cart->getQuote();
                $item = $quote->getItemById($getBoxItems[$i]);
                if (empty($item->getBoxName())) {
                    $item->getItemId();
                    $quote = $this->cart->getQuote();
                    $items = $quote->getItemById($item->getItemId());
                    // $items->setQty($post['boxQty']);
                    // $items->setQty($post['totalQty']);
                    
                    $item->setBoxName($post['input-box-name']);//
                    $item->setBoxProductionDay($post['production']);
                }
                $item->save();                
            }
        }
        return $result;
    }

    /**
     * Get Box Id and check which product is configurable
     * 
     * @param int $itemId
     * 
     * @return bool
     */
    public function CheckProduct($itemId){
        $quoteId = $this->cart->getQuote()->getId();
        $itemsArray = $this->cart->getQuote()->getAllItems();
        foreach ($itemsArray as $items) {
            if($items->getBoxType() == 'yes'){
                if($items->getProductType() == 'configurable')
                {
                    $quotebox = $this->cart->getQuote();
                    $setBoxId = $quotebox->getItemById($itemId);
                    $setBoxId->setBoxType("yes");
                    $setBoxId->save();
                }
            }
        }
        return 'true';
    }
}
