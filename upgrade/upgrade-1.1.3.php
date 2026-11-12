<?php
/**
 * combinacionescarne 1.1.3: conexión con el servidor de actualizaciones de josra.
 *
 * @author josra
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_3($module)
{
    return $module->getUpdater()->registerHooks();
}
