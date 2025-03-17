<?php
/**
 * Controlador de administración para el módulo CombinacionesCarne
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'combinacionescarne/classes/CombinacionesCarneModel.php';

class AdminCombinacionesCarneController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'combinacionescarne_product';
        $this->className = 'CombinacionesCarneModel';
        $this->identifier = 'id_product';
        $this->lang = false;
        
        parent::__construct();
        
        $this->meta_title = $this->l('Configuración de Combinaciones Carne');
        $this->toolbar_title = $this->l('Configuración de Combinaciones Carne');
        
        // Definimos los filtros para la lista
        $this->fields_list = array(
            'id_product' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'product_name' => array(
                'title' => $this->l('Producto'),
                'havingFilter' => true,
            ),
            'precio_por_kilo' => array(
                'title' => $this->l('Precio por kilo (€)'),
                'type' => 'price',
                'align' => 'right',
            ),
            'active' => array(
                'title' => $this->l('Activo'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
            ),
        );
        
        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Eliminar seleccionados'),
                'confirm' => $this->l('¿Eliminar los elementos seleccionados?'),
                'icon' => 'icon-trash'
            ),
            'enableSelection' => array(
                'text' => $this->l('Activar seleccionados'),
                'icon' => 'icon-power-off text-success'
            ),
            'disableSelection' => array(
                'text' => $this->l('Desactivar seleccionados'),
                'icon' => 'icon-power-off text-danger'
            ),
        );
        
        // Botones de acción
        $this->actions = array('edit', 'delete');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }
    
    /**
     * Inicializa la lista
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_product'] = array(
                'href' => self::$currentIndex . '&addcombinacionescarne_product&token=' . $this->token,
                'desc' => $this->l('Añadir nuevo'),
                'icon' => 'process-icon-new'
            );
        }
        
        parent::initPageHeaderToolbar();
    }
    
    /**
     * Renderiza el formulario de edición
     */
    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }
        
        // Obtenemos la información del producto
        $id_product = (int) Tools::getValue('id_product', $obj->id_product);
        $product = new Product($id_product, false, $this->context->language->id);
        
        if (!Validate::isLoadedObject($product)) {
            $this->errors[] = $this->l('Producto no válido');
            return parent::renderForm();
        }
        
        // Obtenemos la configuración actual
        $config = CombinacionesCarneModel::getProductConfig($id_product);
        
        // Obtenemos todos los grupos de atributos
        $attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
        
        // Preparamos las opciones para los select
        $attribute_groups_options = array();
        $attributes_options = array();
        
        foreach ($attribute_groups as $group) {
            $attribute_groups_options[$group['id_attribute_group']] = $group['name'];
            
            // Obtenemos los atributos de este grupo
            $attributes = Attribute::getAttributes($this->context->language->id, $group['id_attribute_group']);
            
            foreach ($attributes as $attribute) {
                $key = $group['id_attribute_group'] . '_' . $attribute['id_attribute'];
                $attributes_options[$key] = $group['name'] . ' - ' . $attribute['name'];
            }
        }
        
        // Definimos el formulario
        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Configuración de combinaciones para ') . $product->name,
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'hidden',
                    'name' => 'id_product',
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Producto'),
                    'name' => 'product_name',
                    'disabled' => true,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Precio por kilo (€)'),
                    'name' => 'precio_por_kilo',
                    'suffix' => '€',
                    'desc' => $this->l('Si es 0, se utilizará el precio base del producto'),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Grupo de atributos para Envasado'),
                    'name' => 'id_attribute_group_envasado',
                    'options' => array(
                        'query' => array_map(function($key, $value) {
                            return array(
                                'id_option' => $key,
                                'name' => $value,
                            );
                        }, array_keys($attribute_groups_options), $attribute_groups_options),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Atributo para Envasado Granel'),
                    'name' => 'id_attribute_envasado_granel',
                    'options' => array(
                        'query' => array_map(function($key, $value) {
                            return array(
                                'id_option' => $key,
                                'name' => $value,
                            );
                        }, array_keys($attributes_options), $attributes_options),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Atributo para Envasado Individual'),
                    'name' => 'id_attribute_envasado_individual',
                    'options' => array(
                        'query' => array_map(function($key, $value) {
                            return array(
                                'id_option' => $key,
                                'name' => $value,
                            );
                        }, array_keys($attributes_options), $attributes_options),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Grupo de atributos para Peso'),
                    'name' => 'id_attribute_group_peso',
                    'options' => array(
                        'query' => array_map(function($key, $value) {
                            return array(
                                'id_option' => $key,
                                'name' => $value,
                            );
                        }, array_keys($attribute_groups_options), $attribute_groups_options),
                        'id' => 'id_option',
                        'name' => 'name',
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Activo'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Sí')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                    'desc' => $this->l('Activa el cálculo automático de precios para este producto'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Guardar'),
                'id' => 'submitAddcombinacionescarne_product',
            ),
            'buttons' => array(
                'calculate' => array(
                    'title' => $this->l('Guardar y calcular precios'),
                    'name' => 'submitCalculate',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-cogs'
                ),
            ),
        );
        
        // Valores por defecto
        $this->fields_value = array(
            'id_product' => $id_product,
            'product_name' => $product->name,
            'precio_por_kilo' => $config['precio_por_kilo'],
            'id_attribute_group_envasado' => $config['id_attribute_group_envasado'],
            'id_attribute_envasado_granel' => $config['id_attribute_group_envasado'] . '_' . $config['id_attribute_envasado_granel'],
            'id_attribute_envasado_individual' => $config['id_attribute_group_envasado'] . '_' . $config['id_attribute_envasado_individual'],
            'id_attribute_group_peso' => $config['id_attribute_group_peso'],
            'active' => $config['active'],
        );
        
        return parent::renderForm();
    }
    
    /**
     * Procesa el formulario
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAddcombinacionescarne_product') || Tools::isSubmit('submitCalculate')) {
            $id_product = (int) Tools::getValue('id_product');
            
            if ($id_product <= 0) {
                $this->errors[] = $this->l('ID de producto no válido');
                return false;
            }
            
            // Preparamos los datos
            $envasado_granel_parts = explode('_', Tools::getValue('id_attribute_envasado_granel'));
            $envasado_individual_parts = explode('_', Tools::getValue('id_attribute_envasado_individual'));
            
            $config = array(
                'precio_por_kilo' => (float) Tools::getValue('precio_por_kilo'),
                'id_attribute_group_envasado' => (int) Tools::getValue('id_attribute_group_envasado'),
                'id_attribute_envasado_granel' => isset($envasado_granel_parts[1]) ? (int) $envasado_granel_parts[1] : 0,
                'id_attribute_envasado_individual' => isset($envasado_individual_parts[1]) ? (int) $envasado_individual_parts[1] : 0,
                'id_attribute_group_peso' => (int) Tools::getValue('id_attribute_group_peso'),
                'active' => (int) Tools::getValue('active')
            );
            
            // Guardamos la configuración
            if (CombinacionesCarneModel::saveProductConfig($id_product, $config)) {
                // Si se solicitó calcular precios, lo hacemos
                if (Tools::isSubmit('submitCalculate')) {
                    CombinacionesCarneModel::updateCombinationPrices($id_product);
                    $this->confirmations[] = $this->l('Configuración guardada y precios calculados con éxito');
                } else {
                    $this->confirmations[] = $this->l('Configuración guardada con éxito');
                }
            } else {
                $this->errors[] = $this->l('Error al guardar la configuración');
            }
        }
        
        return parent::postProcess();
    }
    
    /**
     * Obtiene la lista de productos
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
        
        // Añadimos el nombre del producto
        foreach ($this->_list as &$item) {
            $product = new Product((int) $item['id_product'], false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $item['product_name'] = $product->name;
            } else {
                $item['product_name'] = $this->l('Producto no encontrado');
            }
        }
    }
    
    /**
     * Procesa las acciones en masa
     */
    public function processBulkEnableSelection()
    {
        return $this->processBulkStatusSelection(1);
    }
    
    public function processBulkDisableSelection()
    {
        return $this->processBulkStatusSelection(0);
    }
    
    protected function processBulkStatusSelection($status)
    {
        if ($status == 1) {
            $action = $this->l('activados');
        } else {
            $action = $this->l('desactivados');
        }
        
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $success = true;
            foreach ($this->boxes as $id) {
                $config = CombinacionesCarneModel::getProductConfig((int) $id);
                $config['active'] = (int) $status;
                $success &= CombinacionesCarneModel::saveProductConfig((int) $id, $config);
            }
            
            if ($success) {
                $this->confirmations[] = sprintf($this->l('Los productos seleccionados han sido %s correctamente'), $action);
            } else {
                $this->errors[] = sprintf($this->l('Error al %s los productos seleccionados'), $status ? $this->l('activar') : $this->l('desactivar'));
            }
        } else {
            $this->errors[] = $this->l('No hay productos seleccionados');
        }
        
        return true;
    }
}