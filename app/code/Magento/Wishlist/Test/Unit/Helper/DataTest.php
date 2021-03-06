<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Wishlist\Test\Unit\Helper;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface\Proxy as UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Wishlist\Model\Wishlist;

class DataTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \Magento\Wishlist\Helper\Data */
    protected $model;

    /** @var  WishlistProviderInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $wishlistProvider;

    /** @var  Registry |\PHPUnit_Framework_MockObject_MockObject */
    protected $coreRegistry;

    /** @var  PostHelper |\PHPUnit_Framework_MockObject_MockObject */
    protected $postDataHelper;

    /** @var  WishlistItem |\PHPUnit_Framework_MockObject_MockObject */
    protected $wishlistItem;

    /** @var  Product |\PHPUnit_Framework_MockObject_MockObject */
    protected $product;

    /** @var  StoreManagerInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var  Store |\PHPUnit_Framework_MockObject_MockObject */
    protected $store;

    /** @var  UrlInterface |\PHPUnit_Framework_MockObject_MockObject */
    protected $urlBuilder;

    /** @var  Wishlist |\PHPUnit_Framework_MockObject_MockObject */
    protected $wishlist;

    /** @var  Context |\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /**
     * Set up mock objects for tested class
     *
     * @return void
     */
    public function setUp()
    {
        $this->store = $this->getMockBuilder('Magento\Store\Model\Store')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder('Magento\Store\Model\StoreManagerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($this->store);

        $this->urlBuilder = $this->getMockBuilder('Magento\Framework\UrlInterface\Proxy')
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->context = $this->getMockBuilder('Magento\Framework\App\Helper\Context')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('getUrlBuilder')
            ->willReturn($this->urlBuilder);

        $this->wishlistProvider = $this->getMockBuilder('Magento\Wishlist\Controller\WishlistProviderInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->coreRegistry = $this->getMockBuilder('Magento\Framework\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->postDataHelper = $this->getMockBuilder('Magento\Framework\Data\Helper\PostHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->wishlistItem = $this->getMockBuilder('Magento\Wishlist\Model\Item')
            ->disableOriginalConstructor()
            ->setMethods([
                'getProduct',
                'getWishlistItemId',
            ])
            ->getMock();

        $this->wishlist = $this->getMockBuilder('Magento\Wishlist\Model\Wishlist')
            ->disableOriginalConstructor()
            ->getMock();

        $this->product = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            'Magento\Wishlist\Helper\Data',
            [
                'context' => $this->context,
                'storeManager' => $this->storeManager,
                'wishlistProvider' => $this->wishlistProvider,
                'coreRegistry' => $this->coreRegistry,
                'postDataHelper' => $this->postDataHelper
            ]
        );
    }

    public function testGetAddToCartUrl()
    {
        $url = 'http://magento.com/wishlist/index/index/wishlist_id/1/?___store=default';

        $this->store->expects($this->once())
            ->method('getUrl')
            ->with('wishlist/index/cart', ['item' => '%item%'])
            ->will($this->returnValue($url));

        $this->urlBuilder->expects($this->any())
            ->method('getUrl')
            ->with('wishlist/index/index', ['_current' => true, '_use_rewrite' => true, '_scope_to_url' => true])
            ->will($this->returnValue($url));

        $this->assertEquals($url, $this->model->getAddToCartUrl('%item%'));
    }

    public function testGetConfigureUrl()
    {
        $url = 'http://magento2ce/wishlist/index/configure/id/4/product_id/30/';

        $wishlistItem = $this->getMock(
            'Magento\Wishlist\Model\Item',
            ['getWishlistItemId', 'getProductId'],
            [],
            '',
            false
        );
        $wishlistItem
            ->expects($this->once())
            ->method('getWishlistItemId')
            ->will($this->returnValue(4));
        $wishlistItem
            ->expects($this->once())
            ->method('getProductId')
            ->will($this->returnValue(30));

        $this->urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('wishlist/index/configure', ['id' => 4, 'product_id' => 30])
            ->will($this->returnValue($url));

        $this->assertEquals($url, $this->model->getConfigureUrl($wishlistItem));
    }

    public function testGetWishlist()
    {
        $this->wishlistProvider->expects($this->once())
            ->method('getWishlist')
            ->will($this->returnValue($this->wishlist));

        $this->assertEquals($this->wishlist, $this->model->getWishlist());
    }

    public function testGetWishlistWithCoreRegistry()
    {
        $this->coreRegistry->expects($this->any())
            ->method('registry')
            ->willReturn($this->wishlist);

        $this->assertEquals($this->wishlist, $this->model->getWishlist());
    }

    public function testGetAddToCartParams()
    {
        $url = 'result url';
        $storeId = 1;
        $wishlistItemId = 1;

        $this->wishlistItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $this->wishlistItem->expects($this->once())
            ->method('getWishlistItemId')
            ->willReturn($wishlistItemId);

        $this->product->expects($this->once())
            ->method('isVisibleInSiteVisibility')
            ->willReturn(true);
        $this->product->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->store->expects($this->once())
            ->method('getUrl')
            ->with('wishlist/index/cart')
            ->willReturn($url);

        $this->postDataHelper->expects($this->once())
            ->method('getPostData')
            ->with($url, ['item' => $wishlistItemId])
            ->willReturn($url);

        $this->assertEquals($url, $this->model->getAddToCartParams($this->wishlistItem));
    }

    public function testGetSharedAddToCartUrl()
    {
        $url = 'result url';
        $storeId = 1;
        $wishlistItemId = 1;

        $this->wishlistItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $this->wishlistItem->expects($this->once())
            ->method('getWishlistItemId')
            ->willReturn($wishlistItemId);

        $this->product->expects($this->once())
            ->method('isVisibleInSiteVisibility')
            ->willReturn(true);
        $this->product->expects($this->once())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->store->expects($this->once())
            ->method('getUrl')
            ->with('wishlist/shared/cart')
            ->willReturn($url);

        $this->postDataHelper->expects($this->once())
            ->method('getPostData')
            ->with($url, ['item' => $wishlistItemId])
            ->willReturn($url);

        $this->assertEquals($url, $this->model->getSharedAddToCartUrl($this->wishlistItem));
    }

    public function testGetSharedAddAllToCartUrl()
    {
        $url = 'result url';

        $this->store->expects($this->once())
            ->method('getUrl')
            ->with('*/*/allcart', ['_current' => true])
            ->willReturn($url);

        $this->postDataHelper->expects($this->once())
            ->method('getPostData')
            ->with($url)
            ->willReturn($url);

        $this->assertEquals($url, $this->model->getSharedAddAllToCartUrl());
    }
}
