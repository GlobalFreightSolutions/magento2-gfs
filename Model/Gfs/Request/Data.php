<?php

namespace JustShout\Gfs\Model\Gfs\Request;

use JustShout\Gfs\Helper\Config;
use JustShout\Gfs\Model\Gfs\Cookie;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Gfs Request Data Model
 *
 * @package   JustShout\Gfs
 * @author    JustShout <http://developer.justshoutgfs.com/>
 * @copyright JustShout - 2018
 */
class Data
{
    /**
     * Gfs Config Helper
     *
     * @var Config
     */
    protected $_config;

    /**
     * Checkout Session
     *
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * Product Factory
     *
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * Customer Factory
     *
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Store Manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Country Factory
     *
     * @var CountryFactory
     */
    protected $_countryFactory;

    /**
     * Json
     *
     * @var Json
     */
    protected $_json;

    /**
     * Gfs Address Cookie
     *
     * @var Cookie\Address
     */
    protected $_addressCookie;

    /**
     * Data constructor
     *
     * @param Config                $config
     * @param Session               $checkoutSession
     * @param ProductFactory        $productFactory
     * @param CustomerFactory       $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param CountryFactory        $countryFactory
     * @param Json                  $json
     * @param Cookie\Address        $addressCookie
     */
    public function __construct(
        Config                $config,
        Session               $checkoutSession,
        ProductFactory        $productFactory,
        CustomerFactory       $customerFactory,
        StoreManagerInterface $storeManager,
        CountryFactory        $countryFactory,
        Json                  $json,
        Cookie\Address        $addressCookie
    ) {
        $this->_config = $config;
        $this->_checkoutSession = $checkoutSession;
        $this->_productFactory = $productFactory;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;
        $this->_countryFactory = $countryFactory;
        $this->_json = $json;
        $this->_addressCookie = $addressCookie;
    }

    /**
     * This method will generate the request data object that is used in the checkout widget
     *
     * @return array
     */
    public function getGfsData()
    {
        $data = [];
        $quote = $this->getQuote();
        if (!$quote->getId() || !$this->_getQuoteAddress()->hasData()) {
            return $data;
        }

        $data['Request']['DateRange'] = $this->getDateRange();
        $data['Request']['Order'] = $this->getOrderData();
        $data['Request']['RequestedDeliveryTypes'] = $this->getRequestedDeliveryTypes();
        $data['Request']['Session'] = [];

        return $data;
    }

    /**
     * This will get the customers full address as a string
     *
     * @return string
     */
    public function getInitialAddress()
    {
        $address = $this->_getQuoteAddress();
        if (!$this->_getQuoteAddress()->hasData()) {
            return '';
        }

        $initialAddress = [];
        foreach (explode("\n", $address->getStreet()) as $street) {
            $initialAddress[] = $street;
        }
        $initialAddress[] = $address->getCity();
        $initialAddress[] = $address->getPostcode();
        /** @var Country $countryModel */
        $countryModel = $this->_countryFactory->create();
        /** @var Country $country */
        $country = $countryModel->loadByCode($address->getCountryId());
        $initialAddress[] = $country->getName();

        return implode(', ', $initialAddress);
    }

    /**
     * This method will set the date range of the request object
     *
     * @return array
     */
    public function getDateRange()
    {
        $date = new \DateTime();

        return [
            'DateFrom' => $date->format('Y-m-d'),
            'DateTo'   => $date->modify('+14 day')->format('Y-m-d'),
        ];
    }

    /**
     * This method will get the data that is set the order object
     *
     * @return array
     */
    public function getOrderData()
    {
        $data = [];
        $data['Contents'] = $this->getOrderContents();
        $data['Transit'] = $this->getOrderTransit();
        $data['Shipper'] = $this->getOrderShipper();
        $data['Value'] = $this->getOrderValue();
        switch ($this->_config->getWeightUnit()) {
            case 'lbs':
                $data['imperialDimensions'] = $this->getOrderImperialDimensions();
                break;
            default:
                $data['MetricDimensions'] = $this->getOrderMetricDimensions();
        }

        return $data;
    }

    /**
     * This method will get the order object that stores the items from the order
     *
     * @return array
     */
    public function getOrderContents()
    {
        $items = [];
        $quote = $this->getQuote();
        foreach ($quote->getAllVisibleItems() as $item) {
            $itemData = [
                'Description' => $item->getName(),
                'ItemValue'   => [
                    'CurrencyCode' => $quote->getQuoteCurrencyCode(),
                    'Value'        => (float) $item->getPriceInclTax()
                ],
                'ProductCode' => $item->getSku(),
                'Quantity'    => $item->getQty()
            ];
            $dimensions = $this->_getItemDimensions($item);
            if (!empty($dimensions)) {
                $itemData = array_merge($itemData, $dimensions);
            }
            $customFields = $this->_getItemCustomFields($item);
            if (!empty($customFields)) {
                $itemData['customItemFields'] = $customFields;
            }
            $items[] = $itemData;
        }

        return $items;
    }

    /**
     * This method will populate the gfs data object with the shipping address and customer details
     *
     * @return array
     */
    public function getOrderTransit()
    {
        $data = [];
        $quote = $this->getQuote();
        $shippingAddress = $this->_getQuoteAddress();

        $data['Recipient']['ContactDetails']['Email'] = $quote->getCustomerEmail();
        $data['Recipient']['Location']['AddressLineCollection'] = explode("\n", $shippingAddress->getStreet());
        $data['Recipient']['Location']['CountryCode']['Code'] = $shippingAddress->getCountryId();
        $data['Recipient']['Location']['CountryCode']['Encoding'] = 'ccISO_3166_1_Alpha2';
        $data['Recipient']['Location']['Postcode'] = $shippingAddress->getPostcode();
        $data['Recipient']['Location']['Town'] = $shippingAddress->getCity();
        $data['Recipient']['Person']['FirstName'] = $shippingAddress->getFirstname();
        $data['Recipient']['Person']['LastName'] = $shippingAddress->getLastname();
        $data['Recipient']['Person']['Title'] = $shippingAddress->getPrefix() ? $shippingAddress->getPrefix() : 'Mr';

        return $data;
    }

    /**
     * This method will populate the gfs data object for the shipper
     *
     * @return array
     */
    public function getOrderShipper()
    {
        $data = [];
        $quote = $this->getQuote();
        $shippingAddress = $this->_getQuoteAddress();

        $data['ContactDetails']['Email'] = $quote->getCustomerEmail();
        $data['Location']['AddressLineCollection'] = explode("\n", $shippingAddress->getStreet());
        $data['Location']['CountryCode']['Code'] = $shippingAddress->getCountryId();
        $data['Location']['CountryCode']['Encoding'] = 'ccISO_3166_1_Alpha2';
        $data['Location']['Postcode'] = $shippingAddress->getPostcode();
        $data['Location']['Town'] = $shippingAddress->getCity();
        $data['Person']['FirstName'] = $shippingAddress->getFirstname();
        $data['Person']['LastName'] = $shippingAddress->getLastname();
        $data['Person']['Title'] = $shippingAddress->getPrefix() ? $shippingAddress->getPrefix() : 'Mr';

        return $data;
    }

    /**
     * This method will populate the gfs data object with the order totals
     *
     * @return array
     */
    public function getOrderValue()
    {
        $quote = $this->getQuote();

        return [
            'CurrencyCode' => $quote->getQuoteCurrencyCode(),
            'Value'        => (float) $quote->getSubtotalWithDiscount()
        ];
    }

    /**
     * Get Order Imperial Dimensions
     *
     * @return array
     */
    public function getOrderImperialDimensions()
    {
        $quote = $this->getQuote();
        $weight = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $weight += (float) $item->getWeight();
        }

        return [
            'weight' => [
                'pounds' => $weight,
                'ounces' => (float) ($weight * 16),
            ]
        ];
    }

    /**
     * Get Order Metric Dimensions
     *
     * @return array
     */
    public function getOrderMetricDimensions()
    {
        $quote = $this->getQuote();
        $weight = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $weight += (float) $item->getWeight();
        }

        return [
            'weight' => [
                'scale' => 'wiKilograms',
                'value' => $weight,
            ],
        ];
    }

    /**
     * This method gets the delivery types that are enabled to use
     *
     * @return array
     */
    public function getRequestedDeliveryTypes()
    {
        return $this->_config->getDeliveryTypes();
    }

    /**
     * Get Current Quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * When using the checkout, the shipping address is not saved to the quote when initially going through
     * so the address is stored in the checkout session.
     *
     * @return DataObject
     */
    protected function _getQuoteAddress()
    {
        $address = new DataObject();
        $gfsAddress = $this->_addressCookie->get();
        if (!$gfsAddress) {
            return $address;
        }
        $data = $this->_json->unserialize($gfsAddress);
        if (is_array($data)) {
            $address->addData($data);
        }

        return $address;
    }

    /**
     * @param Quote\Item $item
     *
     * @return array
     */
    protected function _getItemDimensions($item)
    {
        $weight = (float) $item->getWeight();
        if (!$weight) {
            return [];
        }

        switch ($this->_config->getWeightUnit()) {
            case 'kgs':
                return $this->_getItemDimensionsMetric($item);
                break;
            case 'lbs':
                return $this->_getItemDimensionsImperial($item);
                break;
            default:
                return [];
        }
    }

    /**
     * Get the dimensions if the store is set to metric (kgs)
     *
     * @param Quote\Item $item
     *
     * @return array
     */
    protected function _getItemDimensionsMetric($item)
    {
        $dimensions = [
            'metricDimensions' => [
                'weight' => [
                    'scale' => 'wiKilograms',
                    'value' => (float) $item->getWeight(),
                ],
            ],
        ];

        $height = $this->_getItemHeight($item);
        if ($height) {
            $dimensions['metricDimensions']['height'] = $height;
        }

        $width = $this->_getItemWidth($item);
        if ($width) {
            $dimensions['metricDimensions']['width'] = $width;
        }

        $length = $this->_getItemLength($item);
        if ($length) {
            $dimensions['metricDimensions']['length'] = $length;
        }

        if ($height || $width || $length) {
            $dimensions['metricDimensions']['scale'] = $this->_config->getMetricDimensionUnit();
        }

        return $dimensions;
    }

    /**
     * Get the dimensions if the store is set to imperial (lbs)
     *
     * @param Quote\Item $item
     *
     * @return array
     */
    protected function _getItemDimensionsImperial($item)
    {
        $dimensions = [
            'imperialDimensions' => [
                'weight' => [
                    'pounds' => (float) $item->getWeight(),
                    'ounces' => (float) ($item->getWeight() * 16),
                ],
            ],
        ];

        $height = $this->_getItemHeight($item);
        if ($height) {
            $dimensions['imperialDimensions']['height']['inches'] = $height;
            $dimensions['imperialDimensions']['height']['sixteenths'] = $height * 16;
        }

        $width = $this->_getItemWidth($item);
        if ($width) {
            $dimensions['imperialDimensions']['width']['inches'] = $width;
            $dimensions['imperialDimensions']['width']['sixteenths'] = $width * 16;
        }

        $length = $this->_getItemLength($item);
        if ($length) {
            $dimensions['imperialDimensions']['length']['inches'] = $length;
            $dimensions['imperialDimensions']['length']['sixteenths'] = $length * 16;
        }

        return $dimensions;
    }

    /**
     * Get Item Height
     *
     * @param Quote\Item $item
     *
     * @return float
     */
    protected function _getItemHeight($item)
    {
        $attr = $this->_config->getHeightAttribute();
        if (!$attr) {
            return 0.00;
        }

        return (float) $this->_getProductAttributeValue($item->getProduct()->getId(), $attr, 'double');
    }

    /**
     * Get Item Width
     *
     * @param Quote\Item $item
     *
     * @return float
     */
    protected function _getItemWidth($item)
    {
        $attr = $this->_config->getWidthAttribute();
        if (!$attr) {
            return 0.00;
        }

        return (float) $this->_getProductAttributeValue($item->getProduct()->getId(), $attr, 'double');
    }

    /**
     * Get Item Length
     *
     * @param Quote\Item $item
     *
     * @return float
     */
    protected function _getItemLength($item)
    {
        $attr = $this->_config->getLengthAttribute();
        if (!$attr) {
            return 0.00;
        }

        return (float) $this->_getProductAttributeValue($item->getProduct()->getId(), $attr, 'double');
    }

    /**
     * This method will get the custom fields object per item
     *
     * @param Quote\Item $item
     *
     * @return array
     */
    protected function _getItemCustomFields($item)
    {
        $fields = [];
        $fieldNumber = 1;
        foreach ($this->_config->getItemCustomFields() as $field) {
            $attributeCode = isset($field['value']) ? $field['value'] : null;
            if (!$attributeCode) {
                continue;
            }
            $type = isset($field['type']) ? $field['type'] : 'String';
            $value = $this->_getProductAttributeValue(
                (int) $item->getProduct()->getId(),
                $attributeCode,
                $type
            );

            if (!$value && $value !== 0) {
                continue;
            }

            $fields[] = [
                'FieldName'  => $fieldNumber,
                'FieldType'  => $type,
                'FieldValue' => $value,
            ];

            $fieldNumber++;
        }

        return $fields;
    }

    /**
     * This method will get the attribute values by product id
     *
     * @param int    $productId
     * @param string $attributeCode
     * @param string $type
     *
     * @return float|int|null|string
     */
    protected function _getProductAttributeValue($productId, $attributeCode, $type)
    {
        try {
            /** @var Product $productModel */
            $productModel = $this->_productFactory->create();
            /** @var ResourceModel\Product $resourceModel */
            $resourceModel = $productModel->getResource();
            $attribute = $resourceModel->getAttribute($attributeCode);
            if (!$attribute) {
                throw new \Exception();
            }

            $attributeValue = $resourceModel->getAttributeRawValue(
                $productId,
                $attributeCode,
                $this->_getStoreId()
            );

            if (!$attributeValue || is_array($attributeValue)) {
                throw new \Exception();
            }

            if ($attribute->usesSource()) {
                // Handle multi-select attributes
                $labels = [];
                foreach (explode(',', $attributeValue) as $optionId) {
                    $labels[] = $attribute->getSource()->getOptionText($optionId);
                }
                $value = implode(',', $labels);
            } else {
                $value = $attributeValue;
            }

            if ($value !== null) {
                $value = $this->_formatCustomField($value, $type);
            }

        } catch (\Exception $e) {
            $value = null;
        }

        return $value;
    }

    /**
     * This method will get the custom fields for the order object
     *
     * @return array
     */
    protected function _getOrderCustomFields()
    {
        $fields = [];
        $customer = $this->_getQuoteCustomer();
        if (!$customer || !$customer->getId()) {
            return $fields;
        }
        $fieldNumber = 1;
        foreach ($this->_config->getCustomerCustomFields() as $field) {
            $attributeCode = isset($field['value']) ? $field['value'] : null;
            if (!$attributeCode) {
                continue;
            }
            $type = isset($field['type']) ? $field['type'] : 'String';
            $value = $this->_getCustomerAttributeValue($customer, $attributeCode, $type);

            if (!$value && $value !== 0) {
                continue;
            }

            $fields[] = [
                'FieldName'  => $fieldNumber,
                'FieldType'  => $type,
                'FieldValue' => $value,
            ];

            $fieldNumber++;
        }

        return $fields;
    }

    /**
     * This method will get the attribute value for either a customer or a customer address
     *
     * @param Customer $customer
     * @param string   $attributeCode
     * @param string   $type
     *
     * @return float|int|null|string
     */
    protected function _getCustomerAttributeValue($customer, $attributeCode, $type)
    {
        try {
            $attributeCodeSegments = explode(':', $attributeCode);
            $attributeCodeType = isset($attributeCodeSegments[0]) ? $attributeCodeSegments[0] : null;
            $attributeCode = isset($attributeCodeSegments[1]) ? $attributeCodeSegments[1] : null;
            if (!$attributeCodeType || !$attributeCode) {
                throw new \Exception();
            }

            switch ($attributeCodeType) {
                case 'address':
                    $address = $customer->getDefaultShippingAddress();
                    $value = $address->getData($attributeCode);
                    break;
                case 'customer':
                    $value = $customer->getData($attributeCode);
                    break;
                default:
                    $value = null;
            }

            if ($value !== null) {
                $value = $this->_formatCustomField($value, $type);
            }
        } catch (\Exception $e) {
            $value = null;
        }

        return $value;
    }

    /**
     * This method will load the customer entity for the quote
     *
     * @return Customer|bool
     */
    protected function _getQuoteCustomer()
    {
        $customerId = (int) $this->getQuote()->getCustomerId();
        if (!$customerId) {
            return false;
        }

        /** @var Customer $customerModel */
        $customerModel = $this->_customerFactory->create();
        $customer = $customerModel->load($customerId);
        if (!$customer->getId()) {
              return false;
        }

        return $customer;
    }

    /**
     * This method will format the custom field value based on its data type
     *
     * @param string $value
     * @param string $type
     *
     * @return float|int|string
     */
    protected function _formatCustomField($value, $type)
    {
        switch ($type) {
            case 'Integer':
                $value = (int) $value;
                break;
            case 'double':
                $value = (float) $value;
                break;
            default:
                $value = (string) $value;
        }

        return $value;
    }

    /**
     * Get Store Id
     *
     * @return int
     */
    protected function _getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
}
