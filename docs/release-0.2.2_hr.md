# Simbioza 0.2.2

Veliki Confluence importi sada usklađuju poveznice i uključene stranice u
ograničenim završnim koracima koji se mogu nastaviti. Tako jedan skup završnih
radnji više ne prekoračuje uobičajeni vremenski limit proxyja. Uvoz velikog
broja privitaka zahtijeva i manje HTTP zahtjeva.

Ako se veza prekine, preglednik provjerava i nastavlja isti posao. Nakon
osvježavanja stranice posao koji je još u tijeku ima radnju **Nastavi import**
u popisu **Nedavni Confluence importi**. Nemojte pokretati drugi import dok
izvorni posao traje. Proxy prekid sam po sebi ne znači da import nije uspio:
prije ponavljanja provjerite stanje posla i izvještaj.

Pri novom uvozu prazni se Confluence retci izostavljaju. Prikaz dokumenta
ujedno zadržava Bootstrap mrežu ispod trake akcija pa su i već uvezene
stranice ispravno raspoređene bez ponovnog importa. Auth usklađuje stavke SSO
profila na svakoj stranici postavki; uklonjeni profil više ne ostaje u bočnom
meniju drugog modula.

Prije nadogradnje napravite obnovljivu sigurnosnu kopiju. Simbiozu nadogradite
kroz Setup ili CLI; Auth treba dosegnuti 0.1.15, HTML Editor 0.1.38, a
Confluence Import 0.1.41. Jezični paketi imaju reviziju `2026.09.28.4` i mogu se
zasebno osvježiti kroz Setup.
