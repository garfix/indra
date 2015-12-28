# branching en merging

- tabel indra_branch (branch_id, source_id, user_id, time)
- oh, ja nieuwe tabel voor indra gebruikers: indra_user(user_id, extra_id, name) // extra_id koppelt de gebruiker aan een ander pakket
- neem ook de branch_id op in de user?
- veralgemeniseer de inactive tabel voor alle niet-master branches?
    nee, beter is denk ik een indra_branch_int, indra_branch_varchar, ... voor de actieve niet-master triples
    ook mogelijk is een branch_id toevoegen aan de triple tabellen; voor de onderhoudbaarheid het makkelijkst;
        is wel jammer als geen branching wordt gebruikt; en overigens over het algemeen dataverspilling
- revisie moet ook worden uitgebreid met branch_id
- merge branch b1 in b2: voor alle objecten in branch b1, zoek eerst het object in b2, overschrijf de waarden met die in b1. schrijf weg
- maak view-tabellen aan on-demand: als er voor het eerst om gevraagd wordt.
- aanmaken view: 1) haal alle ids op; 2) bouw alle objecten op met (getCustomer(id)) of met getCustomers()). Voor iedere customer wordt dan eerst
    gekeken of die in de huidige branch bestaat, en zo niet: in de source branch (probleem: hij kan verwijderd zijn in de huidige branch)
- er wordt NIET gequeried in de triple store (behalve voor het opbouwen van views, natuurlijk)
- branches worden niet verwijderd als ze gemergd worden

- Een branch is technisch gezien alleen een tag van een revisie.
- NB: een revisie heeft GEEN branch verwijzing; een branch heeft een revisie verwijzing
- Iedere revisie heeft een verwijzing naar een eerdere revisie.

# Revisiebeheer

- elke revisie is een patch, een diff van triples
- elke revisie is ook een set van gewijzigde objecten
- in een revisie kan een object ook voorkomen als de waarde van een referentie attribuut
- je kunt per object zijn laatste revisie bijhouden, gelinkte lijst

Er geldt:

- een revisie mag pas worden ongedaan gemaakt als de objecten die erin voorkomen als onderwerp of referentie, niet later nog terugkomen in een andere revisie
- revision id, index (volgnr triples), triple, action
- revisies zijn geordend; tijdstip alleen is niet voldoende

New revision:

- time
- object-ids
- triple-ids

indra_revision_object
- object
- revision-id
- previous-revision-id

