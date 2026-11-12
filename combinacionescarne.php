<?php
/**
 * Módulo CombinacionesCarne
 *
 * Automatiza el cálculo de precios para combinaciones de productos cárnicos
 * basado en el peso y, opcionalmente, en el tipo de envasado (granel/individual).
 *
 * @author   josra
 * @version  1.1.2
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/CombinacionesCarneModel.php';

class CombinacionesCarne extends Module
{
    public function __construct()
    {
        $this->name = 'combinacionescarne';
        $this->tab = 'pricing_promotion';
        $this->version = '1.1.2';
        $this->author = 'josra';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_,
        );

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
        Configuration::updateValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL', 1.5);
        Configuration::updateValue('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
        Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
        Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
        Configuration::updateValue('COMBINACIONESCARNE_GRUPO_PESO', 0);

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

        $sql2 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "combinacionescarne_peso_map` (
            `id_attribute` INT(10) UNSIGNED NOT NULL,
            `peso_gramos` INT(10) UNSIGNED NOT NULL DEFAULT '0',
            PRIMARY KEY (`id_attribute`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8mb4;";

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
        Configuration::deleteByName('COMBINACIONESCARNE_GRUPO_ENVASADO');
        Configuration::deleteByName('COMBINACIONESCARNE_ATRIBUTO_GRANEL');
        Configuration::deleteByName('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL');
        Configuration::deleteByName('COMBINACIONESCARNE_GRUPO_PESO');

        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "combinacionescarne_product`;";
        $sql2 = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "combinacionescarne_peso_map`;";

        // Limpieza de logs (de esta versión y de versiones anteriores)
        foreach (array('combinacionescarne.log', 'log.txt', 'auto_log.txt', 'single_update_log.txt') as $log_file) {
            $path = _PS_MODULE_DIR_ . 'combinacionescarne/' . $log_file;
            if (file_exists($path)) {
                @unlink($path);
            }
        }

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
        }
    }

    /**
     * Carga el formulario de configuración
     */
    public function getContent()
    {
        $output = '';
        $errors = array();
        $success = false;

        $action = Tools::getValue('action', 'general');
        $id_product = (int) Tools::getValue('id_product', 0);

        // Configuración general
        if (Tools::isSubmit('submit' . $this->name)) {
            $precio_individual = (float) Tools::getValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');

            if ($precio_individual < 0) {
                $errors[] = $this->l('El valor del precio de envasado individual debe ser positivo.');
            } else {
                Configuration::updateValue('COMBINACIONESCARNE_PRECIO_INDIVIDUAL', $precio_individual);
                Configuration::updateValue('COMBINACIONESCARNE_GRUPO_ENVASADO', (int) Tools::getValue('COMBINACIONESCARNE_GRUPO_ENVASADO', 0));
                Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', (int) Tools::getValue('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0));
                Configuration::updateValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', (int) Tools::getValue('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0));
                Configuration::updateValue('COMBINACIONESCARNE_GRUPO_PESO', (int) Tools::getValue('COMBINACIONESCARNE_GRUPO_PESO', 0));

                $success = true;
            }
        }

        // Mapeos de peso
        if (Tools::isSubmit('submitMapeoPeso')) {
            $id_attribute = (int) Tools::getValue('id_attribute');
            $peso_gramos = (int) Tools::getValue('peso_gramos');

            if ($id_attribute <= 0) {
                $errors[] = $this->l('Por favor, seleccione un atributo válido.');
            } elseif ($peso_gramos <= 0) {
                $errors[] = $this->l('El peso debe ser un valor positivo.');
            } else {
                if (CombinacionesCarneModel::savePesoMapeo($id_attribute, $peso_gramos)) {
                    $success = true;
                } else {
                    $errors[] = $this->l('Error al guardar el mapeo de peso.');
                }
            }
        }

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

        // Configuración/cálculo manual por producto (cuando se entra al módulo)
        if (Tools::isSubmit('submitProductConfig') || Tools::isSubmit('submitCalculate')) {
            $id_product = (int) Tools::getValue('id_product');

            if ($id_product <= 0) {
                $errors[] = $this->l('Por favor, seleccione un producto válido.');
            } else {
                $config = array(
                    'precio_por_kilo' => 0,
                    'id_attribute_group_envasado' => (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO'),
                    'id_attribute_envasado_granel' => (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL'),
                    'id_attribute_envasado_individual' => (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL'),
                    'id_attribute_group_peso' => (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO'),
                    'active' => 1,
                );

                if (CombinacionesCarneModel::saveProductConfig($id_product, $config)) {
                    if (CombinacionesCarneModel::updateCombinationPrices($id_product)) {
                        $success = true;
                    } else {
                        $errors[] = $this->l('No se ha calculado ninguna combinación: revisa que tengan un atributo de peso mapeado en gramos.');
                    }
                } else {
                    $errors[] = $this->l('Error al guardar la configuración del producto.');
                }
            }
        }

        if ($success) {
            $output .= $this->displayConfirmation($this->l('Configuración actualizada correctamente.'));
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output .= $this->displayError($error);
            }
        }

        $output .= $this->renderNavTabs($action);

        if ($action == 'mapeos') {
            $output .= $this->renderMapeoPesoConfig();
        } elseif ($action == 'product' && $id_product > 0) {
            $output .= $this->renderProductConfiguration($id_product);
        } else {
            $output .= $this->renderGeneralConfig();
            $output .= $this->renderProductSelector();
        }

        return $output;
    }

    /**
     * Renderiza las pestañas de navegación
     */
    protected function renderNavTabs($current_action)
    {
        $html = '<div class="panel-heading">';
        $html .= '<ul class="nav nav-tabs">';

        $activeClass = ($current_action == 'general') ? 'active' : '';
        $html .= '<li class="nav-item ' . $activeClass . '">';
        $html .= '<a href="' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '" class="nav-link ' . $activeClass . '">';
        $html .= '<i class="icon-cog"></i> ' . $this->l('Configuración General');
        $html .= '</a>';
        $html .= '</li>';

        $activeClass = ($current_action == 'mapeos') ? 'active' : '';
        $html .= '<li class="nav-item ' . $activeClass . '">';
        $html .= '<a href="' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&action=mapeos" class="nav-link ' . $activeClass . '">';
        $html .= '<i class="icon-balance-scale"></i> ' . $this->l('Mapeo de Pesos');
        $html .= '</a>';
        $html .= '</li>';

        if ($current_action == 'product') {
            $id_product = (int) Tools::getValue('id_product', 0);
            if ($id_product > 0) {
                $product = new Product($id_product, false, $this->context->language->id);
                if (Validate::isLoadedObject($product)) {
                    $html .= '<li class="nav-item active">';
                    $html .= '<a href="#" class="nav-link active">';
                    $html .= '<i class="icon-tag"></i> ' . $product->name;
                    $html .= '</a>';
                    $html .= '</li>';
                }
            }
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza el formulario de configuración general
     */
    protected function renderGeneralConfig()
    {
        $attribute_groups = $this->getAttributeGroupsWithValues();

        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return '<div class="panel"><div class="panel-heading"><i class="icon-cogs"></i> ' .
               $this->l('Configuración General') . '</div>' .
               $helper->generateForm(array($this->getConfigForm($attribute_groups))) .
               '</div>';
    }

    /**
     * Estructura del formulario de configuración general
     */
    protected function getConfigForm($attribute_groups)
    {
        $groups_options = array(
            array('id_option' => 0, 'name' => $this->l('-- Seleccionar --')),
        );

        $attributes_options = array(
            array('id_option' => 0, 'name' => $this->l('-- Seleccionar --')),
        );

        foreach ($attribute_groups as $group) {
            $groups_options[] = array(
                'id_option' => $group['id_attribute_group'],
                'name' => $group['name'],
            );

            foreach ($group['attributes'] as $attribute) {
                $attributes_options[] = array(
                    'id_option' => $attribute['id_attribute'],
                    'name' => $group['name'] . ' - ' . $attribute['name'],
                    'group' => $group['id_attribute_group'],
                );
            }
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuración de Precios'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Precio de envasado individual (€)'),
                        'name' => 'COMBINACIONESCARNE_PRECIO_INDIVIDUAL',
                        'desc' => $this->l('Coste adicional por envasado individual (por defecto 1.50€)'),
                        'class' => 'fixed-width-sm',
                        'suffix' => '€',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Grupo de atributos para Peso'),
                        'name' => 'COMBINACIONESCARNE_GRUPO_PESO',
                        'desc' => $this->l('Obligatorio. Grupo de atributos que define el peso del producto (p.ej. "Peso" con valores 500g, 1kg...).'),
                        'options' => array(
                            'query' => $groups_options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Grupo de atributos para Envasado'),
                        'name' => 'COMBINACIONESCARNE_GRUPO_ENVASADO',
                        'desc' => $this->l('Opcional. Si el producto no usa distinción de envasado, déjalo en "-- Seleccionar --" y el módulo calculará solo por peso.'),
                        'options' => array(
                            'query' => $groups_options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Atributo para Envasado Granel'),
                        'name' => 'COMBINACIONESCARNE_ATRIBUTO_GRANEL',
                        'desc' => $this->l('Seleccione el atributo que representa el envasado a granel (informativo; cualquier atributo del grupo de envasado que no sea "Individual" se trata como granel).'),
                        'options' => array(
                            'query' => $attributes_options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Atributo para Envasado Individual'),
                        'name' => 'COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL',
                        'desc' => $this->l('Seleccione el atributo que representa el envasado individual (lleva el coste adicional).'),
                        'options' => array(
                            'query' => $attributes_options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                ),
            ),
        );
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
     * Renderiza el formulario de gestión de mapeos de peso
     */
    protected function renderMapeoPesoConfig()
    {
        $html = '';

        $attribute_groups = $this->getAttributeGroupsWithValues();
        $mapeos_detallados = CombinacionesCarneModel::getPesoMapeosDetallados();

        $html .= '<div class="panel">';
        $html .= '<div class="panel-heading"><i class="icon-plus"></i> ' . $this->l('Añadir nuevo mapeo de peso') . '</div>';

        $html .= '<form id="add_mapeo_form" class="form-horizontal" action="' .
                $this->context->link->getAdminLink('AdminModules') .
                '&configure=' . $this->name . '&action=mapeos" method="post">';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Atributo') . '</label>';
        $html .= '<div class="col-lg-6">';
        $html .= '<select name="id_attribute" class="form-control">';
        $html .= '<option value="0">-- ' . $this->l('Seleccionar') . ' --</option>';

        foreach ($attribute_groups as $group) {
            $html .= '<optgroup label="' . $group['name'] . '">';
            foreach ($group['attributes'] as $attribute) {
                $html .= '<option value="' . $attribute['id_attribute'] . '">' . $attribute['name'] . '</option>';
            }
            $html .= '</optgroup>';
        }

        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Peso (g)') . '</label>';
        $html .= '<div class="col-lg-6">';
        $html .= '<div class="input-group">';
        $html .= '<input type="number" name="peso_gramos" value="0" min="1" class="form-control">';
        $html .= '<span class="input-group-addon">g</span>';
        $html .= '</div>';
        $html .= '<p class="help-block">' . $this->l('Introduce el peso en gramos (p.ej: 500 para medio kilo)') . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitMapeoPeso" class="btn btn-default pull-right">';
        $html .= '<i class="icon-save"></i> ' . $this->l('Guardar') . '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div class="panel">';
        $html .= '<div class="panel-heading"><i class="icon-list"></i> ' . $this->l('Mapeos de peso existentes') . '</div>';

        if (empty($mapeos_detallados)) {
            $html .= '<div class="alert alert-warning">' . $this->l('No hay mapeos de peso configurados.') . '</div>';
        } else {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table">';
            $html .= '<thead><tr>';
            $html .= '<th>' . $this->l('Grupo') . '</th>';
            $html .= '<th>' . $this->l('Atributo') . '</th>';
            $html .= '<th>' . $this->l('Peso (g)') . '</th>';
            $html .= '<th>' . $this->l('Peso (kg)') . '</th>';
            $html .= '<th>' . $this->l('Acciones') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $form_action = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&action=mapeos';

            foreach ($mapeos_detallados as $group) {
                foreach ($group['attributes'] as $attribute) {
                    $html .= '<tr>';
                    $html .= '<td>' . $group['name'] . '</td>';
                    $html .= '<td>' . $attribute['name'] . '</td>';
                    $html .= '<td>';
                    // Edición en línea: reenvía al mismo submitMapeoPeso, que ya actualiza si el atributo existe.
                    $html .= '<form class="form-inline" action="' . $form_action . '" method="post" style="margin:0;">';
                    $html .= '<input type="hidden" name="id_attribute" value="' . $attribute['id_attribute'] . '">';
                    $html .= '<div class="input-group input-group-sm" style="width:140px;">';
                    $html .= '<input type="number" name="peso_gramos" value="' . $attribute['peso_gramos'] . '" min="1" class="form-control">';
                    $html .= '<span class="input-group-addon">g</span>';
                    $html .= '</div> ';
                    $html .= '<button type="submit" name="submitMapeoPeso" class="btn btn-default btn-sm" title="' . $this->l('Guardar') . '">';
                    $html .= '<i class="icon-save"></i></button>';
                    $html .= '</form>';
                    $html .= '</td>';
                    $html .= '<td>' . number_format($attribute['peso_gramos'] / 1000, 3) . ' kg</td>';
                    $html .= '<td>';
                    $html .= '<a href="' . $this->context->link->getAdminLink('AdminModules') .
                             '&configure=' . $this->name . '&action=mapeos&deleteMapeoPeso=1&id_attribute=' .
                             $attribute['id_attribute'] . '" class="btn btn-default"
                             onclick="return confirm(\'' . $this->l('¿Estás seguro que deseas eliminar este mapeo?') . '\');">';
                    $html .= '<i class="icon-trash"></i> ' . $this->l('Eliminar');
                    $html .= '</a>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody></table></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Renderiza la lista de productos como links directos
     */
    protected function renderProductSelector()
    {
        $html = '';
        $products = $this->getProductsWithCombinations();

        $html .= '<div class="panel"><div class="panel-heading"><i class="icon-tags"></i> ' .
                 $this->l('Productos con combinaciones') . '</div>';

        $html .= '<div class="alert alert-info">' .
            $this->l('El cálculo se hace solo. Normalmente no necesitas entrar aquí: entra en la ficha del producto, cambia el precio y guarda. Esta lista es solo para consultar el estado de cada producto o recalcular a mano.') .
            '</div>';

        $html .= '<div class="list-group">';

        foreach ($products as $product) {
            $config = CombinacionesCarneModel::getProductConfig($product['id_product']);
            $activeClass = (Tools::getValue('id_product') == $product['id_product']) ? 'active' : '';
            $estado = !empty($config['active'])
                ? '<span class="label label-success">' . $this->l('Activo') . '</span>'
                : '<span class="label label-default">' . $this->l('Inactivo') . '</span>';

            $url_ficha_producto = $this->context->link->getAdminLink('AdminProducts') .
                '&id_product=' . $product['id_product'] . '&updateproduct&token=' . Tools::getAdminTokenLite('AdminProducts');

            $html .= '<div class="list-group-item ' . $activeClass . '" style="display:flex; align-items:center; justify-content:space-between;">';
            $html .= '<a href="' . $this->context->link->getAdminLink('AdminModules') .
                     '&configure=' . $this->name . '&action=product&id_product=' . $product['id_product'] .
                     '&token=' . Tools::getAdminTokenLite('AdminModules') . '" style="flex:1;">' .
                     $product['name'] . ' (ID: ' . $product['id_product'] . ', Ref: ' . $product['reference'] . ') ' . $estado . '</a>';
            $html .= '<a href="' . $url_ficha_producto . '" class="btn btn-default btn-xs" target="_blank" title="' . $this->l('Abrir ficha del producto') . '">';
            $html .= '<i class="icon-external-link"></i></a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtener grupos de atributos y sus valores
     */
    private function getAttributeGroupsWithValues()
    {
        $id_lang = (int) $this->context->language->id;

        $sql = "SELECT ag.id_attribute_group, agl.name
                FROM `" . _DB_PREFIX_ . "attribute_group` ag
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_group_lang` agl
                    ON (ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = " . $id_lang . ")
                ORDER BY ag.position ASC";

        $groups = Db::getInstance()->executeS($sql);
        $result = array();

        foreach ($groups as $group) {
            $group_data = array(
                'id_attribute_group' => $group['id_attribute_group'],
                'name' => $group['name'],
                'attributes' => array(),
            );

            $sql = "SELECT a.id_attribute, al.name
                    FROM `" . _DB_PREFIX_ . "attribute` a
                    LEFT JOIN `" . _DB_PREFIX_ . "attribute_lang` al
                        ON (a.id_attribute = al.id_attribute AND al.id_lang = " . $id_lang . ")
                    WHERE a.id_attribute_group = " . (int) $group['id_attribute_group'] . "
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
     * Renderiza la configuración de un producto específico
     */
    protected function renderProductConfiguration($id_product)
    {
        $html = '';

        $product = new Product($id_product, false, $this->context->language->id);

        if (!Validate::isLoadedObject($product)) {
            return $html;
        }

        $config = CombinacionesCarneModel::getProductConfig($id_product);
        $tiene_override = !empty($config['id_attribute_group_envasado']) || !empty($config['id_attribute_group_peso']);

        $grupo_peso = !empty($config['id_attribute_group_peso'])
            ? (int) $config['id_attribute_group_peso']
            : (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0);
        $grupo_envasado = !empty($config['id_attribute_group_envasado'])
            ? (int) $config['id_attribute_group_envasado']
            : (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
        $atributo_individual = !empty($config['id_attribute_group_envasado'])
            ? (int) $config['id_attribute_envasado_individual']
            : (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);

        $config_resuelta = array(
            'id_attribute_group_peso' => $grupo_peso,
            'id_attribute_group_envasado' => $grupo_envasado,
            'id_attribute_envasado_individual' => $atributo_individual,
        );

        $precio_base_producto = CombinacionesCarneModel::getPrecioBaseConIva($id_product, $product);
        $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
        $tax_rate = CombinacionesCarneModel::getTaxRate($id_product);
        $tax_multiplier = 1 + ($tax_rate / 100);
        $peso_mapeos = CombinacionesCarneModel::getAllPesoMapeos();

        $sql = "SELECT id_product_attribute, price, reference
                FROM " . _DB_PREFIX_ . "product_attribute
                WHERE id_product = " . (int) $id_product;

        $product_combinations = Db::getInstance()->executeS($sql);
        $combinations = array();

        if (!empty($product_combinations)) {
            foreach ($product_combinations as $pc) {
                $id_product_attribute = (int) $pc['id_product_attribute'];

                $sql = "SELECT a.id_attribute, a.id_attribute_group, al.name as attribute_name,
                               agl.name as group_name
                        FROM " . _DB_PREFIX_ . "product_attribute_combination pac
                        INNER JOIN " . _DB_PREFIX_ . "attribute a
                            ON pac.id_attribute = a.id_attribute
                        INNER JOIN " . _DB_PREFIX_ . "attribute_lang al
                            ON a.id_attribute = al.id_attribute
                            AND al.id_lang = " . (int) $this->context->language->id . "
                        INNER JOIN " . _DB_PREFIX_ . "attribute_group ag
                            ON a.id_attribute_group = ag.id_attribute_group
                        INNER JOIN " . _DB_PREFIX_ . "attribute_group_lang agl
                            ON ag.id_attribute_group = agl.id_attribute_group
                            AND agl.id_lang = " . (int) $this->context->language->id . "
                        WHERE pac.id_product_attribute = " . $id_product_attribute;

                $attributes = Db::getInstance()->executeS($sql);
                $analisis = CombinacionesCarneModel::analizarAtributosCombinacion($attributes, $config_resuelta, $peso_mapeos);

                $attrs_nombres = array();
                foreach ($attributes as $attribute) {
                    $attrs_nombres[] = $attribute['group_name'] . ': ' . $attribute['attribute_name'];
                }

                $combinations[$id_product_attribute] = array(
                    'id_product_attribute' => $id_product_attribute,
                    'reference' => $pc['reference'],
                    'attributes_text' => implode(', ', $attrs_nombres),
                    'analisis' => $analisis,
                );
            }
        }

        $url_ficha_producto = $this->context->link->getAdminLink('AdminProducts') .
            '&id_product=' . $id_product . '&updateproduct&token=' . Tools::getAdminTokenLite('AdminProducts');

        $html .= '<div class="panel">';
        $html .= '<div class="panel-heading">';
        $html .= '<i class="icon-cog"></i> ' . $this->l('Configuración para:') . ' ' . $product->name;
        $html .= ' <a href="' . $url_ficha_producto . '" class="btn btn-default btn-xs pull-right" target="_blank">';
        $html .= '<i class="icon-external-link"></i> ' . $this->l('Abrir ficha del producto');
        $html .= '</a>';
        $html .= '</div>';

        if ($tiene_override) {
            $html .= '<div class="alert alert-info">' .
                $this->l('Este producto tiene su propia configuración de grupos de atributos, distinta de la general.') .
                '</div>';
        } else {
            $html .= '<div class="alert alert-info">' .
                $this->l('Este producto usa la configuración general del módulo (Configuración General).') .
                '</div>';
        }

        if (empty($grupo_peso)) {
            $html .= '<div class="alert alert-warning">' .
                $this->l('No hay ningún grupo de atributos de peso configurado (ni para este producto, ni en la configuración general). El módulo no puede calcular nada hasta que se configure.') .
                '</div>';
        }

        $html .= '<form id="product_config_form" class="form-horizontal" action="' .
                $this->context->link->getAdminLink('AdminModules') .
                '&configure=' . $this->name . '&action=product" method="post">';

        $html .= '<input type="hidden" name="id_product" value="' . $id_product . '">';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Precio base del producto (con IVA)') . '</label>';
        $html .= '<div class="col-lg-9">';
        $html .= '<div class="input-group">';
        $html .= '<input type="text" value="' . $precio_base_producto . '" class="form-control" disabled="disabled">';
        $html .= '<span class="input-group-addon">€</span>';
        $html .= '</div>';
        $html .= '<p class="help-block">' . $this->l('Este precio se trata como el precio por 1 kilo a granel; cada combinación se calcula a partir de él.') . '</p>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitCalculate" class="btn btn-primary pull-right"
                     onclick="return confirm(\'' . $this->l('¿Recalcular ahora el precio de todas las combinaciones de este producto?') . '\');">';
        $html .= '<i class="icon-cogs"></i> ' . $this->l('Calcular y aplicar precios') . '</button>';
        $html .= '</div>';

        $html .= '</form>';

        if (!empty($combinations)) {
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table">';
            $html .= '<thead><tr>';
            $html .= '<th>' . $this->l('ID') . '</th>';
            $html .= '<th>' . $this->l('Referencia') . '</th>';
            $html .= '<th>' . $this->l('Atributos') . '</th>';
            $html .= '<th>' . $this->l('Tipo') . '</th>';
            $html .= '<th>' . $this->l('Estado') . '</th>';
            $html .= '<th>' . $this->l('Impacto en el precio (con IVA)') . '</th>';
            $html .= '<th>' . $this->l('Nuevo precio calculado') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            foreach ($combinations as $combination) {
                $analisis = $combination['analisis'];

                $html .= '<tr' . (!$analisis['tiene_peso'] ? ' class="warning"' : '') . '>';
                $html .= '<td>' . $combination['id_product_attribute'] . '</td>';
                $html .= '<td>' . (!empty($combination['reference']) ? $combination['reference'] : '-') . '</td>';
                $html .= '<td>' . $combination['attributes_text'] . '</td>';

                if (!$analisis['tiene_envasado']) {
                    $tipo = $this->l('Solo peso');
                } elseif ($analisis['es_individual']) {
                    $tipo = $this->l('Peso + envasado individual');
                } else {
                    $tipo = $this->l('Peso + envasado a granel');
                }
                $html .= '<td>' . $tipo . '</td>';

                if ($analisis['tiene_peso']) {
                    $html .= '<td><span class="label label-success">' . $this->l('Calculado') . '</span></td>';

                    $impacto_con_iva = $precio_base_producto * (($analisis['peso_gramos'] / 1000) - 1);
                    if ($analisis['es_individual']) {
                        $impacto_con_iva += $precio_individual;
                    }
                    $nuevo_precio = $precio_base_producto + $impacto_con_iva;

                    $html .= '<td>' . Tools::displayPrice($impacto_con_iva) . '</td>';
                    $html .= '<td>' . Tools::displayPrice($nuevo_precio) . '</td>';
                } else {
                    $html .= '<td><span class="label label-warning">' . $this->l('Sin peso mapeado - no se toca') . '</span></td>';
                    $html .= '<td>-</td>';
                    $html .= '<td>-</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Obtener la lista de productos con combinaciones
     */
    protected function getProductsWithCombinations()
    {
        $sql = "SELECT p.id_product, pl.name, p.reference
                FROM `" . _DB_PREFIX_ . "product` p
                INNER JOIN `" . _DB_PREFIX_ . "product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = " . (int) $this->context->language->id . " AND pl.id_shop = " . (int) $this->context->shop->id . ")
                WHERE p.id_product IN (
                    SELECT DISTINCT id_product FROM `" . _DB_PREFIX_ . "product_attribute`
                )
                ORDER BY pl.name ASC";

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Hook al actualizar un producto (p.ej. al cambiar el precio y guardar)
     */
    public function hookActionProductUpdate($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::ensureActiveIfQualifies($id_product);

            if (!empty($config['active'])) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }

    /**
     * Hook al actualizar una combinación
     */
    public function hookActionProductAttributeUpdate($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::ensureActiveIfQualifies($id_product);

            if (!empty($config['active'])) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }

    /**
     * Hook al añadir una combinación nueva
     */
    public function hookActionProductAttributeAdd($params)
    {
        if (isset($params['id_product_attribute']) && isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $id_product_attribute = (int) $params['id_product_attribute'];

            $config = CombinacionesCarneModel::ensureActiveIfQualifies($id_product);

            if (!empty($config['active'])) {
                CombinacionesCarneModel::updateCombinationPrice($id_product, $id_product_attribute);
            }
        }
    }

    /**
     * Hook al eliminar una combinación
     */
    public function hookActionProductAttributeDelete($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::getProductConfig($id_product);

            if (!empty($config['active'])) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }

    /**
     * Hook al guardar un producto (crear o editar). Es donde se detecta si un
     * producto nuevo (o uno que nunca se había activado) cumple las
     * condiciones para que el módulo empiece a calcular sus combinaciones
     * solo, sin que haga falta entrar al módulo.
     */
    public function hookActionProductSave($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
            $config = CombinacionesCarneModel::ensureActiveIfQualifies($id_product);

            if (!empty($config['active'])) {
                CombinacionesCarneModel::updateCombinationPrices($id_product);
            }
        }
    }

    /**
     * Hook para productos eliminados: limpia la configuración guardada
     */
    public function hookActionProductDelete($params)
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];

            Db::getInstance()->execute(
                "DELETE FROM `" . _DB_PREFIX_ . "combinacionescarne_product`
                 WHERE `id_product` = " . $id_product
            );

            CombinacionesCarneModel::writeLog('Configuración eliminada para producto ID: ' . $id_product . ' (producto eliminado)');
        }
    }
}
