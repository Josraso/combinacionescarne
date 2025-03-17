{*
* Plantilla de configuración de producto para el módulo CombinacionesCarne
*}

<div class="bootstrap">
    <div class="card">
        <div class="card-header">
            <h2>
                <i class="icon icon-tag"></i> 
                {l s='Configuración para' mod='combinacionescarne'}: 
                <a href="{$link->getAdminLink('AdminProducts', true, ['id_product' => $id_product])}&updateproduct" target="_blank">
                    {$product->name}
                    <span class="badge badge-info ml-2">{l s='ID' mod='combinacionescarne'}: {$id_product}</span>
                    <span class="badge badge-secondary ml-2">{l s='Ref' mod='combinacionescarne'}: {$product->reference}</span>
                    <i class="icon icon-external-link" title="{l s='Abrir producto en una nueva pestaña' mod='combinacionescarne'}"></i>
                </a>
            </h2>
        </div>
        
        <div class="card-body">
            <!-- Resumen del producto -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Precio base (con IVA)' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{displayPrice price=$precio_base_producto}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Envasado individual' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{displayPrice price=$precio_individual}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Combinaciones' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{count($combinations)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navegación por pestañas -->
            <ul class="nav nav-tabs" id="productConfigTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="config-tab" data-toggle="tab" href="#config-content" role="tab" aria-controls="config-content" aria-selected="true">
                        <i class="icon icon-cogs"></i> {l s='Configuración' mod='combinacionescarne'}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="combinations-tab" data-toggle="tab" href="#combinations-content" role="tab" aria-controls="combinations-content" aria-selected="false">
                        <i class="icon icon-list"></i> {l s='Combinaciones' mod='combinacionescarne'}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="preview-tab" data-toggle="tab" href="#preview-content" role="tab" aria-controls="preview-content" aria-selected="false">
                        <i class="icon icon-eye"></i> {l s='Vista previa de precios' mod='combinacionescarne'}
                    </a>
                </li>
            </ul>

            <!-- Contenido de pestañas -->
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="productConfigTabsContent">
                <!-- Pestaña de Configuración -->
                <div class="tab-pane fade show active" id="config-content" role="tabpanel" aria-labelledby="config-tab">
                    <form id="product_config_form" class="form-horizontal" action="{$current_url}" method="post">
                        <input type="hidden" name="id_product" value="{$id_product}">
                        
                        <!-- Estado general -->
                        <div class="form-group row">
                            <div class="col-md-9 offset-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="active" name="active" value="1" {if $product_config.active}checked="checked"{/if}>
                                    <label class="custom-control-label" for="active">
                                        <strong>{l s='Activar cálculo automático para este producto' mod='combinacionescarne'}</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    {l s='Cuando está activado, el módulo calculará automáticamente los precios de las combinaciones según el peso y tipo de envasado.' mod='combinacionescarne'}
                                </small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Selector de configuración personalizada -->
                        <div class="form-group row">
                            <div class="col-md-9 offset-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="override_global_config" name="override_global_config" value="0" {if $product_config.override_global}checked="checked"{/if}>
                                    <label class="custom-control-label" for="override_global_config">
                                        <strong>{l s='Personalizar configuración para este producto' mod='combinacionescarne'}</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    {l s='Cuando está desactivado, se utilizará la configuración global del módulo.' mod='combinacionescarne'}
                                </small>
                            </div>
                        </div>
                        
                        <!-- Sección de configuración personalizada -->
                        <div id="custom_config_section" {if !$product_config.override_global}style="display:none"{/if}>
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{l s='Grupo de atributos para Envasado' mod='combinacionescarne'}</label>
                                <div class="col-md-9">
                                    <select name="id_attribute_group_envasado" id="attribute_group_envasado" class="form-control">
                                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                        {foreach from=$attribute_groups item=group}
                                            <option value="{$group.id_attribute_group}" {if $product_config.id_attribute_group_envasado == $group.id_attribute_group}selected="selected"{/if}>
                                                {$group.name}
                                            </option>
                                        {/foreach}
                                    </select>
                                    <small class="form-text text-muted">
                                        {l s='Grupo que contiene los atributos que definen el tipo de envasado (granel/individual).' mod='combinacionescarne'}
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{l s='Atributo para Envasado Granel' mod='combinacionescarne'}</label>
                                <div class="col-md-9">
                                    <select name="id_attribute_envasado_granel" id="attribute_envasado_granel" class="form-control" {if !$product_config.id_attribute_group_envasado}disabled{/if}>
                                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                        {foreach from=$envasado_attributes item=attribute}
                                            <option value="{$attribute.id_attribute}" data-group="{$attribute.id_attribute_group}" {if $product_config.id_attribute_envasado_granel == $attribute.id_attribute}selected="selected"{/if}>
                                                {$attribute.name}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{l s='Atributo para Envasado Individual' mod='combinacionescarne'}</label>
                                <div class="col-md-9">
                                    <select name="id_attribute_envasado_individual" id="attribute_envasado_individual" class="form-control" {if !$product_config.id_attribute_group_envasado}disabled{/if}>
                                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                        {foreach from=$envasado_attributes item=attribute}
                                            <option value="{$attribute.id_attribute}" data-group="{$attribute.id_attribute_group}" {if $product_config.id_attribute_envasado_individual == $attribute.id_attribute}selected="selected"{/if}>
                                                {$attribute.name}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group row">
                                <label class="col-md-3 col-form-label">{l s='Grupo de atributos para Peso' mod='combinacionescarne'}</label>
                                <div class="col-md-9">
                                    <select name="id_attribute_group_peso" id="attribute_group_peso" class="form-control">
                                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                        {foreach from=$attribute_groups item=group}
                                            <option value="{$group.id_attribute_group}" {if $product_config.id_attribute_group_peso == $group.id_attribute_group}selected="selected"{/if}>
                                                {$group.name}
                                            </option>
                                        {/foreach}
                                    </select>
                                    <small class="form-text text-muted">
                                        {l s='Grupo que contiene los atributos de peso del producto.' mod='combinacionescarne'}
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Botones de acción -->
                        <div class="form-group row">
                            <div class="col-md-9 offset-md-3">
                                <button type="submit" name="submitProductConfig" class="btn btn-primary">
                                    <i class="icon icon-save"></i> {l s='Guardar' mod='combinacionescarne'}
                                </button>
                                <button type="submit" name="submitCalculate" class="btn btn-success ml-2">
                                    <i class="icon icon-cogs"></i> {l s='Guardar y calcular precios' mod='combinacionescarne'}
                                </button>
                                <a href="{$current_url}" class="btn btn-outline-secondary ml-2">
                                    <i class="icon icon-arrow-left"></i> {l s='Volver' mod='combinacionescarne'}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Pestaña de Combinaciones -->
						
<div class="tab-pane fade" id="combinations-content" role="tabpanel" aria-labelledby="combinations-tab">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="icon icon-search"></i></span>
                                </div>
                                <input type="text" id="combinations-search" class="form-control" placeholder="{l s='Buscar combinación...' mod='combinacionescarne'}">
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary" id="expand-all-combinations">
                                    <i class="icon icon-plus-square"></i> {l s='Expandir todo' mod='combinacionescarne'}
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="collapse-all-combinations">
                                    <i class="icon icon-minus-square"></i> {l s='Colapsar todo' mod='combinacionescarne'}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="combinations-wrapper">
                        {if empty($combinations)}
                            <div class="alert alert-warning">
                                <i class="icon icon-warning"></i> {l s='Este producto no tiene combinaciones o las combinaciones no están configuradas correctamente.' mod='combinacionescarne'}
                            </div>
                        {else}
                            <div class="accordion" id="combinationsAccordion">
                                {foreach from=$combinations item=combination name=combinations}
                                    <div class="card mb-2 combination-card" data-attributes="{foreach from=$combination.attributes item=attribute}{$attribute.name|lower} {/foreach}">
                                        <div class="card-header d-flex justify-content-between align-items-center" id="heading{$combination.id_product_attribute}">
                                            <div class="combination-header-info">
                                                <strong>#{$combination.id_product_attribute}</strong>: 
                                                {foreach from=$combination.attributes item=attribute name=attrs}
                                                    {$attribute.group_name}: <span class="badge badge-info">{$attribute.name}</span>{if !$smarty.foreach.attrs.last} - {/if}
                                                {/foreach}
                                                <div class="mt-1">
                                                    <small>
                                                        <strong>{l s='Tipo de envasado:' mod='combinacionescarne'}</strong> {if $combination.es_individual}{l s='Individual' mod='combinacionescarne'}{else}{l s='Granel' mod='combinacionescarne'}{/if} - 
                                                        <strong>{l s='Peso Aprox.:' mod='combinacionescarne'}</strong> {$combination.peso_gramos} g ({$combination.peso_kg} kg)
                                                    </small>
                                                </div>
                                            </div>
                                            <button class="btn btn-link btn-sm" type="button" data-toggle="collapse" data-target="#collapse{$combination.id_product_attribute}" aria-expanded="false" aria-controls="collapse{$combination.id_product_attribute}">
                                                <i class="icon icon-info-circle"></i> {l s='Detalles' mod='combinacionescarne'}
                                            </button>
                                        </div>
                                        <div id="collapse{$combination.id_product_attribute}" class="collapse" aria-labelledby="heading{$combination.id_product_attribute}" data-parent="#combinationsAccordion">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5>{l s='Información' mod='combinacionescarne'}</h5>
                                                        <ul class="list-unstyled">
                                                            <li><strong>{l s='Peso:' mod='combinacionescarne'}</strong> {$combination.peso_gramos} g ({$combination.peso_kg} kg)</li>
                                                            <li><strong>{l s='Tipo envasado:' mod='combinacionescarne'}</strong> {if $combination.es_individual}{l s='Individual' mod='combinacionescarne'}{else}{l s='Granel' mod='combinacionescarne'}{/if}</li>
                                                            <li><strong>{l s='Impacto en precio (sin IVA):' mod='combinacionescarne'}</strong> {displayPrice price=$combination.impact_price}</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5>{l s='Cálculo de precio' mod='combinacionescarne'}</h5>
                                                        <table class="table table-sm">
                                                            <tr>
                                                                <td>{l s='Precio base:' mod='combinacionescarne'}</td>
                                                                <td class="text-right">{displayPrice price=$precio_base_producto}</td>
                                                            </tr>
                                                            <tr>
                                                                <td>{l s='Ajuste por peso:' mod='combinacionescarne'}</td>
                                                                <td class="text-right">{displayPrice price=($precio_base_producto * ($combination.peso_kg - 1))}</td>
                                                            </tr>
                                                            {if $combination.es_individual}
                                                                <tr>
                                                                    <td>{l s='Coste envasado individual:' mod='combinacionescarne'}</td>
                                                                    <td class="text-right">{displayPrice price=$precio_individual}</td>
                                                                </tr>
                                                            {/if}
                                                            <tr class="table-active">
                                                                <th>{l s='Precio final (con IVA):' mod='combinacionescarne'}</th>
                                                                {* Corregimos el uso del operador ternario dentro de expresiones complejas *}
                                                                {assign var=precio_final value=$precio_base_producto+($precio_base_producto*($combination.peso_kg-1))}
                                                                {if $combination.es_individual}
                                                                    {assign var=precio_final value=$precio_final+$precio_individual}
                                                                {/if}
                                                                <th class="text-right">{displayPrice price=$precio_final}</th>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                {if $combination.needs_update}
                                                <div class="text-right mt-3">
                                                    <button type="button" class="btn btn-sm btn-outline-primary update-single-combination" data-id="{$combination.id_product_attribute}">
                                                        <i class="icon icon-refresh"></i> {l s='Actualizar esta combinación' mod='combinacionescarne'}
                                                    </button>
                                                </div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        {/if}
                    </div>
                </div>
                
                <!-- Pestaña de Vista Previa de Precios -->
                <div class="tab-pane fade" id="preview-content" role="tabpanel" aria-labelledby="preview-tab">
                    <div class="alert alert-info mb-3">
                        <i class="icon icon-info-circle"></i> {l s='Esta sección muestra una previsualización de cómo se calcularán los precios de las combinaciones según la configuración actual. Los cambios no se guardarán hasta que haga clic en "Guardar y calcular precios".' mod='combinacionescarne'}
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
    <tr>
        <th>{l s='Combinación' mod='combinacionescarne'}</th>
        <th>{l s='Atributos' mod='combinacionescarne'}</th>
        <th>{l s='Peso' mod='combinacionescarne'}</th>
        <th>{l s='Envasado' mod='combinacionescarne'}</th>
        <th class="text-right">{l s='Impacto (con IVA)' mod='combinacionescarne'}</th>
        <th class="text-right">{l s='Precio actual' mod='combinacionescarne'}</th>
        <th class="text-right">{l s='Nuevo precio' mod='combinacionescarne'}</th>
        <th class="text-right">{l s='Diferencia' mod='combinacionescarne'}</th>
    </tr>
</thead>
                            <tbody>
                                {if empty($combinations)}
                                    <tr>
                                        <td colspan="7" class="text-center">{l s='No hay combinaciones disponibles' mod='combinacionescarne'}</td>
                                    </tr>
                                {else}
                                    {foreach from=$combinations item=combination}
                                        {* Corregimos el uso del operador ternario dentro de expresiones complejas *}
                                        {assign var=nuevo_precio value=$precio_base_producto+($precio_base_producto*($combination.peso_kg-1))}
                                        {if $combination.es_individual}
                                            {assign var=nuevo_precio value=$nuevo_precio+$precio_individual}
                                        {/if}
                                        {assign var=precio_actual value=$precio_base_producto+$combination.impact_price_with_tax}
                                        {assign var=diferencia value=$nuevo_precio-$precio_actual}
                                <tr>
    <td>#{$combination.id_product_attribute}</td>
    <td>
        {foreach from=$combination.attributes item=attribute name=attrs}
            {$attribute.group_name}: <span class="badge badge-light">{$attribute.name}</span>{if !$smarty.foreach.attrs.last} - {/if}
        {/foreach}
    </td>
    <td>{$combination.peso_gramos} g</td>
    <td>{if $combination.es_individual}{l s='Individual' mod='combinacionescarne'}{else}{l s='Granel' mod='combinacionescarne'}{/if}</td>
    <td class="text-right">{displayPrice price=$combination.impact_price_with_tax}</td>
    <td class="text-right">{displayPrice price=$precio_actual}</td>
    <td class="text-right">{displayPrice price=$nuevo_precio}</td>
    <td class="text-right {if $diferencia > 0}text-success{elseif $diferencia < 0}text-danger{/if}">
        {if $diferencia != 0}
            {if $diferencia > 0}+{/if}{displayPrice price=$diferencia}
        {else}
            <span class="badge badge-secondary">{l s='Sin cambios' mod='combinacionescarne'}</span>
        {/if}
    </td>
</tr>
                                    {/foreach}
                                {/if}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de procesamiento -->
<div class="modal fade" id="processing-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="sr-only">{l s='Procesando...' mod='combinacionescarne'}</span>
                </div>
                <h5>{l s='Calculando precios' mod='combinacionescarne'}</h5>
                <p>{l s='Por favor, espere mientras se actualizan las combinaciones...' mod='combinacionescarne'}</p>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
// Toggle de configuración personalizada
var overrideCheckbox = document.getElementById('override_global_config');
var customConfigSection = document.getElementById('custom_config_section');
    
if (overrideCheckbox && customConfigSection) {
    overrideCheckbox.addEventListener('change', function() {
        customConfigSection.style.display = this.checked ? 'block' : 'none';
    });
    
    // Configurar estado inicial
    customConfigSection.style.display = overrideCheckbox.checked ? 'block' : 'none';
}
    
    // Filtrado de atributos según el grupo de envasado
    var groupEnvasadoSelect = document.getElementById('attribute_group_envasado');
    var granelSelect = document.getElementById('attribute_envasado_granel');
    var individualSelect = document.getElementById('attribute_envasado_individual');
    
    if (groupEnvasadoSelect && granelSelect && individualSelect) {
        groupEnvasadoSelect.addEventListener('change', function() {
            var selectedGroup = this.value;
            
            // Habilitar/deshabilitar los selectores de atributos
            granelSelect.disabled = (selectedGroup == '0');
            individualSelect.disabled = (selectedGroup == '0');
            
            // Actualizar clase visual para inputs deshabilitados
            if (selectedGroup === '0') {
                granelSelect.parentNode.classList.add('disabled');
                individualSelect.parentNode.classList.add('disabled');
            } else {
                granelSelect.parentNode.classList.remove('disabled');
                individualSelect.parentNode.classList.remove('disabled');
            }
            
            // Filtrar opciones por grupo
            filterSelectOptionsByGroup(granelSelect, selectedGroup);
            filterSelectOptionsByGroup(individualSelect, selectedGroup);
        });
        
        function filterSelectOptionsByGroup(select, groupId) {
            const options = select.querySelectorAll('option');
            let hasValidSelected = false;
            
            for (let i = 1; i < options.length; i++) {
                const optionGroup = options[i].getAttribute('data-group');
                const isValid = groupId === '0' || optionGroup === groupId;
                
                options[i].style.display = isValid ? '' : 'none';
                options[i].disabled = !isValid;
                
                if (options[i].selected && isValid) {
                    hasValidSelected = true;
                }
            }
            
            // Si la opción seleccionada no es válida para el nuevo grupo, seleccionar la primera opción
            if (!hasValidSelected) {
                select.value = '0';
            }
        }
        
        // Aplicar filtro al cargar
        if (groupEnvasadoSelect.value !== '0') {
            const event = new Event('change');
            groupEnvasadoSelect.dispatchEvent(event);
        }
    }
    
    // Búsqueda de combinaciones
    initTableSearch('#combinations-search', '.combinations-wrapper', '.combination-card');
    
    function initTableSearch(inputSelector, tableSelector, rowSelector) {
        const searchInput = document.querySelector(inputSelector);
        if (!searchInput) return;
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector(tableSelector);
            if (!table) return;
            
            const rows = table.querySelectorAll(rowSelector);
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.indexOf(searchTerm) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Expandir/colapsar todas las combinaciones
    const expandAllBtn = document.getElementById('expand-all-combinations');
    const collapseAllBtn = document.getElementById('collapse-all-combinations');
    const combinationCollapses = document.querySelectorAll('.combinations-wrapper .collapse');
    
    if (expandAllBtn && collapseAllBtn && combinationCollapses.length > 0) {
        expandAllBtn.addEventListener('click', function() {
            combinationCollapses.forEach(function(collapse) {
                $(collapse).collapse('show');
            });
        });
        
        collapseAllBtn.addEventListener('click', function() {
            combinationCollapses.forEach(function(collapse) {
                $(collapse).collapse('hide');
            });
        });
    }
    
    // Mostrar modal de procesamiento al calcular precios
    const calcButtons = document.querySelectorAll('[name="submitCalculate"]');
    const processingModal = document.getElementById('processing-modal');
    
    if (calcButtons.length > 0 && processingModal) {
        calcButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                $(processingModal).modal('show');
            });
        });
    }
    
    // Actualizar una combinación específica mediante AJAX
    const updateButtons = document.querySelectorAll('.update-single-combination');
    
    if (updateButtons.length > 0) {
        updateButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const combinationId = this.getAttribute('data-id');
                const productId = {$id_product};
                
                if (!combinationId || !productId) return;
                
                // Mostrar indicador de carga
                const originalText = this.textContent;
                this.innerHTML = '<i class="icon icon-spinner icon-spin"></i> ' + combinacionescarne.i18n.updating;
                this.disabled = true;
                
                // Realizamos la petición AJAX
                fetch(combinacionescarne.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=updateCombination&id_product=' + productId + '&id_product_attribute=' + combinationId + '&ajax=1&token=' + combinacionescarne.token
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        showSuccessMessage(data.message);
                        // Recargar la página después de un breve retraso
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Mostrar mensaje de error
                        showErrorMessage(data.message || '{l s="Error al actualizar la combinación" mod="combinacionescarne"}');
                        // Restaurar botón
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('{l s="Error al procesar la solicitud" mod="combinacionescarne"}');
                    // Restaurar botón
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });
    }
    
    // Configurar el checkbox de "Activar cálculo automático para este producto" como activo por defecto
    const activeCheckbox = document.getElementById('active');
    if (activeCheckbox && activeCheckbox.checked === false) {
        activeCheckbox.checked = true;
    }
    
    // Función para mostrar mensajes de éxito
    function showSuccessMessage(message) {
        if (typeof $.growl === 'function') {
            $.growl.notice({
                title: combinacionescarne.i18n.success,
                message: message
            });
        } else {
            alert(message);
        }
    }
    
    // Función para mostrar mensajes de error
    function showErrorMessage(message) {
        if (typeof $.growl === 'function') {
            $.growl.error({
                title: combinacionescarne.i18n.error,
                message: message
            });
        } else {
            alert(message);
        }
    }
});
</script>						
<script>												
// Script para manejar correctamente las pestañas
document.addEventListener('DOMContentLoaded', function() {
    // Función para activar una pestaña específica
    function activateTab(tabId) {
        // 1. Desactivar todas las pestañas
        document.querySelectorAll('#productConfigTabs .nav-link').forEach(function(tab) {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        
        document.querySelectorAll('#productConfigTabsContent .tab-pane').forEach(function(content) {
            content.classList.remove('show', 'active');
        });
        
        // 2. Activar la pestaña específica
        const tab = document.getElementById(tabId);
        const content = document.getElementById(tabId + '-content');
        
        if (tab && content) {
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            content.classList.add('show', 'active');
        }
    }
    
    // Activar por defecto la pestaña de configuración
    activateTab('config-tab');
    
    // Manejar clics en las pestañas
    document.querySelectorAll('#productConfigTabs .nav-link').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Extraer el ID del target de la pestaña
            const tabId = this.id;
            activateTab(tabId);
        });
    });
});						
</script>												
						
						