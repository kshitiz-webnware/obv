<?php
namespace ObvPayment\Controller\Response;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use ObvPayment\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Sales\Model\Order\Payment\Transaction;

class Index extends  \Magento\Framework\App\Action\Action
{
	protected $_objectmanager;
	protected $_checkoutSession;
	protected $_orderFactory;
	protected $urlBuilder;
	private $logger;
	protected $response;
	protected $config;
	protected $messageManager;
	protected $transactionRepository;
	protected $cart;
	protected $inbox;

	public function __construct( Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
			Logger $logger,
			ScopeConfigInterface $scopeConfig,
			Http $response,
			TransactionBuilder $tb,
			 \Magento\Checkout\Model\Cart $cart,
			 \Magento\AdminNotification\Model\Inbox $inbox,
			 \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
		) {

        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $scopeConfig;
        $this->transactionBuilder = $tb;
		$this->logger = $logger;
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->transactionRepository = $transactionRepository;
		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');

		parent::__construct($context);
    }

	public function execute()
	{
		$storedPaymentRequestId = $this->checkoutSession->getPaymentRequestId();

		$payment_status = $this->getRequest()->getParam('status');
		$transaction_id = $this->getRequest()->getParam('transaction');
		$orderId = base64_decode( $this->getRequest()->getParam('o') );

		if(session_id() == '' || !isset($_SESSION)) {
			session_start();
		}

		$api_data = array();
		$api_data['firstname'] = $_SESSION["api_data"]['firstname'];
		$api_data['lastname'] = $_SESSION["api_data"]['lastname'];
		$api_data['email'] = $_SESSION["api_data"]['email'];
		$api_data['total'] = $_SESSION["api_data"]['total'];
		$api_data['currency'] = $_SESSION["api_data"]['currency'];
		$api_data['return_url'] = $_SESSION["api_data"]['return_url'];
		$api_data['order_no'] = $_SESSION["api_data"]['order_no'];
		$api_data['api_key'] = $_SESSION["api_data"]['api_key'];

		$order = $this->orderFactory->create()->load($api_data['order_no']);
		//print_r($order->getId());
		$payment = $order->getPayment();

		if($payment_status == "SUCCESS")
		{
			$order->setState(Order::STATE_PROCESSING)->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));

			$transaction = $this->transactionRepository->getByTransactionId(
				"-1",
				$payment->getId(),
				$order->getId()
			);
			if($transaction)
			{
				$transaction->setTxnId($transaction_id);
				$transaction->setAdditionalInformation(
					"ObvPayment Transaction Id",$transaction_id
				);
				$transaction->setAdditionalInformation(
					"status","successful"
				);
				$transaction->setIsClosed(1);
				$transaction->save();
			}

			$payment->addTransactionCommentsToOrder(
				$transaction,
				"Transaction is completed successfully"
			);
			$payment->setParentTransactionId(null);
			$order->setCanSendNewEmailFlag(true);
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);

			$payment->save();
			$order->save();

			$this->logger->info("Payment for $transaction_id was credited.");

			$this->_redirect($this->urlBuilder->getUrl('checkout/onepage/success/',  ['_secure' => true]));
		}
		else if($payment_status == "FAIL")
		{
			$transaction = $this->transactionRepository->getByTransactionId(
				"-1",
				$payment->getId(),
				$order->getId()
			);
			$transaction->setTxnId($transaction_id);
			$transaction->setAdditionalInformation(  
				"ObvPayment Transaction Id",$transaction_id
			);
			$transaction->setAdditionalInformation(  
				"status","successful"
			);
			$transaction->setIsClosed(1);
			$transaction->save();
			$payment->addTransactionCommentsToOrder(
				$transaction,
				"The transaction is failed"
			);
			try{
				$items = $order->getItemsCollection();
				foreach($items as $item)
					$this->cart->addOrderItem($item);
				$this->cart->save();
			}catch(Exception $e){
				$message = $e->getMessage();
				$this->logger->info("Not able to add Items to cart Exception MEssage".$message);
			}
			$order->cancel();

			$payment->setParentTransactionId(null);
			$payment->save();
			$order->save();
			$this->logger->info("Payment for $transaction_id failed.");
			$this->_redirect($this->urlBuilder->getUrl('checkout/cart',  ['_secure' => true]));
		}
	}
}