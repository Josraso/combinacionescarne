{*
* Plantilla de visión general para el módulo CombinacionesCarne
*}

<div class="bootstrap">
    <div class="card">
        <div class="card-header">
            <h2><i class="icon icon-cog"></i> {l s='Configuración General' mod='combinacionescarne'}</h2>
        </div>
        <div class="card-body">
            <!-- Resumen del estado -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Productos Configurados' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{$productCount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Combinaciones Gestionadas' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{$combinationsCount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Mapeos de Peso' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{$mappingsCount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <div class="info-box-content">
                            <span class="info-box-text">{l s='Precio Envasado Individual' mod='combinacionescarne'}</span>
                            <span class="info-box-number">{displayPrice price=$packagingPrice}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de configuración general -->
            <form id="configuration_form" method="post" action="{$current_url}">
                <div class="card">
                    <div class="card-header">
                        <h4>{l s='Parámetros Globales' mod='combinacionescarne'}</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{l s='Precio de envasado individual (€)' mod='combinacionescarne'}</label>
                            <div class="col-md-9">
                                <div class="input-group">
                                    <input type="text" name="COMBINACIONESCARNE_PRECIO_INDIVIDUAL" value="{$config_values.COMBINACIONESCARNE_PRECIO_INDIVIDUAL|floatval}" class="form-control" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">{l s='Coste adicional a aplicar a productos con envasado individual' mod='combinacionescarne'}</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{l s='Grupo de atributos para Envasado' mod='combinacionescarne'}</label>
                            <div class="col-md-9">
                                <select name="COMBINACIONESCARNE_GRUPO_ENVASADO" class="form-control" required>
                                    <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                    {foreach from=$attribute_groups item=group}
                                        <option value="{$group.id_attribute_group}" {if $config_values.COMBINACIONESCARNE_GRUPO_ENVASADO == $group.id_attribute_group}selected="selected"{/if}>
                                            {$group.name}
                                        </option>
                                    {/foreach}
                                </select>
                                <small class="form-text text-muted">{l s='Grupo que define el tipo de envasado (granel o individual)' mod='combinacionescarne'}</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{l s='Atributo para Envasado Granel' mod='combinacionescarne'}</label>
                            <div class="col-md-9">
                                <select name="COMBINACIONESCARNE_ATRIBUTO_GRANEL" class="form-control" required>
                                    <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                    {foreach from=$attribute_groups item=group}
                                        <optgroup label="{$group.name}">
                                            {foreach from=$group.attributes item=attribute}
                                                <option value="{$attribute.id_attribute}" {if $config_values.COMBINACIONESCARNE_ATRIBUTO_GRANEL == $attribute.id_attribute}selected="selected"{/if}>
                                                    {$attribute.name}
                                                </option>
                                            {/foreach}
                                        </optgroup>
                                    {/foreach}
                                </select>
                                <small class="form-text text-muted">{l s='Atributo que representa el envasado a granel' mod='combinacionescarne'}</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{l s='Atributo para Envasado Individual' mod='combinacionescarne'}</label>
                            <div class="col-md-9">
                                <select name="COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL" class="form-control" required>
                                    <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                    {foreach from=$attribute_groups item=group}
                                        <optgroup label="{$group.name}">
                                            {foreach from=$group.attributes item=attribute}
                                                <option value="{$attribute.id_attribute}" {if $config_values.COMBINACIONESCARNE_ATRIBUTO_INDIVIDUAL == $attribute.id_attribute}selected="selected"{/if}>
                                                    {$attribute.name}
                                                </option>
                                            {/foreach}
                                        </optgroup>
                                    {/foreach}
                                </select>
                                <small class="form-text text-muted">{l s='Atributo que representa el envasado individual' mod='combinacionescarne'}</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-md-3 col-form-label">{l s='Grupo de atributos para Peso' mod='combinacionescarne'}</label>
                            <div class="col-md-9">
                                <select name="COMBINACIONESCARNE_GRUPO_PESO" class="form-control" required>
                                    <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                    {foreach from=$attribute_groups item=group}
                                        <option value="{$group.id_attribute_group}" {if $config_values.COMBINACIONESCARNE_GRUPO_PESO == $group.id_attribute_group}selected="selected"{/if}>
                                            {$group.name}
                                        </option>
                                    {/foreach}
                                </select>
                                <small class="form-text text-muted">{l s='Grupo que define el peso del producto' mod='combinacionescarne'}</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" name="submit{$module_name}" class="btn btn-primary">
                            <i class="icon icon-save"></i> {l s='Guardar configuración' mod='combinacionescarne'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de productos mejorada -->
    <div id="product-list-section" class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3><i class="icon icon-tags"></i> {l s='Productos con Combinaciones' mod='combinacionescarne'}</h3>
            <div class="input-group" style="max-width: 300px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="icon icon-search"></i></span>
                </div>
                <input type="text" id="product-search" class="form-control" placeholder="{l s='Buscar producto...' mod='combinacionescarne'}">
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped product-list-table">
                    <thead>
                        <tr>
                            <th>{l s='ID' mod='combinacionescarne'}</th>
                            <th>{l s='Nombre del Producto' mod='combinacionescarne'}</th>
                            <th>{l s='Referencia' mod='combinacionescarne'}</th>
                            <th>{l s='Estado' mod='combinacionescarne'}</th>
                            <th>{l s='Acciones' mod='combinacionescarne'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if empty($products)}
                            <tr>
                                <td colspan="5" class="text-center">{l s='No hay productos con combinaciones disponibles' mod='combinacionescarne'}</td>
                            </tr>
                        {else}
                            {foreach $products as $product}
                            <tr>
                                <td>{$product.id_product}</td>
                                <td>{$product.name}</td>
                                <td>{$product.reference}</td>
                                <td>
                                    {if $product.config_active}
                                    <span class="badge badge-success">{l s='Configurado' mod='combinacionescarne'}</span>
                                    {else}
                                    <span class="badge badge-secondary">{l s='No Configurado' mod='combinacionescarne'}</span>
                                    {/if}
                                </td>
                                <td>
                                    <a href="{$current_url}&action=product&id_product={$product.id_product}" class="btn btn-sm btn-primary">
                                        <i class="icon icon-cog"></i> {l s='Configurar' mod='combinacionescarne'}
                                    </a>
                                    {if $product.config_active}
                                   <button class="btn btn-sm btn-success calculate-btn" data-product-id="{$product.id_product}" style="display:none;">
    <i class="icon icon-refresh"></i> {l s='Recalcular' mod='combinacionescarne'}
</button>
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
