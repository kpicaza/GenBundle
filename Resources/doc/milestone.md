GenBundle
=========

## Symfony 3 quality code generator


### Milestones:

Hitos para versiones 1.0.0 y 2.0.0

1. **[Test generator:](#testGen)**
1. **[Entity generator:](#entDocGen)**
1. **[Model generator:](#modelGen)**
1. **[Form generator:](#formGen)**
1. **[Handler generator:](#handlerGen)**
1. **[Controller generator:](#ctrlGen)**
1. **[Rest generator:](#restGen)**
1. **[RestFull generator:](#restFullGen)**
1. **[View generator:](#viewGen)**
1. **[Admin generator:](#adminGen)**


### <a name="testGen">**1. Test generator:**</a>

- Test unitarios y funcionales.
- Pueden ser como mínimo de 2 tipos.
    - Test unitarios.
    - Test funcionales.
- El objetivo es cubrir por encima del 90% de cobertura.
- version 0.1.0
- [More details](test-generator.md)

### <a name="entDocGen">**2. Entity|Document generator:**</a>

- Generar modelos tontos.
- Generar Gateway o puera de enlace.
- version 0.1.5 doctrine ORM.
- **Hito:** version 1.0.0 doctrine ORM oneToMany.
- **Hito:** version 2.0.0 doctrine ODM.

### <a name="modelGen">**3. Model generator:**</a>

- Generar modelo de datos siguiendo el repository pattern y el principio
de inversión de dependencias.
- Generar interfaces para entidades|documentos.
- Generar interfaces de Gateway.
- Generar interfaces de Factoría.
- Generar factorías.
- Generar repositorio.
- Dar de alta los sevición necesarios para activar el modelo.
- version 0.2.0

### <a name="formGen">**4. Form generator:**</a>

- Generar formularios basados en las entidades|documentos de doctrine.
- Generar validación de formularios en formato yaml.
- Dar de alta formularios como servicios.
- version 0.3.0 a 0.5.0

### <a name="handlerGen">**5. Handler generator:**</a>

- Generar interface para los handlers
- Basandose en los modelos y formularios generar los handlers necesarios
para los controlesores.
- Dar de alta los handlers como servicios.
- version 0.5.5

### <a name="ctrlGen">**6. Controller generator:**</a>

- Generar controladores basandose en los handlers.
- Estrictamente relacionado con otro tipo de generador:
    - [Generador Rest](#restGen).
    - [Generador RestFull](#restFullGen).
    - [Generador Admin](#adminGen).
- Generar respouesta accorde con el generador padre.
- version 0.6.0

### <a name="restGen">**6. Rest generator:**</a>

- Generar CRUD con respuesta JSON respetando directrices rest:
    - Métodos GET, POST, PUT, DELETE, OPTIONS.
    - Respuestas HTTP.
    - **Hito:** version 1.0.0 Implementar cache con ETAGs.
    - **Hito:** versión 2.0.0 Método PATCH.
- Enlaza los generadores necesarío para generar el código necesario.
- version 0.7.0 a 0.8.0

### <a name="restFullGen">**7. RestFull generator:**</a>

- Extiende el generador Rest para implementar HATEOAS.
- versión 0.8.5 a **1.0.0**

### <a name="viewGen">**8. View generator:**</a>

- **@TODO**

### <a name="adminGen">**9. Admin generator:**</a>

- **@TODO**
