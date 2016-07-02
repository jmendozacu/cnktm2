<?php

namespace Conekta\Webhook\Controller\Ajax;

class Listener extends \Magento\Framework\App\Action\Action {

    /**
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @var \Magento\Framework\App\Cache\StateInterface
     */
    protected $_cacheState;

    /**
     * @var \Magento\Framework\App\Cache\Frontend\Pool
     */
    protected $_cacheFrontendPool;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\StateInterface $cacheState
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
    \Magento\Framework\App\Action\Context $context, \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList, \Magento\Framework\App\Cache\StateInterface $cacheState, \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool, \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('Your text message');

        parent::__construct($context);
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheState = $cacheState;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Flush cache storage
     *
     */
    public function execute() {

	// By TOM :p  For getting response i have created webhook http://magen4me.xyz/webhook/ajax/listener in conekta admin
        if (isset($_GET['reference_id'])) {
        	//this request source is default.js after successful place order 
            $data    = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->getLastItem();
            $payment = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->load($data->getEntityId());
            $payment->setData('cc_trans_id', $_GET['reference_id']);
            $payment->setData('cc_owner', $_GET['name'])->save();
            $myfile = fopen("var/log/debug.log", "a+") or die("Unable to open file!");
            fwrite($myfile, print_r("record cc_trans_id added", true));
            fwrite($myfile, print_r($data->getEntityId() . "refernce_id" . $_GET['reference_id'], true));
            fclose($myfile);

            echo $data->getParentid();
        } else {
			$body = @file_get_contents('php://input');
            $event = json_decode($body);
            $myfile = fopen("var/log/debug.log", "a+") or die("Unable to open file!");
		      $charge = $event->data->object;
            if ($charge->status == 'paid') {
                $data = $this->_objectManager->create('Magento\Sales\Model\Order\Payment')->getCollection()->addFieldToFilter('cc_trans_id', $charge->id)->getFirstItem();
                $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($data->getParentId());
                $order->setData('state', 'complete');
                $order->setData('status', 'complete');
                $order->setData('base_total_paid', intval(((float) $charge->amount) / 100));
                $order->save();
                // Order Status Changed. Tell Conekta that order status has been changed .
   				//set header status ok for  1.0 and 1.1 http requests
                header('HTTP/1.0 200 OK');
                $this->getResponse()->setHeader('HTTP/1.1','200 OK');
                $this->getResponse()->setHeader('Status','200 OK');
                fwrite($myfile, print_r("status_changed", true));
                fclose($myfile);
            }else
            {
            	// Order possible has not been persisted yet. Tell Conekta to retry one hour later.
            	header('HTTP/1.0 404 Not Found');
            	$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
            	$this->getResponse()->setHeader('Status','404 File not found');
            }
            $this->resultPage = $this->resultPageFactory->create();
            return $this->resultPage;
        }
    }

}
