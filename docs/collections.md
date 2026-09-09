# Recouvrement et relances

`CollectionService` genere des relances idempotentes dans `collection_reminders`. La cle metier couvre eleve, obligation, type, periode planifiee et canal. Les etats sont UPCOMING, DUE, OVERDUE et CRITICAL.

Le service existant `NotificationService` reste l'unique file de notification. Les templates financiers a utiliser sont `payment_received`, `payment_reminder`, `payment_overdue` et `payment_failed`, avec les variables parent_name, student_name, school_name, amount, balance, due_date et reference.
