{*
* Plantilla de mapeo de pesos para el módulo CombinacionesCarne
*}

<div class="bootstrap">
    <div class="card">
        <div class="card-header">
            <h2><i class="icon icon-balance-scale"></i> {l s='Mapeo de Pesos' mod='combinacionescarne'}</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="icon icon-info-circle"></i> {l s='En esta sección debe asignar un peso en gramos a cada atributo que represente un peso específico. Esto es esencial para que el módulo pueda calcular correctamente los precios.' mod='combinacionescarne'}
            </div>

            <!-- Filtros y búsqueda -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{l s='Filtrar por grupo de atributos:' mod='combinacionescarne'}</label>
                        <select id="filter-attribute-group" class="form-control">
                            <option value="0">{l s='Todos los grupos' mod='combinacionescarne'}</option>
                            {foreach from=$attribute_groups item=group}
                                <option value="{$group.id_attribute_group}">{$group.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{l s='Buscar atributo:' mod='combinacionescarne'}</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="icon icon-search"></i></span>
                            </div>
                            <input type="text" id="search-attribute" class="form-control" placeholder="{l s='Nombre del atributo...' mod='combinacionescarne'}">
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button id="add-weight-mapping" class="btn btn-primary" data-toggle="modal" data-target="#add-mapping-modal">
                        <i class="icon icon-plus"></i> {l s='Añadir Nuevo Mapeo' mod='combinacionescarne'}
                    </button>
                </div>
            </div>

            <!-- Tabla de mapeos existentes -->
            <div class="table-responsive">
                <table id="weight-mapping-table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>{l s='Grupo' mod='combinacionescarne'}</th>
                            <th>{l s='Atributo' mod='combinacionescarne'}</th>
                            <th>{l s='Peso (g)' mod='combinacionescarne'}</th>
                            <th>{l s='Peso (kg)' mod='combinacionescarne'}</th>
                            <th class="text-center">{l s='Acciones' mod='combinacionescarne'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if empty($mapeos_detallados)}
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="alert alert-warning">
                                        {l s='No hay mapeos de peso configurados. Añada su primer mapeo haciendo clic en el botón "Añadir Nuevo Mapeo".' mod='combinacionescarne'}
                                    </div>
                                </td>
                            </tr>
                        {else}
                            {foreach from=$mapeos_detallados item=group}
                                {foreach from=$group.attributes item=attribute}
                                    <tr data-group-id="{$group.id_attribute_group}" data-attribute-name="{$attribute.name|lower}">
                                        <td>{$group.name}</td>
                                        <td>{$attribute.name}</td>
                                        <td>
                                            <span class="weight-value">{$attribute.peso_gramos}</span> g
                                            <button class="btn btn-sm btn-link edit-weight-btn" data-attribute-id="{$attribute.id_attribute}" data-weight="{$attribute.peso_gramos}">
                                                <i class="icon icon-pencil"></i>
                                            </button>
                                        </td>
                                        <td>{$attribute.peso_gramos/1000|string_format:"%.3f"} kg</td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-danger delete-mapping-btn" data-attribute-id="{$attribute.id_attribute}" data-attribute-name="{$attribute.name}">
                                                <i class="icon icon-trash"></i> {l s='Eliminar' mod='combinacionescarne'}
                                            </button>
                                        </td>
                                    </tr>
                                {/foreach}
                            {/foreach}
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para añadir/editar mapeo -->
    <div class="modal fade" id="add-mapping-modal" tabindex="-1" role="dialog" aria-labelledby="mapping-modal-label" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="mapping-form" action="{$current_url}" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mapping-modal-label">{l s='Añadir Nuevo Mapeo de Peso' mod='combinacionescarne'}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="id_attribute">{l s='Seleccione un atributo:' mod='combinacionescarne'}</label>
                            <select name="id_attribute" id="id_attribute" class="form-control" required>
                                <option value="">{l s='-- Seleccionar --' mod='combinacionescarne'}</option>
                                {foreach from=$attribute_groups item=group}
                                    <optgroup label="{$group.name}">
                                        {foreach from=$group.attributes item=attribute}
                                            <option value="{$attribute.id_attribute}">{$attribute.name}</option>
                                        {/foreach}
                                    </optgroup>
                                {/foreach}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="peso_gramos">{l s='Peso en gramos:' mod='combinacionescarne'}</label>
                            <div class="input-group">
                                <input type="number" name="peso_gramos" id="peso_gramos" class="form-control" min="1" step="1" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">g</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">{l s='Ejemplos: 500 (para medio kilo), 1000 (para un kilo), etc.' mod='combinacionescarne'}</small>
                        </div>
                        <div class="form-group">
                            <label>{l s='Equivalente en kg:' mod='combinacionescarne'}</label>
                            <div class="input-group">
                                <input type="text" id="peso_kg_preview" class="form-control" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancelar' mod='combinacionescarne'}</button>
                        <button type="submit" name="submitMapeoPeso" class="btn btn-primary">{l s='Guardar' mod='combinacionescarne'}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="delete-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{l s='Confirmar eliminación' mod='combinacionescarne'}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{l s='¿Está seguro que desea eliminar el mapeo para' mod='combinacionescarne'} <strong id="delete-attribute-name"></strong>?</p>
                    <p class="text-danger">{l s='Esta acción no se puede deshacer y podría afectar los precios de los productos que utilizan este atributo.' mod='combinacionescarne'}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancelar' mod='combinacionescarne'}</button>
                    <form id="delete-mapping-form" action="{$current_url}" method="post">
                        <input type="hidden" name="deleteMapeoPeso" value="1">
                        <input type="hidden" name="id_attribute" id="delete-attribute-id">
                        <button type="submit" class="btn btn-danger">{l s='Eliminar' mod='combinacionescarne'}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa el filtro de grupos para la tabla de mapeo de pesos
    const filterGroupSelect = document.getElementById('filter-attribute-group');
    if (filterGroupSelect) {
        filterGroupSelect.addEventListener('change', function() {
            const groupId = this.value;
            const tableRows = document.querySelectorAll('#weight-mapping-table tbody tr');
            
            tableRows.forEach(function(row) {
                if (groupId === '0' || row.getAttribute('data-group-id') === groupId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Búsqueda de atributos en la tabla de mapeo
    const searchInput = document.getElementById('search-attribute');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#weight-mapping-table tbody tr');
            
            tableRows.forEach(function(row) {
                const attributeName = row.getAttribute('data-attribute-name');
                if (attributeName && attributeName.indexOf(searchTerm) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Previsualización del peso en kg al editar
    const pesoGramosInput = document.getElementById('peso_gramos');
    const pesoKgPreview = document.getElementById('peso_kg_preview');
    
    if (pesoGramosInput && pesoKgPreview) {
        pesoGramosInput.addEventListener('input', function() {
            const gramos = parseFloat(this.value) || 0;
            const kilos = (gramos / 1000).toFixed(3);
            pesoKgPreview.value = kilos;
        });
    }
    
    // Edición inline de pesos
    const editWeightButtons = document.querySelectorAll('.edit-weight-btn');
    if (editWeightButtons.length) {
        editWeightButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const attributeId = this.getAttribute('data-attribute-id');
                const currentWeight = this.getAttribute('data-weight');
                
                // Prepara el modal de edición
                const modal = document.getElementById('add-mapping-modal');
                if (!modal) return;
                
                // Cambiar título
                const modalTitle = modal.querySelector('.modal-title');
                if (modalTitle) {
                    modalTitle.textContent = "{l s='Editar Mapeo de Peso' mod='combinacionescarne'}";
                }
                
                // Rellenar valores
                const attributeSelect = document.getElementById('id_attribute');
                const weightInput = document.getElementById('peso_gramos');
                
                if (attributeSelect && weightInput) {
                    attributeSelect.value = attributeId;
                    attributeSelect.disabled = true; // No permitir cambiar el atributo en edición
                    weightInput.value = currentWeight;
                    
                    // Actualizar vista previa
                    if (pesoKgPreview) {
                        pesoKgPreview.value = (currentWeight / 1000).toFixed(3);
                    }
                }
                
                // Mostrar modal
                $(modal).modal('show');
            });
        });
    }
    
    // Confirmación de eliminación de mapeo
    const deleteButtons = document.querySelectorAll('.delete-mapping-btn');
    if (deleteButtons.length) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const attributeId = this.getAttribute('data-attribute-id');
                const attributeName = this.getAttribute('data-attribute-name');
                
                // Prepara el modal de confirmación
                const modal = document.getElementById('delete-confirm-modal');
                if (!modal) return;
                
                const nameElement = document.getElementById('delete-attribute-name');
                const idInput = document.getElementById('delete-attribute-id');
                
                if (nameElement) nameElement.textContent = attributeName;
                if (idInput) idInput.value = attributeId;
                
                // Mostrar modal
                $(modal).modal('show');
            });
        });
    }
    
    // Resetear el modal de mapeo al cerrarlo
    const mappingModal = document.getElementById('add-mapping-modal');
    if (mappingModal) {
        mappingModal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) form.reset();
            
            const attributeSelect = document.getElementById('id_attribute');
            if (attributeSelect) attributeSelect.disabled = false;
            
            const modalTitle = this.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = "{l s='Añadir Nuevo Mapeo de Peso' mod='combinacionescarne'}";
            }
        });
    }
});
</script>