# Project Context

- This project uses **Symfony 7.4**. Always follow best practices, use console commands (`bin/console`), and the modern directory structure for this version.
- The project uses **PHP 8.2**. Leverage features like *readonly* classes, DNF types, *null*, *false*, and *true* as standalone types, etc.
- Do not suggest libraries or code incompatible with PHP 8.2 or Symfony 7.4.
- Stack: **MySQL 8.0** and **nginx**.
- For the database always use MySQL.
- This is a 100% backend application. Secure all APIs, queries, and requests. Always use **LexikJWTAuthenticationBundle** and **API Platform**.
- Apply **SOLID**, **Hexagonal Architecture**, and **Domain-Driven Design**.
- Whenever a model changes, update the migrations accordingly.
- Keep `README.md` up to date with the most relevant project information, structure, and how to run it.
- Keep `CHANGELOG.md` up to date with all changes made.
- Implement the **Event-Driven** pattern to maintain a full audit trail of all employee clock-in/out changes over time.
- Implement the **CQRS** pattern to separate read operations (queries) from write operations (commands) for improved scalability and performance.
- Never run `git commit` or `git push` without prior confirmation from the user.
- Keep `swagger.yaml` updated with new routes, entities, and changes.
- Add **PHPUnit** tests for all endpoints and existing functionality.
- The system must allow employees to register clock-ins and clock-outs, and view a full record of their entries. Each entry must include the date and time of clock-in/out, the associated employee, and any other relevant information.
- `README.md`, `CHANGELOG.md`, and `swagger.yaml` must be written in **English**.

# Console Commands

- `bin/console make:migration` — Create a new migration.
- `bin/console make:entity` — Create a new entity.
- `bin/console make:command` — Create a new command.
- `bin/console make:controller` — Create a new controller.
