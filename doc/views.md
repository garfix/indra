# Views

insert -> add to all views
update -> update to all views
remove -> remove from all views
insert revision undo -> remove from all views
update revision undo -> update all views
remove revision undo -> add to all views

Een revisie mag alleen ongedaan worden als het objecten bevat die daarna niet meer voorkomen in andere revisies.

Voorbeeld view:

Customer

- name

Category

- name.fr_FR
- name.en_EN

Customer

- parents

