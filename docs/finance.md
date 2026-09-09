# Noyau financier

Les montants sont des entiers en XOF. Une `FeeStructure` appartient a une ecole et une annee scolaire et contient plusieurs `Fee` (inscription, scolarite, transport, etc.). `Invoice` materialise les frais pour un eleve; son statut est recalculé depuis les allocations de paiements confirmés.

`PaymentAllocation` separe le paiement de la dette. Les paiements partiels sont supportes et un excedent reste dans `unallocated_amount` comme credit disponible. Les statuts PENDING, FAILED, CANCELLED et REFUNDED ne reduisent jamais le solde.

Toutes les entites financieres portent `school_id` et utilisent la portee tenant existante. Les actions sont journalisees par `Auditable`; un paiement confirme produit un recu PDF.
