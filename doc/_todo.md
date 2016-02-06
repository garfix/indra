# Possible future features

De verschillende verantwoordelijkheden van Domain opsplitsen. Denk na of het mogelijk is te veranderen van configuratie. Zo niet, dan moet je dat afdwingen in de code.

- object-georienteerde revisies
- multilingual
- sets
- lists
- typen hebben geen intrinsieke 'semantische' eigenschappen, zoals naam of titel
- typen kun je in de code hernoemen zonder dat er iets in de database hoeft te veranderen
- bij een (zware) update actie wordt de repo gelockd. Als er dan een actie wordt uitgevoerd, krijgt de gebruiker een foutmelding ('probeer het nog eens').
- gebruik geen string-constanten in applicatie code
- het is ok dat je een business object niet mag wijzigen. De business rules horen elders. Waar?
- het is een idee dat de gebruiker helemaal niet queriet uit de triple tabellen. alleen de views zijn toegestaan.
- alle typen van een object worden opgeslagen in de database, maar als een object wordt geinitialiseerd is dat altijd vanuit 1 type. Het object heeft dan geen weet van zijn andere typen;
    je instantieert dus een 'interpretatie', een 'view' van het object
- attribute en attribute definition is nu nog hetzelfde ding
- object-level locking
- met loadCustomers($ids) meerdere customers, maar ook subklassen in 1x kunnen laden
- ook te implementeren als gedistribueerde database? evt
- prevent double triples by locking; ook geen arrays?
- nieuw datatype: reference (uuid)?
- customization: de gebruiker moet ook overal bijkunnen en aanpassen
- DummyViewStore voor als create-views = 0; idem voor andere features
- Ik denk aan een decorator pattern voor geneste contexten: Context / Branch / Revision / Model
- ik wil objecten eigenlijk niet verzamelen in een save-list bij een revision, ik wil dat ze meteen opgeslagen worden. Op die manier kun je ze meteen gebruiken in een query.
- aan de andere kant is het heel goed om alle wijzigingen eerst te verzamelen voordat ze worden weggeschreven. objecten die eerst gewijzigd worden en daarna verwijderd bijvoorbeeld. De wijzigingen wil je dan niet opslaan.
- save is save, en gaat direct naar de database! / Als je daarna een query doet, moet dit gevonden worden. 
- queryen in de historie / Bv. geef me alle objecten van dit type die zijn verandert sinds datum D 
- is het mogelijk actieve triples helemaal niet meer op te slaan en alleen te werken met de views?
- aan het einde van de merge: verplaats de pointer van de branch naar de nieuwe revisie (beide branches hebben dezelfde revisie)
- geef mij (bepaalde waarden van) alle objecten die verandert cq verwijderd zijn sinds 1 okt.
    => gebruik een last-modified attribuut. als je de verwijderde objecten wilt hebben kun je die loggen via een event plugin
- streef naar een zo klein mogelijke database, gegeven de eisen van de features
- is het mogelijk om de code-generatie te schedulen: om 2:00 's-nachts worden de nieuwe tabellen gemaakt
