<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class BlockBestSellers extends Module
{
	protected static $cache_best_sellers;

	public function __construct()
	{
		$this->name = 'blockbestsellers';
		$this->tab = 'front_office_features';
		$this->version = '1.7.0';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Top-sellers block');
		$this->description = $this->l('Adds a block displaying your store\'s top-selling products.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		$this->_clearCache('*');

		if (!parent::install()
			|| !$this->registerHook('header')
			|| !$this->registerHook('leftColumn')
			|| !$this->registerHook('actionOrderStatusPostUpdate')
			|| !$this->registerHook('addproduct')
			|| !$this->registerHook('updateproduct')
			|| !$this->registerHook('deleteproduct')
			|| !$this->registerHook('displayHomeTab')
			|| !$this->registerHook('displayHomeTabContent')
			|| !ProductSale::fillProductSales()
		)
			return false;

		Configuration::updateValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', 10);

		return true;
	}

	public function uninstall()
	{
		$this->_clearCache('*');

		return parent::uninstall();
	}

	public function hookAddProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookUpdateProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookDeleteProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		$this->_clearCache('*');
	}

	public function _clearCache($template, $cache_id = null, $compile_id = null)
	{
		parent::_clearCache('blockbestsellers.tpl', 'blockbestsellers-col');
		parent::_clearCache('blockbestsellers-home.tpl', 'blockbestsellers-home');
		parent::_clearCache('tab.tpl', 'blockbestsellers-tab');
	}

	/**
	 * Called in administration -> module -> configure
	 */
	public function getContent()
	{
		$output = '';
		if (Tools::isSubmit('submitBestSellers'))
		{
			Configuration::updateValue('PS_BLOCK_BESTSELLERS_DISPLAY', (int)Tools::getValue('PS_BLOCK_BESTSELLERS_DISPLAY'));
			Configuration::updateValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', (int)Tools::getValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY'));
			$this->_clearCache('*');
			$output .= $this->displayConfirmation($this->l('Settings updated'));
		}

		return $output.$this->renderForm();
	}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Products to display'),
						'name' => 'PS_BLOCK_BESTSELLERS_TO_DISPLAY',
						'desc' => $this->l('Determine the number of product to display in this block'),
						'class' => 'fixed-width-xs',
					),
					array(
						'type' => 'switch',
						'label' => $this->l('Always display this block'),
						'name' => 'PS_BLOCK_BESTSELLERS_DISPLAY',
						'desc' => $this->l('Show the block even if no best sellers are available.'),
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('Disabled')
							)
						),
					)
				),
				'submit' => array(
					'title' => $this->l('Save')
				)
			)
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitBestSellers';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
		return array(
			'PS_BLOCK_BESTSELLERS_TO_DISPLAY' => (int)Tools::getValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', Configuration::get('PS_BLOCK_BESTSELLERS_TO_DISPLAY')),
			'PS_BLOCK_BESTSELLERS_DISPLAY' => (int)Tools::getValue('PS_BLOCK_BESTSELLERS_DISPLAY', Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY')),
		);
	}

	public function hookHeader($params)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return;
		if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index')
			$this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
		$this->context->controller->addCSS($this->_path.'blockbestsellers.css', 'all');
	}

	public function hookDisplayHomeTab($params)
	{
		if (!$this->isCached('tab.tpl', $this->getCacheId('blockbestsellers-tab')))
		{
			BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang] = $this->getBestSellers($params);
			$this->smarty->assign('best_sellers', BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang] );
		}

		if (BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang]  === false)
			return false;

		return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blockbestsellers-tab'));
	}

	public function hookDisplayHomeTabContent($params)
	{
		if (!$this->isCached('blockbestsellers-home.tpl', $this->getCacheId('blockbestsellers-home')))
		{
			$this->smarty->assign(array(
				'best_sellers' => BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang] ,
				'homeSize' => Image::getSize(ImageType::getFormatedName('home'))
			));
		}

		if (BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang]  === false)
			return false;

		return $this->display(__FILE__, 'blockbestsellers-home.tpl', $this->getCacheId('blockbestsellers-home'));
	}

	public function hookRightColumn($params)
	{
		if (!$this->isCached('blockbestsellers.tpl', $this->getCacheId('blockbestsellers-col')))
		{
			if (!isset(BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang]))
				BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang] = $this->getBestSellers($params);
			$this->smarty->assign(array(
				'best_sellers' => BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang],
				'display_link_bestsellers' => Configuration::get('PS_DISPLAY_BEST_SELLERS'),
				'mediumSize' => Image::getSize(ImageType::getFormatedName('medium')),
				'smallSize' => Image::getSize(ImageType::getFormatedName('small'))
			));
		}

		if (BlockBestSellers::$cache_best_sellers[$params["cart"]->id_lang] === false)
			return false;

		return $this->display(__FILE__, 'blockbestsellers.tpl', $this->getCacheId('blockbestsellers-col'));
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}

	protected function getBestSellers($params)
	{
		if (Configuration::get('PS_CATALOG_MODE'))
			return false;

		if (!($result = getBestSalesLight((int)$params['cookie']->id_lang, 0, (int)Configuration::get('PS_BLOCK_BESTSELLERS_TO_DISPLAY'))))
			return (Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY') ? array() : false);

		$currency = new Currency($params['cookie']->id_currency);
		$usetax = (Product::getTaxCalculationMethod((int)$this->context->customer->id) != PS_TAX_EXC);
		foreach ($result as &$row)
			$row['price'] = Tools::displayPrice(Product::getPriceStatic((int)$row['id_product'], $usetax), $currency);

		return $result;
	}
}


/*
** Get required informations on best sales products
**
** @param integer $id_lang Language id
** @param integer $page_number Start from (optional)
** @param integer $nb_products Number of products to return (optional)
** @return array keys : id_product, link_rewrite, name, id_image, legend, sales, ean13, upc, link
*/

function getBestSalesLight($id_lang, $page_number = 0, $nb_products = 10, Context $context = null)
{
    if (!$context)
        $context = Context::getContext();
    if ($page_number < 0) $page_number = 0;
    if ($nb_products < 1) $nb_products = 10;

    $sql_groups = '';
    if (Group::isFeatureActive())
    {
        $groups = FrontController::getCurrentCustomerGroups();
        $sql_groups = 'AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
    }

    //Subquery: get product ids in a separate query to (greatly!) improve performances and RAM usage
    $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
    SELECT cp.`id_product`
    FROM `'._DB_PREFIX_.'category_product` cp
    LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = cp.`id_category`)
    WHERE cg.`id_group` '.$sql_groups
    .' AND cp.`id_category` = '.($id_lang == 2? 31:43) );
    
    $ids = array();
    foreach ($products as $product)
        $ids[$product['id_product']] = 1;

    $ids = array_keys($ids);        
    sort($ids);
    $ids = count($ids) > 0 ? implode(',', $ids) : 'NULL';

    //Main query
    $sql = '
    SELECT
        p.id_product,  MAX(product_attribute_shop.id_product_attribute) id_product_attribute, pl.`link_rewrite`, pl.`name`, pl.`description_short`, product_shop.`id_category_default`,
        MAX(image_shop.`id_image`) id_image, il.`legend`,
        ps.`quantity` AS sales, p.`ean13`, p.`upc`, cl.`link_rewrite` AS category, p.show_price, p.available_for_order, IFNULL(stock.quantity, 0) as quantity, p.customizable,
        IFNULL(pa.minimal_quantity, p.minimal_quantity) as minimal_quantity, stock.out_of_stock,
        product_shop.`date_add` > "'.date('Y-m-d', strtotime('-'.(Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int)Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY')).'" as new
    FROM `'._DB_PREFIX_.'product_sale` ps
    LEFT JOIN `'._DB_PREFIX_.'product` p ON ps.`id_product` = p.`id_product`
    '.Shop::addSqlAssociation('product', 'p').'
    LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
        ON (p.`id_product` = pa.`id_product`)
    '.Shop::addSqlAssociation('product_attribute', 'pa', false, 'product_attribute_shop.`default_on` = 1').'
    '.Product::sqlStock('p', 'product_attribute_shop', false, $context->shop).'
    LEFT JOIN `'._DB_PREFIX_.'product_lang` pl
        ON p.`id_product` = pl.`id_product`
        AND pl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('pl').'
    LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product`)'.
    Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1').'
    LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$id_lang.')
    LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
        ON cl.`id_category` = product_shop.`id_category_default`
        AND cl.`id_lang` = '.(int)$id_lang.Shop::addSqlRestrictionOnLang('cl').'
    WHERE product_shop.`active` = 1
    AND p.`visibility` != \'none\'
    AND p.`id_product` IN ('.$ids.')
    GROUP BY product_shop.id_product
    ORDER BY sales DESC
    LIMIT '.(int)($page_number * $nb_products).', '.(int)$nb_products;

    if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql))
        return false;

    return Product::getProductsProperties($id_lang, $result);
}
