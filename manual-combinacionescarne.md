# Manual de Usuario - Módulo CombinacionesCarne

## Guía para principiantes

---

## Índice
1. [Introducción](#introducción)
2. [¿Para qué sirve el módulo?](#para-qué-sirve-el-módulo)
3. [Instalación](#instalación)
4. [Configuración Básica](#configuración-básica)
5. [Configuración de Mapeos de Peso](#configuración-de-mapeos-de-peso)
6. [Configuración por Producto](#configuración-por-producto)
7. [Funcionamiento Automático](#funcionamiento-automático)
8. [Preguntas Frecuentes](#preguntas-frecuentes)
9. [Solución de Problemas](#solución-de-problemas)

---

## Introducción

El módulo **CombinacionesCarne** ha sido diseñado específicamente para tiendas de PrestaShop que venden productos cárnicos (o cualquier otro producto que se venda por peso). Automatiza el cálculo de precios para combinaciones de productos basándose en:

- El peso del producto (en gramos/kilos)
- El tipo de envasado (a granel o individual)

Con este módulo, ya no tendrás que calcular y actualizar manualmente los precios de cada combinación cuando cambies el precio base de un producto.

---

## ¿Para qué sirve el módulo?

El módulo soluciona varios problemas comunes en tiendas que venden productos por peso:

1. **Cálculo automático de precios** según el peso del producto
2. **Adición de coste extra** para productos con envasado individual
3. **Actualización automática** de todas las combinaciones cuando cambia el precio base
4. **Configuración automática** para productos nuevos

Por ejemplo, si vendes carne de vaca con combinaciones para diferentes pesos (500g, 1kg, 2kg) y tienes dos tipos de envasado (a granel y envasado individual), el módulo calculará automáticamente el precio de cada combinación basándose en el peso y añadirá un coste adicional si el producto está envasado individualmente.

---

## Instalación

1. Descomprime el archivo ZIP del módulo
2. Sube la carpeta `combinacionescarne` al directorio `/modules/` de tu instalación de PrestaShop
3. Ve a "Módulos > Gestor de módulos" en el panel de administración
4. Busca "Combinaciones Automáticas para Carne" y haz clic en "Instalar"

![Instalación del módulo](ruta/a/imagen-instalacion.jpg)

Una vez instalado, verás la opción "Configurar" junto al módulo.

---

## Configuración Básica

La primera vez que uses el módulo, necesitarás configurar algunos parámetros básicos:

1. Ve a "Módulos > Gestor de módulos" y busca "Combinaciones Automáticas para Carne"
2. Haz clic en "Configurar"
3. En la pestaña "Configuración General", establece:

   ![Configuración General](ruta/a/imagen-config-general.jpg)

   - **Precio de envasado individual (€)**: El coste adicional que se añadirá a los productos con envasado individual (por ejemplo, 1.50€)
   - **Grupo de atributos para Envasado**: Selecciona el grupo de atributos que distingue entre envasado a granel e individual
   - **Atributo para Envasado Granel**: Selecciona el atributo específico que representa el envasado a granel
   - **Atributo para Envasado Individual**: Selecciona el atributo que representa el envasado individual
   - **Grupo de atributos para Peso**: Selecciona el grupo de atributos que define el peso del producto

4. Haz clic en "Guardar"

> **IMPORTANTE**: Estos ajustes globales se utilizarán para todos los productos que no tengan una configuración específica.

---

## Configuración de Mapeos de Peso

Antes de que el módulo pueda calcular precios correctamente, debes asignar un peso en gramos a cada atributo de peso:

1. En la configuración del módulo, ve a la pestaña "Mapeo de Pesos"
2. Para cada atributo de peso (por ejemplo, "500g", "1kg", etc.), necesitas asignar el valor en gramos:

   ![Mapeo de Pesos](ruta/a/imagen-mapeo-pesos.jpg)

   - Selecciona un atributo de la lista desplegable
   - Introduce el peso en gramos (por ejemplo, para "500g" introduce "500", para "1kg" introduce "1000")
   - Haz clic en "Guardar"
   - Repite para todos los atributos de peso que utilices

Los mapeos que configures aparecerán en una tabla en la misma página.

> **Ejemplo**: Si vendes carne con atributos "500g", "1kg" y "2kg", deberías mapear estos valores a 500, 1000 y 2000 gramos respectivamente.

---

## Configuración por Producto

Una vez configurado el módulo a nivel global, puedes aplicar la configuración a productos específicos:

1. En la configuración del módulo, verás una lista de "Productos con combinaciones"
2. Haz clic en cualquier producto de la lista para configurarlo
3. En la página de configuración del producto:

   ![Configuración por Producto](ruta/a/imagen-config-producto.jpg)

   - Verás el precio base actual del producto (con IVA)
   - Por defecto, se utilizará la configuración global
   - Si lo deseas, puedes marcar "Personalizar configuración" para establecer ajustes específicos para este producto
   - Haz clic en "Calcular y aplicar precios" para actualizar todas las combinaciones del producto

4. Después de hacer clic en "Calcular y aplicar precios", se mostrará una tabla con todas las combinaciones del producto y sus nuevos precios calculados

---

## Funcionamiento Automático

El módulo está diseñado para funcionar automáticamente en diferentes situaciones:

### Cuando cambias el precio base de un producto

Si cambias el precio base de un producto en el catálogo de PrestaShop (desde "Catálogo > Productos"), el módulo detectará este cambio y actualizará automáticamente los precios de todas las combinaciones según el nuevo precio base.

### Cuando añades un nuevo producto

Cuando creas un nuevo producto con combinaciones, el módulo detectará automáticamente el nuevo producto y aplicará la configuración global. No necesitas entrar manualmente en el módulo para cada producto nuevo.

### Cuando añades una nueva combinación

Si añades una nueva combinación a un producto existente, el módulo calculará automáticamente su precio según los atributos de peso y envasado.

### Cuando eliminas un producto

Si eliminas un producto, el módulo limpiará automáticamente su configuración.

---

## Cómo funciona el cálculo de precios

El módulo utiliza la siguiente fórmula para calcular el precio de cada combinación:

1. **Para productos a granel**:
   ```
   Impacto de precio = Precio base × (Peso en kg - 1)
   ```

2. **Para productos con envasado individual**:
   ```
   Impacto de precio = Precio base × (Peso en kg - 1) + Precio envasado individual
   ```

Por ejemplo:
- Si el precio base es 10€/kg y una combinación es de 500g (0.5kg) a granel:
  - Impacto = 10€ × (0.5 - 1) = 10€ × (-0.5) = -5€
  - Precio final = 10€ - 5€ = 5€

- Si la misma combinación tiene envasado individual con un coste adicional de 1.5€:
  - Impacto = 10€ × (0.5 - 1) + 1.5€ = -5€ + 1.5€ = -3.5€
  - Precio final = 10€ - 3.5€ = 6.5€

> **Nota**: El módulo calcula internamente todos los precios con IVA incluido, pero guarda los impactos sin IVA como requiere PrestaShop.

---

## Preguntas Frecuentes

### ¿Necesito configurar cada producto manualmente?

No. El módulo aplicará automáticamente la configuración global a todos los productos con combinaciones. Solo necesitas configurar manualmente los productos que requieran ajustes específicos diferentes a la configuración global.

### ¿Qué pasa si cambio el precio base de un producto?

El módulo detectará automáticamente el cambio de precio y actualizará todas las combinaciones del producto.

### ¿Funciona con cualquier tipo de producto?

El módulo está diseñado principalmente para productos que se venden por peso (como carne, queso, etc.), pero puede adaptarse a cualquier producto donde el precio deba variar proporcionalmente según algún atributo numérico.

### ¿Puedo tener diferentes precios de envasado individual para distintos productos?

No en la versión actual. El precio de envasado individual es un ajuste global. Si necesitas diferentes costes para diferentes productos, considera desarrollar una versión personalizada del módulo.

---

## Solución de Problemas

### Los precios no se actualizan automáticamente

1. Verifica que el producto está correctamente configurado en el módulo
2. Asegúrate de que has mapeado correctamente los atributos de peso
3. Comprueba si hay mensajes de error en el archivo de log ubicado en `/modules/combinacionescarne/log.txt`

### Algunos atributos no aparecen en los selectores

Asegúrate de que los atributos están correctamente creados en "Catálogo > Atributos y Características" y que están asignados a los grupos correspondientes.

### El cálculo de precios no parece correcto

1. Verifica que has mapeado correctamente los pesos en gramos para cada atributo
2. Comprueba que has seleccionado correctamente los grupos de atributos para envasado y peso
3. Verifica el precio base del producto y asegúrate de que incluye IVA

---

## Soporte Técnico

Si tienes problemas que no puedes resolver con esta guía, por favor contacta con nuestro equipo de soporte:

- Email: soporte@ejemplo.com
- Teléfono: +34 900 123 456
- Horario: Lunes a Viernes, 9:00 - 18:00

---

*Manual de Usuario - Versión 1.0 - Fecha: marzo 2025*
