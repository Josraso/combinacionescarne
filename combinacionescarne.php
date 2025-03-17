<?php
/**
 * Módulo CombinacionesCarne - Versión para PrestaShop 8
 * 
 * Automatiza el cálculo de precios para combinaciones de productos cárnicos
 * basado en tipo de envasado y peso
 * 
 * @author   Claude AI
 * @version  1.0.2
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Incluimos el modelo correctamente
require_once dirname(__FILE__) . '/classes/CombinacionesCarneModel.php';

class CombinacionesCarne extends Module
{
    public function __construct()
    {
        $this->name = 'combinacionescarne';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.2';
        $this->author = 'Claude AI';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];

        parent::__construct();

        $this->displayName = $this->l('Combinaciones Automáticas para Carne');
        $this->description = $this->l('Automatiza el cálculo de precios para combinaciones de productos cárnicos.');
        $this->confirmUninstall = $this->l('¿Estás seguro que quieres desinstalar este módulo?');
    }

    /**
     * Instalación del módulo
     */
    public function install()
    {
        // Configuraciones por defecto
        Configuration::updateValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL', 1.5);
        Configuration::updateValue('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
        Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
        Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
        Configuration::updateValue('COMBINACIONESCARNE_GRUPO_PESO', 0);
        
        // Creamos la tabla para guardar la configuración por producto
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "combinacionescarne_product` (
            `id_product` INT(10) UNSIGNED NOT NULL,
            `precio_por_kilo` DECIMAL(20,6) NOT NULL DEFAULT '0.000000',
            `id_attribute_envasado_granel` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `id_attribute_envasado_individual` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `id_attribute_group_envasado` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `id_attribute_group_peso` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1',
            PRIMARY KEY (`id_product`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";
        
        // Creamos la tabla para el mapeo de pesos (cambiando peso_kg a peso_gramos)
        $sql2 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "combinacionescarne_peso_map` (
            `id_attribute` INT(10) UNSIGNED NOT NULL,
            `peso_gramos` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY (`id_attribute`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";
        
        // Registramos los hooks para actualizar productos y combinaciones
        return parent::install() &&
            Db::getInstance()->execute($sql) &&
            Db::getInstance()->execute($sql2) &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionProductAttributeUpdate') &&
            $this->registerHook('actionProductAttributeDelete') &&
            $this->registerHook('actionProductAttributeAdd') &&
            $this->registerHook('actionProductSave') &&
            $this->registerHook('actionProductDelete') &&
            $this->registerHook('displayBackOfficeHeader');
    }

    /**
     * Desinstalación del módulo
     */
    public function uninstall()
    {
        Configuration::deleteByName('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
        
        // Eliminamos las tablas
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "combinacionescarne_product`;";
        $sql2 = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "combinacionescarne_peso_map`;";
        
        return parent::uninstall() &&
            Db::getInstance()->execute($sql) &&
            Db::getInstance()->execute($sql2);
    }

    /**
     * Carga los assets en el back-office
     */
    public function hookDisplayBackOfficeHeader()
    {
        $controller_name = $this->context->controller->controller_name;
        
        if ($controller_name == 'AdminModules' && Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/admin.js');
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
            
            // Agregar variables JavaScript para traducciones y URLs
            Media::addJsDef([
                'combinacionescarne' => [
                    'ajax_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&ajax=1',
                    'token' => Tools::getAdminTokenLite('AdminModules'),
                    'product_id' => (int)Tools::getValue('id_product', 0),
                    'i18n' => [
                        'error' => $this->l('Error'),
                        'success' => $this->l('Éxito'),
                        'updating' => $this->l('Actualizando...'),
                        'calculating' => $this->l('Calculando...'),
                        'selectAttribute' => $this->l('Por favor, seleccione un atributo'),
                        'invalidWeight' => $this->l('El peso debe ser un número positivo'),
                        'selectEnvasadoGroup' => $this->l('Seleccione un grupo de atributos para envasado'),
                        'selectGranelAttribute' => $this->l('Seleccione un atributo para envasado a granel'),
                        'selectIndividualAttribute' => $this->l('Seleccione un atributo para envasado individual'),
                        'selectPesoGroup' => $this->l('Seleccione un grupo de atributos para peso'),
                        'editWeightMapping' => $this->l('Editar Mapeo de Peso'),
                        'addNewWeightMapping' => $this->l('Añadir Nuevo Mapeo de Peso')
                    ]
                ]
            ]);
        }
    }

    /**
     * Carga el formulario de configuración
     */
    public function getContent()
    {
        $output = '';
        $errors = [];
        $success = false;
        
        // Identificamos la acción solicitada
        $action = Tools::getValue('action', 'general');
        $id_product = (int) Tools::getValue('id_product', 0);
        
        // Procesamiento AJAX
        if (Tools::getValue('ajax') == 1) {
            return $this->processAjaxRequests();
        }
        
        // Procesamiento del formulario de configuración general
        if (Tools::isSubmit('submit' . $this->name)) {
            $precio_individual = (float) Tools::getValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
            
            if ($precio_individual < 0) {
                $errors[] = $this->l('El valor del precio de envasado individual debe ser positivo.');
            } else {
                // Guardamos el precio de envasado individual
                Configuration::updateValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL', $precio_individual);
                
                // Guardamos los grupos de atributos globales
                Configuration::updateValue('COMBINACIONESCARNE_GRUPO_ENVASADO', (int) Tools::getValue('COMBINACIONESCARNE_GRUPO_ENVASADO', 0));
                Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', (int) Tools::getValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0));
                Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', (int) Tools::getValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0));
                Configuration::updateValue('COMBINACIONESCARNE_GRUPO_PESO', (int) Tools::getValue('COMBINACIONESCARNE_GRUPO_PESO', 0));
                
                $success = true;
            }
        }
        
        // Procesamiento del formulario de gestión de mapeos
        if (Tools::isSubmit('submitMapeoPeso')) {
            $id_attribute = (int) Tools::getValue('id_attribute');
            $peso_gramos = (int) Tools::getValue('peso_gramos');
            
            if ($id_attribute <= 0) {
                $errors[] = $this->l('Por favor, seleccione un atributo válido.');
            } else if ($peso_gramos <= 0) {
                $errors[] = $this->l('El peso debe ser un valor positivo.');
            } else {
                if (CombinacionesCarneModel::savePesoMapeo($id_attribute, $peso_gramos)) {
                    $success = true;
                } else {
                    $errors[] = $this->l('Error al guardar el mapeo de peso.');
                }
            }
        }
        
        // Procesamiento de eliminación de mapeo
        if (Tools::isSubmit('deleteMapeoPeso')) {
            $id_attribute = (int) Tools::getValue('id_attribute');
            
            if ($id_attribute > 0) {
                if (CombinacionesCarneModel::deletePesoMapeo($id_attribute)) {
                    $success = true;
                } else {
                    $errors[] = $this->l('Error al eliminar el mapeo de peso.');
                }
            }
        }
        
        // Procesamiento del formulario de configuración de producto
        if (Tools::isSubmit('submitProductConfig') || Tools::isSubmit('submitCalculate')) {
            $id_product = (int) Tools::getValue('id_product');
            
            if ($id_product <= 0) {
                $errors[] = $this->l('Por favor, seleccione un producto válido.');
            } else {
                $active = (int) Tools::getValue('active', 1); // Default a 1 (activado)
                $override_global_config = (int) Tools::getValue('override_global_config', 0);
                
                // Obtenemos los valores globales para usar como valores predeterminados
                $id_attribute_group_envasado = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO');
                $id_attribute_envasado_granel = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL');
                $id_attribute_envasado_individual = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL');
                $id_attribute_group_peso = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO');
                
                // Si se enviaron valores específicos para el producto, los usamos
                if ($override_global_config) {
                    $id_attribute_group_envasado = (int) Tools::getValue('id_attribute_group_envasado', $id_attribute_group_envasado);
                    $id_attribute_envasado_granel = (int) Tools::getValue('id_attribute_envasado_granel', $id_attribute_envasado_granel);
                    $id_attribute_envasado_individual = (int) Tools::getValue('id_attribute_envasado_individual', $id_attribute_envasado_individual);
                    $id_attribute_group_peso = (int) Tools::getValue('id_attribute_group_peso', $id_attribute_group_peso);
                }
                
                // Preparamos los datos
                $config = array(
                    'precio_por_kilo' => 0, // Ya no usamos este campo
                    'id_attribute_group_envasado' => $id_attribute_group_envasado,
                    'id_attribute_envasado_granel' => $id_attribute_envasado_granel,
                    'id_attribute_envasado_individual' => $id_attribute_envasado_individual,
                    'id_attribute_group_peso' => $id_attribute_group_peso,
                    'active' => $active
                );
                
                // Guardamos la configuración
                if (CombinacionesCarneModel::saveProductConfig($id_product, $config)) {
                    // Si se solicitó, calculamos los precios
                    if (Tools::isSubmit('submitCalculate')) {
                        if (CombinacionesCarneModel::updateCombinationPrices($id_product)) {
                            $success = true;
                        } else {
                            $errors[] = $this->l('Error al calcular los precios de las combinaciones.');
                        }
                    } else {
                        $success = true;
                    }
                } else {
                    $errors[] = $this->l('Error al guardar la configuración del producto.');
                }
            }
        }
        
        // Asignar variables comunes a todas las vistas
        $this->context->smarty->assign([
            'module_path' => $this->_path,
            'module_version' => $this->version,
            'current_action' => $action,
            'current_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
            'module_confirmation' => $success ? $this->l('Configuración actualizada correctamente.') : null,
            'module_errors' => !empty($errors) ? $errors : [],
        ]);
        
        // Renderizar navegación común
        $navigation = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/navigation.tpl');
        
        // Cargamos la sección correspondiente según la acción
        if ($action == 'mapeos') {
            return $navigation . $this->renderMapeoPesoConfig();
        } else if ($action == 'product' && $id_product > 0) {
            // Añadir nombre del producto para la navegación
            $product = new Product($id_product, false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $this->context->smarty->assign('product_name', $product->name);
            }
            
            // Re-renderizar navegación con el nombre del producto
            $navigation = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/navigation.tpl');
            
            return $navigation . $this->renderProductConfiguration($id_product);
        } else {
            // Sección principal con visión general
            return $navigation . $this->renderGeneralConfig();
        }
    }

    /**
     * Procesa peticiones AJAX
     */
    private function processAjaxRequests()
    {
        header('Content-Type: application/json');
        
        $action = Tools::getValue('action');
        $id_product = (int) Tools::getValue('id_product', 0);
        $response = ['success' => false, 'message' => ''];
        
        if ($action == 'calculatePrices' && $id_product > 0) {
            try {
                $result = CombinacionesCarneModel::updateCombinationPrices($id_product);
                if ($result) {
                    $response = [
                        'success' => true,
                        'message' => $this->l('Precios actualizados correctamente')
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => $this->l('No fue necesario actualizar los precios. Ya están correctos.')
                    ];
                }
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => $this->l('Error al actualizar los precios') . ': ' . $e->getMessage()
                ];
            }
        } elseif ($action == 'updateCombination') {
            $id_product_attribute = (int) Tools::getValue('id_product_attribute', 0);
            
            if ($id_product > 0 && $id_product_attribute > 0) {
                try {
                    $result = CombinacionesCarneModel::updateCombinationPrice($id_product, $id_product_attribute);
                    if ($result) {
                        $response = [
                            'success' => true,
                            'message' => $this->l('Combinación actualizada correctamente')
                        ];
                    } else {
                        $response = [
                            'success' => false,
                            'message' => $this->l('No fue necesario actualizar la combinación. Ya está correcta.')
                        ];
                    }
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $this->l('Error al actualizar la combinación') . ': ' . $e->getMessage()
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => $this->l('ID de producto o combinación no válido')
                ];
            }
        } elseif ($action == 'checkCombinationNeedsUpdate') {
            $id_product_attribute = (int) Tools::getValue('id_product_attribute', 0);
            
            if ($id_product > 0 && $id_product_attribute > 0) {
                try {
                    $needsUpdate = CombinacionesCarneModel::combinationNeedsUpdate($id_product, $id_product_attribute);
                    $response = [
                        'success' => true,
                        'needsUpdate' => $needsUpdate
                    ];
                } catch (Exception $e) {
                    $response = [
                        'success' => false,
                        'message' => $this->l('Error al comprobar la combinación') . ': ' . $e->getMessage()
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => $this->l('ID de producto o combinación no válido')
                ];
            }
        }
        
        return json_encode($response);
    }

    /**
     * Renderiza la página principal con configuración general
     */
    protected function renderGeneralConfig()
    {
        // Obtener estadísticas para el panel de información
        $productCount = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "combinacionescarne_product` WHERE active = 1"
        );
        
        $combinationsCount = 0;
        $productIds = Db::getInstance()->executeS(
            "SELECT id_product FROM `" . _DB_PREFIX_ . "combinacionescarne_product` WHERE active = 1"
        );
        
        if (!empty($productIds)) {
            $productIdsList = array_column($productIds, 'id_product');
            $combinationsCount = Db::getInstance()->getValue(
                "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "product_attribute` 
                 WHERE id_product IN (" . implode(',', array_map('intval', $productIdsList)) . ")"
            );
        }
        
        $mappingsCount = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`"
        );
        
        $packagingPrice = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
        
        // Obtener productos con combinaciones
        $products = $this->getProductsWithCombinations();
        foreach ($products as &$product) {
            $config = CombinacionesCarneModel::getProductConfig($product['id_product']);
            $product['config_active'] = $config['active'];
        }
        
        // Obtener grupos de atributos y sus valores
        $attribute_groups = $this->getAttributeGroupsWithValues();
        
        // Asignar variables a la plantilla
        $this->context->smarty->assign([
            'productCount' => $productCount,
            'combinationsCount' => $combinationsCount,
            'mappingsCount' => $mappingsCount,
            'packagingPrice' => $packagingPrice,
            'products' => $products,
            'attribute_groups' => $attribute_groups,
            'config_values' => $this->getConfigFormValues(),
        ]);
        
        // Renderizar la plantilla
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/overview.tpl');
    }

    /**
     * Renderiza la sección de mapeo de pesos
     */
    protected function renderMapeoPesoConfig()
    {
        // Obtener todos los grupos de atributos con sus valores
        $attribute_groups = $this->getAttributeGroupsWithValues();
        
        // Obtener mapeos existentes
        $mapeos_detallados = CombinacionesCarneModel::getPesoMapeosDetallados();
        
        // Asignar variables a la plantilla
        $this->context->smarty->assign([
            'attribute_groups' => $attribute_groups,
            'mapeos_detallados' => $mapeos_detallados,
            'current_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&action=mapeos',
        ]);
        
        // Renderizar la plantilla
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/weight_mapping.tpl');
    }

/**
 * Renderiza la configuración para un producto específico
 */
protected function renderProductConfiguration($id_product)
{
    // Obtener datos del producto
    $product = new Product($id_product, false, $this->context->language->id);
    
    if (!Validate::isLoadedObject($product)) {
        return $this->l('Producto no válido');
    }
    
    // Obtener configuración guardada
    $product_config = CombinacionesCarneModel::getProductConfig($id_product);
    
    // Determinar si se usa configuración personalizada
    $product_config['override_global'] = false;
    if ($product_config['id_attribute_group_envasado'] != (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO') ||
        $product_config['id_attribute_envasado_granel'] != (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL') ||
        $product_config['id_attribute_envasado_individual'] != (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL') ||
        $product_config['id_attribute_group_peso'] != (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO')) {
        $product_config['override_global'] = false;
    }
    
    // Obtener precio base del producto y precio de envasado individual
    $tax_rate = CombinacionesCarneModel::getTaxRate($id_product);
$tax_multiplier = 1 + ($tax_rate / 100);
$precio_base_producto = $product->price * $tax_multiplier;
    $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
    
    // Obtener grupos de atributos y sus valores
    $attribute_groups = $this->getAttributeGroupsWithValues();
    
    // Obtener atributos específicos para envasado
    $envasado_attributes = [];
    foreach ($attribute_groups as $group) {
        foreach ($group['attributes'] as $attribute) {
            $envasado_attributes[] = [
                'id_attribute' => $attribute['id_attribute'],
                'id_attribute_group' => $group['id_attribute_group'],
                'name' => $group['name'] . ' - ' . $attribute['name']
            ];
        }
    }
    
    // Obtener combinaciones del producto
    $combinations = $this->getProductCombinations($id_product, $product_config);
    
    // Asignar variables a la plantilla
    $this->context->smarty->assign([
        'id_product' => $id_product,
        'product' => $product,
        'product_config' => $product_config,
        'precio_base_producto' => $precio_base_producto,
        'precio_individual' => $precio_individual,
        'attribute_groups' => $attribute_groups,
        'envasado_attributes' => $envasado_attributes,
        'combinations' => $combinations,
        'current_url' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&action=product&id_product=' . $id_product,
        'link' => $this->context->link, // Añadimos el objeto link a la plantilla
    ]);
    
    // Renderizar la plantilla
    return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/product_config.tpl');
}

    /**
     * Obtiene la información detallada de las combinaciones de un producto
     */
    private function getProductCombinations($id_product, $product_config)
    {
        $combinations = [];
        
        // Obtener todas las combinaciones básicas
        $sql = "SELECT id_product_attribute, price
                FROM " . _DB_PREFIX_ . "product_attribute 
                WHERE id_product = " . (int)$id_product;
        
        $product_combinations = Db::getInstance()->executeS($sql);
        
        if (empty($product_combinations)) {
            return $combinations;
        }
        
        // Obtener la tasa de IVA
        $tax_rate = CombinacionesCarneModel::getTaxRate($id_product);
        $tax_multiplier = 1 + ($tax_rate / 100);
        
        // Obtener los mapeos de peso
        $peso_mapeos = CombinacionesCarneModel::getAllPesoMapeos();
        
        // Para cada combinación, obtener sus atributos
        foreach ($product_combinations as $pc) {
            $id_product_attribute = (int)$pc['id_product_attribute'];
            
            // Obtener atributos de esta combinación
            $sql = "SELECT a.id_attribute, a.id_attribute_group, al.name as attribute_name, 
                           agl.name as group_name
                    FROM " . _DB_PREFIX_ . "product_attribute_combination pac
                    INNER JOIN " . _DB_PREFIX_ . "attribute a 
                        ON pac.id_attribute = a.id_attribute
                    INNER JOIN " . _DB_PREFIX_ . "attribute_lang al 
                        ON a.id_attribute = al.id_attribute 
                        AND al.id_lang = " . (int)$this->context->language->id . "
                    INNER JOIN " . _DB_PREFIX_ . "attribute_group ag 
                        ON a.id_attribute_group = ag.id_attribute_group
                    INNER JOIN " . _DB_PREFIX_ . "attribute_group_lang agl 
                        ON ag.id_attribute_group = agl.id_attribute_group 
                        AND agl.id_lang = " . (int)$this->context->language->id . "
                    WHERE pac.id_product_attribute = " . $id_product_attribute;
            
            $attributes = Db::getInstance()->executeS($sql);
            
            if (empty($attributes)) {
                continue;
            }
            
            // Inicializar combinación
            $combination = [
                'id_product_attribute' => $id_product_attribute,
                'impact_price' => (float)$pc['price'],
                'impact_price_with_tax' => (float)$pc['price'] * $tax_multiplier,
                'attributes' => $attributes,
                'es_individual' => false,
                'peso_gramos' => 0,
                'peso_kg' => 0,
                'needs_update' => false
            ];
            
            // Procesar atributos para determinar si es envasado individual y el peso
            foreach ($attributes as $attribute) {
                $id_attribute = (int)$attribute['id_attribute'];
                $id_attribute_group = (int)$attribute['id_attribute_group'];
                
                // Detectar si es envasado individual
                if ($id_attribute_group == $product_config['id_attribute_group_envasado']) {
                    if ($id_attribute == $product_config['id_attribute_envasado_individual']) {
                        $combination['es_individual'] = true;
                    }
                }
                
                // Detectar peso
                if ($id_attribute_group == $product_config['id_attribute_group_peso']) {
                    if (isset($peso_mapeos[$id_attribute])) {
                        $combination['peso_gramos'] = (int)$peso_mapeos[$id_attribute];
                        $combination['peso_kg'] = $combination['peso_gramos'] / 1000;
                    }
                }
            }
            
            // Determinar si la combinación necesita actualización
            $expectedImpactPrice = $this->calculateExpectedImpactPrice(
                $product_config, 
                $combination['peso_kg'], 
                $combination['es_individual'],
                $precio_base_producto,
                $precio_individual,
                $tax_multiplier
            );
            
            // Comprobar si el precio actual difiere del esperado (con un margen de tolerancia para errores de redondeo)
            if (abs($combination['impact_price'] - $expectedImpactPrice) > 0.001) {
                $combination['needs_update'] = true;
            }
            
            $combinations[] = $combination;
        }
        
        return $combinations;
    }

		
		/**
     * Calcula el impacto de precio esperado para una combinación
     */
    private function calculateExpectedImpactPrice($config, $peso_kg, $es_individual, $precio_base, $precio_individual, $tax_multiplier)
    {
        // Calculamos el impacto de precio basado en la diferencia con 1kg (PRECIO CON IVA)
        $impacto_precio_con_iva = $precio_base * ($peso_kg - 1);
        
        // Si es envasado individual, sumamos el coste adicional (CON IVA)
        if ($es_individual) {
            $impacto_precio_con_iva += $precio_individual;
        }
        
        // Convertimos el impacto a valor SIN IVA para que PrestaShop lo interprete correctamente
        $impacto_precio = $impacto_precio_con_iva / $tax_multiplier;
        
        // Redondeamos a 6 decimales (formato de PrestaShop)
        return round($impacto_precio, 6);
    }

    /**
     * Obtiene la lista de productos con combinaciones
     */
    protected function getProductsWithCombinations()
    {
        $sql = "SELECT p.id_product, pl.name, p.reference 
                FROM `" . _DB_PREFIX_ . "product` p
                INNER JOIN `" . _DB_PREFIX_ . "product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = " . (int)$this->context->language->id . " AND pl.id_shop = " . (int)$this->context->shop->id . ")
                WHERE p.id_product IN (
                    SELECT DISTINCT id_product FROM `" . _DB_PREFIX_ . "product_attribute`
                )
                ORDER BY pl.name ASC";
                
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Obtener grupos de atributos y sus valores de forma correcta
     */
    private function getAttributeGroupsWithValues()
    {
        $id_lang = (int)$this->context->language->id;
        
        // Obtener todos los grupos de atributos
        $sql = "SELECT ag.id_attribute_group, agl.name 
                FROM `" . _DB_PREFIX_ . "attribute_group` ag
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_group_lang` agl 
                    ON (ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = " . $id_lang . ")
                ORDER BY ag.position ASC";
        
        $groups = Db::getInstance()->executeS($sql);
        $result = array();
        
        // Para cada grupo, obtener sus atributos
        foreach ($groups as $group) {
            $group_data = array(
                'id_attribute_group' => $group['id_attribute_group'],
                'name' => $group['name'],
                'attributes' => array()
            );
            
            // Obtener los atributos de este grupo
            $sql = "SELECT a.id_attribute, al.name
                    FROM `" . _DB_PREFIX_ . "attribute` a
                    LEFT JOIN `" . _DB_PREFIX_ . "attribute_lang` al 
                        ON (a.id_attribute = al.id_attribute AND al.id_lang = " . $id_lang . ")
                    WHERE a.id_attribute_group = " . (int)$group['id_attribute_group'] . "
                    ORDER BY a.position ASC";
                    
            $attributes = Db::getInstance()->executeS($sql);
            
            if (!empty($attributes)) {
                $group_data['attributes'] = $attributes;
                $result[] = $group_data;
            }
        }
        
        return $result;
    }

    /**
     * Valores por defecto del formulario de configuración general
     */
    protected function getConfigFormValues()
    {
        return array(
            'COMBINACIONESCARNE_PRECIO_INDIVIDUAL' => Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL'),
            'COMBINACIONESCARNE_GRUPO_ENVASADO' => Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0),
            'COMBINACIONESCARNE_ATRIBUTO_GRANEL' => Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0),
            'COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL' => Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0),
            'COMBINACIONESCARNE_GRUPO_PESO' => Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0),
        );
    }

    /**
     * Hook que se ejecuta al actualizar un producto
     */
    public function hookActionProductUpdate($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            
            // Verificamos si la configuración para este producto está activada
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            if (!empty($config) && $config['active']) {
                // Actualizamos las combinaciones
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }
    
    /**
     * Hook que se ejecuta al actualizar una combinación
     */
    public function hookActionProductAttributeUpdate($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            if (!empty($config) && $config['active']) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }

    /**
     * Hook que se ejecuta al añadir una combinación
     */
    public function hookActionProductAttributeAdd($params)
    {
        if (isset($params['id_product_attribute']) && isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $id_product_attribute = (int) $params['id_product_attribute'];
            
            // Verificamos si el producto ya está configurado
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            
            // Si no está configurado, intentamos aplicar configuración global
            if (!$config['active']) {
                $this->hookActionProductSave($params);
                $config = CombinacionesCarneModel::getProductConfig($id_product); // Recargamos config
            }
            
            // Si está activo, actualizamos los precios de la combinación
            if ($config['active']) {
                // Actualizar solo esta combinación específica
                CombinacionesCarneModel::updateCombinationPrice($id_product, $id_product_attribute);
                
                // Registro en log
                file_put_contents(
                    _PS_MODULE_DIR_ . 'combinacionescarne/auto_log.txt',
                    date('Y-m-d H:i:s') . " - Combinación ID: " . $id_product_attribute . " del producto ID: " . $id_product . " actualizada automáticamente\n",
                    FILE_APPEND
                );
            }
        }
    }

    /**
     * Hook que se ejecuta al eliminar una combinación
     */
    public function hookActionProductAttributeDelete($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            if (!empty($config) && $config['active']) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }
    
    /**
     * Hook para detectar la adición o actualización de producto
     * Aplicará la configuración global a productos nuevos con combinaciones
     */
    public function hookActionProductSave($params)
    {
        // Esto se activará cuando se cree un producto nuevo o se edite uno existente
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            
            // Verificamos si ya existe configuración para este producto
            $config = CombinacionesCarneModel::getProductConfig($id_product);
            
            // Si no está activo (es nuevo o nunca se ha configurado)
            if (!$config['active']) {
                // Verificamos si el producto tiene combinaciones
                $has_combinations = $this->productHasCombinations($id_product);
                
                if ($has_combinations) {
                    // Aplicamos la configuración global automáticamente
                    $global_config = array(
                        'precio_por_kilo' => 0, // Ya no usamos este campo
                        'id_attribute_group_envasado' => (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0),
                        'id_attribute_envasado_granel' => (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0),
                        'id_attribute_envasado_individual' => (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0),
                        'id_attribute_group_peso' => (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0),
                        'active' => 1 // Activamos automáticamente
                    );
                    
                    // Guardamos la configuración
                    CombinacionesCarneModel::saveProductConfig($id_product, $global_config);
                    
                    // Calculamos los precios iniciales
                    CombinacionesCarneModel::updateCombinationPrices($id_product);
                    
                    // Registro en log
                    file_put_contents(
                        _PS_MODULE_DIR_ . 'combinacionescarne/auto_log.txt',
                        date('Y-m-d H:i:s') . " - Producto ID: " . $id_product . " configurado automáticamente\n",
                        FILE_APPEND
                    );
                }
            }
        }
    }

    /**
     * Comprueba si un producto tiene combinaciones
     * 
     * @param int $id_product ID del producto
     * @return bool True si tiene combinaciones, False en caso contrario
     */
    private function productHasCombinations($id_product)
    {
        $count = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "product_attribute` 
             WHERE `id_product` = " . (int)$id_product
        );
        
        return $count > 0;
    }
    
    /**
     * Hook para productos eliminados
     * Limpia la configuración cuando se elimina un producto
     */
    public function hookActionProductDelete($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            
            // Eliminar la configuración del producto
            Db::getInstance()->execute(
                "DELETE FROM `" . _DB_PREFIX_ . "combinacionescarne_product`
                 WHERE `id_product` = " . $id_product
            );
            
            // Registro en log
            file_put_contents(
                _PS_MODULE_DIR_ . 'combinacionescarne/auto_log.txt',
                date('Y-m-d H:i:s') . " - Configuración eliminada para producto ID: " . $id_product . " (producto eliminado)\n",
                FILE_APPEND
            );
        }
    }
}