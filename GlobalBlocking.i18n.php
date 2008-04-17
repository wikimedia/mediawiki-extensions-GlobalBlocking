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

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'globalblocking-block-reason'            => 'السبب لهذا المنع:',
	'globalblocking-block-expiry'            => 'انتهاء المنع:',
	'globalblocking-block-expiry-otherfield' => 'وقت آخر:',
	'globalblocking-block-options'           => 'خيارات',
);

/** Bulgarian (Български)
 * @author DCLXVI
 */
$messages['bg'] = array(
	'globalblocking-block-reason'       => 'Причина за блокирането:',
	'globalblocking-block-legend'       => 'Глобално блокиране на потребител',
	'globalblocking-block-submit'       => 'Блокиране на този IP адрес глобално',
	'globalblocking-search-legend'      => 'Търсене на глобално блокиране',
	'globalblocking-search-ip'          => 'IP адрес:',
	'globalblocking-unblock-legend'     => 'Премахване на глобално блокиране',
	'globalblocking-unblock-submit'     => 'Премахване на глобално блокиране',
	'globalblocking-unblock-reason'     => 'Причина:',
	'globalblocking-unblock-successsub' => 'Глобалното блокиране беше премахнато успешно',
	'globalblocking-logpage'            => 'Дневник на глобалните блокирания',
	'globalblocklist'                   => 'Списък на глобално блокираните IP адреси',
	'globalblock'                       => 'Глобално блокиране на IP адрес',
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
	'globalblocking-blocked'                 => "Deine IP-Adresse wurde von '''\$1''' ''(\$2)'' für alle Wikimedia-Wikis gesperrt.
Als Begründung wurde ''„\$3“'' angegeben. Die Sperre dauert ''\$4''.",
	'globalblocking-logpage'                 => 'Globales Sperrlogbuch',
	'globalblocking-block-logentry'          => 'sperrte [[$1]] global für einen Zeitraum von $2 ($3)',
	'globalblocking-unblock-logentry'        => 'entsperrte [[$1]] global',
	'globalblocklist'                        => 'Liste global gesperrter IP-Adressen',
	'globalblock'                            => 'Eine IP-Adresse global sperren',
);

/** Esperanto (Esperanto)
 * @author Yekrats
 */
$messages['eo'] = array(
	'globalblocking-block-options'  => 'Opcioj',
	'globalblocking-search-legend'  => 'Serĉu ĝeneralan forbaron',
	'globalblocking-search-ip'      => 'IP-adreso:',
	'globalblocking-search-submit'  => 'Serĉu forbarojn',
	'globalblocking-list-ipinvalid' => 'La serĉita IP-adreso ($1) estas nevalida.
Bonvolu enigi validan IP-adreson.',
	'globalblocking-search-errors'  => 'Via serĉo estis malsukcesa, ĉar:
$1',
	'globalblocking-list-expiry'    => 'findato $1',
	'globalblocking-list-anononly'  => 'nur anonimuloj',
	'globalblocking-list-unblock'   => 'malforbaru',
	'globalblocking-unblock-reason' => 'Kialo:',
	'globalblocking-blocked'        => "Via IP-adreso estis forbarita en ĉiuj Wikimedia-retejoj de '''\$1''' (''\$2'').
La kialo donata estis ''\"\$3\"''. La findato de la forbaro estas ''\$4''.",
	'globalblock'                   => 'Ĝenerale forbaru IP-adreson',
);

/** French (Français)
 * @author Grondin
 */
$messages['fr'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Permet]] le blocage des adresses IP [[Special:GlobalBlockList|à travers plusieurs wikis]]',
	'globalblocking-block'                   => 'Bloquer globalement une adresse IP',
	'globalblocking-block-intro'             => 'Vous pouvez utiliser cette page pour bloquer une adresse IP sur l’ensemble des wikis.',
	'globalblocking-block-reason'            => 'Motifs de ce blocage :',
	'globalblocking-block-expiry'            => 'Plage d’expiration :',
	'globalblocking-block-expiry-other'      => 'Autre durée d’expiration',
	'globalblocking-block-expiry-otherfield' => 'Autre durée :',
	'globalblocking-block-legend'            => 'Bloquer globalement un utilisateur',
	'globalblocking-block-options'           => 'Options',
	'globalblocking-block-errors'            => 'Le blocage n’a pas réussi, parce que :
$1',
	'globalblocking-block-ipinvalid'         => 'L’adresse IP ($1) que vous avez entrée est incorrecte.
Veuillez noter que vous ne pouvez pas inscrire un nom d’utilisateur !',
	'globalblocking-block-expiryinvalid'     => 'L’expiration que vous avez entrée ($1) est incorrecte.',
	'globalblocking-block-submit'            => 'Bloquer globalement cette adresse IP',
	'globalblocking-block-success'           => 'L’adresse IP $1 a été bloquée avec succès sur l’ensemble des projets Wikimedia.
Vous pouvez consultez la liste des [[Special:Globalblocklist|comptes bloqués globalement]].',
	'globalblocking-block-successsub'        => 'Blocage global réussi',
	'globalblocking-block-alreadyblocked'    => 'L’adresse IP est déjà bloquée globalement. Vous pouvez afficher les blocages existants sur la liste [[Special:Globalblocklist|des blocages globaux]].',
	'globalblocking-list'                    => 'Liste des adresses IP bloquées globalement',
	'globalblocking-search-legend'           => 'Recherche d’un blocage global',
	'globalblocking-search-ip'               => 'Adresse IP :',
	'globalblocking-search-submit'           => 'Recherche des blocages',
	'globalblocking-list-ipinvalid'          => 'L’adresse IP que vous recherchez pour ($1) est incorrecte.
Veuillez entrez une adresse IP correcte.',
	'globalblocking-search-errors'           => 'Votre recherche a été infructueuse, parce que :
$1',
	'globalblocking-list-blockitem'          => "* $1 : '''$2''' (''$3'') bloqué globalement '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'expiration $1',
	'globalblocking-list-anononly'           => 'uniquement anonyme',
	'globalblocking-list-unblock'            => 'débloquer',
	'globalblocking-unblock-ipinvalid'       => 'L’adresse IP que vous avez indiquée ($1) est incorrecte.
Notez bien que vous ne pouvez pas entrer un nom d’utilisateur !',
	'globalblocking-unblock-legend'          => 'Enlever un blocage global',
	'globalblocking-unblock-submit'          => 'Enlever le blocage global',
	'globalblocking-unblock-reason'          => 'Motifs :',
	'globalblocking-unblock-notblocked'      => 'L’adresse IP ($1) que vous avez indiquée ne fait l’objet d’aucun blocage global.',
	'globalblocking-unblock-unblocked'       => "Vous avez réussi à retirer le blocage global n° $2 correspondant à l’adresse IP '''$1'''",
	'globalblocking-unblock-errors'          => 'Vous ne pouvez pas enlever un blocage global pour cette adresse IP, parce que :
$1',
	'globalblocking-unblock-successsub'      => 'Blocage global retiré avec succès',
	'globalblocking-blocked'                 => "Votre adresse IP a été bloquée sur l’ensemble des wiki par '''$1''' (''$2'').
Le motif indiqué a été ''« $3 »''. L’expiration du blocage est pour le ''$4''.",
	'globalblocking-logpage'                 => 'Journal des blocages globaux',
	'globalblocking-block-logentry'          => '[[$1]] bloqué globalement avec une durée d’expiration de $2 ($3)',
	'globalblocking-unblock-logentry'        => 'blocage global retiré sur [[$1]]',
	'globalblocklist'                        => 'Liste des adresses IP bloquées globalement',
	'globalblock'                            => 'Bloquer globalement une adresse IP',
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

/** Manx (Gaelg)
 * @author MacTire02
 */
$messages['gv'] = array(
	'globalblocking-block-expiry-otherfield' => 'Am elley:',
	'globalblocking-block-options'           => 'Reihghyn',
	'globalblocking-search-ip'               => 'Enmys IP:',
	'globalblocking-unblock-reason'          => 'Fa:',
);

/** Hindi (हिन्दी)
 * @author Kaustubh
 */
$messages['hi'] = array(
	'globalblocking-desc'                    => 'आइपी एड्रेस को [[Special:GlobalBlockList|एक से ज्यादा विकियोंपर ब्लॉक]] करने की [[Special:GlobalBlock|अनुमति]] देता हैं।',
	'globalblocking-block'                   => 'एक आइपी एड्रेस को ग्लोबलि ब्लॉक करें',
	'globalblocking-block-intro'             => 'आप इस पन्ने का इस्तेमाल करके सभी विकियोंपर एक आईपी एड्रेस ब्लॉक कर सकतें हैं।',
	'globalblocking-block-reason'            => 'इस ब्लॉक का कारण:',
	'globalblocking-block-expiry'            => 'ब्लॉक समाप्ति:',
	'globalblocking-block-expiry-other'      => 'अन्य समाप्ती समय',
	'globalblocking-block-expiry-otherfield' => 'अन्य समय:',
	'globalblocking-block-legend'            => 'एक सदस्य को ग्लोबली ब्लॉक करें',
	'globalblocking-block-options'           => 'विकल्प',
	'globalblocking-block-errors'            => 'ब्लॉक अयशस्वी हुआ, कारण:
$1',
	'globalblocking-block-ipinvalid'         => 'आपने दिया हुआ आईपी एड्रेस ($1) अवैध हैं।
कृपया ध्यान दें आप सदस्यनाम नहीं दे सकतें!',
	'globalblocking-block-expiryinvalid'     => 'आपने दिया हुआ समाप्ती समय ($1) अवैध हैं।',
	'globalblocking-block-submit'            => 'इस आईपी को ग्लोबली ब्लॉक करें',
	'globalblocking-block-successsub'        => 'ग्लोबल ब्लॉक यशस्वी हुआ',
	'globalblocking-list'                    => 'ग्लोबल ब्लॉक किये हुए आईपी एड्रेसोंकी सूची',
	'globalblocking-search-legend'           => 'ग्लोबल ब्लॉक खोजें',
	'globalblocking-search-ip'               => 'आइपी एड्रेस:',
	'globalblocking-search-submit'           => 'ब्लॉक खोजें',
	'globalblocking-search-errors'           => 'आपकी खोज़ अयशस्वी हुई हैं, कारण:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') ग्लोबली ब्लॉक किया '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'समाप्ती $1',
	'globalblocking-list-anononly'           => 'सिर्फ-अनामक',
	'globalblocking-list-unblock'            => 'अनब्लॉक',
	'globalblocking-unblock-ipinvalid'       => 'आपने दिया हुआ आईपी एड्रेस ($1) अवैध हैं।
कृपया ध्यान दें आप सदस्यनाम नहीं दे सकतें!',
	'globalblocking-unblock-legend'          => 'ग्लोबल ब्लॉक हटायें',
	'globalblocking-unblock-submit'          => 'ग्लोबल ब्लॉक हटायें',
	'globalblocking-unblock-reason'          => 'कारण:',
	'globalblocking-unblock-notblocked'      => 'आपने दिया हुआ आईपी एड्रेस ($1) पर ग्लोबल ब्लॉक नहीं हैं।',
	'globalblocking-unblock-unblocked'       => "आपने '''$1''' इस आइपी एड्रेस पर होने वाला ग्लोबल ब्लॉक #$2 हटा दिया हैं",
	'globalblocking-unblock-errors'          => 'आप इस आईपी एड्रेस का ग्लोबल ब्लॉक हटा नहीं सकतें, कारण:
$1',
	'globalblocking-unblock-successsub'      => 'ग्लोबल ब्लॉक हटा दिया गया हैं',
	'globalblocking-logpage'                 => 'ग्लोबल ब्लॉक सूची',
	'globalblocking-block-logentry'          => '[[$1]] को ग्लोबली ब्लॉक किया समाप्ति समय $2 ($3)',
	'globalblocking-unblock-logentry'        => '[[$1]] का ग्लोबल ब्लॉक निकाल दिया',
	'globalblocklist'                        => 'ग्लोबल ब्लॉक होनेवाले आइपी एड्रेसकी सूची',
	'globalblock'                            => 'एक आइपी एड्रेसको ग्लोबल ब्लॉक करें',
);

/** Hungarian (Magyar)
 * @author Dani
 */
$messages['hu'] = array(
	'globalblocking-list-expiry'    => 'lejárat: $1',
	'globalblocking-unblock-reason' => 'Ok:',
	'globalblock'                   => 'IP-cím globális blokkolása',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'globalblocking-block'                   => 'Eng IP-Adress global spären',
	'globalblocking-block-intro'             => 'Dir kënnt dës Säit benotzen fir eng IP-Adress op alle Wikien ze spären.',
	'globalblocking-block-reason'            => 'Grond fir dës Spär:',
	'globalblocking-block-expiry-otherfield' => 'Aner Dauer:',
	'globalblocking-block-options'           => 'Optiounen',
	'globalblocking-search-ip'               => 'IP-Adress:',
);

/** Marathi (मराठी)
 * @author Kaustubh
 */
$messages['mr'] = array(
	'globalblocking-desc'                    => 'आइपी अंकपत्त्याला [[Special:GlobalBlockList|अनेक विकिंवर ब्लॉक]] करण्याची [[Special:GlobalBlock|परवानगी]] देतो.',
	'globalblocking-block'                   => 'आयपी अंकपत्ता वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-intro'             => 'तुम्ही हे पान वापरून एखाद्या आयपी अंकपत्त्याला सर्व विकिंवर ब्लॉक करू शकता.',
	'globalblocking-block-reason'            => 'या ब्लॉक करीता कारण:',
	'globalblocking-block-expiry'            => 'ब्लॉक समाप्ती:',
	'globalblocking-block-expiry-other'      => 'इतर समाप्ती वेळ',
	'globalblocking-block-expiry-otherfield' => 'इतर वेळ:',
	'globalblocking-block-legend'            => 'एक सदस्य वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-options'           => 'विकल्प',
	'globalblocking-block-errors'            => 'ब्लॉक अयशस्वी झालेला आहे, कारण:
$1',
	'globalblocking-block-ipinvalid'         => 'तुम्ही दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया नोंद घ्या की तुम्ही सदस्य नाव देऊ शकत नाही!',
	'globalblocking-block-expiryinvalid'     => 'तुम्ही दिलेली समाप्तीची वेळ ($1) अयोग्य आहे.',
	'globalblocking-block-submit'            => 'ह्या आयपी अंकपत्त्याला वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-successsub'        => 'वैश्विक ब्लॉक यशस्वी',
	'globalblocking-block-alreadyblocked'    => '$1 हा आयपी अंकपत्ता अगोदरच ब्लॉक केलेला आहे. तुम्ही अस्तित्वात असलेले ब्लॉक [[Special:Globalblocklist|वैश्विक ब्लॉकच्या यादीत]] पाहू शकता.',
	'globalblocking-list'                    => 'वैश्विक पातळीवर ब्लॉक केलेले आयपी अंकपत्ते',
	'globalblocking-search-legend'           => 'एखाद्या वैश्विक ब्लॉक ला शोधा',
	'globalblocking-search-ip'               => 'आयपी अंकपत्ता:',
	'globalblocking-search-submit'           => 'ब्लॉक साठी शोध',
	'globalblocking-list-ipinvalid'          => 'तुम्ही शोधायला दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया योग्य आयपी अंकपत्ता द्या.',
	'globalblocking-search-errors'           => 'तुमचा शोध अयशस्वी झालेला आहे, कारण:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') वैश्विक पातळीवर ब्लॉक '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'समाप्ती $1',
	'globalblocking-list-anononly'           => 'फक्त-अनामिक',
	'globalblocking-list-unblock'            => 'अनब्लॉक',
	'globalblocking-unblock-ipinvalid'       => 'तुम्ही दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया नोंद घ्या की तुम्ही सदस्य नाव वापरू शकत नाही!',
	'globalblocking-unblock-legend'          => 'एक वैश्विक ब्लॉक काढा',
	'globalblocking-unblock-submit'          => 'वैश्विक ब्लॉक काढा',
	'globalblocking-unblock-reason'          => 'कारण:',
	'globalblocking-unblock-notblocked'      => 'तुम्ही दिलेला आयपी अंकपत्ता ($1) वैश्विक पातळीवर ब्लॉक केलेला नाही.',
	'globalblocking-unblock-unblocked'       => "तुम्ही आयपी अंकपत्ता '''$1''' वर असणारा वैश्विक ब्लॉक #$2 यशस्वीरित्या काढलेला आहे",
	'globalblocking-unblock-errors'          => 'तुम्ही या आयपी अंकपत्त्यावरील वैश्विक ब्लॉक काढू शकत नाही, कारण:
$1',
	'globalblocking-unblock-successsub'      => 'वैश्विक ब्लॉक काढलेला आहे',
	'globalblocking-blocked'                 => "तुमचा आयपी अंकपत्ता सर्व विकिमीडिया विकिंवर '''\$1''' (''\$2'') ने ब्लॉक केलेला आहे.
यासाठी ''\"\$3\"'' हे कारण दिलेले आहे. या ब्लॉक ची समाप्ती ''\$4'' आहे.",
	'globalblocking-logpage'                 => 'वैश्विक ब्लॉक सूची',
	'globalblocking-block-logentry'          => '$2 ($3) हा समाप्ती कालावधी देऊन [[$1]] ला वैश्विक पातळीवर ब्लॉक केले',
	'globalblocking-unblock-logentry'        => '[[$1]] वरील वैश्विक ब्लॉक काढला',
	'globalblocklist'                        => 'वैश्विक पातळीवर ब्लॉक केलेल्या आयपी अंकपत्त्यांची यादी',
	'globalblock'                            => 'आयपी अंकपत्त्याला वैश्विक पातळीवर ब्लॉक करा',
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

/** Norwegian Nynorsk (‪Norsk (nynorsk)‬)
 * @author Jorunn
 */
$messages['nn'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Gjer det råd]] å blokkera IP-adresser [[Special:GlobalBlockList|krosswiki]]',
	'globalblocking-block'                   => 'Blokker ei IP-adresse krosswiki',
	'globalblocking-block-intro'             => 'Du kan nytte denne sida til å blokkere ei IP-adresse krosswiki.',
	'globalblocking-block-reason'            => 'Grunngjeving for blokkeringa:',
	'globalblocking-block-expiry'            => 'Blokkeringa varer til:',
	'globalblocking-block-expiry-other'      => 'Anna varigheit',
	'globalblocking-block-expiry-otherfield' => 'Anna tid:',
	'globalblocking-block-legend'            => 'Blokker ein brukar krosswiki',
	'globalblocking-block-options'           => 'Alternativ',
	'globalblocking-block-errors'            => 'Blokkeringa tok ikkje, grunna:',
	'globalblocking-block-ipinvalid'         => 'IP-adressa du skreiv inn ($1) er ugyldig.
Merk at du ikkje kan skrive inn brukarnamn.',
	'globalblocking-block-expiryinvalid'     => 'Varigheita du skreiv inn ($1) er ikkje gyldig.',
	'globalblocking-block-submit'            => 'Blokker denne IP-adressa krosswiki',
	'globalblocking-block-success'           => 'IP-adressa $1 har vote blokkert på alle Wikimedia-prosjekta.
Sjå òg [[Special:Globalblocklist|lista over krosswikiblokkeringar]].',
	'globalblocking-block-successsub'        => 'Krosswikiblokkeringa vart utførd',
	'globalblocking-block-alreadyblocked'    => 'IP-adressa $1 er allereide krosswikiblokkert. Du kan sjå blokkeringa på [[Special:GlobalBlockList|lista over krosswikiblokkeringar]].',
	'globalblocking-list'                    => 'Liste over krosswikiblokkertet IP-adresser',
	'globalblocking-search-legend'           => 'Søk etter ei krosswikiblokkering',
	'globalblocking-search-ip'               => 'IP-adresse:',
	'globalblocking-search-submit'           => 'Søk etter blokkeringar',
	'globalblocking-list-ipinvalid'          => 'IP-adressa du skreiv inn ($1) er ikkje gyldig.
Skriv inn ei gyldig IP-adresse.',
	'globalblocking-search-errors'           => 'Søket ditt lukkast ikkje fordi:
$1',
	'globalblocking-list-blockitem'          => "$1 '''$2''' ('''$3''') blokkerte '''[[Special:Contributions/$4|$4]]''' krosswiki ''($5)''",
	'globalblocking-list-expiry'             => 'varigheit $1',
	'globalblocking-list-anononly'           => 'berre uregistrerte',
	'globalblocking-list-unblock'            => 'fjern blokkeringa',
	'globalblocking-unblock-ipinvalid'       => 'IP-adressa du skreiv inn ($1) er ugyldig.
Merk at du ikkje kan skrive inn brukarnamn.',
	'globalblocking-unblock-legend'          => 'Fjern ei krosswikiblokkering',
	'globalblocking-unblock-submit'          => 'Fjern krosswikiblokkering',
	'globalblocking-unblock-reason'          => 'Grunngjeving:',
	'globalblocking-unblock-notblocked'      => 'IP-adressa du skreiv inn ($1) er ikkje krosswikiblokkert.',
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Gjør det mulig]] å blokkere IP-adresser på [[Special:GlobalBlockList|alle wikier]]',
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
	'globalblocking-block-alreadyblocked'    => 'IP-adressen $1 er blokkkert globalt fra før. Du kan se eksisterende blokkeringer på [[Special:GlobalBlockList|listen over globale blokkeringer]].',
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

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Permet]] lo blocatge de las adreças IP [[Special:GlobalBlockList|a travèrs maites wikis]]',
	'globalblocking-block'                   => 'Blocar globalament una adreça IP',
	'globalblocking-block-intro'             => 'Podètz utilizar aquesta pagina per blocar una adreça IP sus l’ensemble dels wikis.',
	'globalblocking-block-reason'            => "Motius d'aqueste blocatge :",
	'globalblocking-block-expiry'            => 'Plaja d’expiracion :',
	'globalblocking-block-expiry-other'      => 'Autra durada d’expiracion',
	'globalblocking-block-expiry-otherfield' => 'Autra durada :',
	'globalblocking-block-legend'            => 'Blocar globalament un utilizaire',
	'globalblocking-block-options'           => 'Opcions',
	'globalblocking-block-errors'            => 'Lo blocatge a pas capitat, perque :
$1',
	'globalblocking-block-ipinvalid'         => "L’adreça IP ($1) qu'avètz picada es incorrècta.
Notatz que podètz pas inscriure un nom d’utilizaire !",
	'globalblocking-block-expiryinvalid'     => "L’expiracion qu'avètz picada ($1) es incorrècta.",
	'globalblocking-block-submit'            => 'Blocar globalament aquesta adreça IP',
	'globalblocking-block-success'           => 'L’adreça IP $1 es estada blocada amb succès sus l’ensemble dels projèctes Wikimèdia.
Podètz consultaz la tièra dels [[Special:Globalblocklist|comptes blocats globalament]].',
	'globalblocking-block-successsub'        => 'Blocatge global capitat',
	'globalblocking-block-alreadyblocked'    => "L’adreça IP ja es blocada globalament. Podètz afichar los blocatges qu'existisson sus la tièra [[Special:Globalblocklist|dels blocatges globals]].",
	'globalblocking-list'                    => 'Tièra de las adreças IP blocadas globalament',
	'globalblocking-search-legend'           => 'Recèrca d’un blocatge global',
	'globalblocking-search-ip'               => 'Adreça IP :',
	'globalblocking-search-submit'           => 'Recèrca dels blocatges',
	'globalblocking-list-ipinvalid'          => 'L’adreça IP que recercatz per ($1) es incorrècta.
Picatz una adreça IP corrècta.',
	'globalblocking-search-errors'           => 'Vòstra recèrca es estada infructuosa, perque :
$1',
	'globalblocking-list-blockitem'          => "* $1 : '''$2''' (''$3'') blocat globalament '''[[Special:Contribucions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'expiracion $1',
	'globalblocking-list-anononly'           => 'utilizaire non enregistrat unicament',
	'globalblocking-list-unblock'            => 'desblocar',
	'globalblocking-unblock-ipinvalid'       => "L’adreça IP ($1) qu'avètz picada es incorrècta.
Notatz que podètz pas inscriure un nom d’utilizaire !",
	'globalblocking-unblock-legend'          => 'Levar un blocatge global',
	'globalblocking-unblock-submit'          => 'Levar lo blocatge global',
	'globalblocking-unblock-reason'          => 'Motiu :',
	'globalblocking-unblock-notblocked'      => "L’adreça IP ($1) qu'avètz indicada fa pas l’objècte de cap de blocatge global.",
	'globalblocking-unblock-unblocked'       => "Avètz capitat de levar lo blocatge global n° $2 correspondent a l’adreça IP '''$1'''",
	'globalblocking-unblock-errors'          => 'Podètz pas levar un blocatge global per aquesta adreça IP, perque :
$1',
	'globalblocking-unblock-successsub'      => 'Blocatge global levat amb succès',
	'globalblocking-blocked'                 => "Vòstra adreça IP es estada blocada sus l’ensemble dels wiki per '''$1''' (''$2'').
Lo motiu indicat es estat ''« $3 »''. L’expiracion del blocatge es pel ''$4''.",
	'globalblocking-logpage'                 => 'Jornal dels blocatges globals',
	'globalblocking-block-logentry'          => '[[$1]] blocat globalament amb una durada d’expiracion de $2 ($3)',
	'globalblocking-unblock-logentry'        => 'blocatge global levat sus [[$1]]',
	'globalblocklist'                        => 'Tièra de las adreças IP blocadas globalament',
	'globalblock'                            => 'Blocar globalament una adreça IP',
);

/** Polish (Polski)
 * @author Sp5uhe
 */
$messages['pl'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Umożliwia]] równoległe [[Special:GlobalBlockList|blokowanie dla wielu wiki]] adresów IP',
	'globalblocking-block'                   => 'Zablokuj globalnie adres IP',
	'globalblocking-block-intro'             => 'Możesz użyć tej strony do zablokowania adresu IP na wszystkich wiki.',
	'globalblocking-block-reason'            => 'Powód zrobienia tej blokady',
	'globalblocking-block-expiry'            => 'Czas blokady',
	'globalblocking-block-expiry-other'      => 'Inny czas blokady',
	'globalblocking-block-expiry-otherfield' => 'Inny czas blokady',
	'globalblocking-block-legend'            => 'Zablokuj użytkownika globalnie',
	'globalblocking-block-options'           => 'Opcje',
	'globalblocking-block-errors'            => 'Zablokowanie nie powiodło się, ponieważ:
$1',
	'globalblocking-block-ipinvalid'         => 'Wprowadzony przez Ciebie adres IP ($1) jest nieprawidłowy.
Zwróć uwagę na to, że nie możesz wprowadzić nazwy użytkownika!',
	'globalblocking-block-expiryinvalid'     => 'Czas obowiązywania blokady ($1) jest nieprawidłowy.',
	'globalblocking-block-submit'            => 'Zablokuj ten adres IP globalnie',
	'globalblocking-block-success'           => 'Adres IP $1 został z powodzeniem zablokowany na wszystkich projektach Wikimedia.
Możesz to sprawdzić w [[Special:Globalblocklist|spisie globalnych blokad]].',
	'globalblocking-block-successsub'        => 'Globalna blokada wykonana',
	'globalblocking-block-alreadyblocked'    => 'Adres IP $1 jest obecnie globalnie zablokowany. Możesz zobaczyć aktualnie obowiązujące blokady w [[Special:Globalblocklist|spisie globalnych blokad]].',
	'globalblocking-list'                    => 'Spis globalnie zablokowanych adresów IP',
	'globalblocking-search-legend'           => 'Szukaj globalnej blokady',
	'globalblocking-search-ip'               => 'Adres IP:',
	'globalblocking-search-submit'           => 'Szukaj blokad',
	'globalblocking-list-ipinvalid'          => 'Adres IP którego szukasz ($1) jest nieprawidłowy.
Wprowadź prawidłowy adres IP.',
	'globalblocking-search-errors'           => 'Wyszukanie nie powiodło się, ponieważ:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') globalnie zablokował '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'wygaśnie $1',
	'globalblocking-list-anononly'           => 'tylko niezalogowani',
	'globalblocking-list-unblock'            => 'odblokuj',
	'globalblocking-unblock-ipinvalid'       => 'Wprowadzony przez Ciebie adres IP ($1) jest nieprawidłowy.
Zwróć uwagę na to, że nie możesz wprowadzić nazwy użytkownika!',
	'globalblocking-unblock-legend'          => 'Usuń globalną blokadę',
	'globalblocking-unblock-submit'          => 'Usuń globalną blokadę',
	'globalblocking-unblock-reason'          => 'Powód',
	'globalblocking-unblock-notblocked'      => 'Adres IP ($1) który wprowadziłeś nie jest globalnie zablokowany.',
	'globalblocking-unblock-unblocked'       => "Usunąłeś globalną blokadę $2 dla adresu IP '''$1'''",
	'globalblocking-unblock-errors'          => 'Nie możesz usunąć globalnej blokady dla tego adresu IP, ponieważ:
$1',
	'globalblocking-unblock-successsub'      => 'Globalna blokada została zdjęta',
	'globalblocking-blocked'                 => "Twój adres IP został zablokowany na wszystkich wiki należących do Wikimedia przez '''$1''' (''$2'').
Przyczyna blokady: ''„$3”''. Blokada wygaśnie ''$4''.",
	'globalblocking-logpage'                 => 'Rejestr globalnych blokad',
	'globalblocking-block-logentry'          => 'zablokował globalnie [[$1]], czas blokady $2 ($3)',
	'globalblocking-unblock-logentry'        => 'usunął globalną blokadę z [[$1]]',
	'globalblocklist'                        => 'Spis globalnie zablokowanych adresów IP',
	'globalblock'                            => 'Zablokuj globalnie adres IP',
);

/** Portuguese (Português)
 * @author Malafaya
 */
$messages['pt'] = array(
	'globalblocking-block'                   => 'Bloquear globalmente um endereço IP',
	'globalblocking-block-reason'            => 'Motivo para este bloqueio:',
	'globalblocking-block-expiry-otherfield' => 'Outra duração:',
	'globalblocking-block-legend'            => 'Bloquear um utilizador globalmente',
	'globalblocking-block-options'           => 'Opções',
	'globalblocking-block-errors'            => 'O bloqueio não teve sucesso, porque:
$1',
	'globalblocking-search-ip'               => 'Endereço IP:',
	'globalblocking-unblock-reason'          => 'Motivo:',
	'globalblocking-logpage'                 => 'Registo de bloqueios globais',
);

/** Russian (Русский)
 * @author .:Ajvol:.
 */
$messages['ru'] = array(
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Разрешает]] блокировку IP-адресов [[Special:GlobalBlockList|на нескольких вики]]',
	'globalblocking-block'                   => 'Глобальная блокировка IP-адреса',
	'globalblocking-block-intro'             => 'Вы можете использовать эту страницу чтобы заблокировать IP-адрес на всех вики.',
	'globalblocking-block-reason'            => 'Причина блокировки:',
	'globalblocking-block-expiry'            => 'Закончится через:',
	'globalblocking-block-expiry-other'      => 'другое время окончания',
	'globalblocking-block-expiry-otherfield' => 'Другое время:',
	'globalblocking-block-legend'            => 'Глобальное блокирование участника',
	'globalblocking-block-options'           => 'Настройки',
	'globalblocking-block-errors'            => 'Блокировка неудачна. Причина:
$1',
	'globalblocking-block-ipinvalid'         => 'Введённый вами IP-адрес ($1) ошибочен.
Пожалуйста, обратите внимание, вы не можете вводить имя участника!',
	'globalblocking-block-expiryinvalid'     => 'Введённый срок окончания ($1) ошибочен.',
	'globalblocking-block-submit'            => 'Заблокировать этот IP-адрес глобально',
	'globalblocking-block-success'           => 'IP-адрес $1 был успешно заблокирован во всех проектах Викимедиа.
Вы можете обратиться к [[Special:Globalblocklist|списку глобальных блокировок]].',
	'globalblocking-block-successsub'        => 'Глобальная блокировка выполнена успешно',
	'globalblocking-block-alreadyblocked'    => 'IP-адрес $1 уже был заблокирован глобально. Вы можете просмотреть существующие блокировки в [[Special:Globalblocklist|списке глобальных блокировок]].',
	'globalblocking-list'                    => 'Список глобально заблокированных IP-адресов',
	'globalblocking-search-legend'           => 'Поиск глобальной блокировки',
	'globalblocking-search-ip'               => 'IP-адрес:',
	'globalblocking-search-submit'           => 'Найти блокировки',
	'globalblocking-list-ipinvalid'          => 'Вы ищете ошибочный IP-адрес ($1).
Пожалуйста введите корректный IP-адрес.',
	'globalblocking-search-errors'           => 'Ваш поиск не был успешен. Причина:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') заблокирован глобально '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'истекает $1',
	'globalblocking-list-anononly'           => 'только анонимов',
	'globalblocking-list-unblock'            => 'разблокировать',
	'globalblocking-unblock-ipinvalid'       => 'Введённый вами IP-адрес ($1) ошибочен.
Пожалуйста, обратите внимание, вы не можете вводить имя участника!',
	'globalblocking-unblock-legend'          => 'Снятие глобальной блокировки',
	'globalblocking-unblock-submit'          => 'Снять глобальную блокировку',
	'globalblocking-unblock-reason'          => 'Причина:',
	'globalblocking-unblock-notblocked'      => 'Введённый вами IP-адрес ($1) не заблокирован глобально.',
	'globalblocking-unblock-unblocked'       => "Вы успешно сняли глобальную блокировку #$2 с IP-адреса '''$1'''",
	'globalblocking-unblock-errors'          => 'Вы не можете снять глобальную блокировку с этого IP-адреса. Причина:
$1',
	'globalblocking-unblock-successsub'      => 'Глобальная блокировка успешно снята',
	'globalblocking-blocked'                 => "Ваш IP-адрес был заблокирован во всех проектах Викимедиа участником '''$1''' (''$2'').
Была указана причина: ''«$3»''. Срок блокировки: ''$4''.",
	'globalblocking-logpage'                 => 'Журнал глобальных блокировок',
	'globalblocking-block-logentry'          => 'заблокировал глобально [[$1]] со сроком блокировки $2 ($3)',
	'globalblocking-unblock-logentry'        => 'снял глобальную блокировку с [[$1]]',
	'globalblocklist'                        => 'Список заблокированных глобально IP-адресов',
	'globalblock'                            => 'Глобальная блокировка IP-адреса',
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
	'globalblocking-desc'                    => '[[Special:GlobalBlock|Омогућује]] [[Special:GlobalBlockList|глобално блокирање]] ИП адреса на више викија',
	'globalblocking-block'                   => 'Глобално блокирајте ИП адресу',
	'globalblocking-block-intro'             => 'Можете користити ову страницу да блокирате ИП адресу на свим викијима.',
	'globalblocking-block-reason'            => 'Разлог блока:',
	'globalblocking-block-expiry'            => 'Блок истиче:',
	'globalblocking-block-expiry-other'      => 'Друго време истека',
	'globalblocking-block-expiry-otherfield' => 'Друго време:',
	'globalblocking-block-legend'            => 'Блокирајте корисника глобално',
	'globalblocking-block-options'           => 'Опције',
	'globalblocking-block-errors'            => 'Блок није успешан због:
$1',
	'globalblocking-block-ipinvalid'         => 'ИП адреса ($1) коју сте унели није добра.
Запамтите да не можете унети корисничко име!',
	'globalblocking-block-expiryinvalid'     => 'Време истека блока које сте унели ($1) није исправно.',
	'globalblocking-block-submit'            => 'Блокирајте ову ИП адресу глобално',
	'globalblocking-block-success'           => 'Ип адреса $1 је успешно блокирана на свим Викимедијиним пројектима.
Погледајте [[Special:Globalblocklist|списак глобалних блокова]].',
	'globalblocking-block-successsub'        => 'Успешан глобални блок',
	'globalblocking-block-alreadyblocked'    => 'ИП адреса $1 је већ блокирана глобално. Можете погледати списак постојећих [[Special:Globalblocklist|глобалних блокова]].',
	'globalblocking-list'                    => 'Списак глобално блокираних ИП адреса',
	'globalblocking-search-legend'           => 'Претражите глобалне блокове',
	'globalblocking-search-ip'               => 'ИП адреса:',
	'globalblocking-search-submit'           => 'Претражите блокове',
	'globalblocking-list-ipinvalid'          => 'ИП адреса коју тражите ($1) није исправна.
Молимо Вас унесите исправну ИП адресу.',
	'globalblocking-search-errors'           => 'Ваша претрага није успешна због:
$1',
	'globalblocking-list-blockitem'          => "$1: '''$2''' (''$3'') глобално блокирао '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry'             => 'истиче $1',
	'globalblocking-list-anononly'           => 'само анонимне',
	'globalblocking-list-unblock'            => 'одблокирај',
	'globalblocking-unblock-ipinvalid'       => 'ИП адреса ($1) коју сте унели није исправна.
Запамтите да не можете уносити корисничка имена!',
	'globalblocking-unblock-legend'          => 'Уклоните глобални блок',
	'globalblocking-unblock-submit'          => 'Уклоните глобални блок',
	'globalblocking-unblock-reason'          => 'Разлог:',
	'globalblocking-unblock-notblocked'      => 'ИП адреса ($1) коју сте унели није глобално блокирана.',
	'globalblocking-unblock-unblocked'       => "Успешно сте уклонили глобални блок #$2 за ИП адресу '''$1'''.",
	'globalblocking-unblock-errors'          => 'Не можете уклонити глобални блок за ту ИП адресу због:
$1',
	'globalblocking-unblock-successsub'      => 'Глобални блок успешно уклоњен',
	'globalblocking-blocked'                 => "Ваша ИП адреса је блокирана на свим Викимедијиним викијима. Корисник који је блокирао '''$1''' (''$2'').
Разлог за блокаду је „''$3''”. Блок истиче ''$4''.",
	'globalblocking-logpage'                 => 'Историја глобалних блокова',
	'globalblocking-block-logentry'          => 'глобално блокирао [[$1]] са временом истицања од $2 ($3)',
	'globalblocking-unblock-logentry'        => 'уклонио глобални блок за [[$1]]',
	'globalblocklist'                        => 'Списак глобално блокираних ИП адреса',
	'globalblock'                            => 'Глобално блокирајте ИП адресу',
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
	'globalblocking-block-ipinvalid'         => 'IP-adressen du skrev in ($1) är ogiltig.
Notera att du inte kan skriva in användarnamn.',
	'globalblocking-block-expiryinvalid'     => 'Varigheten du skrev in ($1) är ogiltig.',
	'globalblocking-block-submit'            => 'Blockera denna IP-adress globalt',
	'globalblocking-block-success'           => 'IP-adressen $1 har blivit blockerad på alla Wikimedia-projekt.
Du vill kanske att se [[Special:Globalblocklist|listan över globala blockeringar]].',
	'globalblocking-block-successsub'        => 'Global blockering lyckades',
	'globalblocking-block-alreadyblocked'    => 'IP-adressen $1 är redan blockerad globalt. Du kan visa den existerande blockeringen på [[Special:Globalblocklist|listan över globala blockeringar]].',
	'globalblocking-list'                    => 'Lista över globalt blockerade IP-adresser',
	'globalblocking-search-legend'           => 'Sök efter en global blockering',
	'globalblocking-search-ip'               => 'IP-adress:',
	'globalblocking-search-submit'           => 'Sök efter blockeringar',
	'globalblocking-list-ipinvalid'          => 'IP-adressen du skrev in ($1) är ogiltig.
Skriv in en giltig IP-adress.',
	'globalblocking-search-errors'           => 'Din sökning misslyckades på grund av:
$1',
	'globalblocking-list-blockitem'          => "$1 '''$2''' ('''$3''') blockerade '''[[Special:Contributions/$4|$4]]''' globalt ''($5)''",
	'globalblocking-list-expiry'             => 'varighet $1',
	'globalblocking-list-anononly'           => 'endast oregistrerade',
	'globalblocking-list-unblock'            => 'avblockera',
	'globalblocking-unblock-ipinvalid'       => 'IP-adressen du skrev in ($1) är ogiltig.
Notera att du inte kan skriva in användarnamn!',
	'globalblocking-unblock-legend'          => 'Ta bort en global blockering',
	'globalblocking-unblock-submit'          => 'Ta bort global blockering',
	'globalblocking-unblock-reason'          => 'Anledning:',
	'globalblocking-unblock-notblocked'      => 'IP-adressen du skrev in ($1) är inte globalt blockerad.',
	'globalblocking-unblock-unblocked'       => "Du har tagit bort den globala blockeringen (#$2) på IP-adressen '''$1'''",
	'globalblocking-unblock-errors'          => 'Du kan inte ta bort en global blockering på den IP-adressen för att:
$1',
	'globalblocking-unblock-successsub'      => 'Global blockering borttagen',
	'globalblocking-blocked'                 => "Din IP-adress har blivit blockerad på alla Wikimedia-wikier av '''$1''' (''$2'').
Anledningar var '''$3'''. Blockeringen utgår ''$4''.",
	'globalblocking-logpage'                 => 'Logg för globala blockeringar',
	'globalblocking-block-logentry'          => 'blockerade [[$1]] globalt med en varighet på $2 ($3)',
	'globalblocking-unblock-logentry'        => 'tog bort global blockering på [[$1]]',
	'globalblocklist'                        => 'Lista över globalt blockerade IP-adresser',
	'globalblock'                            => 'Blockera en IP-adress globalt',
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

