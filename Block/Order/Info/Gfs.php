<?php

namespace JustShout\Gfs\Block\Order\Info;

use JustShout\Gfs\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

/**
 * Gfs Block
 *
 * @package   JustShout\Gfs
 * @author    JustShout <http://developer.justshoutgfs.com/>
 * @copyright JustShout - 2018
 */
class Gfs extends Template
{
    /**
     * Order Entity
     *
     * @var Order
     */
    protected $_order;

    /**
     * GFS Helper
     *
     * @var Data
     */
    protected $_gfsHelper;

    /**
     * GFS Shipping Data
     *
     * @var array
     */
    protected $_gfsCloseCheckoutData;

    /**
     * Gfs constructor
     *
     * @param Template\Context $context
     * @param Data             $gfsHelper
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        Data             $gfsHelper,
        array            $data = []
    ) {
        parent::__construct($context, $data);
        $this->_gfsHelper = $gfsHelper;
    }

    /**
     * Get Order
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Set Order
     *
     * @param Order $order
     *
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->_order = $order;

        return $this;
    }

    /**
     * This method will get the data from the close checkout response stored against the order entity
     *
     * @return array
     */
    public function getGfsCloseCheckoutData()
    {
        if (!$this->_gfsCloseCheckoutData) {
            $this->_gfsCloseCheckoutData = $this->_gfsHelper->getGfsCloseCheckoutData($this->getOrder());
        }

        return $this->_gfsCloseCheckoutData;
    }

    /**
     * Get the service title
     *
     * @return string
     */
    public function getServiceTitle()
    {
        $data = $this->getGfsCloseCheckoutData();
        $service = trim($data['selectedService']['methodTitle']);
        if (isset($data['selectedService']['deliveryTimeFrom'])) {
            $deliveryDate = $this->_gfsHelper->getGfsDate($data['selectedService']['deliveryTimeFrom']);
            $service .= ' - ' . $deliveryDate->format('d/m/Y');
        }

        return $service;
    }

    /**
     * Get the estimated delivery time for standard deliveries
     *
     * @return string
     */
    public function getServiceTime()
    {
        $data = $this->getGfsCloseCheckoutData();
        if (!isset($data['selectedService']['deliveryTimeFrom']) || !isset($data['selectedService']['deliveryTimeTo'])) {
            return null;
        }
        $deliveryTimeFrom = $this->_gfsHelper->getGfsDate($data['selectedService']['deliveryTimeFrom']);
        $deliveryTimeTo = $this->_gfsHelper->getGfsDate($data['selectedService']['deliveryTimeTo']);

        return __('Delivery Between %1 - %2',
            $deliveryTimeFrom->format('g:sa'),
            $deliveryTimeTo->format('g:sa')
        );
    }

    /**
     * This method will get the address of the drop point delivery service
     *
     * @return string
     */
    public function getDropPointAddress()
    {
        $data = $this->getGfsCloseCheckoutData();
        $lines = [];
        if (isset($data['selectedDroppoint']['droppointDescription'])) {
            $lines[] = trim($data['selectedDroppoint']['droppointDescription']);
        }
        if (isset($data['selectedDroppoint']['geoLocation']['addressLines'])) {
            $lines[] = implode(', ', $data['selectedDroppoint']['geoLocation']['addressLines']);
        }
        if (isset($data['selectedDroppoint']['geoLocation']['county'])) {
            $lines[] = trim($data['selectedDroppoint']['geoLocation']['county']);
        }
        if (isset($data['selectedDroppoint']['geoLocation']['town'])) {
            $lines[] = trim($data['selectedDroppoint']['geoLocation']['town']);
        }
        if (isset($data['selectedDroppoint']['geoLocation']['postCode'])) {
            $lines[] = trim($data['selectedDroppoint']['geoLocation']['postCode']);
        }

        return implode('<br/>', $lines);
    }

    /**
     * Get the delivery times for drop points
     *
     * @return array
     */
    public function getDropPointOpeningTimes()
    {
        $data = $this->getGfsCloseCheckoutData();
        $times = [];
        if (!isset($data['selectedDroppoint']['collectionSlots'])) {
            return $times;
        }

        for ($d = 0; $d < 7; $d++) {
            if (!isset($data['selectedDroppoint']['collectionSlots'][$d])) {
                continue;
            }
            $day = $data['selectedDroppoint']['collectionSlots'][$d];
            $dayName = $this->_gfsHelper->getGfsDate($day['collectionDate']);
            $times[$dayName->format('N')] = [
                'day'  => $dayName->format('D'),
                'from' => $day['timeSlots'][0]['from'],
                'to'   => $day['timeSlots'][0]['to']
            ];
        }

        ksort($times);

        return $times;
    }

    /**
     * Get the GFS Logo
     *
     * @return null|string
     */
    public function getGfsLogo()
    {
        $currentUrl = $this->_storeManager->getStore()->getCurrentUrl(false);
        $logo = $this->getViewFileUrl('JustShout_Gfs::images/logo.png');
        if (strpos($currentUrl, 'print') !== false) {
            $logo = null;
        }

        return $logo;
    }

    /**
     * Set the block template
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->setTemplate($this->_getGfsTemplate());

        parent::_beforeToHtml();

        return $this;
    }

    /**
     * This method will decided which template will show the gfs shipping data
     *
     * @return null|string
     */
    protected function _getGfsTemplate()
    {
        $template = null;
        $order = $this->getOrder();
        $gfsShippingData = $this->_gfsHelper->getGfsShippingData($order);
        $gfsCloseCheckoutData = $this->getGfsCloseCheckoutData();
        if (empty($gfsShippingData) || empty($gfsCloseCheckoutData)) {
            return $template;
        }

        if (isset($gfsShippingData['shippingMethodType'])) {
            $template = sprintf('JustShout_Gfs::order/info/gfs/%s.phtml', $gfsShippingData['shippingMethodType']);
        }

        return $template;
    }
}
