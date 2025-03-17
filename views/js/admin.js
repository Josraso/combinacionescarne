/**
 * JavaScript mejorado para el módulo CombinacionesCarne
 */

document.addEventListener('DOMContentLoaded', function() {
    // ======== FUNCIONALIDAD COMÚN ========
    
    // Inicializa tooltips para toda la interfaz
    if (typeof $().tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Inicializa popovers para información contextual
    if (typeof $().popover === 'function') {
        $('[data-toggle="popover"]').popover({
            trigger: 'hover',
            html: true
        });
    }
    
    // Funcionalidad de búsqueda para tablas
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
    
    // Añadir animaciones fadeIn a elementos
    function addFadeInEffect(selector, delay = 0) {
        const elements = document.querySelectorAll(selector);
        if (!elements.length) return;
        
        elements.forEach(function(element, index) {
            element.classList.add('fade-in');
            element.style.animationDelay = (delay * index) + 'ms';
        });
    }
    
    // Función para mostrar notificaciones
    function showNotification(type, message) {
        // Si está disponible la función de notificaciones de PrestaShop
        if (typeof $.growl === 'function') {
            $.growl[type === 'error' ? 'error' : 'notice']({
                title: type === 'error' ? combinacionescarne.i18n.error : combinacionescarne.i18n.success,
                message: message
            });
            return;
        }
        
        // Implementación fallback simple
        const notificationArea = document.querySelector('.notifications-container');
        if (!notificationArea) {
            alert(message);
            return;
        }
        
        const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        const alertIcon = type === 'error' ? 'icon-times-circle' : 'icon-check-circle';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} fade-in`;
        notification.innerHTML = `
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="icon ${alertIcon}"></i> ${message}
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => {
                if (notification.parentNode) {
                    notificationArea.removeChild(notification);
                }
            }, 500);
        }, 5000);
    }
    
    // ======== DASHBOARD ========
    
    // Búsqueda de productos en la tabla del dashboard
    initTableSearch('#product-search', '.product-list-table', 'tbody tr');
    
    // Botones de recálculo rápido
    const calculateButtons = document.querySelectorAll('.calculate-btn');
    if (calculateButtons.length) {
        calculateButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const productId = this.getAttribute('data-product-id');
                if (!productId) return;
                
                // Mostrar spinner en el botón
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="icon icon-spinner icon-spin"></i> ' + combinacionescarne.i18n.calculating;
                this.disabled = true;
                
                // Realizar petición AJAX para recalcular precios
                fetch(combinacionescarne.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=calculatePrices&id_product=' + productId + '&ajax=1&token=' + combinacionescarne.token
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                    } else {
                        showNotification('notice', data.message);
                    }
                    
                    // Restaurar botón
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', combinacionescarne.i18n.error);
                    
                    // Restaurar botón
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                });
            });
        });
    }
    
    // ======== MAPEO DE PESOS ========
    
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
    initTableSearch('#search-attribute', '#weight-mapping-table', 'tbody tr');
    
    // Previsualización del peso en kg al editar
    const pesoGramosInput = document.getElementById('peso_gramos');
    const pesoKgPreview = document.getElementById('peso_kg_preview');
    
    if (pesoGramosInput && pesoKgPreview) {
        pesoGramosInput.addEventListener('input', function() {
            const gramos = parseFloat(this.value) || 0;
            const kilos = (gramos / 1000).toFixed(3);
            pesoKgPreview.value = kilos;
        });
        
        // Ejecutar conversión inicial si hay un valor predeterminado
        if (pesoGramosInput.value) {
            const event = new Event('input');
            pesoGramosInput.dispatchEvent(event);
        }
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
                    modalTitle.textContent = combinacionescarne.i18n.editWeightMapping;
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
    
    // Resetear el modal al cerrarlo
    const mappingModal = document.getElementById('add-mapping-modal');
    if (mappingModal) {
        mappingModal.addEventListener('hidden.bs.modal', function() {
            const form = this.querySelector('form');
            if (form) form.reset();
            
            const attributeSelect = document.getElementById('id_attribute');
            if (attributeSelect) attributeSelect.disabled = false;
            
            const modalTitle = this.querySelector('.modal-title');
            if (modalTitle) {
                modalTitle.textContent = combinacionescarne.i18n.addNewWeightMapping;
            }
        });
    }
    
   // Validación del formulario de mapeo
    const mappingForm = document.getElementById('mapping-form');
    if (mappingForm) {
        mappingForm.addEventListener('submit', function(e) {
            const attributeSelect = document.getElementById('id_attribute');
            const weightInput = document.getElementById('peso_gramos');
            
            if (!attributeSelect || !weightInput) return;
            
            if (attributeSelect.value === '') {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.selectAttribute);
                return;
            }
            
            const weight = parseInt(weightInput.value);
            if (isNaN(weight) || weight <= 0) {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.invalidWeight);
                return;
            }
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
    
    // ======== CONFIGURACIÓN DE PRODUCTO ========
    
    // Toggle de configuración personalizada
    const overrideCheckbox = document.getElementById('override_global_config');
    const customConfigSection = document.getElementById('custom_config_section');
    
    if (overrideCheckbox && customConfigSection) {
        overrideCheckbox.addEventListener('change', function() {
            // Usar una transición suave
            if (this.checked) {
                customConfigSection.style.display = 'block';
                setTimeout(() => {
                    customConfigSection.style.opacity = 1;
                }, 10);
            } else {
                customConfigSection.style.opacity = 0;
                setTimeout(() => {
                    customConfigSection.style.display = 'none';
                }, 300);
            }
        });
        
        // Configurar estado inicial
        if (overrideCheckbox.checked) {
            customConfigSection.style.display = 'block';
            customConfigSection.style.opacity = 1;
        } else {
            customConfigSection.style.display = 'none';
            customConfigSection.style.opacity = 0;
        }
    }
    
    // Filtrado de atributos según grupo de envasado
    const groupEnvasadoSelect = document.getElementById('attribute_group_envasado');
    const granelSelect = document.getElementById('attribute_envasado_granel');
    const individualSelect = document.getElementById('attribute_envasado_individual');
    
    if (groupEnvasadoSelect && granelSelect && individualSelect) {
        groupEnvasadoSelect.addEventListener('change', function() {
            const selectedGroup = this.value;
            
            // Habilitar/deshabilitar selectores
            granelSelect.disabled = selectedGroup === '0';
            individualSelect.disabled = selectedGroup === '0';
            
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
    
    // Expandir/colapsar todas las combinaciones
    const expandAllBtn = document.getElementById('expand-all-combinations');
    const collapseAllBtn = document.getElementById('collapse-all-combinations');
    
    if (expandAllBtn && collapseAllBtn) {
        expandAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.combinations-wrapper .collapse');
            collapses.forEach(function(collapse) {
                $(collapse).collapse('show');
            });
        });
        
        collapseAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.combinations-wrapper .collapse');
            collapses.forEach(function(collapse) {
                $(collapse).collapse('hide');
            });
        });
    }
    
    // Actualización individual de combinaciones
    const updateCombinationButtons = document.querySelectorAll('.update-single-combination');
    
    if (updateCombinationButtons.length > 0) {
        updateCombinationButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const combinationId = this.getAttribute('data-id');
                const productId = combinacionescarne.product_id;
                
                if (!combinationId || !productId) return;
                
                // Mostrar indicador de carga
                const originalText = this.textContent;
                this.innerHTML = '<i class="icon icon-spinner icon-spin"></i> ' + combinacionescarne.i18n.updating;
                this.disabled = true;
                
                // Realizar petición AJAX
                fetch(combinacionescarne.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=updateCombination&id_product=' + productId + '&id_product_attribute=' + combinationId + '&ajax=1&token=' + combinacionescarne.token
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                        // Recargar la página después de un breve retraso
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // Mostrar mensaje de error
                        showNotification('notice', data.message || combinacionescarne.i18n.error);
                        
                        // Restaurar botón
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', combinacionescarne.i18n.error);
                    
                    // Restaurar botón
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });
    }
    
    // Validación del formulario de configuración de producto
    const productConfigForm = document.getElementById('product_config_form');
    
    if (productConfigForm) {
        productConfigForm.addEventListener('submit', function(e) {
            // Solo validamos si está activo
            const activeCheckbox = document.getElementById('active');
            if (!activeCheckbox || !activeCheckbox.checked) return;
            
            // Solo validamos si tiene configuración personalizada
            const overrideCheckbox = document.getElementById('override_global_config');
            if (!overrideCheckbox || !overrideCheckbox.checked) return;
            
            // Validar grupo de envasado
            const envasadoGroup = document.getElementById('attribute_group_envasado');
            if (envasadoGroup && envasadoGroup.value === '0') {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.selectEnvasadoGroup);
                return;
            }
            
            // Validar atributos de envasado
            const granelAttr = document.getElementById('attribute_envasado_granel');
            const individualAttr = document.getElementById('attribute_envasado_individual');
            
            if (granelAttr && granelAttr.value === '0') {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.selectGranelAttribute);
                return;
            }
            
            if (individualAttr && individualAttr.value === '0') {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.selectIndividualAttribute);
                return;
            }
            
            // Validar grupo de peso
            const pesoGroup = document.getElementById('attribute_group_peso');
            if (pesoGroup && pesoGroup.value === '0') {
                e.preventDefault();
                showNotification('error', combinacionescarne.i18n.selectPesoGroup);
                return;
            }
        });
    }
    
    // Asegurar que el checkbox de "Activar cálculo automático para este producto" esté activo por defecto
    const activeCheckbox = document.getElementById('active');
    if (activeCheckbox) {
        if (!activeCheckbox.checked) {
            activeCheckbox.checked = true;
        }
    }
    
    // ======== ANIMACIONES INICIALES ========
    addFadeInEffect('.card', 100);
    addFadeInEffect('.info-box', 100);
    addFadeInEffect('.combination-card', 50);

});
// Activar la primera pestaña al cargar, pero permitir que funcione normalmente después
document.addEventListener('DOMContentLoaded', function() {
    // Código para activar la primera pestaña solo al inicio
    setTimeout(function() {
        // Solo activa la primera pestaña si ninguna está activa actualmente
        var anyActive = false;
        document.querySelectorAll('#productConfigTabsContent .tab-pane').forEach(function(pane) {
            if (pane.classList.contains('active')) {
                anyActive = true;
            }
        });
        
        // Si ninguna pestaña está activa, activar la primera
        if (!anyActive) {
            // Activar la primera pestaña mediante Bootstrap API
            if (typeof $ !== 'undefined') {
                $('#config-tab').tab('show');
            } else {
                // Fallback manual
                document.getElementById('config-tab').classList.add('active');
                document.getElementById('config-content').classList.add('show', 'active');
            }
        }
    }, 100); // Pequeño retraso para asegurar que la página se ha cargado completamente
});		
		
		
		