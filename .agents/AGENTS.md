# Contexto del Proyecto
- Este proyecto utiliza **Symfony 7.4**. Utiliza siempre las mejores prácticas, comandos de consola (`bin/console`) y la estructura de directorios moderna de esta versión.
- El proyecto utiliza **PHP 8.2**. Aprovecha las características de esta versión como clases *readonly*, tipos DNF (Disjunctive Normal Form), clases *null*, *false* y *true* como tipos independientes, etc.
- No sugieras librerías o código que sean incompatibles con PHP 8.2 o Symfony 7.4.
- Trabajas con MySQL 8.0 y nginx para exponer la aplicación
- Para la base de datos usa MySQL
- Es una aplicacion 100% backend, securiza todas las apis, queries, y requests. Utiliza siempre LexikJWTAuthenticationBundle y API Platform.
- Aplica SOLID, arquitectura hexagonal y Domain Driven Design.
- Siempre que se haga un cambio en los modelos, actualiza las migraciones.
- Mantén actualizado el README.md con la información más relevante del proyecto, estructura y como ejecutarlo. 
- Mantén actualizado el CHANGELOG.md con los cambios realizados.
- Implementar el patrón de Event Driven para mantener un registro de todos los cambios en los fichajes de los empleados a lo largo del tiempo. Esto puede agregar un valor significativo al sistema al permitir una auditoría completa de los datos.
- Implementar el patrón CQRS para separar las operaciones de lectura (queries) de las operaciones de escritura (commands), lo que puede mejorar la escalabilidad y el rendimiento del sistema.
- No hacer git commit ni git push sin previo aviso.
- Actauliza el fichero swagger.yaml con las nuevas rutas, entidades y cambios realizados.
- Añadir tests con phpunit a todos los endpoints y funcionalidades existentes.
- El sistema debe permitir a los empleados registrar sus entradas y salidas, así como visualizar un registro de todos sus fichajes. Cada fichaje debe contener la información de la fecha y la hora de entrada/salida, el empleado asociado y cualquier información adicional relevante.

# Comandos de consola
- `bin/console make:migration`: Crea una nueva migración.
- `bin/console make:entity`: Crea una nueva entidad.
- `bin/console make:command`: Crea un nuevo comando.
- `bin/console make:controller`: Crea un nuevo controlador.
- `bin/console make:controller`: Crea un nuevo controlador.
- `bin/console make:controller`: Crea un nuevo controlador.