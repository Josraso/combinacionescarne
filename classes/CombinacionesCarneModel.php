<?php
/**
 * Clase modelo para el módulo CombinacionesCarne
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class CombinacionesCarneModel
{
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
                'active' => 0
            );

        }
        
        return $result;
		
    }
    /**
 * Comprueba si una combinación necesita ser actualizada
 * 
 * @param int $id_product ID del producto
 * @param int $id_product_attribute ID de la combinación
 * @return bool True si necesita actualización, False en caso contrario
 */
public static function combinationNeedsUpdate($id_product, $id_product_attribute)
{
    $id_product = (int) $id_product;
    $id_product_attribute = (int) $id_product_attribute;
    
    // Obtener la configuración del producto
    $config = self::getProductConfig($id_product);
    
// Usamos el precio base original sin descuentos específicos (con IVA)
$product = new Product($id_product);
$tax_rate = self::getTaxRate($id_product);
$tax_multiplier = 1 + ($tax_rate / 100);
$precio_base = $product->price * $tax_multiplier;
    
    // Obtenemos la configuración de grupos si no está definida a nivel de producto
    if (empty($config['id_attribute_group_envasado'])) {
        $config['id_attribute_group_envasado'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
        $config['id_attribute_envasado_granel'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
        $config['id_attribute_envasado_individual'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
        $config['id_attribute_group_peso'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0);
    }
    
    // Obtenemos el precio del envasado individual (con IVA)
    $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
    
    // Verificar si tenemos configurados los grupos necesarios
    if ($config['id_attribute_group_peso'] == 0 || $config['id_attribute_group_envasado'] == 0) {
        return false;
    }
    
    // Cargamos los mapeos de peso
    $peso_mapeos = self::getAllPesoMapeos();
    
    // Obtener los atributos de esta combinación
    $attributes_query = "
        SELECT a.id_attribute, a.id_attribute_group
        FROM " . _DB_PREFIX_ . "product_attribute_combination pac
        INNER JOIN " . _DB_PREFIX_ . "attribute a ON pac.id_attribute = a.id_attribute
        WHERE pac.id_product_attribute = " . (int)$id_product_attribute;
    
    $attributes = Db::getInstance()->executeS($attributes_query);
    
    if (empty($attributes)) {
        return false;
    }
    
    // Variables para determinar si es envasado individual y el peso
    $es_individual = false;
    $peso_gramos = 0;
    
    // Recorremos los atributos de la combinación
    foreach ($attributes as $attribute) {
        $id_attribute = (int)$attribute['id_attribute'];
        $id_attribute_group = (int)$attribute['id_attribute_group'];
        
        // Detectamos si es envasado individual
        if ($id_attribute_group == $config['id_attribute_group_envasado']) {
            if ($id_attribute == $config['id_attribute_envasado_individual']) {
                $es_individual = true;
            }
        }
        
        // Detectamos el peso a partir del mapeo
        if ($id_attribute_group == $config['id_attribute_group_peso']) {
            // Obtenemos el peso del mapeo
            if (isset($peso_mapeos[$id_attribute])) {
                $peso_gramos = (int)$peso_mapeos[$id_attribute];
            }
        }
    }
    
    // Obtenemos la tasa de IVA para hacer la conversión
    $tax_rate = self::getTaxRate($id_product);
    $tax_multiplier = 1 + ($tax_rate / 100);
    
    // Calculamos el impacto de precio basado en la diferencia con 1kg (PRECIO CON IVA)
    $peso_en_kg = $peso_gramos / 1000;
    $impacto_precio_con_iva = $precio_base * ($peso_en_kg - 1);
    
    // Si es envasado individual, sumamos el coste adicional (CON IVA)
    if ($es_individual) {
        $impacto_precio_con_iva += $precio_individual;
    }
    
    // Convertimos el impacto a valor SIN IVA para que PrestaShop lo interprete correctamente
    $impacto_precio_esperado = $impacto_precio_con_iva / $tax_multiplier;
    
    // Redondeamos a 6 decimales (formato de PrestaShop)
    $impacto_precio_esperado = round($impacto_precio_esperado, 6);
    
    // Obtenemos el impacto actual
    $impacto_actual = (float)Db::getInstance()->getValue(
        "SELECT price FROM `" . _DB_PREFIX_ . "product_attribute` 
         WHERE id_product_attribute = " . (int)$id_product_attribute
    );
    
    // Comparamos con margen de tolerancia para errores de redondeo
    return (abs($impacto_actual - $impacto_precio_esperado) > 0.001);
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
        
        // Verificamos si ya existe configuración para este producto
        $exists = Db::getInstance()->getValue(
            "SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "combinacionescarne_product`
             WHERE `id_product` = " . $id_product
        );
        
        // Preparamos los datos
        $data = array(
            'id_product' => $id_product,
            'precio_por_kilo' => (float) $config['precio_por_kilo'],
            'id_attribute_envasado_granel' => (int) $config['id_attribute_envasado_granel'],
            'id_attribute_envasado_individual' => (int) $config['id_attribute_envasado_individual'],
            'id_attribute_group_envasado' => (int) $config['id_attribute_group_envasado'],
            'id_attribute_group_peso' => (int) $config['id_attribute_group_peso'],
            'active' => (int) $config['active']
        );
        
        // Insertamos o actualizamos según corresponda
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
     * @return array Todos los mapeos de peso
     */
    public static function getAllPesoMapeos()
    {
        $sql = "SELECT id_attribute, peso_gramos 
                FROM `" . _DB_PREFIX_ . "combinacionescarne_peso_map`";
        
        $results = Db::getInstance()->executeS($sql);
        $mapeos = array();
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $mapeos[$row['id_attribute']] = $row['peso_gramos'];
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
                $mapeos[$row['id_attribute']] = $row['peso_gramos'];
            }
        }
        
        return $mapeos;
    }
    
    /**
     * Obtiene información detallada de los atributos mapeados
     *
     * @return array Detalles de los atributos de peso mapeados
     */
    public static function getPesoMapeosDetallados()
    {
        $id_lang = (int)Context::getContext()->language->id;
        
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
        
        // Agrupamos por grupos de atributos
        $mapeos_por_grupo = array();
        
        foreach ($results as $row) {
            $id_attribute_group = $row['id_attribute_group'];
            
            if (!isset($mapeos_por_grupo[$id_attribute_group])) {
                $mapeos_por_grupo[$id_attribute_group] = array(
                    'id_attribute_group' => $id_attribute_group,
                    'name' => $row['group_name'],
                    'attributes' => array()
                );
            }
            
            $mapeos_por_grupo[$id_attribute_group]['attributes'][] = array(
                'id_attribute' => $row['id_attribute'],
                'name' => $row['attribute_name'],
                'peso_gramos' => $row['peso_gramos']
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
        
        // Verificamos si ya existe un mapeo para este atributo
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
                'peso_gramos' => $peso_gramos
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
             WHERE `id_attribute` = " . (int)$id_attribute
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
        
        // Obtenemos el grupo de reglas fiscales del producto
        $id_tax_rules_group = (int) Db::getInstance()->getValue(
            "SELECT id_tax_rules_group FROM `" . _DB_PREFIX_ . "product` WHERE id_product = " . $id_product
        );
        
        if ($id_tax_rules_group <= 0) {
            return 0;
        }
        
        // Obtenemos la tasa de IVA para el grupo de reglas fiscales
        $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $id_shop = (int) Context::getContext()->shop->id;
        
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
    * Actualiza los precios de las combinaciones de un producto
    * 
    * @param int $id_product ID del producto
    * @return bool Éxito o fracaso
    */
   public static function updateCombinationPrices($id_product)
   {
       $id_product = (int) $id_product;
       
       // Obtenemos la configuración del producto
       $config = self::getProductConfig($id_product);
       
// Usamos el precio base original sin descuentos específicos (con IVA)
$product = new Product($id_product);
$tax_rate = self::getTaxRate($id_product);
$tax_multiplier = 1 + ($tax_rate / 100);
$precio_base = $product->price * $tax_multiplier;
       
       // Obtenemos la configuración de grupos si no está definida a nivel de producto
       if (empty($config['id_attribute_group_envasado'])) {
           $config['id_attribute_group_envasado'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
           $config['id_attribute_envasado_granel'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
           $config['id_attribute_envasado_individual'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
           $config['id_attribute_group_peso'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0);
       }
       
       // Obtenemos el precio del envasado individual (con IVA)
       $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
       
       // Creamos un array para guardar logs de debugging
       $debug_logs = array();
       $debug_logs[] = "Producto ID: " . $id_product;
       $debug_logs[] = "Precio base (con IVA): " . $precio_base;
       $debug_logs[] = "Precio envase individual (con IVA): " . $precio_individual;
       $debug_logs[] = "Grupo atributo peso: " . $config['id_attribute_group_peso'];
       $debug_logs[] = "Grupo atributo envasado: " . $config['id_attribute_group_envasado'];
       $debug_logs[] = "Atributo envasado granel: " . $config['id_attribute_envasado_granel'];
       $debug_logs[] = "Atributo envasado individual: " . $config['id_attribute_envasado_individual'];
       
       // Verificar si tenemos configurados los grupos necesarios
       if ($config['id_attribute_group_peso'] == 0 || $config['id_attribute_group_envasado'] == 0) {
           $debug_logs[] = "Error: No se han configurado los grupos de atributos necesarios";
           file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
           return false;
       }
       
       // Procesamos cada combinación y calculamos su precio
       $updates = array();
       
       // Primero, construimos un mapeo de pesos por atributo
       $peso_mapeos = self::getAllPesoMapeos();
       $debug_logs[] = "Mapeos de peso cargados: " . count($peso_mapeos);
       
       // Obtenemos información sobre todas las combinaciones
       $combinations_query = "
           SELECT pa.id_product_attribute, pa.price 
           FROM " . _DB_PREFIX_ . "product_attribute pa
           WHERE pa.id_product = " . (int)$id_product;
       
       $combinations = Db::getInstance()->executeS($combinations_query);
       
       if (empty($combinations)) {
           $debug_logs[] = "No se encontraron combinaciones para este producto";
           file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
           return false;
       }
       
       $debug_logs[] = "Número de combinaciones encontradas: " . count($combinations);
       
       // Extraemos todos los IDs de combinaciones para la consulta
       $combination_ids = array();
       foreach ($combinations as $combination) {
           $combination_ids[] = (int)$combination['id_product_attribute'];
       }
       
       if (empty($combination_ids)) {
           $debug_logs[] = "Error: No se pudieron obtener IDs de combinaciones";
           file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
           return false;
       }
       
       // Obtenemos los atributos de todas las combinaciones de una sola vez para optimizar
       $attributes_query = "
           SELECT pac.id_product_attribute, a.id_attribute, a.id_attribute_group, al.name
           FROM " . _DB_PREFIX_ . "product_attribute_combination pac
           INNER JOIN " . _DB_PREFIX_ . "attribute a ON pac.id_attribute = a.id_attribute
           LEFT JOIN " . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = " . (int)Context::getContext()->language->id . ")
           WHERE pac.id_product_attribute IN (" . implode(',', $combination_ids) . ")";
       
       $all_attributes = Db::getInstance()->executeS($attributes_query);
       
       // Agrupamos los atributos por id_product_attribute
       $attributes_by_combination = array();
       foreach ($all_attributes as $attr) {
           $id_product_attribute = (int)$attr['id_product_attribute'];
           if (!isset($attributes_by_combination[$id_product_attribute])) {
               $attributes_by_combination[$id_product_attribute] = array();
           }
           $attributes_by_combination[$id_product_attribute][] = $attr;
       }
       
       // Procesamos cada combinación
       foreach ($combinations as $combination) {
           $id_product_attribute = (int)$combination['id_product_attribute'];
           $debug_logs[] = "\n--- Procesando combinación ID: " . $id_product_attribute . " ---";
           
           // Verificamos si tenemos atributos para esta combinación
           if (!isset($attributes_by_combination[$id_product_attribute]) || empty($attributes_by_combination[$id_product_attribute])) {
               $debug_logs[] = "No se encontraron atributos para esta combinación";
               continue;
           }
           
           $attributes = $attributes_by_combination[$id_product_attribute];
           $debug_logs[] = "Atributos de la combinación: " . count($attributes);
           
           // Variables para determinar si es envasado individual y el peso
           $es_individual = false;
           $peso_gramos = 0;
           
           // Recorremos los atributos de la combinación
           foreach ($attributes as $attribute) {
               $id_attribute = (int)$attribute['id_attribute'];
               $id_attribute_group = (int)$attribute['id_attribute_group'];
               $nombre_atributo = $attribute['name'];
               
               $debug_logs[] = "Atributo: " . $nombre_atributo . " (ID: " . $id_attribute . ", Grupo: " . $id_attribute_group . ")";
               
               // Detectamos si es envasado individual
               if ($id_attribute_group == $config['id_attribute_group_envasado']) {
                   if ($id_attribute == $config['id_attribute_envasado_individual']) {
                       $es_individual = true;
                       $debug_logs[] = "Detectado como envasado individual";
                   } else if ($id_attribute == $config['id_attribute_envasado_granel']) {
                       $debug_logs[] = "Detectado como envasado a granel";
                   }
               }
               
               // Detectamos el peso a partir del mapeo
               if ($id_attribute_group == $config['id_attribute_group_peso']) {
                   $debug_logs[] = "Atributo de peso: " . $nombre_atributo;
                   
                   // Obtenemos el peso del mapeo
                   if (isset($peso_mapeos[$id_attribute])) {
                       $peso_gramos = (int)$peso_mapeos[$id_attribute];
                       $debug_logs[] = "Peso desde mapeo: " . $peso_gramos . " gramos (" . ($peso_gramos / 1000) . " kg)";
                   } else {
                       $debug_logs[] = "¡ADVERTENCIA! No se encontró mapeo de peso para este atributo";
                   }
               }
           }
           
           // Obtenemos la tasa de IVA para hacer la conversión
           $tax_rate = self::getTaxRate($id_product);
           $tax_multiplier = 1 + ($tax_rate / 100);
           
           // Calculamos el impacto de precio basado en la diferencia con 1kg (PRECIO CON IVA)
           $peso_en_kg = $peso_gramos / 1000;
           $impacto_precio_con_iva = $precio_base * ($peso_en_kg - 1);
           $debug_logs[] = "Impacto base por diferencia de peso (" . $precio_base . " * (" . $peso_en_kg . " - 1)): " . $impacto_precio_con_iva;
           
           // Si es envasado individual, sumamos el coste adicional (CON IVA)
           if ($es_individual) {
               $impacto_precio_con_iva += $precio_individual;
               $debug_logs[] = "Con envase individual (+". $precio_individual ."): " . $impacto_precio_con_iva;
           }
           
           // Convertimos el impacto a valor SIN IVA para que PrestaShop lo interprete correctamente
           $impacto_precio = $impacto_precio_con_iva / $tax_multiplier;
           
           // Redondeamos a 6 decimales (formato de PrestaShop)
           $impacto_precio = round($impacto_precio, 6);
           
           $debug_logs[] = "Tasa IVA: " . $tax_rate . "% (multiplicador: " . $tax_multiplier . ")";
           $debug_logs[] = "Impacto final SIN IVA (para guardar): " . $impacto_precio;
           
           // Añadimos la actualización a nuestra lista
           $updates[$id_product_attribute] = $impacto_precio;
       }
       
       $success = true;
       
       // Primero actualizamos usando SQL directo para todas las combinaciones
       foreach ($updates as $id_product_attribute => $impact_price) {
           $result = Db::getInstance()->execute(
               "UPDATE `" . _DB_PREFIX_ . "product_attribute` 
                SET `price` = " . (float)$impact_price . " 
                WHERE `id_product_attribute` = " . (int)$id_product_attribute . " 
                AND `id_product` = " . (int)$id_product
           );
           
           if (!$result) {
               $debug_logs[] = "Error al actualizar combinación #" . $id_product_attribute . " mediante SQL directo: " . Db::getInstance()->getMsgError();
           } else {
               $debug_logs[] = "Combinación #" . $id_product_attribute . " actualizada mediante SQL directo con impacto: " . $impact_price;
           }
       }
       
       // Luego utilizamos la clase Combination para cada combinación
       // Esto asegura que se ejecuten todos los hooks de PrestaShop
       foreach ($updates as $id_product_attribute => $impact_price) {
           try {
               // Cargamos la combinación usando el objeto Combination de PrestaShop
               $combination = new Combination($id_product_attribute);
               
               if (Validate::isLoadedObject($combination)) {
                   // Establecemos el precio (impacto)
                   $combination->price = (float)$impact_price;
                   
                   // Guardamos la combinación
                   $result = $combination->save();
                   
                   if (!$result) {
                       $debug_logs[] = "Error al guardar la combinación #" . $id_product_attribute . " mediante objeto Combination";
                       $success = false;
                   } else {
                       $debug_logs[] = "Combinación #" . $id_product_attribute . " guardada mediante objeto Combination con impacto: " . $impact_price;
                   }
               } else {
                   $debug_logs[] = "No se pudo cargar el objeto Combination para ID: " . $id_product_attribute;
                   $success = false;
               }
           } catch (Exception $e) {
               $debug_logs[] = "Excepción al procesar combinación #" . $id_product_attribute . ": " . $e->getMessage();
               $success = false;
           }
       }
       
       // Guardamos el log para depuración
       file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
       
       // Realizamos una limpieza completa de caché
       Tools::clearAllCache();
       
       // Forzamos la actualización del valor PS_SPECIFIC_PRICE_FEATURE_ACTIVE
       Configuration::updateGlobalValue('PS_SPECIFIC_PRICE_FEATURE_ACTIVE', '1');
       
       return $success;
   }
   
   /**
    * Actualiza el precio de una combinación específica
    * 
    * @param int $id_product ID del producto
    * @param int $id_product_attribute ID de la combinación
    * @return bool Éxito o fracaso
    */
   public static function updateCombinationPrice($id_product, $id_product_attribute)
   {
       $id_product = (int) $id_product;
       $id_product_attribute = (int) $id_product_attribute;
       
       // Obtenemos la configuración del producto
       $config = self::getProductConfig($id_product);
       
// Usamos el precio base original sin descuentos específicos (con IVA)
$product = new Product($id_product);
$tax_rate = self::getTaxRate($id_product);
$tax_multiplier = 1 + ($tax_rate / 100);
$precio_base = $product->price * $tax_multiplier;
       
       // Obtenemos la configuración de grupos si no está definida a nivel de producto
       if (empty($config['id_attribute_group_envasado'])) {
           $config['id_attribute_group_envasado'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_ENVASADO', 0);
           $config['id_attribute_envasado_granel'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_GRANEL', 0);
           $config['id_attribute_envasado_individual'] = (int) Configuration::get('COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL', 0);
           $config['id_attribute_group_peso'] = (int) Configuration::get('COMBINACIONESCARNE_GRUPO_PESO', 0);
       }
       
       // Obtenemos el precio del envasado individual (con IVA)
       $precio_individual = (float) Configuration::get('COMBINACIONESCARNE_PRECIO_INDIVIDUAL');
       
       // Creamos un array para guardar logs de debugging
       $debug_logs = array();
       $debug_logs[] = "Actualización individual - Producto ID: " . $id_product . " - Combinación ID: " . $id_product_attribute;
       
       // Verificar si tenemos configurados los grupos necesarios
       if ($config['id_attribute_group_peso'] == 0 || $config['id_attribute_group_envasado'] == 0) {
           $debug_logs[] = "Error: No se han configurado los grupos de atributos necesarios";
           file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
           return false;
       }
       
       // Cargamos los mapeos de peso
       $peso_mapeos = self::getAllPesoMapeos();
       
       // Obtenemos los atributos de esta combinación
       $attributes_query = "
           SELECT a.id_attribute, a.id_attribute_group, al.name
           FROM " . _DB_PREFIX_ . "product_attribute_combination pac
           INNER JOIN " . _DB_PREFIX_ . "attribute a ON pac.id_attribute = a.id_attribute
           LEFT JOIN " . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = " . (int)Context::getContext()->language->id . ")
           WHERE pac.id_product_attribute = " . (int)$id_product_attribute;
       
       $attributes = Db::getInstance()->executeS($attributes_query);
       
       if (empty($attributes)) {
           $debug_logs[] = "No se encontraron atributos para esta combinación";
           file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/log.txt', implode("\n", $debug_logs));
           return false;
       }
       
       // Variables para determinar si es envasado individual y el peso
       $es_individual = false;
       $peso_gramos = 0;
       
       // Recorremos los atributos de la combinación
       foreach ($attributes as $attribute) {
           $id_attribute = (int)$attribute['id_attribute'];
           $id_attribute_group = (int)$attribute['id_attribute_group'];
           $nombre_atributo = $attribute['name'];
           
           $debug_logs[] = "Atributo: " . $nombre_atributo . " (ID: " . $id_attribute . ", Grupo: " . $id_attribute_group . ")";
           
           // Detectamos si es envasado individual
           if ($id_attribute_group == $config['id_attribute_group_envasado']) {
               if ($id_attribute == $config['id_attribute_envasado_individual']) {
                   $es_individual = true;
                   $debug_logs[] = "Detectado como envasado individual";
               } else if ($id_attribute == $config['id_attribute_envasado_granel']) {
                   $debug_logs[] = "Detectado como envasado a granel";
               }
           }
           
           // Detectamos el peso a partir del mapeo
           if ($id_attribute_group == $config['id_attribute_group_peso']) {
               $debug_logs[] = "Atributo de peso: " . $nombre_atributo;
               
               // Obtenemos el peso del mapeo
               if (isset($peso_mapeos[$id_attribute])) {
                   $peso_gramos = (int)$peso_mapeos[$id_attribute];
                   $debug_logs[] = "Peso desde mapeo: " . $peso_gramos . " gramos (" . ($peso_gramos / 1000) . " kg)";
               } else {
                   $debug_logs[] = "¡ADVERTENCIA! No se encontró mapeo de peso para este atributo";
               }
           }
       }
       
       // Obtenemos la tasa de IVA para hacer la conversión
       $tax_rate = self::getTaxRate($id_product);
       $tax_multiplier = 1 + ($tax_rate / 100);
       
       // Calculamos el impacto de precio basado en la diferencia con 1kg (PRECIO CON IVA)
       $peso_en_kg = $peso_gramos / 1000;
       $impacto_precio_con_iva = $precio_base * ($peso_en_kg - 1);
       $debug_logs[] = "Impacto base por diferencia de peso (" . $precio_base . " * (" . $peso_en_kg . " - 1)): " . $impacto_precio_con_iva;
       
       // Si es envasado individual, sumamos el coste adicional (CON IVA)
       if ($es_individual) {
           $impacto_precio_con_iva += $precio_individual;
           $debug_logs[] = "Con envase individual (+". $precio_individual ."): " . $impacto_precio_con_iva;
       }
       
       // Convertimos el impacto a valor SIN IVA para que PrestaShop lo interprete correctamente
       $impacto_precio = $impacto_precio_con_iva / $tax_multiplier;
       
       // Redondeamos a 6 decimales (formato de PrestaShop)
       $impacto_precio = round($impacto_precio, 6);
       
       $debug_logs[] = "Tasa IVA: " . $tax_rate . "% (multiplicador: " . $tax_multiplier . ")";
       $debug_logs[] = "Impacto final SIN IVA (para guardar): " . $impacto_precio;
       
       // Actualizamos usando SQL directo
       $result_sql = Db::getInstance()->execute(
           "UPDATE `" . _DB_PREFIX_ . "product_attribute` 
            SET `price` = " . (float)$impacto_precio . " 
            WHERE `id_product_attribute` = " . (int)$id_product_attribute . " 
            AND `id_product` = " . (int)$id_product
       );
       
       if (!$result_sql) {
           $debug_logs[] = "Error al actualizar mediante SQL: " . Db::getInstance()->getMsgError();
       }
       
       // Actualizamos usando la clase Combination para activar los hooks
       $success = false;
       try {
           $combination = new Combination($id_product_attribute);
           if (Validate::isLoadedObject($combination)) {
               $combination->price = (float)$impacto_precio;
               $success = $combination->save();
               
               $debug_logs[] = $success ? 
                   "Combinación guardada correctamente mediante objeto Combination" : 
                   "Error al guardar mediante objeto Combination";
           } else {
               $debug_logs[] = "No se pudo cargar el objeto Combination para ID: " . $id_product_attribute;
           }
       } catch (Exception $e) {
           $debug_logs[] = "Excepción: " . $e->getMessage();
       }
       
       // Limpiamos caché
       if (method_exists('Tools', 'clearAllCache')) {
           Tools::clearAllCache();
       }
       
       // Log
       file_put_contents(_PS_MODULE_DIR_ . 'combinacionescarne/single_update_log.txt', implode("\n", $debug_logs));
       
       return $success || $result_sql;
   }
}