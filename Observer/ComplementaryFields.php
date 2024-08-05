<?php
namespace MestreMage\OneStepCheckout\Observer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
class ComplementaryFields implements ObserverInterface
{

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\CustomerFactory $customer,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Customer\Model\SessionFactory $sessionFactory
    ) {
        $this->_storeManager = $storeManager;
        $this->orderCustomerService = $orderCustomerService;
        $this->_orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->customer = $customer;
        $this->addressFactory = $addressFactory;
        $this->sessionFactory = $sessionFactory;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getOrder();
            $postJson = json_decode(file_get_contents('php://input'));
            if(isset($postJson->comments) && !empty($postJson->comments)) {
                $order->addStatusHistoryComment($postJson->comments);
                $order->save();
            }


            $this->convertGuestToCustomer($order, $postJson);


        } catch (\Exception $e){
            throw new \Magento\Framework\Exception\StateException(__($e->getMessage()));
        }
    }

    protected function convertGuestToCustomer($order, $data) {
        if (!isset($data->code_password) || empty($data->code_password)) {
            return;
        }

        if ($data->confirm_password != $data->code_password) {
            throw new \Magento\Framework\Exception\StateException(__('As senhas não conferem.'));
        }

        $customer= $this->customer->create();
        $customer->setWebsiteId($this->_storeManager->getStore()->getWebsiteId());
        $customer->setEmail($order->getCustomerEmail());
        $customer->setFirstname($order->getCustomerFirstname());
        $customer->setLastname($order->getCustomerLastname());
        $customer->setPassword($data->code_password);
        $customer->setTaxvat(($data->billingAddress->vatId??''));
        $customer->sendNewAccountEmail();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/teste.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $logger->info(\json_encode($data));

        if(isset($data->billingAddress->middlename)){
            $customer->setMiddlename($data->billingAddress->middlename);
        }

        if(isset($data->billingAddress->prefix)){
            $customer->setPrefix($data->billingAddress->prefix);
        }

        if(isset($data->billingAddress->suffix)){
            $customer->setSuffix($data->billingAddress->suffix);
        }

        if(isset($data->billingAddress->company)){
            $customer->setCompany($data->billingAddress->company);
        }

        if(isset($data->billingAddress->fax)){
            $customer->setFax($data->billingAddress->fax);
        }

        if(isset($data->billingAddress->fax)){
            $customer->setTelephone($data->billingAddress->telephone);
        }

        if(isset($data->mm_dob)){
            $customer->setDob($data->mm_dob);
        }

        if(isset($data->mm_gender)){
            $customer->setGender($data->mm_gender);
        }

        $customer->save();

        $this->sessionFactory->create()->setCustomerAsLoggedIn($customer);
        
        $address = $this->addressFactory->create();
        $address->setCustomerId($customer->getId());
        $address->setFirstname($data->billingAddress->firstname);
        $address->setLastname($data->billingAddress->lastname);

        if(isset($data->billingAddress->middlename)){
            $address->setMiddlename($data->billingAddress->middlename);
        }

        if(isset($data->billingAddress->prefix)){
            $address->setPrefix($data->billingAddress->prefix);
        }

        if(isset($data->billingAddress->suffix)){
            $address->setSuffix($data->billingAddress->suffix);
        }

        if(isset($data->billingAddress->company)){
            $address->setCompany($data->billingAddress->company);
        }

        if(isset($data->billingAddress->fax)){
            $address->setFax($data->billingAddress->fax);
        }

        if(isset($data->billingAddress->fax)){
            $address->setTelephone($data->billingAddress->telephone);
        }

        $address->setCountryId($data->billingAddress->countryId);
        $address->setPostcode($data->billingAddress->postcode);
        $address->setCity($data->billingAddress->city);
        $address->setTelephone($data->billingAddress->telephone);
        $address->setStreet((array)$data->billingAddress->street);
        $address->setRegion($data->billingAddress->region);
        $address->setRegionCode($data->billingAddress->regionCode);
        $address->setRegionId($data->billingAddress->regionId);
        $address->setIsDefaultBilling('1');
        $address->setIsDefaultShipping('1');
        $address->setSaveInAddressBook('1');
        $address->save();

        $order->setCustomerId($customer->getId());
        $order->setCustomerGroupId(1);
        $order->setCustomerIsGuest(0);
        $order->save();

    }

}