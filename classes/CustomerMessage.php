<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CustomerMessageCore
 *
 * @since 1.0.0
 */
class CustomerMessageCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_customer_thread;
    public $id_employee;
    public $message;
    public $file_name;
    public $ip_address;
    public $user_agent;
    public $private;
    public $date_add;
    public $date_upd;
    public $read;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_message',
        'primary' => 'id_customer_message',
        'fields'  => [
            'id_employee'        => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer_thread' => ['type' => self::TYPE_INT],
            'ip_address'         => ['type' => self::TYPE_STRING, 'validate' => 'isIp2Long', 'size' => 15],
            'message'            => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 16777216],
            'file_name'          => ['type' => self::TYPE_STRING],
            'user_agent'         => ['type' => self::TYPE_STRING],
            'private'            => ['type' => self::TYPE_INT],
            'date_add'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'           => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'read'               => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'id_employee'        => [
                'xlink_resource' => 'employees',
            ],
            'id_customer_thread' => [
                'xlink_resource' => 'customer_threads',
            ],
        ],
    ];

    /**
     * @param int  $idOrder
     * @param bool $private
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMessagesByOrderId($idOrder, $private = true)
    {
        return Db::getInstance()->executeS(
            '
			SELECT cm.*,
				c.`firstname` AS cfirstname,
				c.`lastname` AS clastname,
				e.`firstname` AS efirstname,
				e.`lastname` AS elastname,
				(COUNT(cm.id_customer_message) = 0 AND ct.id_customer != 0) AS is_new_for_me
			FROM `'._DB_PREFIX_.'customer_message` cm
			LEFT JOIN `'._DB_PREFIX_.'customer_thread` ct
				ON ct.`id_customer_thread` = cm.`id_customer_thread`
			LEFT JOIN `'._DB_PREFIX_.'customer` c
				ON ct.`id_customer` = c.`id_customer`
			LEFT OUTER JOIN `'._DB_PREFIX_.'employee` e
				ON e.`id_employee` = cm.`id_employee`
			WHERE ct.id_order = '.(int) $idOrder.'
			'.(!$private ? 'AND cm.`private` = 0' : '').'
			GROUP BY cm.id_customer_message
			ORDER BY cm.date_add DESC
		'
        );
    }

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTotalCustomerMessages($where = null)
    {
        if (is_null($where)) {
            return (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(*)
				FROM '._DB_PREFIX_.'customer_message
				LEFT JOIN `'._DB_PREFIX_.'customer_thread` ct ON (cm.`id_customer_thread` = ct.`id_customer_thread`)
				WHERE 1'.Shop::addSqlRestriction()
            );
        } else {
            return (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(*)
				FROM '._DB_PREFIX_.'customer_message cm
				LEFT JOIN `'._DB_PREFIX_.'customer_thread` ct ON (cm.`id_customer_thread` = ct.`id_customer_thread`)
				WHERE '.$where.Shop::addSqlRestriction()
            );
        }
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!empty($this->file_name)) {
            @unlink(_PS_UPLOAD_DIR_.$this->file_name);
        }

        return parent::delete();
    }
}
