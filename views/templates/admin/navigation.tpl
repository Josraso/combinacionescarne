{*
* Plantilla de navegación para el módulo CombinacionesCarne
*}

<div class="card mb-3">
    <div class="card-header">
        <h2 class="card-header-title">
            {l s='Combinaciones Automáticas para Carne' mod='combinacionescarne'}
            <span class="badge badge-info ml-2">v{$module_version}</span>
        </h2>
    </div>
    <div class="card-body">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {if $current_action == 'general'}active{/if}" href="{$current_url}">
                    <i class="icon icon-cog"></i> {l s='Configuración General' mod='combinacionescarne'}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {if $current_action == 'mapeos'}active{/if}" href="{$current_url}&action=mapeos">
                    <i class="icon icon-balance-scale"></i> {l s='Mapeo de Pesos' mod='combinacionescarne'}
                </a>
            </li>
            {if $current_action == 'product' && isset($product_name)}
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="icon icon-tag"></i> {$product_name}
                    </a>
                </li>
            {/if}
        </ul>
    </div>
</div>

{* Notificaciones *}
<div class="notifications-container">
    {if isset($module_confirmation) && $module_confirmation}
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="icon icon-check-circle"></i> {$module_confirmation}
        </div>
    {/if}
    
    {if isset($module_errors) && !empty($module_errors)}
        {foreach $module_errors as $error}
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="icon icon-times-circle"></i> {$error}
            </div>
        {/foreach}
    {/if}
</div>

{* Ayuda rápida contextual *}
{if $current_action == 'general'}
    <div class="alert alert-info">
        <i class="icon icon-info-circle"></i> {l s='Configure los parámetros globales del módulo, incluyendo el precio de envasado individual y los grupos de atributos utilizados para peso y envasado.' mod='combinacionescarne'}
    </div>
{elseif $current_action == 'mapeos'}
    <div class="alert alert-info">
        <i class="icon icon-info-circle"></i> {l s='Configure el mapeo entre los atributos de peso (como "500g", "1kg") y sus valores correspondientes en gramos que serán utilizados para los cálculos.' mod='combinacionescarne'}
    </div>
{elseif $current_action == 'product'}
    <div class="alert alert-info">
        <i class="icon icon-info-circle"></i> {l s='Configure la forma en que se calculan los precios para este producto específico y gestione sus combinaciones.' mod='combinacionescarne'}
    </div>
{/if}
