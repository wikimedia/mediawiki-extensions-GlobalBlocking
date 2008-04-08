<?php
/**
 * Internationalisation file for extension GlobalBlocking.
 *
 * @addtogroup Extensions
 */

$messages = array();

/** English
 * @author Andrew Garrett
 */
$messages['en'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Allows]] IP addresses to be [[Special:GlobalBlockList|blocked across multiple wikis]]',
	'globalblocking-block' => 'Globally block an IP address',
	'globalblocking-expiry-options' => '-',
	'globalblocking-block-intro' => 'You can use this page to block an IP address on all wikis.',
	'globalblocking-block-reason' => 'Reason for this block:',
	'globalblocking-block-expiry' => 'Block expiry:',
	'globalblocking-block-expiry-other' => 'Other expiry time',
	'globalblocking-block-expiry-otherfield' => 'Other time:',
	'globalblocking-block-legend' => 'Block a user globally',
	'globalblocking-block-options' => 'Options',
	'globalblocking-block-errors' => "The block was unsuccessful, because: \n$1",
	'globalblocking-block-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-block-expiryinvalid' => 'The expiry you entered ($1) is invalid.',
	'globalblocking-block-submit' => 'Block this IP address globally',
	'globalblocking-block-success' => 'The IP address $1 has been successfully blocked on all Wikimedia projects.
You may wish to consult the [[Special:Globalblocklist|list of global blocks]].',
	'globalblocking-block-successsub' => 'Global block successful',
	'globalblocking-block-alreadyblocked' => 'The IP address $1 is already blocked globally. You can view the existing block on the [[Special:Globalblocklist|list of global blocks]].',
	'globalblocking-list' => 'List of globally blocked IP addresses',
	'globalblocking-search-legend' => 'Search for a global block',
	'globalblocking-search-ip' => 'IP Address:',
	'globalblocking-search-submit' => 'Search for blocks',
	'globalblocking-list-ipinvalid' => 'The IP address you searched for ($1) is invalid.
Please enter a valid IP address.',
	'globalblocking-search-errors' => "Your search was unsuccessful, because:\n\$1",
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globally blocked '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiry $1',
	'globalblocking-list-anononly' => 'anon-only',
	'globalblocking-list-unblock' => 'unblock',

	'globalblocking-unblock-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-unblock-legend' => 'Remove a global block',
	'globalblocking-unblock-submit' => 'Remove global block',
	'globalblocking-unblock-reason' => 'Reason:',
	'globalblocking-unblock-notblocked' => 'The IP address ($1) you entered is not globally blocked.',
	'globalblocking-unblock-unblocked' => "You have successfully removed the global block #$2 on the IP address '''$1'''",
	'globalblocking-unblock-errors' => "You cannot remove a global block for that IP address, because:\n\$1",
	'globalblocking-unblock-successsub' => 'Global block successfully removed',

	'globalblocking-blocked' => "Your IP address has been blocked on all Wikimedia wikis by '''$1''' (''$2'').
The reason given was ''\"$3\"''. The block's expiry is ''$4''.",

	'globalblocking-logpage' => 'Global block log',
	'globalblocking-block-logentry' => 'globally blocked [[$1]] with an expiry time of $2 ($3)',
	'globalblocking-unblock-logentry' => 'removed global block on [[$1]]',

	'globalblocklist' => 'List of globally blocked IP addresses',
	'globalblock' => 'Globally block an IP address',
);

/** Bulgarian (Български)
 * @author DCLXVI
 */
$messages['bg'] = array(
	'globalblocking-block-reason'   => 'Причина за блокирането:',
	'globalblocking-block-submit'   => 'Блокиране на този IP адрес глобално',
	'globalblocking-search-ip'      => 'IP адрес:',
	'globalblocking-unblock-reason' => 'Причина:',
	'globalblocking-logpage'        => 'Дневник на глобалните блокирания',
	'globalblocklist'               => 'Списък на глобално блокираните IP адреси',
	'globalblock'                   => 'Глобално блокиране на IP адрес',
);

/** German (Deutsch)
 * @author MF-Warburg
 */
$messages['de'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Sperrt]] IP-Adressen auf [[Special:GlobalBlockList|allen Wikis]]',
	'globalblocking-block'                   => 'Eine IP-Adresse global sperren',
	'globalblocking-block-intro'             => 'Auf dieser Seite kannst du IP-Adressen für alle Wikis sperren.',
	'globalblocking-block-reason'            => 'Grund für die Sperre:',
	'globalblocking-block-expiry'            => 'Sperrdauer:',
	'globalblocking-block-expiry-other'      => 'Andere Dauer',
	'globalblocking-block-expiry-otherfield' => 'Andere Dauer (englisch):',
	'globalblocking-block-legend'            => 'Einen Benutzer global sperren',
	'globalblocking-block-options'           => 'Optionen',
	'globalblocking-block-errors'            => 'Die Sperre war nicht erfolgreich. Grund:
$1',
	'globalblocking-block-ipinvalid'         => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Beachte, dass du keinen Benutzernamen eingeben darfst!',
	'globalblocking-block-expiryinvalid'     => 'Die Sperrdauer ($1) ist ungültig.',
	'globalblocking-block-submit'            => 'Diese IP-Adresse global sperren',
	'globalblocking-block-success'           => 'Die IP-Adresse $1 wurde erfolgreich auf allen Wikimedia-Projekten gesperrt.
Die globale Sperrliste befindet sich [[Special:Globalblocklist|hier]].',
	'globalblocking-block-successsub'        => 'Erfolgreich global gesperrt',
	'globalblocking-block-alreadyblocked'    => 'Die IP-Adresse $1 wurde schon global gesperrt. Du kannst die bestehende Sperre in der [[Special:Globalblocklist|globalen Sperrliste]] einsehen.',
	'globalblocking-list'                    => 'Liste global gesperrter IP-Adressen',
	'globalblocking-search-legend'           => 'Eine globale Sperre suchen',
	'globalblocking-search-ip'               => 'IP-Adresse:',
	'globalblocking-search-submit'           => 'Sperren suchen',
	'globalblocking-list-ipinvalid'          => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Bitte gib eine gültige IP-Adresse ein.',
	'globalblocking-search-errors'           => 'Die Suche war nicht erfolgreich. Grund:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (auf ''$3'') sperrte '''[[Special:Contributions/$4|$4]]''' global ''($5)''",
	'globalblocking-list-expiry'             => 'Sperrdauer $1',
	'globalblocking-list-anononly'           => 'nur Anonyme',
	'globalblocking-list-unblock'            => 'entsperren',
	'globalblocking-unblock-ipinvalid'       => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Beachte, dass du keinen Benutzernamen eingeben darfst!',
	'globalblocking-unblock-legend'          => 'Global entsperren',
	'globalblocking-unblock-submit'          => 'Global entsperren',
	'globalblocking-unblock-reason'          => 'Grund:',
	'globalblocking-unblock-notblocked'      => 'Die IP-Adresse ($1), die du eingegeben hast, ist nicht global gesperrt.',
	'globalblocking-unblock-unblocked'       => "Die hast erfolgreich die IP-Adresse '''$1''' (Sperr-ID $2) entsperrt",
	'globalblocking-unblock-errors'          => 'Du kannst diese IP nicht global entsperren. Grund:
$1',
	'globalblocking-unblock-successsub'      => 'Erfolgreich global entsperrt',
	'globalblocking-blocked'                 => "Deine IP-Adresse wurde von '''\$1''' (''\$2'') für alle Wikimedia-Wikis gesperrt.
Als Begründung wurde ''\"\$3\"'' angegeben. Die Sperre dauert ''\$4''.",
	'globalblocking-logpage'                 => 'Globales Sperrlogbuch',
	'globalblocking-block-logentry'          => 'sperrte [[$1]] global für einen Zeitraum von $2 ($3)',
	'globalblocking-unblock-logentry'        => 'entsperrte [[$1]] global',
	'globalblocklist'                        => 'Liste global gesperrter IP-Adressen',
	'globalblock'                            => 'Eine IP-Adresse global sperren',
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Permite]] que os enderezos IP sexan [[Special:GlobalBlockList|bloqueados en múltiples wikis]]',
	'globalblocking-block'                   => 'Bloqueo global dun enderezo IP',
	'globalblocking-block-intro'             => 'Pode usar esta páxina para bloquear un enderezo IP en todos os wikis.',
	'globalblocking-block-reason'            => 'Razón para o bloqueo:',
	'globalblocking-block-expiry'            => 'Expiración do bloqueo:',
	'globalblocking-block-expiry-other'      => 'Outro período de tempo de expiración',
	'globalblocking-block-expiry-otherfield' => 'Outro período de tempo:',
	'globalblocking-block-legend'            => 'Bloquear un usuario globalmente',
	'globalblocking-block-options'           => 'Opcións',
	'globalblocking-block-errors'            => 'O bloqueo non se puido levar a cabo porque: $1',
	'globalblocking-block-ipinvalid'         => 'O enderezo IP ($1) que tecleou é inválido.
Por favor, decátese de que non pode teclear un nome de usuario!',
	'globalblocking-block-expiryinvalid'     => 'O período de expiración que tecleou ($1) é inválido.',
	'globalblocking-block-submit'            => 'Bloquear este enderezo IP globalmente',
	'globalblocking-block-success'           => 'O enderezo IP $1 foi bloqueado con éxito en todos os proxectos Wikimedia.
Quizais desexa consultar a [[Special:Globalblocklist|listaxe de bloqueos globais]].',
	'globalblocking-block-successsub'        => 'Bloqueo global exitoso',
	'globalblocking-block-alreadyblocked'    => 'O enderezo IP xa está globalmente bloqueada. Pode ver os bloqueos vixentes na [[Special:Globalblocklist|listaxe de bloqueos globais]].',
	'globalblocking-list'                    => 'Listaxe dos bloqueos globais a enderezos IP',
	'globalblocking-search-legend'           => 'Procurar bloqueos globais',
	'globalblocking-search-ip'               => 'Enderezo IP:',
	'globalblocking-search-submit'           => 'Procurar bloqueos',
	'globalblocking-list-ipinvalid'          => 'O enderezo IP que procurou ($1) é inválido.
Por favor, teclee un enderezo IP válido.',
	'globalblocking-search-errors'           => 'A súa procura non foi exitosa porque:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') bloqueou globalmente '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'expira $1',
	'globalblocking-list-anononly'           => 'só anón.',
	'globalblocking-list-unblock'            => 'desbloquear',
	'globalblocking-unblock-ipinvalid'       => 'O enderezo IP ($1) que tecleou é inválido.
Por favor, decátese de que non pode teclear un nome de usuario!',
	'globalblocking-unblock-legend'          => 'Retirar un bloqueo global',
	'globalblocking-unblock-submit'          => 'Retirar bloqueo global',
	'globalblocking-unblock-reason'          => 'Razón:',
	'globalblocking-unblock-notblocked'      => 'O enderezo IP ($1) que tecleou non está bloqueado globalmente.',
	'globalblocking-unblock-unblocked'       => "Retirou con éxito o bloqueo global #$2 que tiña o enderezo IP '''$1'''",
	'globalblocking-unblock-errors'          => 'Non pode retirar o bloqueo global dese enderezo IP porque:
$1',
	'globalblocking-unblock-successsub'      => 'A retirada do bloqueo global foi un éxito',
	'globalblocking-blocked'                 => "O seu enderezo IP foi bloqueado en todos os wikis Wikimedia por '''\$1''' (''\$2'').
A razón que deu foi ''\"\$3\"''. A expiración do bloqueo será ''\$4''.",
	'globalblocking-logpage'                 => 'Rexistro de bloqueos globais',
	'globalblocking-block-logentry'          => 'bloqueou globalmente [[$1]] cun período de expiración de $2 ($3)',
	'globalblocking-unblock-logentry'        => 'retirado o bloqueo global en [[$1]]',
	'globalblocklist'                        => 'Listaxe dos bloqueos globais a enderezos IP',
	'globalblock'                            => 'Bloquear globalmente un enderezo IP',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'globalblocking-block'        => 'Eng IP-Adress global spären',
	'globalblocking-block-intro'  => 'Dir kënnt dës Säit benotzen fir eng IP-Adress op alle Wikien ze spären.',
	'globalblocking-block-reason' => 'Grond fir dës Spär:',
	'globalblocking-search-ip'    => 'IP-Adress:',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'globalblocking-desc'                    => "[[Special:GlobalBlock|Maakt het mogelijk]] IP-addressen [[Special:GlobalBlockList|in meerdere wiki's tegelijk]] te blokkeren",
	'globalblocking-block'                   => 'Een IP-adres globaal blokkeren',
	'globalblocking-block-intro'             => "U kunt deze pagina gebruiken om een IP-adres op alle wiki's te blokkeren.",
	'globalblocking-block-reason'            => 'Reden voor deze blokkade:',
	'globalblocking-block-expiry'            => 'Verloopdatum blokkade:',
	'globalblocking-block-expiry-other'      => 'Andere verlooptermijn',
	'globalblocking-block-expiry-otherfield' => 'Andere tijd:',
	'globalblocking-block-legend'            => 'Een gebruiker globaal blokkeren',
	'globalblocking-block-options'           => 'Opties',
	'globalblocking-block-errors'            => 'De blokkade is niet geslaagd omdat: $1',
	'globalblocking-block-ipinvalid'         => 'Het IP-adres dat u hebt opgegeven is onjuist.
Let op: u kunt geen gebruikersnaam opgeven!',
	'globalblocking-block-expiryinvalid'     => 'De verloopdatum/tijd die u hebt opgegeven is ongeldig ($1).',
	'globalblocking-block-submit'            => 'Dit IP-adres globaal blokkeren',
	'globalblocking-block-success'           => 'De blokkade van het IP-adres $1 voor alle projecten van Wikimedia is geslaagd.
U kunt een [[Special:Globalblocklist|lijst van alle globale blokkades]] bekijken.',
	'globalblocking-block-successsub'        => 'Globale blokkade geslaagd',
	'globalblocking-block-alreadyblocked'    => 'Het IP-adres $1 is al globaal geblokkeerd. U kunt de bestaande blokkade bekijken in de [[Special:Globalblocklist|lijst met globale blokkades]].',
	'globalblocking-list'                    => 'Lijst met globaal geblokeerde IP-adressen',
	'globalblocking-search-legend'           => 'Naar een globale blokkade zoeken',
	'globalblocking-search-ip'               => 'IP-adres:',
	'globalblocking-search-submit'           => 'Naar blokkades zoeken',
	'globalblocking-list-ipinvalid'          => 'Het IP-adres waar u naar zocht is onjuist ($1).
Voer alstublieft een correct IP-adres in.',
	'globalblocking-search-errors'           => 'Uw zoekopdracht is niet geslaagd, omdat:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') heeft '''[[Special:Contributions/$4|$4]]''' globaal geblokkeerd ''($5)''",
	'globalblocking-list-expiry'             => 'verloopt $1',
	'globalblocking-list-anononly'           => 'alleen anoniemen',
	'globalblocking-list-unblock'            => 'blokkade opheffen',
	'globalblocking-unblock-ipinvalid'       => 'Het IP-adres dat u hebt ingegeven is onjuist.
Let op: u kunt geen gebruikersnaam ingeven!',
	'globalblocking-unblock-legend'          => 'Een globale blokkade verwijderen',
	'globalblocking-unblock-submit'          => 'Globale blokkade verwijderen',
	'globalblocking-unblock-reason'          => 'Reden:',
	'globalblocking-unblock-notblocked'      => 'Het IP-adres ($1) dat u hebt ingegeven is niet globaal geblokkeerd.',
	'globalblocking-unblock-unblocked'       => "U hebt de globale blokkade met nummer $2 voor het IP-adres '''$1''' verwijderd",
	'globalblocking-unblock-errors'          => 'U kunt de globale blokkade voor dat IP-adres niet verwijderen omdat:
$1',
	'globalblocking-unblock-successsub'      => 'De globale blokkade is verwijderd',
	'globalblocking-blocked'                 => "Uw IP-adres is door '''\$1''' (''\$2'') geblokkeerd op alle wiki's van Wikimedia.
De reden is ''\"\$3\"''. De blokkade verloopt op ''\$4''.",
	'globalblocking-logpage'                 => 'Globaal blokkeerlogboek',
	'globalblocking-block-logentry'          => 'heeft [[$1]] globaal geblokkeerd met een verlooptijd van $2 ($3)',
	'globalblocking-unblock-logentry'        => 'heeft de globale blokkade voor [[$1]] verwijderd',
	'globalblocklist'                        => 'Lijst van globaal geblokkeerde IP-adressen',
	'globalblock'                            => 'Een IP-adres globaal blokkeren',
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'globalblocking-block'                   => 'Blokker en IP-adresse globalt',
	'globalblocking-block-intro'             => 'Du kan bruke denne siden for å blokkere en IP-adresse på alle wikier.',
	'globalblocking-block-reason'            => 'Blokkeringsårsak:',
	'globalblocking-block-expiry'            => 'Varighet:',
	'globalblocking-block-expiry-other'      => 'Annen varighet',
	'globalblocking-block-expiry-otherfield' => 'Annen tid:',
	'globalblocking-block-legend'            => 'Blokker en bruker globalt',
	'globalblocking-block-options'           => 'Alternativer',
	'globalblocking-block-errors'            => 'Blokkeringen mislyktes på grunn av: $1',
	'globalblocking-block-ipinvalid'         => 'IP-adressen du skrev inn ($1) er ugyldig.
Merk at du ikke kan skrive inn brukernavn.',
	'globalblocking-block-expiryinvalid'     => 'Varigheten du skrev inn ($1) er ugyldig.',
	'globalblocking-block-submit'            => 'Blokker denne IP-adressen globalt',
	'globalblocking-block-success'           => 'IP-adressen $1 har blitt blokkert på alle Wikimedia-prosjekter.
Du ønsker kanskje å se [[Special:Globalblocklist|listen over globale blokkeringer]].',
	'globalblocking-block-successsub'        => 'Global blokkering lyktes',
	'globalblocking-list'                    => 'Liste over globalt blokkerte IP-adresser',
	'globalblocking-search-legend'           => 'Søk etter en global blokkering',
	'globalblocking-search-ip'               => 'IP-adresse:',
	'globalblocking-search-submit'           => 'Søk etter blokkeringer',
	'globalblocking-list-ipinvalid'          => 'IP-adressen du skrev inn ($1) er ugyldig.
Skriv inn en gyldig IP-adresse.',
	'globalblocking-search-errors'           => 'Søket ditt mislyktes på grunn av:
$1',
	'globalblocking-list-blockitem'          => "$1 '''$2''' ('''$3''') blokkerte '''[[Special:Contributions/$4|$4]]''' globalt ''($5)''",
	'globalblocking-list-expiry'             => 'varighet $1',
	'globalblocking-list-anononly'           => 'kun uregistrerte',
	'globalblocking-list-unblock'            => 'avblokker',
	'globalblocking-unblock-ipinvalid'       => 'IP-adressen du skrev inn ($1) er ugyldig.
Merk at du ikke kan skrive inn brukernavn.',
	'globalblocking-unblock-legend'          => 'Fjern en global blokkering',
	'globalblocking-unblock-submit'          => 'Fjern global blokkering',
	'globalblocking-unblock-reason'          => 'Årsak:',
	'globalblocking-unblock-notblocked'      => 'IP-adressen du skrev inn ($1) er ikke blokkert globalt.',
	'globalblocking-unblock-unblocked'       => "Du har fjernet den globale blokkeringen (#$2) på IP-adressen '''$1'''",
	'globalblocking-unblock-errors'          => 'Du kan ikke fjerne en global blokkering på den IP-adressen fordi:
$1',
	'globalblocking-unblock-successsub'      => 'Global blokkering fjernet',
	'globalblocking-blocked'                 => "IP-adressen din har blitt blokkert på alle Wikimedia-wikier av '''$1''' (''$2'').
Årsaken som ble oppgitt var '''$3'''. Blokkeringen utgår ''$4''.",
	'globalblocking-logpage'                 => 'Global blokkeringslogg',
	'globalblocking-block-logentry'          => 'blokkerte [[$1]] globalt med en varighet på $2 ($3)',
	'globalblocking-unblock-logentry'        => 'fjernet global blokkering på [[$1]]',
	'globalblocklist'                        => 'Liste over globalt blokkerte IP-adresser',
	'globalblock'                            => 'Blokker en IP-adresse globalt',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Umožňuje]] zablokovať IP adresy [[Special:GlobalBlockList|na viacerých wiki]]',
	'globalblocking-block'                   => 'Globálne zablokovať IP adresu',
	'globalblocking-block-intro'             => 'Táto stránka slúži na zablokovanie IP adresy na všetkých wiki.',
	'globalblocking-block-reason'            => 'Dôvod blokovania:',
	'globalblocking-block-expiry'            => 'Vypršanie blokovania:',
	'globalblocking-block-expiry-other'      => 'Iný čas vypršania',
	'globalblocking-block-expiry-otherfield' => 'Iný čas:',
	'globalblocking-block-legend'            => 'Globálne zablokovať používateľa',
	'globalblocking-block-options'           => 'Voľby',
	'globalblocking-block-errors'            => 'Blokovanie bolo neúspešné z nasledovného dôvodu:  
$1',
	'globalblocking-block-ipinvalid'         => 'IP adresa ($1), ktorú ste zadali nie je platná.
Majte na pamäti, že nemôžete zadať meno používateľa!',
	'globalblocking-block-expiryinvalid'     => 'Čas vypršania, ktorý ste zadali ($1) je neplatný.',
	'globalblocking-block-submit'            => 'Globálne zablokovať túto IP adresu',
	'globalblocking-block-success'           => 'IP adresa $1 bola úspešne zablokovaná na všetkých projektoch Wikimedia.
Možno budete chcieť skontrolovať [[Special:Globalblocklist|Zoznam globálnych blokovaní]].',
	'globalblocking-block-successsub'        => 'Globálne blokovanie úspešné',
	'globalblocking-block-alreadyblocked'    => 'IP adresa $1 je už globálne zablokovaná. Existujúce blokovanie si môžete pozrieť v [[Special:Globalblocklist|Zozname globálnych blokovaní]].',
	'globalblocking-list'                    => 'Zoznam globálne zablokovaných IP adries',
	'globalblocking-search-legend'           => 'Hľadať globálne blokovanie',
	'globalblocking-search-ip'               => 'IP adresa:',
	'globalblocking-search-submit'           => 'Hľadať blokovania',
	'globalblocking-list-ipinvalid'          => 'IP adresa, ktorú ste hľadali ($1) je neplatná.
Prosím, zadajte platnú IP adresu.',
	'globalblocking-search-errors'           => 'Vaše hľadanie bolo neúspešné z nasledovného dôvodu:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') globálne zablokoval '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'vyprší $1',
	'globalblocking-list-anononly'           => 'iba anonym',
	'globalblocking-list-unblock'            => 'odblokovať',
	'globalblocking-unblock-ipinvalid'       => 'IP adresa ($1), ktorú ste zadali, je neplatná.
Majte na pamäti, že nemôžete zadať používateľské meno!',
	'globalblocking-unblock-legend'          => 'Odstrániť globálne blokovanie',
	'globalblocking-unblock-submit'          => 'Odstrániť globálne blokovanie',
	'globalblocking-unblock-reason'          => 'Dôvod:',
	'globalblocking-unblock-notblocked'      => 'IP adresa ($1), ktorú ste zadali, nie je globálne zablokovaná.',
	'globalblocking-unblock-unblocked'       => "Úspešne ste odstránili globálne blokovanie #$2 IP adresy '''$1'''",
	'globalblocking-unblock-errors'          => 'Nemôžete odstrániť globálne blokovanie tejto IP adresy z nasledovného dôvodu:
$1',
	'globalblocking-unblock-successsub'      => 'Globálne blokovanie bolo úspešne odstránené',
	'globalblocking-blocked'                 => "Vašu IP adresu zablokoval na všetkých wiki nadácie Wikimedia '''$1''' (''$2'').
Ako dôvod udáva ''„$3“''. Blokovanie vyprší ''$4''.",
	'globalblocking-logpage'                 => 'Záznam globálnych blokovaní',
	'globalblocking-block-logentry'          => 'globálne zablokoval [[$1]] s časom vypršania $2 ($3)',
	'globalblocking-unblock-logentry'        => 'odstránil globálne blokovanie [[$1]]',
	'globalblocklist'                        => 'Zoznam globálne zablokovaných IP adries',
	'globalblock'                            => 'Globálne zablokovať IP adresu',
);

/** Serbian Cyrillic ekavian (ћирилица)
 * @author Sasa Stefanovic
 */
$messages['sr-ec'] = array(
	'globalblocking-block-reason'            => 'Разлог блока:',
	'globalblocking-block-expiry-otherfield' => 'Друго време:',
	'globalblocking-block-legend'            => 'Блокирајте корисника глобално',
	'globalblocking-block-options'           => 'Опције',
	'globalblocking-search-ip'               => 'ИП адреса:',
	'globalblocking-list-expiry'             => 'истиче $1',
	'globalblocking-list-anononly'           => 'само анонимне',
	'globalblocking-list-unblock'            => 'одблокирај',
);

/** Swedish (Svenska)
 * @author M.M.S.
 */
$messages['sv'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Tillåter]] IP-adresser att bli [[Special:GlobalBlockList|blockerade tvärs över mångfaldiga wikier]]',
	'globalblocking-block'                   => 'Blockerar en IP-adress globalt',
	'globalblocking-block-intro'             => 'Du kan använda denna sida för att blockera en IP-adress på alla wikier.',
	'globalblocking-block-reason'            => 'Blockeringsorsak:',
	'globalblocking-block-expiry'            => 'Varighet:',
	'globalblocking-block-expiry-other'      => 'Annan varighet',
	'globalblocking-block-expiry-otherfield' => 'Annan tid:',
	'globalblocking-block-legend'            => 'Blockerar en användare globalt',
	'globalblocking-block-options'           => 'Alternativ',
	'globalblocking-block-errors'            => 'Blockeringen misslyckades på grund av: $1',
);

/** Vietnamese (Tiếng Việt)
 * @author Vinhtantran
 */
$messages['vi'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Cho phép]] [[Special:GlobalBlockList|cấm địa chỉ IP trên nhiều wiki]]',
	'globalblocking-block'                   => 'Cấm một địa chỉ IP trên toàn hệ thống',
	'globalblocking-block-intro'             => 'Bạn có thể sử dụng trang này để cấm một địa chỉ IP trên tất cả các wiki.',
	'globalblocking-block-reason'            => 'Lý do cấm:',
	'globalblocking-block-expiry'            => 'Hết hạn cấm:',
	'globalblocking-block-expiry-other'      => 'Thời gian hết hạn khác',
	'globalblocking-block-expiry-otherfield' => 'Thời hạn khác:',
	'globalblocking-block-legend'            => 'Cấm một thành viên trên toàn hệ thống',
	'globalblocking-block-options'           => 'Tùy chọn',
	'globalblocking-block-errors'            => 'Cấm không thành công, lý do:
$1',
	'globalblocking-block-ipinvalid'         => 'Bạn đã nhập địa chỉ IP ($1) không hợp lệ.
Xin chú ý rằng không thể nhập một tên người dùng!',
	'globalblocking-block-expiryinvalid'     => 'Thời hạn bạn nhập ($1) không hợp lệ.',
	'globalblocking-block-submit'            => 'Cấm địa chỉ IP này trên toàn hệ thống',
	'globalblocking-block-success'           => 'Đã cấm thành công địa chỉ IP $1 trên tất cả các dự án Wikimedia.
Có thể bạn cần xem lại [[Special:Globalblocklist|danh sách các lần cấm toàn hệ thống]].',
	'globalblocking-block-successsub'        => 'Cấm thành công trên toàn hệ thống',
	'globalblocking-block-alreadyblocked'    => 'Địa chỉ IP $1 đã bị cấm trên toàn hệ thống rồi. Bạn có thể xem những IP đang bị cấm tại [[Special:Globalblocklist|danh sách các lần cấm toàn hệ thống]].',
	'globalblocking-list'                    => 'Danh sách các địa chỉ IP bị cấm trên toàn hệ thống',
	'globalblocking-search-legend'           => 'Tìm một lần cấm toàn hệ thống',
	'globalblocking-search-ip'               => 'Địa chỉ IP:',
	'globalblocking-search-submit'           => 'Tìm lần cấm',
	'globalblocking-list-ipinvalid'          => 'Địa chỉ IP bạn muốn tìm ($1) không hợp lệ.
Xin hãy nhập một địa IP hợp lệ.',
	'globalblocking-search-errors'           => 'Tìm kiếm không thành công, lý do:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') đã cấm '''[[Special:Contributions/$4|$4]]''' trên toàn hệ thống ''($5)''",
	'globalblocking-list-expiry'             => 'hết hạn $1',
	'globalblocking-list-anononly'           => 'chỉ cấm vô danh',
	'globalblocking-list-unblock'            => 'bỏ cấm',
	'globalblocking-unblock-ipinvalid'       => 'Bạn đã nhập địa chỉ IP ($1) không hợp lệ.
Xin chú ý rằng không thể nhập một tên người dùng!',
	'globalblocking-unblock-legend'          => 'Xóa bỏ một lần cấm toàn hệ thống',
	'globalblocking-unblock-submit'          => 'Bỏ cấm hệ thống',
	'globalblocking-unblock-reason'          => 'Lý do:',
	'globalblocking-unblock-notblocked'      => 'Địa chỉ IP ($1) bạn đã nhập không bị cấm trên toàn hệ thống.',
	'globalblocking-unblock-unblocked'       => "Bạn đã bỏ thành công lần cấm #$2 đối với địa chỉ IP '''$1'''",
	'globalblocking-unblock-errors'          => 'Bạn không thể bỏ cấm cho địa chỉ IP này, lý do:',
	'globalblocking-unblock-successsub'      => 'Đã bỏ cấm trên toàn hệ thống thành công',
	'globalblocking-blocked'                 => "Địa chỉ IP của bạn đã bị '''\$1''' (''\$2'') cấm trên tất cả các wiki của Wikimedia.
Lý do được đưa ra là ''\"\$3\"''. Thời gian hết hạn cấm là ''\$4''.",
	'globalblocking-logpage'                 => 'Nhật trình cấm trên toàn hệ thống',
	'globalblocking-block-logentry'          => 'đã cấm [[$1]] trên toàn hệ thống với thời gian hết hạn của $2 ($3)',
	'globalblocking-unblock-logentry'        => 'đã bỏ cấm trên toàn hệ thống vào [[$1]]',
	'globalblocklist'                        => 'Danh sách các địa chỉ IP bị cấm trên toàn hệ thống',
	'globalblock'                            => 'Cấm một địa chỉ IP trên toàn hệ thống',
);

