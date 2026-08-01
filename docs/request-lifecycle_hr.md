# Životni ciklus zahtjeva

Na visokoj razini životni ciklus zahtjeva izgleda ovako:

1. Web-poslužitelj prosljeđuje HTTP zahtjev prednjem kontroleru u direktoriju
   `public/`.
2. Aplikacija se pokreće:
   - učitava konfiguracijske datoteke i gradi DI spremnik
   - izvršava bootstrap logiku
   - registrira globalni middleware iz konfiguracije
3. Usmjeravanje:
   - usmjerivač povezuje dolazni zahtjev s definiranom rutom
   - primjenjuje se middleware pridružen ruti
4. Kontroler ili akcija:
   - povezana akcija izvršava logiku, koristi servise i vraća odgovor
5. Iscrtavanje prikaza, ako je primjenjivo:
   - sustav prikaza sastavlja stranicu iz predložaka i rasporeda
6. Slanje odgovora:
   - PSR-7 odgovor šalje se klijentu

Zapisi i događaji:

- relevantni događaji mogu se objaviti, a zapisi spremiti prema konfiguraciji
