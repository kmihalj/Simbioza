# Simbioza 0.1.74

Ovo korektivno izdanje popravlja nadogradnju postojeće FPM instalacije kada
updater mora dopuniti administratorski upravljane postavke izbornika ili teme.

Updater sada takvu postojeću datoteku prepisuje pod ekskluzivnim zaključavanjem
bez zamjene inodea. Time ostaju nepromijenjeni njezin FPM vlasnik, runtime grupa,
ACL i prava te ih neprivilegirani `simbioza-deploy` proces više ne mora pokušati
vratiti operacijom `chown`.

Regresijski testovi zasebno potvrđuju očuvanje inodea, vlasnika, grupe i prava
za postavke izbornika i spremljene teme.
