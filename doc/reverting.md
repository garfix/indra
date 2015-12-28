wat gebeurt er als je een revisie ongedaan maakt?

Een object opslaan kan via de model. Objecten van meerdere typen ineens opslaan kan met:
Context::getRevisionStore()->saveMultiple([$customer, $description);

Als je meerdere revisies in 1 request toelaat, kun je dan geen circulaire dependencies maken?

- r1: A.ref = C
- r2: C.ref = A
- save r1
- save r2
- draai r2 terug => r1 is stuk? nee

- r1: A.attr = x
- r2: delete A
- r3: A.attr = y
- save r1
- save r2
- draai r2 terug => r1 is stuk? ja

Dat is stuk, maar ook niet waarschijnlijk in een request. En daarnaast kan de triple store hier op controleren; net voor het opslaan.

Nee dat is niet stuk. De triple store haalt net voor het opslaan het oude object op. Bij het opslaan worden alle attributen (mogelijk opnieuw) geschreven.

- Het is niet mogelijk tabel-wijzigingen in een revisie op te nemen
- De data is afhankelijk van de code. Beter gezegd: applicatie-data is afhankelijk van definitie data.
    In de database staat data die alleen gemaakt kon worden met code die door definitie-code is gemaakt. Als de definitie code verandert, en dus de applicatie-code,
    dan kun je de oude data van de database niet (helemaal) meer gebruiken.
- Dit is niet superbelangrijk, maar je moet er wel wat mee.
