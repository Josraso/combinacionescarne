<?php
/**
 * Controlador para gestionar la configuración de combinaciones de productos cárnicos
 * 
 * @author   Claude AI
 * @version  1.0.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'combinacionescarne/classes/CombinacionesCarneModel.php';

class AdminCombinacionesCarneProductController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'combinacionescarne_product';
        $this->identifier = 'id_product';
        $this->className = 'CombinacionesCarneModel';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
        
        parent::__construct();
        
        $this->_orderBy = 'id_product';
        $this->_orderWay = 'DESC';
        $this->fields_list = [
            'id_product' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->l('Producto'),
                'filter_key' => 'pl!name',
            ],
            'reference' => [
                'title' => $this->l('Referencia'),
                'filter_key' => 'p!reference',
            ],
            'precio_por_kilo' => [
                'title' => $this->l('Precio por kilo'),
                'align' => 'right',
                'type' => 'price',
            ],
            'active' => [
                'title' => $this->l('Activo'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'filter_key' => 'cp!active',
            ],
        ];
        
        $this->bulk_actions = [
            'enable' => [
                'text' => $this->l('Activar seleccionados'),
                'icon' => 'icon-power-off text-success',
            ],
            'disable' => [
                'text' => $this->l('Desactivar seleccionados'),
                'icon' => 'icon-power-off text-danger',
            ],
        ];
    }
    
    /**
     * Inicializar lista para añadir los joins necesarios
     */
    public function initProcess()
    {
        parent::initProcess();
        
        if ($this->action == 'edit') {
            $id_product = (int)Tools::getValue('id_product');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $id_product . '&updateproduct');
        }
    }
    
    /**
     * Procesar acciones masivas
     */
    public function processBulkEnable()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id_product) {
                $config = CombinacionesCarneModel::getProductConfig($id_product);
                $config['active'] = 1;
                CombinacionesCarneModel::saveProductConfig($id_product, $config);
            }
        }
        
        return parent::processBulkEnable();
    }
    
    public function processBulkDisable()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id_product) {
                $config = CombinacionesCarneModel::getProductConfig($id_product);
                $config['active'] = 0;
                CombinacionesCarneModel::saveProductConfig($id_product, $config);
            }
        }
        
        return parent::processBulkDisable();
    }
    
    /**
     * Obtener atributos de un grupo para PS8
     * Esta función reemplaza Attribute::getAttributes() para PS8
     */
    private function getAttributesForGroup($id_lang, $id_attribute_group)
    {
        $sql = "SELECT a.*, al.name
                FROM `" . _DB_PREFIX_ . "attribute` a
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_lang` al 
                    ON (a.id_attribute = al.id_attribute AND al.id_lang = " . (int)$id_lang . ")
                WHERE a.id_attribute_group = " . (int)$id_attribute_group . "
                ORDER BY a.position ASC";
                
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Personalizar SQL para la lista
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        // Join con la tabla de productos para obtener información adicional
        $this->_join = '
            LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product` = a.`id_product`)
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.(int)$id_lang.' AND pl.`id_shop` = '.(int)$this->context->shop->id.')
            LEFT JOIN `'._DB_PREFIX_.'combinacionescarne_product` cp ON (p.`id_product` = cp.`id_product`)
        ';
        
        $this->_select = '
            pl.`name`, 
            p.`reference`,
            IFNULL(cp.`precio_por_kilo`, 0) as precio_por_kilo,
            IFNULL(cp.`active`, 0) as active
        ';
        
        $this->_where = 'AND p.`id_product` IN (
            SELECT DISTINCT pa.`id_product` 
            FROM `'._DB_PREFIX_.'product_attribute` pa
        )';
        
        // Filtrar solo productos con combinaciones
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }
    
    /**
     * Personalizar botones de acción
     */
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Quitar el botón de añadir nuevo, ya que se configura en la página del producto
        if (isset($this->toolbar_btn['new'])) {
            unset($this->toolbar_btn['new']);
        }
    }
    
    /**
     * Calcular precios para un producto
     */
    public function postProcess()
    {
        if (Tools::isSubmit('calculate_prices')) {
            $id_product = (int)Tools::getValue('id_product');
            if ($id_product > 0) {
                if (CombinacionesCarneModel::updateCombinationPrices($id_product)) {
                    $this->confirmations[] = $this->l('Precios calculados correctamente.');
                } else {
                    $this->errors[] = $this->l('Error al calcular los precios.');
                }
            }
        }
        
        if (Tools::isSubmit('statuscombinacionescarne_product')) {
            $id_product = (int)Tools::getValue('id_product');
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            $config['active'] = !$config['active'];
            if (CombinacionesCarneModel::saveProductConfig($id_product, $config)) {
                $this->confirmations[] = $this->l('Estado actualizado correctamente.');
                if ($config['active']) {
                    CombinacionesCarneModel::updateCombinationPrices($id_product);
                }
            }
        }
        
        return parent::postProcess();
    }
    
    /**
     * Mostrar botón para calcular precios
     */
    public function displayCalculateLink($token, $id_product)
    {
        if (!isset(self::$cache_lang['Calculate'])) {
            self::$cache_lang['Calculate'] = $this->l('Calcular precios');
        }

        $this->context->smarty->assign([
            'href' => self::$currentIndex . '&' . $this->identifier . '=' . $id_product . '&calculate_prices&token=' . $token,
            'action' => self::$cache_lang['Calculate'],
            'id' => $id_product
        ]);

        return $this->context->smarty->fetch('helpers/list/list_action_supply_order_change_state.tpl');
    }
}
