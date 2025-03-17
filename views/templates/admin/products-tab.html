{*
* Template para la configuración en la página de producto - versión PS8
*}

<div class="card">
    <div class="card-header">
        <h3 class="card-header-title">{l s='Combinaciones Automáticas para Carne' mod='combinacionescarne'}</h3>
    </div>
    
    <div class="card-body">
        <div class="alert alert-info">
            {l s='Este módulo permite calcular automáticamente el impacto en el precio de las combinaciones basado en el tipo de envasado y el peso.' mod='combinacionescarne'}
        </div>
        
        <form id="combinacionescarne_form" class="form-horizontal" action="" method="post">
            <input type="hidden" name="combinacionescarne_submited" value="1" />
            <input type="hidden" name="id_product" value="{$combinacionescarne_product_id}" />
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Precio por kilo (€)' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" name="precio_por_kilo" value="{$combinacionescarne_product_config.precio_por_kilo|floatval}" class="form-control" />
                        <div class="input-group-append">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        {l s='Si es 0, se utilizará el precio base del producto.' mod='combinacionescarne'}
                    </small>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Grupo de atributos para Envasado' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <select name="id_attribute_group_envasado" class="form-control" id="attribute_group_envasado">
                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                        {foreach from=$combinacionescarne_attribute_groups item=group}
                            <option value="{$group.id_attribute_group}" {if $combinacionescarne_product_config.id_attribute_group_envasado == $group.id_attribute_group}selected="selected"{/if}>
                                {$group.name}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Atributo para Envasado Granel' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <select name="id_attribute_envasado_granel" class="form-control" id="attribute_envasado_granel">
                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                        {foreach from=$combinacionescarne_attribute_groups item=group}
                            {foreach from=$group.attributes item=attribute}
                                <option value="{$group.id_attribute_group}_{$attribute.id_attribute}" class="attribute-option attribute-group-{$group.id_attribute_group}" {if $combinacionescarne_product_config.id_attribute_group_envasado == $group.id_attribute_group && $combinacionescarne_product_config.id_attribute_envasado_granel == $attribute.id_attribute}selected="selected"{/if}>
                                    {$group.name} - {$attribute.name}
                                </option>
                            {/foreach}
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Atributo para Envasado Individual' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <select name="id_attribute_envasado_individual" class="form-control" id="attribute_envasado_individual">
                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                        {foreach from=$combinacionescarne_attribute_groups item=group}
                            {foreach from=$group.attributes item=attribute}
                                <option value="{$group.id_attribute_group}_{$attribute.id_attribute}" class="attribute-option attribute-group-{$group.id_attribute_group}" {if $combinacionescarne_product_config.id_attribute_group_envasado == $group.id_attribute_group && $combinacionescarne_product_config.id_attribute_envasado_individual == $attribute.id_attribute}selected="selected"{/if}>
                                    {$group.name} - {$attribute.name}
                                </option>
                            {/foreach}
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Grupo de atributos para Peso' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <select name="id_attribute_group_peso" class="form-control">
                        <option value="0">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                        {foreach from=$combinacionescarne_attribute_groups item=group}
                            <option value="{$group.id_attribute_group}" {if $combinacionescarne_product_config.id_attribute_group_peso == $group.id_attribute_group}selected="selected"{/if}>
                                {$group.name}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Precio de envasado individual (€)' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <div class="input-group">
                        <input type="text" value="{$combinacionescarne_precio_individual|floatval}" class="form-control" disabled="disabled" />
                        <div class="input-group-append">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        {l s='Este valor se configura en la configuración general del módulo.' mod='combinacionescarne'}
                    </small>
                </div>
            </div>
            
            <div class="form-group row">
                <label class="col-lg-3 col-form-label">
                    {l s='Activo' mod='combinacionescarne'}
                </label>
                <div class="col-lg-9">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="combinacionescarne_active" name="combinacionescarne_active" value="1" {if $combinacionescarne_product_config.active == 1}checked="checked"{/if}>
                        <label class="custom-control-label" for="combinacionescarne_active">
                            {l s='Activar el cálculo automático de precios para este producto' mod='combinacionescarne'}
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group row">
                <div class="col-lg-9 offset-lg-3">
                    <button type="submit" name="submitAddproduct" class="btn btn-primary">
                        <i class="material-icons">save</i> {l s='Guardar' mod='combinacionescarne'}
                    </button>
                    <button type="submit" name="submitCalculate" class="btn btn-secondary ml-2">
                        <i class="material-icons">build</i> {l s='Guardar y calcular precios' mod='combinacionescarne'}
                    </button>
                </div>
            </div>
        </form>
        
        {if !empty($combinacionescarne_combinations)}
            <hr>
            <h4>{l s='Vista previa de combinaciones' mod='combinacionescarne'}</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='ID' mod='combinacionescarne'}</th>
                            <th>{l s='Atributos' mod='combinacionescarne'}</th>
                            <th>{l s='Impacto en el precio' mod='combinacionescarne'}</th>
                            <th>{l s='Impacto con IVA' mod='combinacionescarne'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$combinacionescarne_combinations item=combination}
                            <tr>
                                <td>{$combination.id_product_attribute}</td>
                                <td>
                                    {foreach from=$combination.attributes item=attribute name=attributes}
                                        {$attribute.group_name}: {$attribute.name}
                                        {if !$smarty.foreach.attributes.last}, {/if}
                                    {/foreach}
                                </td>
                                <td>{displayPrice price=$combination.impact_price}</td>
                                <td>{displayPrice price=$combination.impact_price_with_tax}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Filtrar los atributos según el grupo seleccionado
    document.getElementById('attribute_group_envasado').addEventListener('change', function() {
        var groupId = this.value;
        var granelOptions = document.querySelectorAll('#attribute_envasado_granel option');
        var individualOptions = document.querySelectorAll('#attribute_envasado_individual option');
        
        // Ocultar todas las opciones excepto la primera
        for (var i = 1; i < granelOptions.length; i++) {
            granelOptions[i].style.display = 'none';
        }
        for (var i = 1; i < individualOptions.length; i++) {
            individualOptions[i].style.display = 'none';
        }
        
        // Mostrar solo las opciones del grupo seleccionado
        var granelGroupOptions = document.querySelectorAll('#attribute_envasado_granel option.attribute-group-' + groupId);
        var individualGroupOptions = document.querySelectorAll('#attribute_envasado_individual option.attribute-group-' + groupId);
        
        for (var i = 0; i < granelGroupOptions.length; i++) {
            granelGroupOptions[i].style.display = '';
        }
        for (var i = 0; i < individualGroupOptions.length; i++) {
            individualGroupOptions[i].style.display = '';
        }
    });
    
    // Inicializar el filtro
    var event = new Event('change');
    document.getElementById('attribute_group_envasado').dispatchEvent(event);
    
    // Funcionalidad para actualizar la tabla de mapeo de pesos
    var weightGroupSelect = document.querySelector('select[name="id_attribute_group_peso"]');
    if (weightGroupSelect) {
        weightGroupSelect.addEventListener('change', function() {
            // Mostrar mensaje informativo al cambiar el grupo de pesos
            var infoMessage = document.createElement('div');
            infoMessage.className = 'alert alert-warning mt-2';
            infoMessage.innerHTML = '{l s="Has seleccionado un nuevo grupo de peso. Guarda los cambios para configurar los mapeos de peso." mod="combinacionescarne"}';
            
            // Eliminar alertas previas
            var prevAlerts = this.parentNode.querySelectorAll('.alert-warning');
            for (var i = 0; i < prevAlerts.length; i++) {
                prevAlerts[i].remove();
            }
            
            if (this.value != '0') {
                this.parentNode.appendChild(infoMessage);
            }
        });
    }
    
    // Validar formulario antes de enviar
    document.querySelector('form#combinacionescarne_form').addEventListener('submit', function(event) {
        var precioPorKilo = parseFloat(document.querySelector('input[name="precio_por_kilo"]').value);
        if (isNaN(precioPorKilo) || precioPorKilo < 0) {
            alert('{l s="El precio por kilo debe ser un número positivo" mod="combinacionescarne"}');
            event.preventDefault();
            return false;
        }
        
        // Verificar que se ha seleccionado un grupo de envasado
        var grupoEnvasado = document.querySelector('select#attribute_group_envasado').value;
        if (grupoEnvasado == '0') {
            alert('{l s="Debes seleccionar un grupo de atributos para el envasado" mod="combinacionescarne"}');
            event.preventDefault();
            return false;
        }
        
        // Verificar que se ha seleccionado un grupo de peso
        var grupoPeso = document.querySelector('select[name="id_attribute_group_peso"]').value;
        if (grupoPeso == '0') {
            alert('{l s="Debes seleccionar un grupo de atributos para el peso" mod="combinacionescarne"}');
            event.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>