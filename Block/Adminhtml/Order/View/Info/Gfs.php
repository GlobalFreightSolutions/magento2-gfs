<?php

namespace JustShout\Gfs\Block\Adminhtml\Order\View\Info;

use JustShout\Gfs\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Block\Adminhtml\Order\AbstractOrder;
use Magento\Sales\Helper\Admin;
use Magento\Sales\Model\Order;

/**
 * Gfs Admin Order Block
 *
 * @package   JustShout\Gfs
 * @author    JustShout <http://developer.justshoutgfs.com/>
 * @copyright JustShout - 2018
 */
class Gfs extends AbstractOrder
{
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
     * Order Entity
     *
     * @var Order
     */
    protected $_order;

    /**
     * Gfs constructor
     *
     * @param Context  $context
     * @param Registry $registry
     * @param Admin    $adminHelper
     * @param Data     $gfsHelper
     * @param array    $data
     */
    public function __construct(
        Context  $context,
        Registry $registry,
        Admin    $adminHelper,
        Data     $gfsHelper,
        array    $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
        $this->_gfsHelper = $gfsHelper;
    }

    /**
     * Set Order Entity
     *
     * @param Order $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->_order = $order;

        return $this;
    }

    /**
     * Get Order Entity
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get Shipping Amount
     *
     * @return string
     */
    public function getShippingAmount()
    {
        return $this->displayShippingPriceInclTax($this->getOrder());
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
     * Get the service name for standard deliveries
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
     * Get the carrier for the delivery
     *
     * @return null|string
     */
    public function getCarrier()
    {
        $carrier = null;
        $data = $this->getGfsCloseCheckoutData();
        if (isset($data['selectedService']['carrierName'])) {
            $carrier = $data['selectedService']['carrierName'];
        }

        return $carrier;
    }

    /**
     * Get the service code for the delivery
     *
     * @return null|string
     */
    public function getServiceCode()
    {
        $carrier = null;
        $data = $this->getGfsCloseCheckoutData();
        if (isset($data['selectedService']['carrierCode'])) {
            $carrier = $data['selectedService']['carrierCode'];
        }

        return $carrier;
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

        return __('<strong>Delivery Time:</strong> %1 - %2',
            $deliveryTimeFrom->format('g:sa'),
            $deliveryTimeTo->format('g:sa')
        );
    }

    /**
     * Get Latest Despatch
     *
     * @return string
     */
    public function getLatestDespatch()
    {
        $data = $this->getGfsCloseCheckoutData();
        if (!isset($data['selectedService']['latestDespatch'])) {
            return null;
        }

        $latestDepatch = $this->_gfsHelper->getGfsDate($data['selectedService']['latestDespatch']);

        return __('<strong>Despatch by:</strong> %1',
            $latestDepatch->format('d/m/Y')
        );
    }

    /**
     * This method will get the address of the drop point delivery service
     *
     * @return string
     */
    public function getDropPointServiceAddress()
    {
        $data = $this->getGfsCloseCheckoutData();
        $dropPointId = $this->getDropPointId();
        $lines = [];
        if (isset($data['selectedDroppoint']['droppointDescription'])) {
            $description = trim($data['selectedDroppoint']['droppointDescription']);
            $description .= $dropPointId ? '<strong>' . $dropPointId . '</strong>' : null;
            $lines[] = $description;
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
     * Get Drop Point Id
     *
     * @return string
     */
    public function getDropPointId()
    {
        $data = $this->getGfsCloseCheckoutData();
        $dropPointId = isset($data['selectedDroppoint']['droppointId']) ? $data['selectedDroppoint']['droppointId'] : null;

        return trim($dropPointId);
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
            $template = sprintf('JustShout_Gfs::order/view/info/gfs/%s.phtml', $gfsShippingData['shippingMethodType']);
        }

        return $template;
    }
}
