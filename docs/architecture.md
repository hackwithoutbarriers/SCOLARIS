# Architecture

Scolaris uses Laravel's conventional MVC layout with Filament as the authenticated administration panel.

## Tenancy

Every school-owned table has a `school_id`. Models use `BelongsToSchool`, which applies `SchoolScope` using the authenticated user's school (or the explicit `SchoolContext` used by jobs/imports). New records inherit the current school when one is not supplied. Policies also compare the record's school to the authenticated school, so bypassing a query scope does not grant access.

Use `withoutGlobalScopes()` only in trusted system work such as seeders and imports, and always provide an explicit school ID.

## Domain

`AcademicYear` owns `Term`, `ClassRoom` and `Enrollment`. `Student` belongs to a school and has many `Guardian` records through `guardian_student`; each student may have multiple guardians and a primary flag. `TeacherAssignment` connects a teacher, class, subject and academic year.

## Auditability

The `Auditable` concern records create, update and delete events in `audit_logs`, including actor, school, model and changed values. Audit logs are append-only at the application layer.

## Extension points

- Put new school-owned models behind `BelongsToSchool` and `SchoolResourcePolicy`.
- Add Filament resources under `app/Filament/Resources`.
- Use `SchoolContext` when running queued jobs without an authenticated request.
- Keep cross-school reporting in privileged, explicit services rather than disabling scopes in controllers.
