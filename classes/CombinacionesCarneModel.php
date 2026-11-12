<?php
/**
 * Clase modelo para el módulo CombinacionesCarne
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CombinacionesCarneModel
{
    const LOG_FILE = 'combinacionescarne.log';
    const LOG_MAX_LINES = 400;

    /**
     * Obtiene la configuración guardada para un producto
     *
     * @param int $id_product ID del producto
     * @return array Configuración del producto
     */
    public static function getProductConfig($id_product)
    {
        $id_product = (int) $id_product;

        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "combinacionescarne_product`
                WHERE `id_product` = " . $id_product;

        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return array(
                'id_product' => $id_product,
                'precio_por_kilo' => 0,
                'id_attribute_envasado_granel' => 0,
                'id_attribute_envasado_individual' => 0,
                'id_attribute_group_envasado' => 0,
                'id_attribute_group_peso' => 0,
                'active' => 0,
            );
        }

        return $result;
    }

    /**
     * Guarda la configuración para un producto
     *
     * @param int $id_product ID del producto
     * @param array $config Configuración a guardar
     * @return bool Éxito o fracaso
     */
    public static function saveProductConfig($id_product, $config)
    {
        $id_product = (int) $id_product;

        $exists = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "combinacionescarne_product`
             WHERE `id_product` = " . $id_product
        );

        $data = array(
            'id_product' => $id_product,
            'precio_por_kilo' => (float) $config['precio_por_kilo'],
            'id_attribute_envasado_granel' => (int) $config['id_attribute_envasado_granel'],
            'id_attribute_envasado_individual' => (int) $config['id_attribute_envasado_individual'],
            'id_attribute_group_envasado' => (int) $config['id_attribute_group_envasado'],
            'id_attribute_group_peso' => (int) $config['id_attribute_group_peso'],
            'active' => (int) $config['active'],
        );

        if ($exists) {
            return Db::getInstance()->update(
                'combinacionescarne_product',
                $data,
                'id_product = ' . $id_product
            );
        } else {
            return Db::getInstance()->insert('combinacionescarne_product', $data);
        }
    }

    /**
     * Obtiene todos los mapeos de peso
     *
     * @return array Todos los mapeos de peso (id_attribute => gramos)
     */
    public static function getAllPesoMapeos()
    {
        $sql = "SELECT id_attribute, peso_gramos
                FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`";

        $results = Db::getInstance()->executeS($sql);
        $mapeos = array();

        if (!empty($results)) {
            foreach ($results as $row) {
                $mapeos[$row['id_attribute']] = (int) $row['peso_gramos'];
            }
        }

        return $mapeos;
    }

    /**
     * Obtiene los mapeos de peso para atributos específicos
     *
     * @param array $id_attributes Lista de IDs de atributos
     * @return array Mapeos de peso por ID de atributo
     */
    public static function getPesoMapeos($id_attributes = array())
    {
        if (empty($id_attributes)) {
            return self::getAllPesoMapeos();
        }

        $sql = "SELECT id_attribute, peso_gramos
                FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`
                WHERE id_attribute IN (" . implode(',', array_map('intval', $id_attributes)) . ")";

        $results = Db::getInstance()->executeS($sql);
        $mapeos = array();

        if (!empty($results)) {
            foreach ($results as $row) {
                $mapeos[$row['id_attribute']] = (int) $row['peso_gramos'];
            }
        }

        return $mapeos;
    }

    /**
     * Obtiene información detallada de los atributos mapeados
     *
     * @return array Detalles de los atributos de peso mapeados, agrupados
     */
    public static function getPesoMapeosDetallados()
    {
        $id_lang = (int) Context::getContext()->language->id;

        $sql = "SELECT m.id_attribute, m.peso_gramos, a.id_attribute_group,
                       ag.position as group_position, a.position as attribute_position,
                       IFNULL(agl.name, 'Sin nombre') as group_name,
                       IFNULL(al.name, 'Sin nombre') as attribute_name
                FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map` m
                LEFT JOIN `" . _DB_PREFIX_ . "attribute` a ON m.id_attribute = a.id_attribute
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_group` ag ON a.id_attribute_group = ag.id_attribute_group
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_group_lang` agl
                    ON ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = " . $id_lang . "
                LEFT JOIN `" . _DB_PREFIX_ . "attribute_lang` al
                    ON a.id_attribute = al.id_attribute AND al.id_lang = " . $id_lang . "
                ORDER BY ag.position, a.position";

        $results = Db::getInstance()->executeS($sql);

        if (!$results) {
            return array();
        }

        $mapeos_por_grupo = array();

        foreach ($results as $row) {
            $id_attribute_group = $row['id_attribute_group'];

            if (!isset($mapeos_por_grupo[$id_attribute_group])) {
                $mapeos_por_grupo[$id_attribute_group] = array(
                    'id_attribute_group' => $id_attribute_group,
                    'name' => $row['group_name'],
                    'attributes' => array(),
                );
            }

            $mapeos_por_grupo[$id_attribute_group]['attributes'][] = array(
                'id_attribute' => $row['id_attribute'],
                'name' => $row['attribute_name'],
                'peso_gramos' => $row['peso_gramos'],
            );
        }

        return $mapeos_por_grupo;
    }

    /**
     * Guarda un mapeo de peso para un atributo
     *
     * @param int $id_attribute ID del atributo
     * @param int $peso_gramos Peso en gramos
     * @return bool Éxito o fracaso
     */
    public static function savePesoMapeo($id_attribute, $peso_gramos)
    {
        $id_attribute = (int) $id_attribute;
        $peso_gramos = (int) $peso_gramos;

        $exists = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`
             WHERE `id_attribute` = " . $id_attribute
        );

        if ($exists) {
            return Db::getInstance()->update(
                'combinacionescarne_peso_map',
                array('peso_gramos' => $peso_gramos),
                'id_attribute = ' . $id_attribute
            );
        } else {
            return Db::getInstance()->insert('combinacionescarne_peso_map', array(
                'id_attribute' => $id_attribute,
                'peso_gramos' => $peso_gramos,
            ));
        }
    }

    /**
     * Elimina un mapeo de peso
     *
     * @param int $id_attribute ID del atributo
     * @return bool Éxito o fracaso
     */
    public static function deletePesoMapeo($id_attribute)
    {
        return Db::getInstance()->execute(
            "DELETE FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`
             WHERE `id_attribute` = " . (int) $id_attribute
        );
    }

    /**
     * Obtiene la tasa de IVA para un producto
     *
     * @param int $id_product ID del producto
     * @return float Tasa de IVA (porcentaje)
     */
    public static function getTaxRate($id_product)
    {
        $id_product = (int) $id_product;

        $id_tax_rules_group = (int) Db::getInstance()->getValue(
            "SELECT id_tax_rules_group FROM `" . _DB_PREFIX_ . "product` WHERE id_product = " . $id_product
        );

        if ($id_tax_rules_group <= 0) {
            return 0;
        }

        $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');

        $tax_rate = (float) Db::getInstance()->getValue(
            "SELECT t.rate
             FROM `" . _DB_PREFIX_ . "tax_rule` tr
             JOIN `" . _DB_PREFIX_ . "tax` t ON tr.id_tax = t.id_tax
             WHERE tr.id_tax_rules_group = " . $id_tax_rules_group . "
             AND tr.id_country = " . $id_country . "
             AND (tr.id_state = 0 OR tr.id_state IS NULL)
             ORDER BY tr.id_tax_rule ASC"
        );

        return $tax_rate;
    }

    /**
     * Precio base del producto, con IVA, SIN pasar por Product::getPrice().
     *
     * Product::getPrice() con id_product_attribute = null (o incluso 0, en
     * PS 8/9) resuelve internamente a la combinación marcada como
     * predeterminada y le suma su impacto ya guardado — es decir, deja de
     * ser el precio base real en cuanto el producto tiene combinaciones.
     * Para este módulo necesitamos el precio de catálogo tal cual está en
     * la ficha (pestaña Precio), así que se coge directamente del objeto
     * Product (columna `price`, sin IVA) y se le aplica el IVA a mano.
     *
     * @param int $id_product
     * @param Product|null $product Opcional, para no cargarlo dos veces
     * @return float
     */
    public static function getPrecioBaseConIva($id_product, Product $product = null)
    {
        if ($product === null) {
            $product = new Product((int) $id_product);
        }

        $tax_rate = self::getTaxRate($id_product);
        $tax_multiplier = 1 + ($tax_rate / 100);

        return round(((float) $product->price) * $tax_multiplier, 6);
    }

    /**
     * Completa la configuración de un producto con los valores globales
     * cuando el producto no tiene su propio grupo de peso/envasado definido.
     *
     * @param array $config
     * @return array
     */
    protected static function resolveConfig($config)
    {
        if (empty($config['id_attribute_group_peso'])) {
            $config['id_attribute_group_peso'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0);
        }

        if (empty($config['id_attribute_group_envasado'])) {
            $config['id_attribute_group_envasado'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
            $config['id_attribute_envasado_granel'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
            $config['id_attribute_envasado_individual'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
        }

        return $config;
    }

    /**
     * Analiza los atributos de una combinación y determina si tiene un peso
     * reconocido (obligatorio para poder calcular) y si va a tratarse como
     * envasado individual (el envasado es opcional: si el producto no tiene
     * grupo de envasado configurado, toda combinación se trata "solo por peso").
     *
     * @param array $attributes Atributos de la combinación (id_attribute, id_attribute_group)
     * @param array $config Configuración ya resuelta (ver resolveConfig)
     * @param array $peso_mapeos Mapeos de peso (id_attribute => gramos)
     * @return array ['tiene_peso' => bool, 'peso_gramos' => int, 'tiene_envasado' => bool, 'es_individual' => bool]
     */
    public static function analizarAtributosCombinacion($attributes, $config, $peso_mapeos)
    {
        $tiene_peso = false;
        $peso_gramos = 0;
        $tiene_envasado = false;
        $es_individual = false;

        $grupo_peso = (int) $config['id_attribute_group_peso'];
        $grupo_envasado = (int) $config['id_attribute_group_envasado'];

        foreach ($attributes as $attribute) {
            $id_attribute = (int) $attribute['id_attribute'];
            $id_attribute_group = (int) $attribute['id_attribute_group'];

            if ($grupo_peso > 0 && $id_attribute_group == $grupo_peso) {
                if (isset($peso_mapeos[$id_attribute]) && (int) $peso_mapeos[$id_attribute] > 0) {
                    $tiene_peso = true;
                    $peso_gramos = (int) $peso_mapeos[$id_attribute];
                }
            }

            if ($grupo_envasado > 0 && $id_attribute_group == $grupo_envasado) {
                $tiene_envasado = true;
                if ($id_attribute == (int) $config['id_attribute_envasado_individual']) {
                    $es_individual = true;
                }
            }
        }

        return array(
            'tiene_peso' => $tiene_peso,
            'peso_gramos' => $peso_gramos,
            'tiene_envasado' => $tiene_envasado,
            'es_individual' => $es_individual,
        );
    }

    /**
     * Calcula el impacto de precio (sin IVA, como lo necesita PrestaShop) para
     * una combinación ya analizada con analizarAtributosCombinacion().
     *
     * @param float $precio_base Precio base del producto, con IVA
     * @param float $precio_individual Coste adicional de envasado individual, con IVA
     * @param float $tax_multiplier
     * @param array $analisis Resultado de analizarAtributosCombinacion()
     * @return float
     */
    protected static function calcularImpacto($precio_base, $precio_individual, $tax_multiplier, $analisis)
    {
        $peso_en_kg = $analisis['peso_gramos'] / 1000;
        $impacto_con_iva = $precio_base * ($peso_en_kg - 1);

        if ($analisis['es_individual']) {
            $impacto_con_iva += $precio_individual;
        }

        $impacto = $impacto_con_iva / $tax_multiplier;

        return round($impacto, 6);
    }

    /**
     * Comprueba si un producto cumple las condiciones para que el módulo se
     * active solo para él (sin que el usuario tenga que entrar al módulo):
     * tiene que existir un grupo de peso (propio o global) y al menos una
     * combinación de ese producto con un atributo de ese grupo mapeado en
     * gramos. El envasado NO hace falta: si no hay grupo de envasado, el
     * producto se calcula igualmente, solo por peso.
     *
     * Si el producto ya está activo, no hace nada.
     *
     * @param int $id_product
     * @return array Configuración del producto (ya actualizada si se auto-activó)
     */
    public static function ensureActiveIfQualifies($id_product)
    {
        $id_product = (int) $id_product;
        $config = self::getProductConfig($id_product);

        if (!empty($config['active'])) {
            return $config;
        }

        $resolved = self::resolveConfig($config);

        if (empty($resolved['id_attribute_group_peso'])) {
            return $config;
        }

        $combination_ids = self::getCombinationIds($id_product);

        if (empty($combination_ids)) {
            return $config;
        }

        $attributes_by_combination = self::getAttributesByCombination($combination_ids);
        $peso_mapeos = self::getAllPesoMapeos();

        $qualifies = false;

        foreach ($attributes_by_combination as $attributes) {
            $analisis = self::analizarAtributosCombinacion($attributes, $resolved, $peso_mapeos);
            if ($analisis['tiene_peso']) {
                $qualifies = true;
                break;
            }
        }

        if (!$qualifies) {
            return $config;
        }

        $resolved['precio_por_kilo'] = isset($config['precio_por_kilo']) ? $config['precio_por_kilo'] : 0;
        $resolved['active'] = 1;

        self::saveProductConfig($id_product, $resolved);
        self::writeLog('Producto ID: ' . $id_product . ' - activado automáticamente (combinación con peso reconocido detectada)');

        return self::getProductConfig($id_product);
    }

    /**
     * IDs de combinación de un producto
     *
     * @param int $id_product
     * @return array
     */
    protected static function getCombinationIds($id_product)
    {
        $sql = "SELECT pa.id_product_attribute
                FROM " . _DB_PREFIX_ . "product_attribute pa
                WHERE pa.id_product = " . (int) $id_product;

        $rows = Db::getInstance()->executeS($sql);
        $ids = array();

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $ids[] = (int) $row['id_product_attribute'];
            }
        }

        return $ids;
    }

    /**
     * Atributos de cada combinación, agrupados por id_product_attribute
     *
     * @param array $combination_ids
     * @return array
     */
    protected static function getAttributesByCombination($combination_ids)
    {
        if (empty($combination_ids)) {
            return array();
        }

        $sql = "SELECT pac.id_product_attribute, a.id_attribute, a.id_attribute_group
                FROM " . _DB_PREFIX_ . "product_attribute_combination pac
                INNER JOIN " . _DB_PREFIX_ . "attribute a ON pac.id_attribute = a.id_attribute
                WHERE pac.id_product_attribute IN (" . implode(',', array_map('intval', $combination_ids)) . ")";

        $rows = Db::getInstance()->executeS($sql);
        $by_combination = array();

        foreach ($rows as $row) {
            $id_product_attribute = (int) $row['id_product_attribute'];
            if (!isset($by_combination[$id_product_attribute])) {
                $by_combination[$id_product_attribute] = array();
            }
            $by_combination[$id_product_attribute][] = $row;
        }

        return $by_combination;
    }

    /**
     * Actualiza los precios de todas las combinaciones de un producto.
     * Las combinaciones que no tengan un atributo de peso reconocido y
     * mapeado en gramos se dejan tal cual (no se tocan).
     *
     * @param int $id_product ID del producto
     * @return bool Éxito o fracaso
     */
    public static function updateCombinationPrices($id_product)
    {
        $id_product = (int) $id_product;

        $config = self::resolveConfig(self::getProductConfig($id_product));

        $product = new Product($id_product);
        $precio_base = self::getPrecioBaseConIva($id_product, $product);
        $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');

        if (empty($config['id_attribute_group_peso'])) {
            self::writeLog('Producto ID: ' . $id_product . ' - no se puede calcular: falta el grupo de atributos de peso');
            return false;
        }

        $combination_ids = self::getCombinationIds($id_product);

        if (empty($combination_ids)) {
            self::writeLog('Producto ID: ' . $id_product . ' - no tiene combinaciones');
            return false;
        }

        $attributes_by_combination = self::getAttributesByCombination($combination_ids);
        $peso_mapeos = self::getAllPesoMapeos();

        $tax_rate = self::getTaxRate($id_product);
        $tax_multiplier = 1 + ($tax_rate / 100);

        $updates = array();
        $omitidas = array();

        foreach ($combination_ids as $id_product_attribute) {
            $attributes = isset($attributes_by_combination[$id_product_attribute])
                ? $attributes_by_combination[$id_product_attribute]
                : array();

            $analisis = self::analizarAtributosCombinacion($attributes, $config, $peso_mapeos);

            if (!$analisis['tiene_peso']) {
                // Sin atributo de peso reconocido y mapeado: no se toca.
                $omitidas[] = $id_product_attribute;
                continue;
            }

            $updates[$id_product_attribute] = self::calcularImpacto($precio_base, $precio_individual, $tax_multiplier, $analisis);
        }

        if (!empty($omitidas)) {
            self::writeLog('Producto ID: ' . $id_product . ' - combinaciones sin peso mapeado (no tocadas): ' . implode(', ', $omitidas));
        }

        if (empty($updates)) {
            self::writeLog('Producto ID: ' . $id_product . ' - ninguna combinación tenía peso reconocido, nada que calcular');
            return false;
        }

        $success = self::applyUpdates($updates);

        Tools::clearAllCache();
        Configuration::updateGlobalValue('PS_SPECIFIC_PRICE_FEATURE_ACTIVE', '1');

        self::writeLog('Producto ID: ' . $id_product . ' - ' . count($updates) . ' combinación(es) recalculada(s)');

        return $success;
    }

    /**
     * Actualiza el precio de una única combinación. Si no tiene un atributo
     * de peso reconocido y mapeado, no hace nada (devuelve false).
     *
     * @param int $id_product ID del producto
     * @param int $id_product_attribute ID de la combinación
     * @return bool Éxito o fracaso
     */
    public static function updateCombinationPrice($id_product, $id_product_attribute)
    {
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;

        $config = self::resolveConfig(self::getProductConfig($id_product));

        if (empty($config['id_attribute_group_peso'])) {
            self::writeLog('Combinación ID: ' . $id_product_attribute . ' - no se puede calcular: falta el grupo de atributos de peso');
            return false;
        }

        $product = new Product($id_product);
        $precio_base = self::getPrecioBaseConIva($id_product, $product);
        $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');

        $attributes_by_combination = self::getAttributesByCombination(array($id_product_attribute));
        $attributes = isset($attributes_by_combination[$id_product_attribute]) ? $attributes_by_combination[$id_product_attribute] : array();

        if (empty($attributes)) {
            self::writeLog('Combinación ID: ' . $id_product_attribute . ' - no se encontraron atributos');
            return false;
        }

        $peso_mapeos = self::getAllPesoMapeos();
        $analisis = self::analizarAtributosCombinacion($attributes, $config, $peso_mapeos);

        if (!$analisis['tiene_peso']) {
            self::writeLog('Combinación ID: ' . $id_product_attribute . ' - sin peso mapeado, no se toca');
            return false;
        }

        $tax_rate = self::getTaxRate($id_product);
        $tax_multiplier = 1 + ($tax_rate / 100);

        $impacto = self::calcularImpacto($precio_base, $precio_individual, $tax_multiplier, $analisis);

        $success = self::applyUpdates(array($id_product_attribute => $impacto));

        if (method_exists('Tools', 'clearAllCache')) {
            Tools::clearAllCache();
        }

        self::writeLog('Combinación ID: ' . $id_product_attribute . ' (producto ' . $id_product . ') - recalculada, impacto: ' . $impacto);

        return $success;
    }

    /**
     * Guarda el precio (impacto) de un conjunto de combinaciones usando el
     * objeto Combination (para que PrestaShop dispare sus hooks normales).
     * Si por lo que sea no se puede cargar el objeto, recurre a un UPDATE
     * directo como último recurso.
     *
     * @param array $updates [id_product_attribute => impacto]
     * @return bool
     */
    protected static function applyUpdates($updates)
    {
        $success = true;

        foreach ($updates as $id_product_attribute => $impact_price) {
            $combination = new Combination($id_product_attribute);

            if (Validate::isLoadedObject($combination)) {
                $combination->price = (float) $impact_price;
                if (!$combination->save()) {
                    $success = false;
                    self::writeLog('Error al guardar la combinación #' . $id_product_attribute . ' mediante el objeto Combination');
                }
            } else {
                // Último recurso: UPDATE directo si el objeto no carga.
                $result = Db::getInstance()->execute(
                    "UPDATE `" . _DB_PREFIX_ . "product_attribute`
                     SET `price` = " . (float) $impact_price . "
                     WHERE `id_product_attribute` = " . (int) $id_product_attribute
                );

                if (!$result) {
                    $success = false;
                }

                self::writeLog('Combinación #' . $id_product_attribute . ' actualizada por SQL directo (no se pudo cargar el objeto Combination)');
            }
        }

        return $success;
    }

    /**
     * Añade una línea al log del módulo, con rotación para que no crezca sin
     * límite.
     *
     * @param string $message
     */
    public static function writeLog($message)
    {
        $path = _PS_MODULE_DIR_ . 'combinacionescarne/' . self::LOG_FILE;

        $line = date('Y-m-d H:i:s') . ' - ' . $message;

        $lines = array();
        if (file_exists($path)) {
            $existing = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($existing)) {
                $lines = $existing;
            }
        }

        $lines[] = $line;

        if (count($lines) > self::LOG_MAX_LINES) {
            $lines = array_slice($lines, -self::LOG_MAX_LINES);
        }

        @file_put_contents($path, implode("\n", $lines) . "\n");
    }
}
