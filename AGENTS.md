# AGENTS.md

## Language
- Always respond to the user in Arabic.
- Keep code, file names, class names, function names, and technical terms in English.

## Project architecture
- This is a Laravel 10 API project.
- Preferred architecture is: Route -> Controller -> Request(if necessary) -> Service -> Repository.
- Do not bypass the service layer unless the existing code already does so intentionally.

## Runtime and infra
- The project uses Laravel Sail.
- The project uses Laravel Octane.
- Keep scalability in mind when proposing changes.
- Avoid solutions that create unnecessary tight coupling or reduce scalability.

## Reference documents
- Before making schema-related or business-logic changes, read:
    - docs/AI_DATABASE_CONTEXT.md "to understand the database schema and relationships and business logic."

## Safety rules
- Do not modify `.env` files.
- Ask before changing existing migrations that may affect current schema/data.
- Explain the planned changes before editing files.

## Verification
- When suggesting verification, prefer project-appropriate commands such as Sail/Artisan commands used by this repo.
