---
id: UC-003
name: importNodes
title: Importar y exportar elementos
status: draft
origin: code
sourceOfTruth: code
---

# UC-003 Importar y exportar elementos — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/importNodes/README.md) | [Análisis](../../../analysis/use-cases/importNodes/README.md) | [Diseño](../../../design/use-cases/importNodes/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/importNodes/README.md#pruebas) |

Diseño: [README](../../../design/use-cases/importNodes/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `node_importer` | servicio | `classes/local/node_importer.php` | sí |
| `yaml` | servicio | `classes/yaml.php` | sí |
| Páginas de importación y exportación | páginas | `import.php`, `export.php` | sí |
| `import_form` | formulario | `classes/form/import_form.php` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| Importar y exportar nodos | flujo principal | `tests/local/node_importer_test.php` |
| El lector y el escritor de YAML | flujo principal | `tests/yaml_test.php` |
| Recorrido Behat de importar y exportar | flujo principal | `tests/behat/import_export.feature` |
