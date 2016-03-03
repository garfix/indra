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
- object-level locking
- ook te implementeren als gedistribueerde database? evt
- prevent double triples by locking; ook geen arrays?
- nieuw datatype: reference (uuid)?
- customization: de gebruiker moet ook overal bijkunnen en aanpassen
- ik wil objecten eigenlijk niet verzamelen in een save-list bij een revision, ik wil dat ze meteen opgeslagen worden. Op die manier kun je ze meteen gebruiken in een query.
- aan de andere kant is het heel goed om alle wijzigingen eerst te verzamelen voordat ze worden weggeschreven. objecten die eerst gewijzigd worden en daarna verwijderd bijvoorbeeld. De wijzigingen wil je dan niet opslaan.
- save is save, en gaat direct naar de database! / Als je daarna een query doet, moet dit gevonden worden. 
- queryen in de historie / Bv. geef me alle objecten van dit type die zijn verandert sinds datum D 
- geef mij (bepaalde waarden van) alle objecten die verandert cq verwijderd zijn sinds 1 okt.
    => gebruik een last-modified attribuut. als je de verwijderde objecten wilt hebben kun je die loggen via een event plugin
- is het mogelijk om de code-generatie te schedulen: om 2:00 's-nachts worden de nieuwe tabellen gemaakt
- het idee dat een attribuut bij meerdere types mag horen is niet meer haalbaar (de vraag is ook of het een goed idee is)
- het centrale idee van de snapshots is dat je niet alle data hoeft te instantieren voor een pagina die je bekijkt. enkele tabellen volstaan (tijd beperkt)
- hoelang duurt het om een redelijke grote tabel te kopiëren?
- als je een vorige versie hebt uitgecheckt, mag je daarin niet schrijven, alleen lezen.
