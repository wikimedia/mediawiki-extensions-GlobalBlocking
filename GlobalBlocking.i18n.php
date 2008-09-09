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
	'globalblocking-expiry-options' => '-', # do not translate or duplicate this message to other languages
	'globalblocking-block-intro' => 'You can use this page to block an IP address on all wikis.',
	'globalblocking-block-reason' => 'Reason for this block:',
	'globalblocking-block-expiry' => 'Block expiry:',
	'globalblocking-block-expiry-other' => 'Other expiry time',
	'globalblocking-block-expiry-otherfield' => 'Other time:',
	'globalblocking-block-legend' => 'Block a user globally',
	'globalblocking-block-options' => 'Options:',
	'globalblocking-block-errors' => "Your block was unsuccessful, for the following {{PLURAL:$1|reason|reasons}}:",
	'globalblocking-block-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-block-expiryinvalid' => 'The expiry you entered ($1) is invalid.',
	'globalblocking-block-submit' => 'Block this IP address globally',
	'globalblocking-block-success' => 'The IP address $1 has been successfully blocked on all projects.',
	'globalblocking-block-successsub' => 'Global block successful',
	'globalblocking-block-alreadyblocked' => 'The IP address $1 is already blocked globally.
You can view the existing block on the [[Special:GlobalBlockList|list of global blocks]].',
	'globalblocking-block-bigrange' => 'The range you specified ($1) is too big to block.
You may block, at most, 65,536 addresses (/16 ranges)',
	
	'globalblocking-list-intro' => 'This is a list of all global blocks which are currently in effect.
Some blocks are marked as locally disabled: this means that they apply on other sites, but a local administrator has decided to disable them on this wiki.',
	'globalblocking-list' => 'List of globally blocked IP addresses',
	'globalblocking-search-legend' => 'Search for a global block',
	'globalblocking-search-ip' => 'IP address:',
	'globalblocking-search-submit' => 'Search for blocks',
	'globalblocking-list-ipinvalid' => 'The IP address you searched for ($1) is invalid.
Please enter a valid IP address.',
	'globalblocking-search-errors' => "Your search was unsuccessful, for the following {{PLURAL:$1|reason|reasons}}:",
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globally blocked '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiry $1',
	'globalblocking-list-anononly' => 'anonymous only',
	'globalblocking-list-unblock' => 'remove',
	'globalblocking-list-whitelisted' => 'locally disabled by $1: $2',
	'globalblocking-list-whitelist' => 'local status',
	'globalblocking-goto-block' => 'Globally block an IP address',
	'globalblocking-goto-unblock' => 'Remove a global block',
	'globalblocking-goto-status' => 'Change local status for a global block',
		
	'globalblocking-return' => 'Return to the list of global blocks',
	'globalblocking-notblocked' => 'The IP address ($1) you entered is not globally blocked.',

	'globalblocking-unblock' => 'Remove a global block',
	'globalblocking-unblock-ipinvalid' => 'The IP address ($1) you entered is invalid.
Please note that you cannot enter a user name!',
	'globalblocking-unblock-legend' => 'Remove a global block',
	'globalblocking-unblock-submit' => 'Remove global block',
	'globalblocking-unblock-reason' => 'Reason:',
	'globalblocking-unblock-unblocked' => "You have successfully removed the global block #$2 on the IP address '''$1'''",
	'globalblocking-unblock-errors' => "Your removal of the global block was unsuccessful, for the following {{PLURAL:$1|reason|reasons}}:",
	'globalblocking-unblock-successsub' => 'Global block successfully removed',
	'globalblocking-unblock-subtitle' => 'Removing global block',
	'globalblocking-unblock-intro' => 'You can use this form to remove a global block.
[[Special:GlobalBlockList|Click here]] to return to the global block list.',
	
	'globalblocking-whitelist' => 'Local status of global blocks',
	'globalblocking-whitelist-legend' => 'Change local status',
	'globalblocking-whitelist-reason' => 'Reason for change:',
	'globalblocking-whitelist-status' => 'Local status:',
	'globalblocking-whitelist-statuslabel' => 'Disable this global block on {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Change local status',
	'globalblocking-whitelist-whitelisted' => "You have successfully disabled the global block #$2 on the IP address '''$1''' on {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "You have successfully re-enabled the global block #$2 on the IP address '''$1''' on {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Local status successfully changed',
	'globalblocking-whitelist-nochange' => 'You made no change to the local status of this block.
[[Special:GlobalBlockList|Return to the global block list]].',
	'globalblocking-whitelist-errors' => 'Your change to the local status of a global block was unsuccessful, for the following {{PLURAL:$1|reason|reasons}}:',
	'globalblocking-whitelist-intro' => "You can use this form to edit the local status of a global block.
If a global block is disabled on this wiki, users on the affected IP address will be able to edit normally.
[[Special:GlobalBlockList|Return to the global block list]].",

	'globalblocking-blocked' => "Your IP address has been blocked on all wikis by '''$1''' (''$2'').
The reason given was ''\"$3\"''.
The block ''$4''.",

	'globalblocking-logpage' => 'Global block log',
	'globalblocking-logpagetext' => 'This is a log of global blocks which have been made and removed on this wiki.
It should be noted that global blocks can be made and removed on other wikis, and that these global blocks may affect this wiki.
To view all active global blocks, you may view the [[Special:GlobalBlockList|global block list]].',
	'globalblocking-block-logentry' => 'globally blocked [[$1]] with an expiry time of $2',
	'globalblocking-unblock-logentry' => 'removed global block on [[$1]]',
	'globalblocking-whitelist-logentry' => 'disabled the global block on [[$1]] locally',
	'globalblocking-dewhitelist-logentry' => 're-enabled the global block on [[$1]] locally',

	'globalblocklist' => 'List of globally blocked IP addresses',
	'globalblock' => 'Globally block an IP address',
	'globalblockstatus' => 'Local status of global blocks',
	'removeglobalblock' => 'Remove a global block',
	
	// User rights
	'right-globalblock' => 'Make global blocks',
	'right-globalunblock' => 'Remove global blocks',
	'right-globalblock-whitelist' => 'Disable global blocks locally',
);

/** Message documentation (Message documentation)
 * @author Darth Kule
 * @author Ficell
 * @author Jon Harald Søby
 * @author Meno25
 * @author Mormegil
 * @author Nike
 * @author Purodha
 * @author Raymond
 * @author Siebrand
 */
$messages['qqq'] = array(
	'globalblocking-desc' => 'Short description of this extension, shown on [[Special:Version]]. Do not translate or change links.',
	'globalblocking-block' => 'Same special page with this page:

* [[MediaWiki:Globalblock/{{SUBPAGENAME}}]]',
	'globalblocking-block-expiry-otherfield' => '{{Identical|Other time}}',
	'globalblocking-block-options' => '{{Identical|Options}}',
	'globalblocking-block-errors' => "The first line of the error message shown on [[Special:GlobalBlock]] (see [[mw:Extension:GlobalBlocking]]) if the block has been unsuccessful. After this message, a list of specific errors is shown (see [[Special:Prefixindex/MediaWiki:Globalblocking-block-bigrange|globalblocking-block-bigrange]], [[Special:Prefixindex/MediaWiki:Globalblocking-block-expiryinvalid|globalblocking-block-expiryinvalid]] etc.).

* $1 – the ''number'' of errors (not the errors themselves)",
	'globalblocking-block-ipinvalid' => '{{Identical|The IP address ($1) ...}}',
	'globalblocking-search-ip' => '{{Identical|IP Address}}',
	'globalblocking-list-blockitem' => '* $1 is a time stamp
* $2 is the blocking user
* $3 is the source wiki for the blocking user
* $4 is the blocked user
* $5 are the block options',
	'globalblocking-list-anononly' => '{{Identical|Anon only}}',
	'globalblocking-list-whitelist' => '{{Identical|Local status}}',
	'globalblocking-unblock-ipinvalid' => '{{Identical|The IP address ($1) ...}}',
	'globalblocking-unblock-reason' => '{{Identical|Reason}}',
	'globalblocking-whitelist-legend' => '{{Identical|Change local status}}',
	'globalblocking-whitelist-reason' => '{{Identical|Reason for change}}',
	'globalblocking-whitelist-status' => '{{Identical|Local status}}',
	'globalblocking-whitelist-submit' => '{{Identical|Change local status}}',
	'globalblocking-logpagetext' => 'Shown as header of [[meta:Special:Log/gblblock]] (example only, this extension is not installed on Betawiki)',
	'globalblocking-unblock-logentry' => "This message is a log entry. '''$1''' are contributions of an IP. For an example see http://meta.wikimedia.org/wiki/Special:Log/gblblock?uselang=en",
	'globalblock' => 'Same special page with this page:

* [[MediaWiki:Globalblocking-block/{{SUBPAGENAME}}]]',
	'right-globalblock' => '{{doc-right}}',
	'right-globalunblock' => '{{doc-right}}',
	'right-globalblock-whitelist' => '{{doc-right}}',
);

/** Afrikaans (Afrikaans)
 * @author Arnobarnard
 * @author Naudefj
 */
$messages['af'] = array(
	'globalblocking-desc' => "[[Special:GlobalBlock|Maak dit moontlik]] om IP-adresse [[Special:GlobalBlockList|oor veelvoudige wiki's]] te versper",
	'globalblocking-block' => "Versper 'n IP adres globaal",
	'globalblocking-block-intro' => "U kan hierdie bladsy gebruik om 'n IP adres op alle wikis te versper.",
	'globalblocking-block-reason' => 'Rede vir hierdie versperring:',
	'globalblocking-block-expiry' => 'Verstryk van versperring:',
	'globalblocking-block-expiry-other' => 'Ander verstryktyd',
	'globalblocking-block-expiry-otherfield' => 'Ander tyd:',
	'globalblocking-block-legend' => "Versper 'n gebruiker globaal",
	'globalblocking-block-options' => 'Opsies',
	'globalblocking-block-errors' => 'Die versperring was nie suksesvol nie, as gevolg van:
$1',
	'globalblocking-block-ipinvalid' => "Die IP adres ($1) wat U ingevoer het is ongeldig.
Let asseblief dat U nie 'n gebruikersnaam kan invoer nie!",
	'globalblocking-block-expiryinvalid' => 'Die verstryking ($1) wat U ingevoer het is ongeldig.',
	'globalblocking-block-submit' => 'Versper hierdie IP adres globaal',
	'globalblocking-block-success' => 'Die IP adres $1 is suksesvol versper op alle Wikimedia projekte.
U mag dalk die [[Special:GlobalBlockList|lys van globale versperrings]] wil konsulteer.',
	'globalblocking-block-successsub' => 'Globale versperring suksesvol',
	'globalblocking-block-alreadyblocked' => 'Die IP adres $1 is alreeds globaal versper. U kan die bestaande versperring op die [[Special:GlobalBlockList|lys van globale versperrings]] bekyk.',
	'globalblocking-block-bigrange' => 'Die reeks wat u verskaf het ($1) is te groot om te versper. U mag op die meeste 65.536 adresse versper (/16-reekse)',
	'globalblocking-list' => 'Lys van globale versperde IP adresse',
	'globalblocking-search-legend' => "Soek vir 'n globale versperring",
	'globalblocking-search-ip' => 'IP adres:',
	'globalblocking-search-submit' => 'Soek vir versperrings',
	'globalblocking-list-ipinvalid' => "Die IP adres wat U na gesoek het ($1) is ongeldig.
Voer asseblief 'n geldige IP adres in.",
	'globalblocking-search-errors' => 'U soektog was nie suksesvol nie, as gevolg van:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') het '''[[Special:Contributions/$4|$4]]''' globaal versper, met ''($5)''",
	'globalblocking-list-expiry' => 'verstryking $1',
	'globalblocking-list-anononly' => 'anoniem-alleen',
	'globalblocking-list-unblock' => 'deurlaat',
	'globalblocking-list-whitelisted' => 'lokaal afgeskakel deur $1: $2',
	'globalblocking-list-whitelist' => 'lokale status',
	'globalblocking-unblock-ipinvalid' => "Die IP adres ($1) wat U ingevoer het is ongeldig.
Let asseblief dat U nie 'n gebruikersnaam kan invoer nie!",
	'globalblocking-unblock-legend' => "Verwyder 'n globale versperring",
	'globalblocking-unblock-submit' => 'Verwyder globale versperring',
	'globalblocking-unblock-reason' => 'Rede:',
	'globalblocking-unblock-unblocked' => "U het suksesvol die globale versperring #$2 op die IP adres '''$1''' verwyder",
	'globalblocking-unblock-errors' => 'U kan nie die globale versperring vir daardie IP adres verwyder nie, as gevolg van:
$1',
	'globalblocking-unblock-successsub' => 'Globale versperring suksesvol verwyder',
	'globalblocking-unblock-subtitle' => 'Verwyder globale versperring',
	'globalblocking-whitelist-legend' => 'Wysig lokale status',
	'globalblocking-whitelist-reason' => 'Rede vir wysiging:',
	'globalblocking-whitelist-status' => 'Lokale status:',
	'globalblocking-whitelist-statuslabel' => 'Skakel hierdie globale versperring op {{SITENAME}} af',
	'globalblocking-whitelist-submit' => 'Wysig lokale status',
	'globalblocking-whitelist-whitelisted' => "U het suksesvol die globale versperring #$2 op die IP adres '''$1''' op {{SITENAME}} afgeskakel.",
	'globalblocking-whitelist-dewhitelisted' => "U het suksesvol die globale versperring #$2 op die IP adres '''$1''' op {{SITENAME}} heraangeskakel.",
	'globalblocking-whitelist-successsub' => 'Lokale status suksesvol gewysig',
	'globalblocking-blocked' => "U IP adres is versper op alle Wikimedia wikis deur '''\$1''' (''\$2'').
Die rede gegee is ''\"\$3\"''. Die versperring verstryk is ''\$4''.",
	'globalblocking-logpage' => 'Globale versperring boekstaaf',
	'globalblocking-block-logentry' => "[[$1]] is globaal versper met 'n verstryktyd van $2",
	'globalblocking-unblock-logentry' => 'verwyder globale versperring op [[$1]]',
	'globalblocking-whitelist-logentry' => 'die globale versperring op [[$1]] is lokaal afgeskakel',
	'globalblocking-dewhitelist-logentry' => 'die globale versperring op [[$1]] is heraangeskakel',
	'globalblocklist' => 'Lys van globaal versperde IP adresse',
	'globalblock' => "Versper 'n IP adres globaal",
	'right-globalblock' => 'Rig globale versperrings op',
	'right-globalunblock' => 'Verwyder globale versperrings',
	'right-globalblock-whitelist' => 'Skakel globale versperrings lokaal af',
);

/** Aragonese (Aragonés)
 * @author Juanpabl
 */
$messages['an'] = array(
	'globalblocking-unblock-reason' => 'Razón:',
);

/** Arabic (العربية)
 * @author Alnokta
 * @author Meno25
 * @author OsamaK
 */
$messages['ar'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|يسمح]] بمنع عناوين الأيبي [[Special:GlobalBlockList|عبر ويكيات متعددة]]',
	'globalblocking-block' => 'منع عام لعنوان أيبي',
	'globalblocking-block-intro' => 'أنت يمكنك استخدام هذه الصفحة لمنع عنوان أيبي في كل الويكيات.',
	'globalblocking-block-reason' => 'السبب لهذا المنع:',
	'globalblocking-block-expiry' => 'انتهاء المنع:',
	'globalblocking-block-expiry-other' => 'وقت انتهاء آخر',
	'globalblocking-block-expiry-otherfield' => 'وقت آخر:',
	'globalblocking-block-legend' => 'امنع مستخدم منعا عاما',
	'globalblocking-block-options' => 'خيارات:',
	'globalblocking-block-errors' => 'منعك كان غير ناجح، {{PLURAL:$1|للسبب التالي|للأسباب التالية}}:',
	'globalblocking-block-ipinvalid' => 'عنوان الأيبي ($1) الذي أدخلته غير صحيح.
من فضلك لاحظ أنه لا يمكنك إدخال اسم مستخدم!',
	'globalblocking-block-expiryinvalid' => 'تاريخ الانتهاء الذي أدخلته ($1) غير صحيح.',
	'globalblocking-block-submit' => 'منع عنوان الأيبي هذا منعا عاما',
	'globalblocking-block-success' => 'عنوان الأيبي $1 تم منعه بنجاح في كل المشاريع.',
	'globalblocking-block-successsub' => 'نجح المنع العام',
	'globalblocking-block-alreadyblocked' => 'عنوان الأيبي $1 ممنوع منعا عاما بالفعل. يمكنك رؤية المنع الموجود في [[Special:GlobalBlockList|قائمة عمليات المنع العامة]].',
	'globalblocking-block-bigrange' => 'النطاق الذي حددته ($1) كبير جدا للمنع. يمكنك منع، كحد أقصى، 65,536 عنوان (نطاقات /16)',
	'globalblocking-list-intro' => 'هذه قائمة بكل عمليات المنع العامة الحالية. بعض عمليات المنع معلمة كمعطلة محليا: هذا يعني أنها تنطبق على المواقع الأخرى، لكن إداريا محليا قرر تعطيلها في هذا الويكي.',
	'globalblocking-list' => 'قائمة عناوين الأيبي الممنوعة منعا عاما',
	'globalblocking-search-legend' => 'بحث عن منع عام',
	'globalblocking-search-ip' => 'عنوان الأيبي:',
	'globalblocking-search-submit' => 'بحث عن عمليات المنع',
	'globalblocking-list-ipinvalid' => 'عنوان الأيبي الذي بحثت عنه ($1) غير صحيح.
من فضلك أدخل عنوان أيبي صحيح.',
	'globalblocking-search-errors' => 'بحثك لم يكن ناجحا، {{PLURAL:$1|للسبب التالي|للأسباب التالية}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') منع بشكل عام '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'الانتهاء $1',
	'globalblocking-list-anononly' => 'المجهولون فقط',
	'globalblocking-list-unblock' => 'إزالة',
	'globalblocking-list-whitelisted' => 'تم تعطيله محليا بواسطة $1: $2',
	'globalblocking-list-whitelist' => 'الحالة المحلية',
	'globalblocking-goto-block' => 'منع عام لعنوان أيبي',
	'globalblocking-goto-unblock' => 'إزالة منع عام',
	'globalblocking-goto-status' => 'تغيير الحالة المحلية لمنع عام',
	'globalblocking-return' => 'رجوع إلى قائمة عمليات المنع العامة',
	'globalblocking-notblocked' => 'عنوان الأيبي ($1) الذي أدخلته ليس ممنوعا منعا عاما.',
	'globalblocking-unblock' => 'إزالة منع عام',
	'globalblocking-unblock-ipinvalid' => 'عنوان الأيبي ($1) الذي أدخلته غير صحيح.
من فضلك لاحظ أنه لا يمكنك إدخال اسم مستخدم!',
	'globalblocking-unblock-legend' => 'إزالة منع عام',
	'globalblocking-unblock-submit' => 'إزالة المنع العام',
	'globalblocking-unblock-reason' => 'السبب:',
	'globalblocking-unblock-unblocked' => "أنت أزلت بنجاح المنع العام #$2 على عنوان الأيبي '''$1'''",
	'globalblocking-unblock-errors' => 'إزالتك للمنع العام لم تكن ناجحة، {{PLURAL:$1|للسبب التالي|لأسباب التالية}}:',
	'globalblocking-unblock-successsub' => 'المنع العام تمت إزالته بنجاح',
	'globalblocking-unblock-subtitle' => 'إزالة المنع العام',
	'globalblocking-unblock-intro' => 'يمكنك استخدام هذه الاستمارة لإزالة منع عام.
[[Special:GlobalBlockList|اضغط هنا]] للرجوع إلى قائمة المنع العامة.',
	'globalblocking-whitelist' => 'الحالة المحلية لعمليات المنع العامة',
	'globalblocking-whitelist-legend' => 'تغيير الحالة المحلية',
	'globalblocking-whitelist-reason' => 'السبب للتغيير:',
	'globalblocking-whitelist-status' => 'الحالة المحلية:',
	'globalblocking-whitelist-statuslabel' => 'تعطيل هذا المنع العام في {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'تغيير الحالة المحلية',
	'globalblocking-whitelist-whitelisted' => "أنت عطلت بنجاح المنع العام #$2 على عنوان الأيبي '''$1''' في {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "أنت أعدت تفعيل بنجاح المنع العام #$2 على عنوان الأيبي '''$1''' في {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'الحالة المحلية تم تغييرها بنجاح',
	'globalblocking-whitelist-nochange' => 'أنت لم تقم بأي تغيير للحالة المحلية لهذا المنع.
[[Special:GlobalBlockList|رجوع إلى قائمة المنع العامة]].',
	'globalblocking-whitelist-errors' => 'تغييرك للحالة المحلية للمنع العام لم يكن ناجحا، {{PLURAL:$1|للسبب التالي|للأسباب التالية}}:',
	'globalblocking-whitelist-intro' => 'يمكنك استخدام هذه الاستمارة لتعديل الحالة المحلية لمنع عام. لو أن منعا عاما تم تعطيله في هذا الويكي، المستخدمون على عنوان الأيبي المتأثر سيمكنهم التعديل بشكل طبيعي.
[[Special:GlobalBlockList|اضغط هنا]] للرجوع إلى قائمة المنع العامة.',
	'globalblocking-blocked' => "عنوان الأيبي الخاص بك تم منعه على كل الويكيات بواسطة '''\$1''' (''\$2'').
السبب المعطى كان ''\"\$3\"''. المنع ''\$4''.",
	'globalblocking-logpage' => 'سجل المنع العام',
	'globalblocking-logpagetext' => 'هذا سجل بعمليات المنع العامة التي تم عملها وإزالتها على هذا الويكي.
ينبغي ملاحظة أن عمليات المنع العامة يمكن عملها وإزالتها على الويكيات الأخرى، وأن عمليات المنع العامة هذه ربما تؤثر على هذا الويكي.
لرؤية كل عمليات المنع العامة النشطة، يمكنك رؤية [[Special:GlobalBlockList|قائمة المنع العامة]].',
	'globalblocking-block-logentry' => 'منع بشكل عام [[$1]] لمدة $2',
	'globalblocking-unblock-logentry' => 'أزال المنع العام على [[$1]]',
	'globalblocking-whitelist-logentry' => 'عطل المنع العام على [[$1]] محليا',
	'globalblocking-dewhitelist-logentry' => 'أعاد تفعيل المنع العام على [[$1]] محليا',
	'globalblocklist' => 'قائمة عناوين الأيبي الممنوعة منعا عاما',
	'globalblock' => 'منع عام لعنوان أيبي',
	'globalblockstatus' => 'الحالة المحلية للمنع العام',
	'removeglobalblock' => 'إزالة منع عام',
	'right-globalblock' => 'عمل عمليات منع عامة',
	'right-globalunblock' => 'إزالة عمليات المنع العامة',
	'right-globalblock-whitelist' => 'تعطيل عمليات المنع العامة محليا',
);

/** Egyptian Spoken Arabic (مصرى)
 * @author Meno25
 * @author Ramsis II
 */
$messages['arz'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock| بيسمح]] بمنع عناوين الاى بى [[Special:GlobalBlockList|على اكتر من ويكي]]',
	'globalblocking-block' => 'اعمل منع عام لعنوان اى بي',
	'globalblocking-block-intro' => 'ممكن تستعمل الصفحة دى هلشان تمنع عنوان اى بى من على كل الويكيهات.',
	'globalblocking-block-reason' => 'المنع دا علشان:',
	'globalblocking-block-expiry' => 'انتهاء المنع:',
	'globalblocking-block-expiry-other' => 'وقت انتها تاني',
	'globalblocking-block-expiry-otherfield' => 'وقت تاني:',
	'globalblocking-block-legend' => 'اعمل منع عام ليوزر',
	'globalblocking-block-options' => 'اختيارات:',
	'globalblocking-block-errors' => 'المنع اللى عملته مانفعش، علشان {{PLURAL:$1|السبب دا|الاسباب دي}}:',
	'globalblocking-block-ipinvalid' => 'عنوان الأيبى ($1) اللى دخلته مش صحيح.
لو سمحت تاخد بالك انه ماينفعش تدخل  اسم يوزر!',
	'globalblocking-block-expiryinvalid' => 'تاريخ الانتهاء ($1) اللى دخلته مش صحيح.',
	'globalblocking-block-submit' => 'امنع عنوان الاى بى دا منع عام',
	'globalblocking-block-success' => 'عنوان الاى بى $1 اتمنع بنجاح فى كل المشاريع',
	'globalblocking-block-successsub' => 'المنع العام ناجح',
	'globalblocking-block-alreadyblocked' => 'عنوان الايبى $1 ممنوع منع عام من قبل كدا.
ممكن تشوف المنع الموجود هنا [[Special:GlobalBlockList|لستة المنع العام]].',
	'globalblocking-block-bigrange' => 'النطاق اللى حددته ($1) كبير قوى على المنع. انت ممكن تمنع، كحد أقصى، 65,536 عنوان (نطاقات /16)',
	'globalblocking-list-intro' => 'دى لستة بكل عمليات المنع العام اللى شغالة دلوقتي.
فى شوية منهم متعلم على انهم متعطلين ع المستوى المحلي، دا معناه انهم بينطبقو على المواقع التانية
بس فى ادارى محلى قرر يعطلها فى الويكى دا.',
	'globalblocking-list' => 'لستة عناوين الأيبى الممنوعة منع عام',
	'globalblocking-search-legend' => 'تدوير على منع عام',
	'globalblocking-search-ip' => 'عنوان الأيبي:',
	'globalblocking-search-submit' => 'تدوير على عمليات المنع',
	'globalblocking-list-ipinvalid' => 'عنوان الأيبى اللى دورت عليه($1) مش صحيح.
لو سمحت تدخل عنوان أيبى صحيح.',
	'globalblocking-search-errors' => 'تدويرك مانفعش ،{{PLURAL:$1|علشان|علشان}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') ممنوعين منع عام '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => '$1 بينتهي',
	'globalblocking-list-anononly' => 'المجهولين بس',
	'globalblocking-list-unblock' => 'شيل',
	'globalblocking-list-whitelisted' => 'اتعطل  محلى بواسطة $1: $2',
	'globalblocking-list-whitelist' => 'الحالة المحلية',
	'globalblocking-goto-block' => 'منع عام لعنوان أيبي',
	'globalblocking-goto-unblock' => 'شيل منع عام',
	'globalblocking-goto-status' => 'تغيير الحالة المحلية لمنع عام',
	'globalblocking-return' => 'ارجع للستة المنع  العام',
	'globalblocking-notblocked' => 'عنوان الاى بى ($1) اللى دخلته مش ممنوع منع عام',
	'globalblocking-unblock' => 'شيل منع عام',
	'globalblocking-unblock-ipinvalid' => 'عنوان الأيبى ($1) اللى دخلته مش صحيح.
لو سمحت تاخد بالك  انه ماينفعش تدخل اسم يوزر!',
	'globalblocking-unblock-legend' => 'شيل منع العام',
	'globalblocking-unblock-submit' => 'شيل المنع العام',
	'globalblocking-unblock-reason' => 'السبب:',
	'globalblocking-unblock-unblocked' => "إنتا شيلت بنجاح المنع العام #$2 على عنوان الأيبى '''$1'''",
	'globalblocking-unblock-errors' => 'شيلانك للمنع العام كان مش ناجح، علشان {{PLURAL:$1|السبب دا|الاسباب دي}}:',
	'globalblocking-unblock-successsub' => 'المنع العام اتشال بنجاح.',
	'globalblocking-unblock-subtitle' => 'شيل المنع العام',
	'globalblocking-unblock-intro' => 'ممكن تستعمل الاستمارة دى علشان تشيل منع عام.
[[Special:GlobalBlockList|دوس هنا]] علشان ترجع للستة المنع العام.',
	'globalblocking-whitelist' => 'الحالة المحلية لعمليات المنع العامة',
	'globalblocking-whitelist-legend' => 'غير الحالة المحلية',
	'globalblocking-whitelist-reason' => 'سبب التغيير:',
	'globalblocking-whitelist-status' => 'الحالة المحلية:',
	'globalblocking-whitelist-statuslabel' => '{{SITENAME}} عطل المنع العام دا على',
	'globalblocking-whitelist-submit' => 'غير الحالة المحلية.',
	'globalblocking-whitelist-whitelisted' => "إنتا عطلت بنجاح المنع العام #$2 على عنوان الأيبى '''$1''' فى {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "انت فعلت تانى بنجاح المنع العام #$2 على عنوان الاى بى  '''$1''' فى {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'الحالة المحلية اتغيرت ببنجاح',
	'globalblocking-whitelist-nochange' => 'انت ما عملتش اى تغيير فى للحالة المحلية للمنع دا.
[[Special:GlobalBlockList|ارجع للستة المنع العام]].',
	'globalblocking-whitelist-errors' => 'التغيير اللى عملته للحالة المحلية للمنع العام ما نجحش،علشان{{PLURAL:$1|السبب دا|الاسباب دي}}:',
	'globalblocking-whitelist-intro' => 'ممكن تستعمل الاستمارة دى علشان تعدل الحالة المحلية للمنع العام.لو  فى منع عام متعطل على الويكى دا ،اليوزرز على عنوان الاى بى المتاثر ح يقدرو يعملو تعديل بشكل طبيعي.
[[Special:GlobalBlockList|الرجوع للستة المنع العامة]].',
	'globalblocking-blocked' => "'''\$1''' (''\$2'') عمل منع لعنوان الاى بى بتاعك  على كل الويكيهات.
السبب هو ''\"\$3\"''.
المنع ''\"\$4\"''.",
	'globalblocking-logpage' => 'سجل المنع العام',
	'globalblocking-logpagetext' => 'دا سجل بعمليات المنع العام اللى اتعملت و اتشالت فى الويكى دا.
لازم تاخد بالك ان عمليات المنع العام ممكن تتعمل و تتشال على الويكيهات التانية، و ان عمليات المنع العام دى ممكن تاثر على الويكى دا.
علشان تشوف  كل عمليات المنع العام النشيطة، بص على [[Special:GlobalBlockList|لستة المنع العام]].',
	'globalblocking-block-logentry' => '$2 امنع [[$1]] على المستوى العام وينتهى بتاريخ',
	'globalblocking-unblock-logentry' => 'شيل المنع العام من على [[$1]]',
	'globalblocking-whitelist-logentry' => 'عطل المنع العام على [[$1]] على المستوى المحلى',
	'globalblocking-dewhitelist-logentry' => 'شغل من تانى المنع العام على [[$1]] على المستوى المحلى',
	'globalblocklist' => 'لستة عناوين الاى بى الممنوعة منع عام',
	'globalblock' => 'منع عام لعنوان أى بي',
	'globalblockstatus' => 'الحالة المحلية للمنع العام',
	'removeglobalblock' => 'شيل منع عام',
	'right-globalblock' => 'اعمل منع عام',
	'right-globalunblock' => 'شيل المنع العام',
	'right-globalblock-whitelist' => 'عطل المنع العام على المستوى المحلي',
);

/** Asturian (Asturianu)
 * @author Esbardu
 */
$messages['ast'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permite]] [[Special:GlobalBlockList|bloquiar en múltiples wikis]] direicciones IP',
	'globalblocking-block' => 'Bloquiar globalmente una direición IP',
	'globalblocking-block-intro' => 'Pues usar esta páxina pa bloquiar una direición IP en toles wikis.',
	'globalblocking-block-reason' => "Motivu d'esti bloquéu:",
	'globalblocking-block-expiry' => 'Caducidá del bloquéu:',
	'globalblocking-block-expiry-other' => 'Otra caducidá',
	'globalblocking-block-legend' => 'Bloquiar globalmente a un usuariu',
	'globalblocking-block-options' => 'Opciones:',
	'globalblocking-block-ipinvalid' => "La direición IP ($1) qu'especificasti nun ye válida.
¡Por favor fíxate en que nun pues poner un nome d'usuariu!",
	'globalblocking-block-expiryinvalid' => "La caducidá qu'especificasti ($1) nun ye válida.",
	'globalblocking-block-submit' => 'Bloquiar globalmente esta direición IP',
	'globalblocking-block-success' => 'La direición IP $1 foi bloquiada en tolos proyeutos con ésitu.',
	'globalblocking-block-successsub' => 'Bloquéu global con ésitu',
	'globalblocking-list' => 'Llista de direiciones IP bloquiaes globalmente',
	'globalblocking-search-legend' => 'Buscar una cuenta global',
	'globalblocking-search-ip' => 'Direición IP:',
	'globalblocking-search-submit' => 'Buscar bloqueos',
	'globalblocking-list-anononly' => 'namái anónimos',
	'globalblocking-list-unblock' => 'eliminar',
	'globalblocking-list-whitelisted' => 'desactiváu llocalmente por $1: $2',
	'globalblocking-list-whitelist' => 'estatus llocal',
	'globalblocking-goto-block' => 'Bloquiar globalmente una direición IP',
	'globalblocking-goto-unblock' => 'Eliminar un bloquéu global',
	'globalblocking-goto-status' => "Camudar l'estatus llocal d'un bloquéu global",
	'globalblocking-return' => 'Tornar a la llista de bloqueos globales',
	'globalblocking-notblocked' => "La direición IP ($1) qu'escribisti nun ta bloquiada globalmente.",
	'globalblocking-unblock' => 'Eliminar un bloquéu global',
	'globalblocking-unblock-ipinvalid' => "La direición IP ($1) qu'especificasti nun ye válida.
¡Por favor fíxate en que nun pues poner un nome d'usuariu!",
	'globalblocking-unblock-legend' => 'Eleminar un bloquéu global',
	'globalblocking-unblock-submit' => 'Eliminar bloquéu global',
	'globalblocking-unblock-reason' => 'Motivu:',
	'globalblocking-unblock-successsub' => 'Bloquéu global elimináu con ésitu',
	'globalblocking-unblock-subtitle' => 'Eliminando bloquéu global',
	'globalblocking-unblock-intro' => 'Pues usar esti formulariu pa eleminar un bloquéu global.
[[Special:GlobalBlockList|Calca equí]] pa tornar a la llista de bloqueos globales.',
	'globalblocking-whitelist' => 'Estatus lloal de bloqueos globales',
	'globalblocking-whitelist-legend' => "Camudar l'estatus llocal",
	'globalblocking-whitelist-reason' => 'Motivu del cambéu:',
	'globalblocking-whitelist-status' => 'Estatus llocal:',
	'globalblocking-whitelist-statuslabel' => 'Desactivar esti bloquéu global en {{SITENAME}}',
	'globalblocking-whitelist-submit' => "Camudar l'estatus llocal",
	'globalblocking-whitelist-successsub' => 'Estatus llocal camudáu con ésitu',
	'globalblocking-whitelist-nochange' => "Nun se ficieron cambeos nel estatus llocal d'esti bloquéu.
[[Special:GlobalBlockList|Torna a la llista de bloqueos globlaes]].",
	'globalblocking-blocked' => "La to direición IP foi bloquiada en toles wikis por '''\$1''' ('''\$2''').
El motivu dau foi ''\"\$3\"''.
El bloquéu ''\$4''.",
	'globalblocking-logpage' => 'Rexistru de bloqueos globales',
	'globalblocking-block-logentry' => 'bloquió globalmente a [[$1]] con una caducidá de $2',
	'globalblocking-unblock-logentry' => "eliminó'l bloquéu global de [[$1]]",
	'globalblocking-whitelist-logentry' => "desactivó'l bloquéu global de [[$1]] llocalmente",
	'globalblocking-dewhitelist-logentry' => "reactivó'l bloquéu global de [[$1]] llocalmente",
	'globalblocklist' => 'Llista de direiciones IP bloquiaes globalmente',
	'globalblock' => 'Bloquiar globalmente una direición IP',
	'globalblockstatus' => 'Estatus llocal de bloqueos globales',
	'removeglobalblock' => 'Eliminar un bloquéu global',
	'right-globalblock' => 'Aplicar bloqueos globales',
	'right-globalunblock' => 'Eliminar bloqueos globales',
	'right-globalblock-whitelist' => 'Desactivar llocalmente bloqueos globales',
);

/** Belarusian (Taraškievica orthography) (Беларуская (тарашкевіца))
 * @author EugeneZelenko
 */
$messages['be-tarask'] = array(
	'globalblocking-block-reason' => 'Прычына блякаваньня:',
	'globalblocking-block-expiry-otherfield' => 'Іншы тэрмін:',
	'globalblocking-block-successsub' => 'Глябальнае блякаваньне пасьпяховае',
	'globalblocking-search-ip' => 'IP-адрас:',
	'globalblocking-list-anononly' => 'толькі ананімаў',
	'globalblocking-list-unblock' => 'разблякаваць',
	'globalblocking-unblock-reason' => 'Прычына:',
	'globalblocking-whitelist-reason' => 'Прычына зьмены:',
	'globalblocking-logpage' => 'Журнал глябальных блякаваньняў',
	'globalblocklist' => 'Сьпіс глябальна заблякаваных IP-адрасоў',
);

/** Bulgarian (Български)
 * @author DCLXVI
 * @author Spiritia
 */
$messages['bg'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Позволява]] IP-адреси да се [[Special:GlobalBlockList|блокират едновременно в множество уикита]]',
	'globalblocking-block' => 'Глобално блокиране на IP-адрес',
	'globalblocking-block-intro' => 'Чрез тази страница може да се блокира IP-адрес едновременно във всички уикита.',
	'globalblocking-block-reason' => 'Причина за блокирането:',
	'globalblocking-block-expiry' => 'Изтичане на блокирането:',
	'globalblocking-block-expiry-other' => 'Друг срок за изтичане',
	'globalblocking-block-expiry-otherfield' => 'Друг срок:',
	'globalblocking-block-legend' => 'Глобално блокиране на потребител',
	'globalblocking-block-options' => 'Настройки:',
	'globalblocking-block-errors' => 'Блокирането беше неуспешно поради {{PLURAL:$1|следната причина|следните причини}}:',
	'globalblocking-block-ipinvalid' => 'Въведеният IP-адрес ($1) е невалиден.
Имайте предвид, че не можете да въвеждате потребителско име!',
	'globalblocking-block-expiryinvalid' => 'Въведеният краен срок ($1) е невалиден.',
	'globalblocking-block-submit' => 'Блокиране на този IP адрес глобално',
	'globalblocking-block-success' => 'IP-адресът $1 беше успешно блокиран във всички проекти.',
	'globalblocking-block-successsub' => 'Глобалното блокиране беше успешно',
	'globalblocking-block-alreadyblocked' => 'IP адресът $1 е вече блокиран глобално. Можете да прегледате съществуващите блокирания в [[Special:GlobalBlockList|списъка с глобални блокирания]].',
	'globalblocking-block-bigrange' => 'Избраният регистър ($1) е твърде голям, за да бъде изцяло блокиран.
Наведнъж е възможно да се блокират най-много 65,536 адреса (/16 регистри)',
	'globalblocking-list' => 'Списък на глобално блокирани IP адреси',
	'globalblocking-search-legend' => 'Търсене на глобално блокиране',
	'globalblocking-search-ip' => 'IP адрес:',
	'globalblocking-search-submit' => 'Търсене на блокирания',
	'globalblocking-list-ipinvalid' => 'Потърсеният от нас IP-адрес ($1) е невалиден.
Въведете валиден IP-адрес.',
	'globalblocking-search-errors' => 'Търсенето беше неуспешно по {{PLURAL:$1|следната причина|следните причини}}: 
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') блокира глобално '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'срок на изтичане $1',
	'globalblocking-list-anononly' => 'само анонимни',
	'globalblocking-list-unblock' => 'отблокиране',
	'globalblocking-list-whitelisted' => 'локално изключен от $1: $2',
	'globalblocking-list-whitelist' => 'локален статут',
	'globalblocking-goto-block' => 'Глобално блокиране на IP-адрес',
	'globalblocking-goto-unblock' => 'Премахване на глобално блокиране',
	'globalblocking-goto-status' => 'Промяна на локалния статут на глобално блокиране',
	'globalblocking-return' => 'Връщане към списъка с глобалните блокирания',
	'globalblocking-notblocked' => 'Въведеният IP адрес ($1) не е блокиран глобално.',
	'globalblocking-unblock' => 'Премахване на глобално блокиране',
	'globalblocking-unblock-ipinvalid' => 'Въведеният IP адрес ($1) е невалиден.
Имайте предвид, че не можете да въвеждате потребителско име!',
	'globalblocking-unblock-legend' => 'Премахване на глобално блокиране',
	'globalblocking-unblock-submit' => 'Премахване на глобално блокиране',
	'globalblocking-unblock-reason' => 'Причина:',
	'globalblocking-unblock-unblocked' => "Успешно премахнахте глобалното блокиране #$2 на IP адрес '''$1'''",
	'globalblocking-unblock-errors' => 'Не можете да премахнете глобалното блокиране на този IP адрес поради {{PLURAL:$1|следната причина|следните причини}}:',
	'globalblocking-unblock-successsub' => 'Глобалното блокиране беше премахнато успешно',
	'globalblocking-unblock-subtitle' => 'Премахване на глобално блокиране',
	'globalblocking-unblock-intro' => 'Можете да използвате този формуляр, за да премахнете глобално блокиране.
[[Special:GlobalBlockList|Върнете се към списъка с глобални блокирания]].',
	'globalblocking-whitelist' => 'Локално състояние на глобалните блокирания',
	'globalblocking-whitelist-legend' => 'Промяна на локалния статут',
	'globalblocking-whitelist-reason' => 'Причина за промяната:',
	'globalblocking-whitelist-status' => 'Локален статут:',
	'globalblocking-whitelist-statuslabel' => 'Изключване на това глобално блокиране за {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Промяна на локалния статут',
	'globalblocking-whitelist-whitelisted' => "Успешно изключихте глобално блокиране #$2 на IP адрес '''$1''' в {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Успешно активирахте глобално блокиране #$2 на IP адрес '''$1''' в {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Локалният статут беше променен успешно',
	'globalblocking-whitelist-nochange' => 'Не сте внесли промени в локалното състояние на това блокиране.
[[Special:GlobalBlockList|Върнете се към списъка с глобални блокирания]].',
	'globalblocking-whitelist-errors' => 'Вашият опит за промяна на локалното състояние на глобалното блокиране беше неуспешен по  {{PLURAL:$1|следната причина|следните причини}}:',
	'globalblocking-whitelist-intro' => 'Можете да използвате този формуляр, за да промените локалното състояние на дадено глобално блокиране.
Ако глобалното блокиране бъде свалено за това уики, потребителите с достъп от съответния IP-адрес ще могат да редактират нормално.
[[Special:GlobalBlockList|Върнете се към списъка с глобални блокирания]].',
	'globalblocking-blocked' => "Вашият IP адрес беше блокиран във всички уикита от '''$1''' (''$2'').
Посочената причина е ''„$3“''. Блокирането ''$4''.",
	'globalblocking-logpage' => 'Дневник на глобалните блокирания',
	'globalblocking-logpagetext' => 'Това е дневник на глобалните блокирания, които са били наложени или премахнати в това уики.
Глобални блокирания могат да се налагат и премахват и в други уикита, и те могат да се отразят локално и тук.
[[Special:GlobalBlockList|Вижте списъка на всички текущи глобални блокирания.]]',
	'globalblocking-block-logentry' => 'глобално блокиране на [[$1]] със срок на изтичане $2',
	'globalblocking-unblock-logentry' => 'премахна глобалното блокиране на [[$1]]',
	'globalblocking-whitelist-logentry' => 'премахна на локално ниво глобалното блокиране на [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'възвърна на локално ниво глобалното блокиране на [[$1]]',
	'globalblocklist' => 'Списък на глобално блокираните IP адреси',
	'globalblock' => 'Глобално блокиране на IP адрес',
	'globalblockstatus' => 'Локално състояние на глобалните блокирания',
	'removeglobalblock' => 'Премахване на глобално блокиране',
	'right-globalblock' => 'Създаване на глобални блокирания',
	'right-globalunblock' => 'Премахване на глобални блокирания',
	'right-globalblock-whitelist' => 'Локално спиране на глобалните блокирания',
);

/** Catalan (Català)
 * @author Paucabot
 */
$messages['ca'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permet]] [[Special:GlobalBlockList|bloquejar]] les adreces IP de diversos wikis',
	'globalblocking-block' => 'Bloqueja una adreça IP globalment',
	'globalblocking-block-intro' => 'Podeu usar aquesta pàgina per bloquejar una adreça IP a tots els wikis.',
	'globalblocking-block-reason' => 'Raó per al bloqueig:',
	'globalblocking-block-expiry' => 'Expiració del bloqueig:',
	'globalblocking-block-expiry-other' => "Una altra data d'expiració",
	'globalblocking-block-expiry-otherfield' => 'Una altra durada:',
	'globalblocking-block-legend' => 'Bloqueja un usuari globalment',
	'globalblocking-block-options' => 'Opcions:',
	'globalblocking-block-ipinvalid' => "L'adreça IP ($1) introduïda no és vàlida.
Recordau que no podeu introduir un nom d'usuari!",
	'globalblocking-block-submit' => 'Bloqueja aquesta adreça IP globalment',
	'globalblocking-list' => 'Llista de les adreces IP bloquejades globalment',
	'globalblocking-search-legend' => 'Cerca bloquejos globals',
	'globalblocking-search-ip' => 'Adreça IP:',
	'globalblocking-search-submit' => 'Cerca bloquejos',
	'globalblocking-goto-block' => 'Bloqueja globalment una adreça IP',
	'globalblocking-goto-unblock' => 'Cancel·la un bloqueig global',
	'globalblocking-return' => 'Torna a la llista de bloquejos globals',
	'globalblocking-notblocked' => "L'adreça IP que heu introduït ($1) no està bloquejada globalment.",
	'globalblocking-unblock' => 'Cancel·la un bloqueig global',
	'globalblocking-unblock-ipinvalid' => "L'adreça IP ($1) introduïda no és vàlida.
Recordau que no podeu introduir un nom d'usuari!",
	'globalblocking-unblock-legend' => 'Cancel·la un bloqueig global',
	'globalblocking-unblock-submit' => 'Cancel·la un bloqueig global',
	'globalblocking-unblock-reason' => 'Raó:',
	'globalblocking-unblock-successsub' => "S'ha cancel·lat correctament el bloqueig global",
	'globalblocking-whitelist-reason' => 'Raó pel canvi:',
	'globalblocking-whitelist-statuslabel' => 'Inhabilita aquest bloqueig global a {{SITENAME}}',
	'globalblocking-logpage' => 'Registre de bloquejos globals',
	'globalblocking-unblock-logentry' => "S'ha cancel·lat el bloqueig global de [[$1]]",
	'globalblocking-whitelist-logentry' => "S'ha inhabilitat localment el bloqueig global de [[$1]]",
	'globalblocking-dewhitelist-logentry' => "S'ha rehabilitat localment el bloqueig global de [[$1]]",
	'globalblocklist' => 'Llista de les adreces IP bloquejades globalment',
	'globalblock' => 'Bloqueja una adreça IP globalment',
	'removeglobalblock' => 'Cancel·la el bloqueig global',
	'right-globalunblock' => 'Cancel·la bloquejos globals',
	'right-globalblock-whitelist' => 'Inhabilita els bloquejos globals localment',
);

/** Czech (Česky)
 * @author Li-sung
 * @author Mormegil
 */
$messages['cs'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Umožňuje]] blokovat IP adresy [[Special:GlobalBlockList|na více wiki současně]]',
	'globalblocking-block' => 'Globálně zablokovat IP adresu',
	'globalblocking-block-intro' => 'Pomocí této stránky můžete některou IP adresu zablokovat na všech wiki.',
	'globalblocking-block-reason' => 'Důvod blokování:',
	'globalblocking-block-expiry' => 'Délka:',
	'globalblocking-block-expiry-other' => 'Jiná délka bloku',
	'globalblocking-block-expiry-otherfield' => 'Jiný čas vypršení:',
	'globalblocking-block-legend' => 'Globálně zablokovat uživatele',
	'globalblocking-block-options' => 'Možnosti:',
	'globalblocking-block-errors' => 'Blokování se {{PLURAL:$1|z následujícího důvodu|z následujících důvodů}} nezdařilo:',
	'globalblocking-block-ipinvalid' => 'Vámi zadaná IP adresa ($1) je neplatná.
Uvědomte si, že nemůžete zadat uživatelské jméno!',
	'globalblocking-block-expiryinvalid' => 'Vámi zadaný čas vypršení ($1) je neplatný.',
	'globalblocking-block-submit' => 'Globálně zablokovat tuto IP adresu',
	'globalblocking-block-success' => 'IP adresa $1 byla na všech projektech úspěšně zablokována.',
	'globalblocking-block-successsub' => 'Úspěšné globální zablokování',
	'globalblocking-block-alreadyblocked' => 'IP adresa $1 již je globálně zablokována. Existující zablokování si můžete prohlédnout na [[Special:GlobalBlockList|seznamu globálních bloků]]',
	'globalblocking-block-bigrange' => 'Nelze zablokovat vámi uvedený rozsah ($1), protože je příliš velký. Můžete zablokovat maximálně 65&nbsp;535 adres (rozsah /16).',
	'globalblocking-list-intro' => 'Toto je seznam všech platných globálních zablokování. Některá zablokování jsou označena jako lokálně zneplatněná: to znamená, že působí na ostatních wiki, ale místní správce se rozhodl je na této wiki vypnout.',
	'globalblocking-list' => 'Seznam globálně zablokovaných IP adres',
	'globalblocking-search-legend' => 'Hledání globálního bloku',
	'globalblocking-search-ip' => 'IP adresa:',
	'globalblocking-search-submit' => 'Hledat blok',
	'globalblocking-list-ipinvalid' => 'IP adresa ($1), kterou jste chtěli vyhledat, není platná.
Zadejte platnou IP adresu.',
	'globalblocking-search-errors' => 'Vaše hledání bylo z {{PLURAL:$1|následujícího důvodu|následujících důvodů}} neúspěšné:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globálně blokuje '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'vyprší $1',
	'globalblocking-list-anononly' => 'jen anonymové',
	'globalblocking-list-unblock' => 'uvolnit',
	'globalblocking-list-whitelisted' => 'lokálně zneplatněno uživatelem $1: $2',
	'globalblocking-list-whitelist' => 'lokální stav',
	'globalblocking-goto-block' => 'Globálně zablokovat IP adresu',
	'globalblocking-goto-unblock' => 'Globálně odblokovat',
	'globalblocking-goto-status' => 'Změnit místní stav globálního zablokování',
	'globalblocking-return' => 'Návrat na seznam globálních blokování',
	'globalblocking-notblocked' => 'Vámi zadaná IP adresa ($1) není globálně zablokovaná.',
	'globalblocking-unblock' => 'Globální odblokování',
	'globalblocking-unblock-ipinvalid' => 'Vámi zadaná IP adresa ($1) je neplatná.
Uvědomte si, že nemůžete zadat uživatelské jméno!',
	'globalblocking-unblock-legend' => 'Uvolnění globální blokování',
	'globalblocking-unblock-submit' => 'Globálně odblokovat',
	'globalblocking-unblock-reason' => 'Důvod:',
	'globalblocking-unblock-unblocked' => "Úspěšně jste uvolnili globální blokování ID #$2 na IP adresu '''$1'''",
	'globalblocking-unblock-errors' => 'Váš pokus o odblokování nebyl úspěšný z {{PLURAL:$1|následujícího důvodu|následujících důvodů|následujících důvodů}}:',
	'globalblocking-unblock-successsub' => 'Odblokování proběhlo úspěšně',
	'globalblocking-unblock-subtitle' => 'Uvolňuje se globální blokování',
	'globalblocking-unblock-intro' => 'Tímto formulářem je možno uvolnit globální blokování. 
Můžete se vrátit na [[Special:GlobalBlockList|seznam globálně zablokovaných]].',
	'globalblocking-whitelist' => 'Lokální nastavení globálního zablokování',
	'globalblocking-whitelist-legend' => 'Změnit lokální nastavení',
	'globalblocking-whitelist-reason' => 'Důvod změny:',
	'globalblocking-whitelist-status' => 'Lokální stav:',
	'globalblocking-whitelist-statuslabel' => 'Zneplatnit toto globální blokování na {{GRAMMAR:6sg|{{SITENAME}}}}',
	'globalblocking-whitelist-submit' => 'Změnit místní stav',
	'globalblocking-whitelist-whitelisted' => "Úspěšně jste na {{grammar:6sg|{{SITENAME}}}} zneplatnili globální zablokování #$2 IP adresy '''$1'''.",
	'globalblocking-whitelist-dewhitelisted' => "Úspěšně jste na {{grammar:6sg|{{SITENAME}}}} zrušili výjimku z globálního zablokování #$2 IP adresy '''$1'''.",
	'globalblocking-whitelist-successsub' => 'Lokální stav byl úspěšně upraven',
	'globalblocking-whitelist-nochange' => 'Na stavu tohoto zablokování jste nic nezměnili. [[Special:GlobalBlockList|Návrat na seznam globálních blokování.]]',
	'globalblocking-whitelist-errors' => 'Z {{PLURAL:$1|následujícího důvodu|následujících důvodů}} se nepodařilo změnit lokální stav globálního zablokování:',
	'globalblocking-whitelist-intro' => 'Tento formulář můžete použít na změnu místního stavu globálního zablokování. Pokud bude globální blok na této wiki zrušen, budou moci uživatelé na dotčené IP adrese normálně editovat. [[Special:GlobalBlockList|Návrat se seznam globální bloků]]',
	'globalblocking-blocked' => "Vaší IP adrese byla globálně na všech wiki zablokována možnost editace. Zablokoval vás uživatel '''$1''' (''$2'').
Udaným důvodem bylo ''„$3“''. Zablokování ''$4''.",
	'globalblocking-logpage' => 'Kniha globálních zablokování',
	'globalblocking-logpagetext' => 'Toto je kniha globální blokování a jejich uvolnění provedených na této wiki. 
Globální blokování lze provést i na jiných wiki a i ty ovlivňují blokování na této wiki. 
Všechny aktivní globální blokování naleznete na [[Special:GlobalBlockList|seznamu globálně blokovaných IP adres]].',
	'globalblocking-block-logentry' => 'globálně blokuje [[$1]] s časem vypršení $2',
	'globalblocking-unblock-logentry' => 'globálně odblokovává [[$1]]',
	'globalblocking-whitelist-logentry' => 'lokálně zneplatnil globální zablokování [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'zrušil lokální výjimku globálního zablokování [[$1]]',
	'globalblocklist' => 'Seznam globálně blokovaných IP adres',
	'globalblock' => 'Globálně zablokovat IP adresu',
	'globalblockstatus' => 'Místní stav globálního blokování',
	'removeglobalblock' => 'Odstranit globální zablokování',
	'right-globalblock' => 'Globální blokování',
	'right-globalunblock' => 'Rušení globálních blokování',
	'right-globalblock-whitelist' => 'Definování výjimek z globálního zablokování',
);

/** German (Deutsch)
 * @author MF-Warburg
 * @author Raimond Spekking
 */
$messages['de'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Sperrt]] IP-Adressen auf [[Special:GlobalBlockList|allen Wikis]]',
	'globalblocking-block' => 'Eine IP-Adresse global sperren',
	'globalblocking-block-intro' => 'Auf dieser Seite kannst du IP-Adressen für alle Wikis sperren.',
	'globalblocking-block-reason' => 'Grund für die Sperre:',
	'globalblocking-block-expiry' => 'Sperrdauer:',
	'globalblocking-block-expiry-other' => 'Andere Dauer',
	'globalblocking-block-expiry-otherfield' => 'Andere Dauer (englisch):',
	'globalblocking-block-legend' => 'Einen Benutzer global sperren',
	'globalblocking-block-options' => 'Optionen:',
	'globalblocking-block-errors' => 'Die Sperre war nicht erfolgreich. {{PLURAL:$1|Grund|Gründe}}:',
	'globalblocking-block-ipinvalid' => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Beachte, dass du keinen Benutzernamen eingeben darfst!',
	'globalblocking-block-expiryinvalid' => 'Die Sperrdauer ($1) ist ungültig.',
	'globalblocking-block-submit' => 'Diese IP-Adresse global sperren',
	'globalblocking-block-success' => 'Die IP-Adresse $1 wurde erfolgreich auf allen Projekten gesperrt.',
	'globalblocking-block-successsub' => 'Erfolgreich global gesperrt',
	'globalblocking-block-alreadyblocked' => 'Die IP-Adresse $1 wurde schon global gesperrt. Du kannst die bestehende Sperre in der [[Special:GlobalBlockList|globalen Sperrliste]] einsehen.',
	'globalblocking-block-bigrange' => 'Der Adressbereich, den du angegeben hast ($1) ist zu groß. Du kannst höchstens 65.536 IPs sperren (/16-Adressbereiche)',
	'globalblocking-list-intro' => 'Dies ist eine Liste aller gültigen globalen Sperren. Einige Sperren wurden lokal deaktiviert. Dies bedeutet, dass die Sperren auf anderen Projekten gültig sind, aber ein lokaler Administrator entschieden hat, sie für dieses Wiki zu deaktivieren.',
	'globalblocking-list' => 'Liste global gesperrter IP-Adressen',
	'globalblocking-search-legend' => 'Eine globale Sperre suchen',
	'globalblocking-search-ip' => 'IP-Adresse:',
	'globalblocking-search-submit' => 'Sperren suchen',
	'globalblocking-list-ipinvalid' => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Bitte gib eine gültige IP-Adresse ein.',
	'globalblocking-search-errors' => 'Die Suche war nicht erfolgreich. {{PLURAL:$1|Grund|Gründe}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (auf ''$3'') sperrte '''[[Special:Contributions/$4|$4]]''' global ''($5)''",
	'globalblocking-list-expiry' => 'Sperrdauer $1',
	'globalblocking-list-anononly' => 'nur Anonyme',
	'globalblocking-list-unblock' => 'entsperren',
	'globalblocking-list-whitelisted' => 'lokal abgeschaltet von $1: $2',
	'globalblocking-list-whitelist' => 'lokaler Status',
	'globalblocking-goto-block' => 'IP-Adresse global sperren',
	'globalblocking-goto-unblock' => 'Globale Sperre aufheben',
	'globalblocking-goto-status' => 'Lokalen Status für eine globale Sperre ändern',
	'globalblocking-return' => 'Zurück zur Liste der globalen Sperren',
	'globalblocking-notblocked' => 'Die eingegebene IP-Adresse ($1) ist nicht global gesperrt.',
	'globalblocking-unblock' => 'Globale Sperre aufheben',
	'globalblocking-unblock-ipinvalid' => 'Du hast eine ungültige IP-Adresse ($1) eingegeben.
Beachte, dass du keinen Benutzernamen eingeben darfst!',
	'globalblocking-unblock-legend' => 'Global entsperren',
	'globalblocking-unblock-submit' => 'Global entsperren',
	'globalblocking-unblock-reason' => 'Grund:',
	'globalblocking-unblock-unblocked' => "Die hast erfolgreich die IP-Adresse '''$1''' (Sperr-ID $2) entsperrt",
	'globalblocking-unblock-errors' => 'Die Aufhebung der globalen Sperre war nicht erfolgreich. {{PLURAL:$1|Grund|Gründe}}:',
	'globalblocking-unblock-successsub' => 'Erfolgreich global entsperrt',
	'globalblocking-unblock-subtitle' => 'Globale Sperre entfernen',
	'globalblocking-unblock-intro' => 'Mit diesem Formular kannst du eine globale Sperre aufheben. [[Special:GlobalBlockList|Klicke hier]], um zur Liste der globalen Sperren zurückzukehren.',
	'globalblocking-whitelist' => 'Lokaler Status einer globalen Sperre',
	'globalblocking-whitelist-legend' => 'Lokalen Status bearbeiten',
	'globalblocking-whitelist-reason' => 'Grund der Änderung:',
	'globalblocking-whitelist-status' => 'Lokaler Status:',
	'globalblocking-whitelist-statuslabel' => 'Diese globale Sperre auf {{SITENAME}} aufheben',
	'globalblocking-whitelist-submit' => 'Lokalen Status ändern',
	'globalblocking-whitelist-whitelisted' => "Du hast erfolgreich die globale Sperre #$2 der IP-Adresse '''$1''' auf {{SITENAME}} aufgehoben.",
	'globalblocking-whitelist-dewhitelisted' => "Du hast erfolgreich die globale Sperre #$2 der IP-Adresse '''$1''' auf {{SITENAME}} wieder eingeschaltet.",
	'globalblocking-whitelist-successsub' => 'Lokaler Status erfolgreich geändert',
	'globalblocking-whitelist-nochange' => 'Du hast den lokalen Status der Sperre nicht verändert.
[[Special:GlobalBlockList|Zurück zur Liste der globalen Sperre]]',
	'globalblocking-whitelist-errors' => 'Deine Änderung des lokalen Status einer globalen Sperre war nicht erfolgreich. {{PLURAL:$1|Grund|Gründe}}:',
	'globalblocking-whitelist-intro' => 'Du kannst mit diesem Formular den lokalen Status einer globalen Sperre ändern. Wenn eine globale Sperre in dem Wiki deaktiviert wurde, können Seiten über die entsprechende IP-Adresse normal bearbeitet werden. [[Special:GlobalBlockList|Klicke hier]], um zur Liste der globalen Sperren zurückzukehren.',
	'globalblocking-blocked' => "Deine IP-Adresse wurde von '''$1''' ''($2)'' für alle Wikis gesperrt.
Als Begründung wurde ''„$3“'' angegeben. Die Sperre ''$4''.",
	'globalblocking-logpage' => 'Globales Sperrlogbuch',
	'globalblocking-logpagetext' => 'Dies ist das Logbuch der globalen Sperren, die in diesem Wiki eingerichtet oder aufgehoben wurden.
Globale Sperren können in einem anderen Wiki eingerichtet und aufgehoben werden, so dass die dortigen Sperren auch dieses Wiki betreffen können.
Für eine Liste aller aktiven globalen Sperren siehe die [[Special:GlobalBlockList|globale Sperrliste]].',
	'globalblocking-block-logentry' => 'sperrte [[$1]] global für einen Zeitraum von $2',
	'globalblocking-unblock-logentry' => 'entsperrte [[$1]] global',
	'globalblocking-whitelist-logentry' => 'schaltete die globale Sperre von „[[$1]]“ lokal ab',
	'globalblocking-dewhitelist-logentry' => 'schaltete die globale Sperre von „[[$1]]“ lokal wieder ein',
	'globalblocklist' => 'Liste global gesperrter IP-Adressen',
	'globalblock' => 'Eine IP-Adresse global sperren',
	'globalblockstatus' => 'Lokaler Status der globalen Sperre',
	'removeglobalblock' => 'Globale Sperre aufheben',
	'right-globalblock' => 'Globale Sperren einrichten',
	'right-globalunblock' => 'Globale Sperren aufheben',
	'right-globalblock-whitelist' => 'Globale Sperren lokal abschalten',
);

/** Esperanto (Esperanto)
 * @author Yekrats
 */
$messages['eo'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permesas]] IP-adreso esti [[Special:GlobalBlockList|forbarita trans multaj vikioj]].',
	'globalblocking-block' => 'Ĝenerale forbaru IP-adreson',
	'globalblocking-block-intro' => 'Vi povas uzi ĉi tiun paĝon por forbari IP-adreson en ĉiuj vikioj.',
	'globalblocking-block-reason' => 'Kialo por ĉi tiu forbaro:',
	'globalblocking-block-expiry' => 'Findato de forbaro:',
	'globalblocking-block-expiry-other' => 'Alia findato',
	'globalblocking-block-expiry-otherfield' => 'Alia daŭro:',
	'globalblocking-block-legend' => 'Forbaru uzanto ĝenerale',
	'globalblocking-block-options' => 'Preferoj:',
	'globalblocking-block-errors' => 'La forbaro malsukcesis, pro la {{PLURAL:$1|jena kialo|jenaj kialoj}}:
$1',
	'globalblocking-block-ipinvalid' => 'La IP-adreso ($1) kiun vi enigis estas nevalida.
Bonvolu noti ke vi ne povas enigi salutnomo!',
	'globalblocking-block-expiryinvalid' => 'La findaton kiun vi enigis ($1) estas nevalida.',
	'globalblocking-block-submit' => 'Forbaru ĉi tiun IP-adreson ĝenerale',
	'globalblocking-block-success' => 'La IP-adreso $1 estis sukcese forbarita por ĉiuj projektoj.',
	'globalblocking-block-successsub' => 'Ĝenerala forbaro estis sukcesa',
	'globalblocking-block-alreadyblocked' => 'La IP-adreso $1 estas jam forbarita ĝenerale. Vi povas rigardi la ekzistanta forbaro en la [[Special:GlobalBlockList|Listo de ĝeneralaj forbaroj]].',
	'globalblocking-block-bigrange' => 'La intervalo kiun vi entajpis ($1) estas tro grando por forbari.
Vi povas forbari maksimume 65,536 adrresojn (/16 IP-intervalojn)',
	'globalblocking-list-intro' => 'Jen listo de ĉiuj transvikiaj forbaroj kiuj nune efikas.
Iuj forbaroj estas markitaj kiel loke permesitaj; ĉi tiu signifas ke la forbaro efikas en aliaj vikioj, sed loka administranto decidis permesi la konton en ĉi tiu vikio.',
	'globalblocking-list' => 'Listo de ĝenerale forbaritaj IP-adresoj',
	'globalblocking-search-legend' => 'Serĉu ĝeneralan forbaron',
	'globalblocking-search-ip' => 'IP-adreso:',
	'globalblocking-search-submit' => 'Serĉi forbarojn',
	'globalblocking-list-ipinvalid' => 'La serĉita IP-adreso ($1) estas nevalida.
Bonvolu enigi validan IP-adreson.',
	'globalblocking-search-errors' => 'Via serĉo estis malsukcesa, ĉar la {{PLURAL:$1|jena kialo|jenaj kialoj}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') ĝenerale forbaris uzanton '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'findato $1',
	'globalblocking-list-anononly' => 'nur anonimuloj',
	'globalblocking-list-unblock' => 'malforbari',
	'globalblocking-list-whitelisted' => 'loke malebligita de $1: $2',
	'globalblocking-list-whitelist' => 'loka statuso',
	'globalblocking-goto-block' => 'Ĝenerale forbari IP-adreson',
	'globalblocking-goto-unblock' => 'Forigi ĝeneralan blokon',
	'globalblocking-goto-status' => 'Ŝanĝigi lokan statuson por ĝenerala forbaro',
	'globalblocking-return' => 'Reiri al listo de ĝeneralaj forbaroj',
	'globalblocking-notblocked' => 'La IP-adreso ($1) kiun vi enigis ne estas ĝenerale forbarita.',
	'globalblocking-unblock' => 'Forigi ĝeneralan blokon',
	'globalblocking-unblock-ipinvalid' => 'La IP-adreso ($1) kiun vi enigis estas nevalida.
Bonvolu noti ke vi ne povas enigi salutnomo!',
	'globalblocking-unblock-legend' => 'Forigi ĝeneralan forbaron',
	'globalblocking-unblock-submit' => 'Forigi ĝeneralan forbaron',
	'globalblocking-unblock-reason' => 'Kialo:',
	'globalblocking-unblock-unblocked' => "Vi sukcese forigis la ĝeneralan forbaron #$2 por la IP-adreso '''$1'''",
	'globalblocking-unblock-errors' => 'Via restarigo de la ĝenerala forbaro estis nesukcesa, por la {{PLURAL:$1|jena kialo|jenaj kialoj}}:',
	'globalblocking-unblock-successsub' => 'Ĝenerala forbaro estis sukcese forigita',
	'globalblocking-unblock-subtitle' => 'Forigante ĝeneralan forbaron',
	'globalblocking-unblock-intro' => 'Vi povas uzi ĉi tiu paĝo por forviŝi ĝeneralan forbaron.
[[Special:GlobalBlockList|Klaku ĉi tie]] por reiri al la listo de ĝeneralaj forbaroj.',
	'globalblocking-whitelist' => 'Loka statuso de ĝeneralaj blokoj',
	'globalblocking-whitelist-legend' => 'Ŝanĝi lokan statuson',
	'globalblocking-whitelist-reason' => 'Kialo por ŝanĝo:',
	'globalblocking-whitelist-status' => 'Loka statuso:',
	'globalblocking-whitelist-statuslabel' => 'Malebligu ĉi tiun ĝeneralan forbaron por {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Ŝanĝi lokan statuson',
	'globalblocking-whitelist-whitelisted' => "Vi sukcese malebligis la ĝeneralan forbaron #$2 por la IP-adreso '''$1''' en {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Vi sukcese reebligis la ĝeneralan forbaron #$2 por la IP-adreso '''$1''' en {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Loka statuso sukcese ŝanĝiĝis.',
	'globalblocking-whitelist-nochange' => 'Vi faris neniun ŝanĝon al la loka statuso de ĉi tiu forbaro.
[[Special:GlobalBlockList|Reiri al la listo de ĝeneralaj forbaroj]].',
	'globalblocking-whitelist-errors' => 'Via ŝanĝo al la loka statuso de ĝenerala forbaro malsukcesis, pro la {{PLURAL:$1|jena kialo|jenaj kialoj}}:',
	'globalblocking-whitelist-intro' => 'Vi povas uzi ĉi tiun paĝon por redakti la lokan statuson de ĝenerala forbaro.
Se ĝenerala forbaro estas malŝaltita en ĉi tiu vikio, uzantoj de tiu IP-adreso eblos redakti norme.
[[Special:GlobalBlockList|Reiri al la listo de ĝeneralaj forbaroj]].',
	'globalblocking-blocked' => "Via IP-adreso estis forbarita en ĉiuj Wikimedia-retejoj de '''\$1''' (''\$2'').
La kialo donata estis ''\"\$3\"''. 
La forbaro estas ''\$4''.",
	'globalblocking-logpage' => 'Protokolo de ĝeneralaj forbaroj',
	'globalblocking-logpagetext' => 'Jen protokolo de ĝeneralaj forbaroj kiuj estis faritaj kaj forigitaj en ĉi tiu vikio.
Estas notinda ke ĝeneralaj forbaroj povas esti faritaj kaj forigitaj en aliaj vikioj, kaj ĉi tiuj forbaroj povas efiki ĉi tiun vikion.
Vidi ĉiujn aktivajn ĝeneralajn forbarojn, vi povas vidi la [[Special:GlobalBlockList|liston de ĝeneralaj forbaroj]].',
	'globalblocking-block-logentry' => 'ĝenerale forbaris [[$1]] kun findato de $2',
	'globalblocking-unblock-logentry' => 'forigis ĝeneralajn forbarojn por [[$1]]',
	'globalblocking-whitelist-logentry' => 'malebligis la ĝeneralan forbaron por [[$1]] loke',
	'globalblocking-dewhitelist-logentry' => 'reebligis la ĝeneralan forbaron por [[$1]] loke',
	'globalblocklist' => 'Listo de ĝenerale forbaritaj IP-adresoj',
	'globalblock' => 'Ĝenerale forbari IP-adreson',
	'globalblockstatus' => 'Loka statuso de ĝeneralaj forbaroj',
	'removeglobalblock' => 'Forigi ĝeneralan blokon',
	'right-globalblock' => 'Faru ĝeneralajn forbarojn',
	'right-globalunblock' => 'Forigu ĝeneralajn forbarojn',
	'right-globalblock-whitelist' => 'Malebligu ĝeneralajn forbarojn loke',
);

/** Persian (فارسی)
 * @author Huji
 * @author Mardetanha
 */
$messages['fa'] = array(
	'globalblocking-desc' => 'قطع دسترسی نشانی‌های اینترنتی [[Special:GlobalBlockList|در چندین ویکی]] را [[Special:GlobalBlock|ممکن می‌سازد]]',
	'globalblocking-block' => 'قطع دسترسی یک نشانی اینترنتی به صورت سراسری',
	'globalblocking-block-intro' => 'شما می‌توانید از این صفحه برای قطع دسترسی یک نشانی اینترنتی در تمام ویکی‌ها استفاده کنید.',
	'globalblocking-block-reason' => 'دلیل برای این قطع دسترسی:',
	'globalblocking-block-expiry' => 'خاتمه:',
	'globalblocking-block-expiry-other' => 'زمان‌ خاتمه دیگر',
	'globalblocking-block-expiry-otherfield' => 'زمانی دیگر:',
	'globalblocking-block-legend' => 'قطع دسترسی یک کاربر به صورت سراسری',
	'globalblocking-block-options' => 'گزینه‌ها:',
	'globalblocking-block-errors' => 'قطع دسترسی شما به این {{PLURAL:$1|دلیل|دلایل}} ناموفق بود:',
	'globalblocking-block-ipinvalid' => 'نشانی اینترنتی که شما وارد کردید ($1) غیر مجاز است.
توجه داشته باشید که شما نمی‌توانید یک نام کاربری را وارد کنید!',
	'globalblocking-block-expiryinvalid' => 'زمان خاتمه‌ای که وارد کردید ($1) غیر مجاز است.',
	'globalblocking-block-submit' => 'قطع دسترسی سراسری این نشانی اینترنتی',
	'globalblocking-block-success' => 'دسترسی نشانی اینترنتی $1 با موفقیت در تمام پروژه‌های قطع شد.',
	'globalblocking-block-successsub' => 'قطع دسترسی سراسری موفق بود',
	'globalblocking-block-alreadyblocked' => 'دسترسی نشانی اینتری $1 از قبل به طور سراسری بسته است.
شما می‌توانید قطع دسترسی موجود را در [[Special:GlobalBlockList|فهرست قطع دسترسی‌های سراسری]] ببینید.',
	'globalblocking-block-bigrange' => 'بازه‌ای که شما معین کردید ($1) بیش از اندازه بزرگ است.
شما حداکثر می‌توانید ۶۵۵۳۶ نشانی (یک بازه ‎/16) را غیر فعال کنید.',
	'globalblocking-list-intro' => 'این فهرستی از تمام قطع دسترسی‌های سراسری است که در حال حاضر فعال هستند.
برخی قطع دسترسی‌ها ممکن است به طور محلی غیر فعال شده باشند: این به آن معنی است که آن‌ها روی دیگر وبگاه‌ها اثر می‌گذارند، اما یک مدیر محلی تصمیم گرفته‌است که آن‌ها را روی این ویکی غیر فعال کند.',
	'globalblocking-list' => 'فهرست نشانی‌های اینترنتی که دسترسی‌شان به طور سراسری قطع شده‌است',
	'globalblocking-search-legend' => 'جستجو برای یک قطع دسترسی سراسری',
	'globalblocking-search-ip' => 'نشانی IP:',
	'globalblocking-search-submit' => 'جستجوی قطع دسترسی‌ها',
	'globalblocking-list-ipinvalid' => 'نشانی اینترنتی که شما جستجو کردید ($1) غیر مجاز است.
لطفاً یک نشانی اینترنتی مجاز وارد کنید.',
	'globalblocking-search-errors' => 'جستجوی شما به {{PLURAL:$1|دلیل|دلایل}} روبرو ناموفق بود:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') دسترسی '''[[Special:Contributions/$4|$4]]''' ''($5)'' را به طور سراسری بست",
	'globalblocking-list-expiry' => 'خاتمه $1',
	'globalblocking-list-anononly' => 'فقط کاربران گمنام',
	'globalblocking-list-unblock' => 'حذف',
	'globalblocking-list-whitelisted' => 'توسط $1: $2 به طور محلی غیر فعال شد',
	'globalblocking-list-whitelist' => 'وضعیت محلی',
	'globalblocking-goto-block' => 'قطع دسترسی سراسری یک نشانی اینترنتی',
	'globalblocking-goto-unblock' => 'حذف یک قطع دسترسی سراسری',
	'globalblocking-goto-status' => 'تغییر وضعیت محلی یک قطع دسترسی سراسری',
	'globalblocking-return' => 'بازگشت به فهرست قطع دسترسی‌های سراسری',
	'globalblocking-notblocked' => 'دسترسی نشانی اینترنتی که وارد کردید ($1) به طور سراسری بسته نیست.',
	'globalblocking-unblock' => 'حذف یک قطع دسترسی سراسری',
	'globalblocking-unblock-ipinvalid' => 'نشانی اینترنتی که وارد کردید ($1) غیر مجاز است.
لطفاً توجه داشته باشید که نمی‌تواند یک نام کاربری را وارد کنید.',
	'globalblocking-unblock-legend' => 'حذف یک قطع دسترسی سراسری',
	'globalblocking-unblock-submit' => 'حذف قطع دصترسی سراسری',
	'globalblocking-unblock-reason' => 'دلیل:',
	'globalblocking-unblock-unblocked' => "شما با موفقیت قطع دسترسی سراسری شماره $2 را از نشانی اینترنتی '''$1''' برداشتید",
	'globalblocking-unblock-errors' => 'حذف قطع دسترسی سراسری به {{PLURAL:$1|دلیل|دلایل}} روبرو ناموفق بود:',
	'globalblocking-unblock-successsub' => 'قطع دسترسی سراسری با موفقیت حذف شد',
	'globalblocking-unblock-subtitle' => 'حذف قطع دسترسی سراسری',
	'globalblocking-unblock-intro' => 'شما می‌توانید این فرم را برای حذف یک قطع دسترسی سراسری استفاده کنید.
برای بازگشت به فهرست قطع دسترسی‌های سراسری [[Special:GlobalBlockList|این‌جا کلیک کنید]].',
	'globalblocking-whitelist' => 'وضعیت محلی قطع دسترسی‌های سراسری',
	'globalblocking-whitelist-legend' => 'تغییر وضعیت محلی',
	'globalblocking-whitelist-reason' => 'دلیل تغییر:',
	'globalblocking-whitelist-status' => 'وضعیت محلی:',
	'globalblocking-whitelist-statuslabel' => 'غیر فعال کردن قطع دسترسی سراسری در {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'تغییر وضعیت محلی',
	'globalblocking-whitelist-whitelisted' => "شما با موفقیت قطع دسترسی شماره $2 را روی نشانی اینترنتی '''$1''' در {{SITENAME}} غیر فعال کردید.",
	'globalblocking-whitelist-dewhitelisted' => "شما با موفقیت قطع دسترسی شماره $2 را روی نشانی اینترنتی '''$1''' در {{SITENAME}} دوباره فعال کردید.",
	'globalblocking-whitelist-successsub' => 'وضعیت محلی به طور موفق تغییر یافت',
	'globalblocking-whitelist-nochange' => 'شما تغییری در وضعیت محلی این قطع دسترسی سراسری ایجاد نکردید
[[Special:GlobalBlockList|بازگشت به فهرست قطع دسترسی های سراسری]].',
	'globalblocking-whitelist-errors' => 'تغییری که شما در وضعیت محلی یک قطع دسترسی سراسری ایجاد کردید به {{PLURAL:$1|دلیل|دلایل}} روبرو موفق نبود:',
	'globalblocking-whitelist-intro' => 'شما می‌توانید از این فرم برای ویرایش وضعیت محلی یک قطع دسترسی سراسری استفاده کنید.
اگر یک قطع دسترسی سراسری در این ویکی غیر فعال شود، کاربرهایی که روی نشانی اینترنتی مربوط به آن قرار دارند قادر به ویرایش به صورت معمولی خواهند بود.
[[Special:GlobalBlockList|بازگشت به فهرست قطع دسترسی‌های سراسری]].',
	'globalblocking-blocked' => "دسترسی نشانی اینترنتی شما به تمام ویکی‌ها توسط '''$1''' (''$2'') قطع شده است.
دلیل ارائه شده چنین بوده است: ''«$3'»''.
این قطع دسترسی ''$4''.",
	'globalblocking-logpage' => 'سیاههٔ قطع دسترسی سراسری',
	'globalblocking-logpagetext' => 'این یک سیاهه از قطع دسترسی‌های سراسری است که در این ویکی ایجاد و حذف شده‌اند.
باید توجه داشت که قطع دسترسی‌های سراسری می‌تواند در ویکی‌های دیگر ایجاد یا حذف شود، و چنین قطع دسترسی‌هایی می‌تواند روی این ویکی تاثیر بگذارد.
برای مشاهدهٔ تمام قطع دسترسی‌های سراسری فعال، شما می‌توانید [[Special:GlobalBlockList|فهرست قطع دسترسی‌های سراسری]] را ببینید.',
	'globalblocking-block-logentry' => 'دسترسی [[$1]] را تا $2 به طور سراسری قطع کرد',
	'globalblocking-unblock-logentry' => 'حذف قطع دسترسی سراسری [[$1]]',
	'globalblocking-whitelist-logentry' => 'غیر فعال کردن قطع دسترسی سراسری [[$1]] به طور محلی',
	'globalblocking-dewhitelist-logentry' => 'دوباره فعال کردن قطع دسترسی سراسری [[$1]] به طور محلی',
	'globalblocklist' => 'فهرست نشانی‌های اینترنتی بسته شده به طور سراسری',
	'globalblock' => 'قطع دصترسی سراسری یک نشانی اینترنتی',
	'globalblockstatus' => 'وضعیت محلی قعط دسترسی‌های سراسری',
	'removeglobalblock' => 'حذف یک قطع دسترسی سراسری',
	'right-globalblock' => 'ایجاد قطع دسترسی‌های سراسری',
	'right-globalunblock' => 'حذف قطع دسترسی‌های سراسری',
	'right-globalblock-whitelist' => 'غیر فعال کردن قطع دسترسی‌های سراسری به طور محلی',
);

/** Finnish (Suomi)
 * @author Agony
 * @author Crt
 * @author Jaakonam
 * @author Nike
 * @author Str4nd
 */
$messages['fi'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Mahdollistaa]] IP-osoitteiden [[Special:GlobalBlockList|estämisen useasta wikistä kerralla]].',
	'globalblocking-block' => 'Estä IP-osoite globaalisti',
	'globalblocking-block-intro' => 'Voit käyttää tätä sivua IP-osoitteen estämiseen kaikista wikeistä.',
	'globalblocking-block-reason' => 'Perustelu',
	'globalblocking-block-expiry' => 'Kesto',
	'globalblocking-block-expiry-other' => 'Muu kestoaika',
	'globalblocking-block-expiry-otherfield' => 'Muu aika',
	'globalblocking-block-legend' => 'Estä käyttäjä globaalisti',
	'globalblocking-block-options' => 'Asetukset',
	'globalblocking-block-errors' => 'Esto epäonnistui {{PLURAL:$1|seuraavan syyn|seuraavien syiden}} takia:',
	'globalblocking-block-ipinvalid' => 'Antamasi IP-osoite $1 oli virheellinen.
Huomaathan ettet voi syöttää käyttäjätunnusta.',
	'globalblocking-block-expiryinvalid' => 'Antamasi eston kesto ”$1” oli virheellinen.',
	'globalblocking-block-submit' => 'Estä tämä IP-osoite globaalisti',
	'globalblocking-block-success' => 'IP-osoite $1 on estetty kaikissa projekteissa.',
	'globalblocking-block-successsub' => 'Globaali esto onnistui',
	'globalblocking-block-alreadyblocked' => 'IP-osoite $1 on jo estetty globaalisti. Voit tarkastella estoa [[Special:GlobalBlockList|globaalien estojen luettelosta]].',
	'globalblocking-block-bigrange' => 'Antamasi osoiteavaruus $1 on liian suuri. Voit estää korkeintaan 65&nbsp;536 osoitetta kerralla (/16-avaruus)',
	'globalblocking-list-intro' => 'Tämä lista sisältää kaikki voimassa olevat globaalit estot. Jotkut estoista on saatettu merkitä paikallisesti poiskytketyiksi: tämä tarkoittaa että esto on voimassa muilla sivustoilla, mutta paikallinen ylläpitäjä on päättänyt poiskytkeä eston paikallisesta wikistä.',
	'globalblocking-list' => 'Globaalisti estetyt IP-osoitteet',
	'globalblocking-search-legend' => 'Etsi globaaleja estoja',
	'globalblocking-search-ip' => 'IP-osoite',
	'globalblocking-search-submit' => 'Etsi estoja',
	'globalblocking-list-ipinvalid' => 'Haettu IP-osoite $1 oli virheellinen.
Anna kelvollinen IP-osoite.',
	'globalblocking-search-errors' => 'Haku epäonnistui {{PLURAL:$1|seuraavasta syystä|seuraavista syistä}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') esti globaalisti käyttäjän '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'päättyy $1',
	'globalblocking-list-anononly' => 'vain anonyymit',
	'globalblocking-list-unblock' => 'poista',
	'globalblocking-list-whitelisted' => 'paikallisesti poiskytketty käyttäjän $1 toimesta: $2',
	'globalblocking-list-whitelist' => 'paikallinen tila',
	'globalblocking-goto-block' => 'Estä IP-osoite globaalisti',
	'globalblocking-goto-unblock' => 'Poista globaali esto',
	'globalblocking-goto-status' => 'Vaihda globaalin eston paikallista tilaa',
	'globalblocking-return' => 'Palaa globaalien estojen listaan',
	'globalblocking-notblocked' => 'Antamasi IP-osoite $1 ei ole globaalisti estetty.',
	'globalblocking-unblock' => 'Poista globaali esto',
	'globalblocking-unblock-ipinvalid' => 'Antamasi IP-osoite $1 oli virheellinen.
Huomaathan ettet voi syöttää käyttäjätunnusta!',
	'globalblocking-unblock-legend' => 'Globaalin esto poisto',
	'globalblocking-unblock-submit' => 'Poista globaali esto',
	'globalblocking-unblock-reason' => 'Perustelu',
	'globalblocking-unblock-unblocked' => "IP-osoitteen '''$1''' globaali esto #$2 poistettu onnistuneesti",
	'globalblocking-unblock-errors' => 'Globaalin eston poisto epäonnistui {{PLURAL:$1|seuraavan syyn|seuraavien syiden}} takia:',
	'globalblocking-unblock-successsub' => 'Globaaliesto poistettu onnistuneesti',
	'globalblocking-unblock-subtitle' => 'Globaali eston poisto',
	'globalblocking-unblock-intro' => 'Voit käyttää tätä lomaketta globaalin eston poistamiseksi. Voit myös palata takaisin [[Special:GlobalBlockList|globaalien estojen listaan]].',
	'globalblocking-whitelist' => 'Globaalien estojen paikallinen tila',
	'globalblocking-whitelist-legend' => 'Vaihda paikallinen tila',
	'globalblocking-whitelist-reason' => 'Perustelu',
	'globalblocking-whitelist-status' => 'Paikallinen tila:',
	'globalblocking-whitelist-statuslabel' => 'Poiskytke tämä globaali esto {{GRAMMAR:elative|{{SITENAME}}}}',
	'globalblocking-whitelist-submit' => 'Vaihda paikallinen tila',
	'globalblocking-whitelist-whitelisted' => "IP-osoitteen '''$1''' globaali eston #$2 poiskytkentä {{GRAMMAR:inessive|{{SITENAME}}}} onnistui.",
	'globalblocking-whitelist-dewhitelisted' => "IP-osoitteen '''$1''' globaalin eston #$2 uudelleenkytkentä {{GRAMMAR:inessive|{{SITENAME}}}} onnistui.",
	'globalblocking-whitelist-successsub' => 'Paikallinen tila vaihdettu onnistuneesti',
	'globalblocking-whitelist-nochange' => 'Et tehnyt muutoksia tämän eston paikalliseen tilaan. Voit myös palata [[Special:GlobalBlockList|globaalien estojen listaan]].',
	'globalblocking-whitelist-errors' => 'Globaalin eston paikallisen tilan muuttaminen epäonnistui {{PLURAL:$1|seuraavan syyn|seuraavien syiden}} takia:',
	'globalblocking-whitelist-intro' => 'Voit käyttää tätä lomaketta globaalin eston paikallisen tilan muokkaamiseksi. Jos globaali esto on poiskytetty tästä wikistä, IP-osoitetta käyttävät käyttäjät voivat muokata normaalisti. [[Special:GlobalBlockList|Napsauta tästä]] palataksesi takaisin globaalien estojen listaan.',
	'globalblocking-blocked' => "'''$1''' (''$2'') on estänyt IP-osoitteesi kaikissa wikeissä.
Syy: ''$3''
Esto: ''$4''",
	'globalblocking-logpage' => 'Globaalien estojen loki',
	'globalblocking-unblock-logentry' => 'poisti IP-osoitteen [[$1]] globaalin eston',
	'globalblocking-whitelist-logentry' => 'kytki globaalin eston [[$1]] pois paikallisesti',
	'globalblocking-dewhitelist-logentry' => 'kytki globaalin eston [[$1]] uudelleen paikallisesti',
	'globalblocklist' => 'Globaalisti estetyt IP-osoitteet',
	'globalblock' => 'Estä IP-osoite globaalisti',
	'globalblockstatus' => 'Globaalien estojen paikallinen tila',
	'removeglobalblock' => 'Poista globaaliesto',
	'right-globalblock' => 'Estää globaalisti',
	'right-globalunblock' => 'Poistaa globaaleja estoja',
	'right-globalblock-whitelist' => 'Poiskytkeä globaaleja estoja paikallisesti',
);

/** French (Français)
 * @author Grondin
 * @author IAlex
 * @author Seb35
 * @author Sherbrooke
 * @author Verdy p
 * @author Zetud
 */
$messages['fr'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permet]] le blocage des adresses IP [[Special:GlobalBlockList|à travers plusieurs wikis]]',
	'globalblocking-block' => 'Bloquer globalement une adresse IP',
	'globalblocking-block-intro' => 'Vous pouvez utiliser cette page pour bloquer une adresse IP sur l’ensemble des wikis.',
	'globalblocking-block-reason' => 'Motifs de ce blocage :',
	'globalblocking-block-expiry' => 'Plage d’expiration :',
	'globalblocking-block-expiry-other' => 'Autre durée d’expiration',
	'globalblocking-block-expiry-otherfield' => 'Autre durée :',
	'globalblocking-block-legend' => 'Bloquer globalement un utilisateur',
	'globalblocking-block-options' => 'Options :',
	'globalblocking-block-errors' => 'Le blocage a échoué pour {{PLURAL:$1|le motif suivant|les motifs suivants}} :',
	'globalblocking-block-ipinvalid' => 'L’adresse IP ($1) que vous avez entrée est incorrecte.
Veuillez noter que vous ne pouvez pas inscrire un nom d’utilisateur !',
	'globalblocking-block-expiryinvalid' => 'L’expiration que vous avez entrée ($1) est incorrecte.',
	'globalblocking-block-submit' => 'Bloquer globalement cette adresse IP',
	'globalblocking-block-success' => 'L’adresse IP $1 a été bloquée avec succès sur l’ensemble des projets.',
	'globalblocking-block-successsub' => 'Blocage global réussi',
	'globalblocking-block-alreadyblocked' => 'L’adresse IP $1 est déjà bloquée globalement. Vous pouvez afficher les blocages existants sur la liste [[Special:GlobalBlockList|des blocages globaux]].',
	'globalblocking-block-bigrange' => 'La plage que vous avez spécifiée ($1) est trop grande pour être bloquée. Vous ne pouvez pas bloquer plus de 65&nbsp;536 adresses (plages en /16).',
	'globalblocking-list-intro' => 'Voici la liste de tous les blocages globaux actifs. Quelques plages sont marquées comme localement désactivées : ceci signifie qu’elles sont appliquées sur d’autres sites, mais qu’un administrateur local a décidé de les désactiver sur ce wiki.',
	'globalblocking-list' => 'Liste des adresses IP bloquées globalement',
	'globalblocking-search-legend' => 'Recherche d’un blocage global',
	'globalblocking-search-ip' => 'Adresse IP :',
	'globalblocking-search-submit' => 'Recherche des blocages',
	'globalblocking-list-ipinvalid' => 'L’adresse IP que vous recherchez pour ($1) est incorrecte.
Veuillez entrez une adresse IP correcte.',
	'globalblocking-search-errors' => 'Votre recherche a été infructueuse pour {{PLURAL:$1|le motif suivant|les motifs suivants}} :',
	'globalblocking-list-blockitem' => "* $1 : '''$2''' (''$3'') bloqué globalement '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiration $1',
	'globalblocking-list-anononly' => 'uniquement anonyme',
	'globalblocking-list-unblock' => 'débloquer',
	'globalblocking-list-whitelisted' => 'désactivé localement par $1 : $2',
	'globalblocking-list-whitelist' => 'statut local',
	'globalblocking-goto-block' => 'Bloquer globalement une adresse IP',
	'globalblocking-goto-unblock' => 'Enlever un blocage global',
	'globalblocking-goto-status' => 'Modifie le status local d’un blocage global',
	'globalblocking-return' => 'Retourner à la liste des blocages globaux',
	'globalblocking-notblocked' => "L’adresse IP ($1) que vous avez inscrite n'est pas globalement bloquée.",
	'globalblocking-unblock' => 'Enlever un blocage global',
	'globalblocking-unblock-ipinvalid' => 'L’adresse IP que vous avez indiquée ($1) est incorrecte.
Veuillez noter que que vous ne pouvez pas entrer un nom d’utilisateur !',
	'globalblocking-unblock-legend' => 'Enlever un blocage global',
	'globalblocking-unblock-submit' => 'Enlever le blocage global',
	'globalblocking-unblock-reason' => 'Motifs :',
	'globalblocking-unblock-unblocked' => "Vous avez réussi à retirer le blocage global n° $2 correspondant à l’adresse IP '''$1'''",
	'globalblocking-unblock-errors' => 'Vous ne pouvez pas enlever un blocage global pour cette adresse IP pour {{PLURAL:$1|le motif suivant|les motifs suivants}} :
$1',
	'globalblocking-unblock-successsub' => 'Blocage global retiré avec succès',
	'globalblocking-unblock-subtitle' => 'Suppression du blocage global',
	'globalblocking-unblock-intro' => 'Vous pouvez utiliser ce formulaire pour retirer un blocage global.
[[Special:GlobalBlockList|Cliquez ici]] pour revenir à la liste globale des blocages.',
	'globalblocking-whitelist' => 'Statut local des blocages globaux',
	'globalblocking-whitelist-legend' => 'Changer le statut local',
	'globalblocking-whitelist-reason' => 'Raison de la modification :',
	'globalblocking-whitelist-status' => 'Statut local :',
	'globalblocking-whitelist-statuslabel' => 'Désactiver ce blocage global sur {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Changer le statut local',
	'globalblocking-whitelist-whitelisted' => "Vous avez désactivé avec succès le blocage global n° $2 sur l'adresse IP '''$1''' sur {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Vous avez réactivé avec succès le blocage global n° $2 sur l'adresse IP '''$1''' sur {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Statut local changé avec succès',
	'globalblocking-whitelist-nochange' => 'Vous n’avez pas modifié le statut local de ce blocage.
[[Special:GlobalBlockList|Revenir à la liste globale des blocages]].',
	'globalblocking-whitelist-errors' => 'Votre modifications vers le statut local d’un blocage global n’a pas été couronnée de succès pour {{PLURAL:$1|le motif suivant|les motifs suivants}} :',
	'globalblocking-whitelist-intro' => 'Vous pouvez utiliser ce formulaire pour modifier le statut local d’un blocage global. Si un blocage global est désactivé sur ce wiki, les utilisateurs concernés par l’adresse IP pourront éditer normalement. [[Special:GlobalBlockList|Cliquez ici]] pour retourner à la liste globale.',
	'globalblocking-blocked' => "Votre adresse IP a été bloquée sur l’ensemble des wiki par '''$1''' (''$2'').
Le motif indiqué était « $3 ». La plage ''$4''.",
	'globalblocking-logpage' => 'Journal des blocages globaux',
	'globalblocking-logpagetext' => 'Voici un journal des blocages globaux qui ont été faits et révoqués sur ce wiki.
Il devrait être relevé que les blocages globaux peut être faits ou annulés sur d’autres wikis, et que lesdits blocages globaux sont de nature à interférer sur ce wiki.
Pour visionner tous les blocages globaux actifs, vous pouvez visiter la [[Special:GlobalBlockList|liste des blocages globaux]].',
	'globalblocking-block-logentry' => '[[$1]] bloqué globalement avec une durée d’expiration de $2',
	'globalblocking-unblock-logentry' => 'blocage global retiré sur [[$1]]',
	'globalblocking-whitelist-logentry' => 'a désactivé localement le blocage global de [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'a réactivé localement le blocage global de [[$1]]',
	'globalblocklist' => 'Liste des adresses IP bloquées globalement',
	'globalblock' => 'Bloquer globalement une adresse IP',
	'globalblockstatus' => 'Statuts locaux des blocages globaux',
	'removeglobalblock' => 'Supprimer un blocage global',
	'right-globalblock' => 'Bloquer des utilisateurs globalement',
	'right-globalunblock' => 'Débloquer des utilisateurs bloqués globalement',
	'right-globalblock-whitelist' => 'Désactiver localement les blocages globaux',
);

/** Western Frisian (Frysk)
 * @author Snakesteuben
 */
$messages['fy'] = array(
	'globalblocking-block-expiry-otherfield' => 'In oare tiid:',
);

/** Galician (Galego)
 * @author Prevert
 * @author Toliño
 */
$messages['gl'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permite]] que os enderezos IP sexan [[Special:GlobalBlockList|bloqueados en múltiples wikis]]',
	'globalblocking-block' => 'Bloqueo global dun enderezo IP',
	'globalblocking-block-intro' => 'Pode usar esta páxina para bloquear un enderezo IP en todos os wikis.',
	'globalblocking-block-reason' => 'Razón para o bloqueo:',
	'globalblocking-block-expiry' => 'Expiración do bloqueo:',
	'globalblocking-block-expiry-other' => 'Outro período de tempo de expiración',
	'globalblocking-block-expiry-otherfield' => 'Outro período de tempo:',
	'globalblocking-block-legend' => 'Bloquear un usuario globalmente',
	'globalblocking-block-options' => 'Opcións:',
	'globalblocking-block-errors' => 'O seu bloqueo non puido levarse a cabo {{PLURAL:$1|pola seguinte razón|polas seguintes razóns}}:',
	'globalblocking-block-ipinvalid' => 'O enderezo IP ($1) que tecleou é inválido.
Por favor, decátese de que non pode teclear un nome de usuario!',
	'globalblocking-block-expiryinvalid' => 'O período de expiración que tecleou ($1) é inválido.',
	'globalblocking-block-submit' => 'Bloquear este enderezo IP globalmente',
	'globalblocking-block-success' => 'O enderezo IP $1 foi bloqueado con éxito en todos os proxectos.',
	'globalblocking-block-successsub' => 'Bloqueo global exitoso',
	'globalblocking-block-alreadyblocked' => 'O enderezo IP "$1" xa está globalmente bloqueado. Pode ver os bloqueos vixentes na [[Special:GlobalBlockList|listaxe de bloqueos globais]].',
	'globalblocking-block-bigrange' => 'O rango especificado ($1) é demasiado grande para bloquealo. Pode bloquear, como máximo, 65.536 enderezos (/16 rangos)',
	'globalblocking-list-intro' => 'Esta é unha lista de todos os bloqueos globais vixentes.
Algúns bloqueos están marcados como deshabilitados localmente: isto significa que se aplican noutros sitios, pero que un administrador local decidiu retirar o bloqueo neste wiki.',
	'globalblocking-list' => 'Lista dos bloqueos globais a enderezos IP',
	'globalblocking-search-legend' => 'Procurar bloqueos globais',
	'globalblocking-search-ip' => 'Enderezo IP:',
	'globalblocking-search-submit' => 'Procurar os bloqueos',
	'globalblocking-list-ipinvalid' => 'O enderezo IP que procurou ($1) é inválido.
Por favor, teclee un enderezo IP válido.',
	'globalblocking-search-errors' => 'A súa procura non tivo éxito {{PLURAL:$1|pola seguinte razón|polas seguintes razóns}}:',
	'globalblocking-list-blockitem' => "\$1: '''\$2''' (''\$3'') bloqueou globalmente a \"'''[[Special:Contributions/\$4|\$4]]'''\" ''(\$5)''",
	'globalblocking-list-expiry' => 'expira $1',
	'globalblocking-list-anononly' => 'só anón.',
	'globalblocking-list-unblock' => 'desbloquear',
	'globalblocking-list-whitelisted' => 'deshabilitado localmente por $1: $2',
	'globalblocking-list-whitelist' => 'status local',
	'globalblocking-goto-block' => 'Bloquear globalmente un enderezo IP',
	'globalblocking-goto-unblock' => 'Retirar un bloqueo global',
	'globalblocking-goto-status' => 'Cambiar o status local dun bloqueo global',
	'globalblocking-return' => 'Voltar á lista de bloqueos globais',
	'globalblocking-notblocked' => 'O enderezo IP ($1) que inseriu non está globalmente bloqueado.',
	'globalblocking-unblock' => 'Retirar un bloqueo global',
	'globalblocking-unblock-ipinvalid' => 'O enderezo IP ($1) que tecleou é inválido.
Por favor, decátese de que non pode teclear un nome de usuario!',
	'globalblocking-unblock-legend' => 'Retirar un bloqueo global',
	'globalblocking-unblock-submit' => 'Retirar bloqueo global',
	'globalblocking-unblock-reason' => 'Razón:',
	'globalblocking-unblock-unblocked' => "Retirou con éxito o bloqueo global #$2 que tiña o enderezo IP '''$1'''",
	'globalblocking-unblock-errors' => 'A súa eliminación do bloqueo global non puido levarse a cabo {{PLURAL:$1|pola seguinte razón|polas seguintes razóns}}:',
	'globalblocking-unblock-successsub' => 'A retirada do bloqueo global foi un éxito',
	'globalblocking-unblock-subtitle' => 'Eliminando o bloqueo global',
	'globalblocking-unblock-intro' => 'Pode usar este formulario para retirar un bloqueo global.
[[Special:GlobalBlockList|Prema aquí]] para voltar á lista dos bloqueos globais.',
	'globalblocking-whitelist' => 'Status local dos bloqueos globais',
	'globalblocking-whitelist-legend' => 'Cambiar o status local',
	'globalblocking-whitelist-reason' => 'Motivo para o cambio:',
	'globalblocking-whitelist-status' => 'Status local:',
	'globalblocking-whitelist-statuslabel' => 'Deshabilitar este bloqueo global en {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Cambiar o status local',
	'globalblocking-whitelist-whitelisted' => "Deshabilitou con éxito en {{SITENAME}} o bloqueo global #$2 do enderezo IP '''$1'''.",
	'globalblocking-whitelist-dewhitelisted' => "Volveu habilitar con éxito en {{SITENAME}} o bloqueo global #$2 do enderezo IP '''$1'''.",
	'globalblocking-whitelist-successsub' => 'O status local foi trocado con éxito',
	'globalblocking-whitelist-nochange' => 'Non lle fixo ningún cambio ao status local deste bloqueo.
[[Special:GlobalBlockList|Voltar á lista dos bloqueos globais]].',
	'globalblocking-whitelist-errors' => 'O cambio do status local dun bloqueo global fracasou {{PLURAL:$1|polo seguinte motivo|polos seguintes motivos}}:',
	'globalblocking-whitelist-intro' => 'Pode usar este formulario para editar o status local dun bloqueo global.
Se un bloqueo global está deshabilitado neste wiki, os usuarios que usen o enderezo IP afectado poderán editar sen problemas.
[[Special:GlobalBlockList|Voltar á lista dos bloqueos globais]].',
	'globalblocking-blocked' => "O seu enderezo IP foi bloqueado en todos os wikis por '''\$1''' (''\$2'').
A razón que deu foi ''\"\$3\"''. O bloqueo, ''\$4''.",
	'globalblocking-logpage' => 'Rexistro de bloqueos globais',
	'globalblocking-logpagetext' => 'Este é un rexistro dos bloqueos globais que foron feitos e retirados neste wiki.
Déase de conta de que os bloqueos globais poden ser feitos e retirados noutros wikis e este bloqueos poden afectar a este.
Para ver todos os bloqueos globais activos, pode ollar a [[Special:GlobalBlockList|lista dos bloqueos globais]].',
	'globalblocking-block-logentry' => 'bloqueou globalmente a "[[$1]]" cun período de expiración de $2',
	'globalblocking-unblock-logentry' => 'retirado o bloqueo global en [[$1]]',
	'globalblocking-whitelist-logentry' => 'deshabilitou localmente o bloqueo global en [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'volveu habilitar localmente o bloqueo global en [[$1]]',
	'globalblocklist' => 'Lista dos bloqueos globais a enderezos IP',
	'globalblock' => 'Bloquear globalmente un enderezo IP',
	'globalblockstatus' => 'Status local dos bloqueos globais',
	'removeglobalblock' => 'Retirar un bloqueo global',
	'right-globalblock' => 'Realizar bloqueos globais',
	'right-globalunblock' => 'Eliminar bloqueos globais',
	'right-globalblock-whitelist' => 'Deshabilitar bloqueos globais localmente',
);

/** Gothic (𐌲𐌿𐍄𐌹𐍃𐌺)
 * @author Jocke Pirat
 */
$messages['got'] = array(
	'globalblocking-unblock-reason' => 'Faírina:',
);

/** Manx (Gaelg)
 * @author MacTire02
 */
$messages['gv'] = array(
	'globalblocking-block-expiry-otherfield' => 'Am elley:',
	'globalblocking-block-options' => 'Reihghyn',
	'globalblocking-search-ip' => 'Enmys IP:',
	'globalblocking-unblock-reason' => 'Fa:',
);

/** Hawaiian (Hawai`i)
 * @author Singularity
 */
$messages['haw'] = array(
	'globalblocking-unblock-reason' => 'Kumu:',
);

/** Hebrew (עברית)
 * @author Agbad
 * @author Rotemliss
 */
$messages['he'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|אפשרות]] ל[[Special:GlobalBlockList|חסימה גלובלית בין אתרי הוויקי]] של כתובות IP',
	'globalblocking-block' => 'חסימה גלובלית של כתובת IP',
	'globalblocking-block-intro' => 'באפשרותכם להשתמש בדף זה כדי לחסום כתובת IP בכל אתרי הוויקי.',
	'globalblocking-block-reason' => 'סיבה לחסימה:',
	'globalblocking-block-expiry' => 'פקיעת החסימה:',
	'globalblocking-block-expiry-other' => 'זמן פקיעה אחר',
	'globalblocking-block-expiry-otherfield' => 'זמן אחר:',
	'globalblocking-block-legend' => 'חסימה גלובלית של משתמש',
	'globalblocking-block-options' => 'אפשרויות:',
	'globalblocking-block-errors' => 'החסימה נכשלה בגלל {{PLURAL:$1|הסיבה הבאה|הסיבות הבאות}}:',
	'globalblocking-block-ipinvalid' => 'כתובת ה־IP שהקלדתם ($1) אינה תקינה.
שימו לב שאין באפשרותכם להכניס שם משתמש!',
	'globalblocking-block-expiryinvalid' => 'זמן פקיעת החסימה שהקלדתם ($1) אינו תקין.',
	'globalblocking-block-submit' => 'חסימה גלובלית של כתובת ה־IP הזו',
	'globalblocking-block-success' => 'כתובת ה־IP $1 נחסמה בהצלחה בכל אתרי הוויקי.',
	'globalblocking-block-successsub' => 'החסימה הגלובלית הושלמה בהצלחה',
	'globalblocking-block-alreadyblocked' => 'כתובת ה־IP $1 כבר נחסמה באופן גלובלי. באפשרותכם לצפות בחסימה הקיימת ב[[Special:GlobalBlockList|רשימת החסימות הגלובליות]].',
	'globalblocking-block-bigrange' => 'הטווח שציינתם ($1) גדול מדי לחסימה. באפשרותכם לחסום לכל היותר 65,536 כתובות (טווחים מסוג /16)',
	'globalblocking-list-intro' => 'זוהי רשימה של כל החסימות הגלובליות הקיימות כרגע. חלק מהחסימות מסומנות כחסימות מוגבלות באופן מקומי: פירוש הדבר שהן תקפות באתרים אחרים, אך אחד ממפעילי המערכת המקומיים החליט לבטלן באתר זה.',
	'globalblocking-list' => 'רשימת כתובות IP שנחסמו גלובלית',
	'globalblocking-search-legend' => 'חיפוש חסימה גלובלית',
	'globalblocking-search-ip' => 'כתובת IP:',
	'globalblocking-search-submit' => 'חיפוש חסימות',
	'globalblocking-list-ipinvalid' => 'כתובת ה־IP שהקלדתם ($1) אינה תקינה.
אנא הקלידו כתובת IP תקינה.',
	'globalblocking-search-errors' => 'החיפוש נכשל בגלל {{PLURAL:$1|הסיבה הבאה|הסיבות הבאות}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') חסם באופן גלובלי את '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'פקיעת החסימה: $1',
	'globalblocking-list-anononly' => 'משתמשים אנונימיים בלבד',
	'globalblocking-list-unblock' => 'הסרה',
	'globalblocking-list-whitelisted' => 'בוטל באופן מקומי על ידי $1: $2',
	'globalblocking-list-whitelist' => 'מצב מקומי',
	'globalblocking-goto-block' => 'חסימה גלובלית של כתובת IP',
	'globalblocking-goto-unblock' => 'הסרת חסימה גלובלית',
	'globalblocking-goto-status' => 'שינוי המצב המקומי של חסימה גלובלית',
	'globalblocking-return' => 'חזרה לרשימת החסימות הגלובליות',
	'globalblocking-notblocked' => 'כתובת ה־IP שהקלדתם ($1) אינה חסומה באופן גלובלי.',
	'globalblocking-unblock' => 'הסרת חסימה גלובלית',
	'globalblocking-unblock-ipinvalid' => 'כתובת ה־IP ($1) שהקלדתם אינה תקינה.
שימו לב שאין באפשרותכם להכניס שם משתמש!',
	'globalblocking-unblock-legend' => 'הסרת חסימה גלובלית',
	'globalblocking-unblock-submit' => 'הסרת חסימה גלובלית',
	'globalblocking-unblock-reason' => 'סיבה:',
	'globalblocking-unblock-unblocked' => "החסימה הגלובלית #$2 של כתובת ה־IP '''$1''' הוסרה בהצלחה",
	'globalblocking-unblock-errors' => 'הסרת החסימה הגלובלית נכשלה בגלל {{PLURAL:$1|הסיבה הבאה|הסיבות הבאות}}:',
	'globalblocking-unblock-successsub' => 'החסימה הגלובלית הוסרה בהצלחה',
	'globalblocking-unblock-subtitle' => 'הסרת חסימה גלובלית',
	'globalblocking-unblock-intro' => 'באפשרותכם להשתמש בטופס זה כדי להסיר חסימה גלובלית. [[Special:GlobalBlockList|חזרה לרשימת החסימות הגלובליות]].',
	'globalblocking-whitelist' => 'המצב המקומי של החסימות הגלובליות',
	'globalblocking-whitelist-legend' => 'שינוי המצב המקומי',
	'globalblocking-whitelist-reason' => 'סיבה לשינוי:',
	'globalblocking-whitelist-status' => 'מצב מקומי:',
	'globalblocking-whitelist-statuslabel' => 'ביטול החסימה הגלובלית ב{{grammar:תחילית|{{SITENAME}}}}',
	'globalblocking-whitelist-submit' => 'שינוי המצב המקומי',
	'globalblocking-whitelist-whitelisted' => "החסימה הגלובלית #$2 של כתובת ה־IP '''$1''' בוטלה בהצלחה ב{{grammar:תחילית|{{SITENAME}}}}.",
	'globalblocking-whitelist-dewhitelisted' => "החסימה הגלובלית #$2 של כתובת ה־IP '''$1''' הופעלה מחדש בהצלחה ב{{grammar:תחילית|{{SITENAME}}}}.",
	'globalblocking-whitelist-successsub' => 'המצב המקומי שונה בהצלחה',
	'globalblocking-whitelist-nochange' => 'לא ביצעתם שינוי במצב המקומי של חסימה זו. [[Special:GlobalBlockList|חזרה לרשימת החסימות הגלובליות]].',
	'globalblocking-whitelist-errors' => 'השינוי למצב המקומי של החסימה הגלובלית נכשל בגלל {{PLURAL:$1|הסיבה הבאה|הסיבות הבאות}}:',
	'globalblocking-whitelist-intro' => 'באפשרותכם להשתמש בטופס זה כדי לערוך את המצב המקומי של חסימה גלובלית. אם החסימה הגלובלית תבוטל באתר זה, המשתמשים בכתובת ה־IP המושפעת מהחסימה יוכלו לערוך כרגיל. [[Special:GlobalBlockList|חזרה לרשימת החסימות הגלובליות]].',
	'globalblocking-blocked' => "כתובת ה־IP שלכם נחסמה בכל אתרי הוויקי על ידי '''\$1''' ('''\$2''').
הסיבה שניתנה הייתה '''\"\$3\"'''.
זמן פקיעת החסימה הינו '''\$4'''.",
	'globalblocking-logpage' => 'יומן החסימות הגלובליות',
	'globalblocking-logpagetext' => 'זהו יומן החסימות הגלובליות שהופעלו והוסרו באתר זה.
שימו לב שניתן להפעיל ולהסיר חסימות גלובליות גם באתרים אחרים, ושהחסימות הגלובליות האלה עשויות להשפיע גם על האתר הזה.
כדי לצפות בכל החסימות הגלובליות הפעילות, ראו [[Special:GlobalBlockList|רשימת החסימות הגלובליות]].',
	'globalblocking-block-logentry' => 'חסם באופן גלובלי את [[$1]] עם זמן פקיעה של $2',
	'globalblocking-unblock-logentry' => 'הסיר את החסימה הגלובלית של [[$1]]',
	'globalblocking-whitelist-logentry' => 'ביטל את החסימה הגלובלית של [[$1]] באופן מקומי',
	'globalblocking-dewhitelist-logentry' => 'הפעיל מחדש את החסימה הגלובלית של [[$1]] באופן מקומי',
	'globalblocklist' => 'רשימת כתובות IP החסומות באופן גלובלי',
	'globalblock' => 'חסימת כתובת IP באופן גלובלי',
	'globalblockstatus' => 'המצב המקומי של החסימות הגלובליות',
	'removeglobalblock' => 'הסרת חסימה גלובלית',
	'right-globalblock' => 'יצירת חסימות גלובליות',
	'right-globalunblock' => 'הסרת חסימות גלובליות',
	'right-globalblock-whitelist' => 'ביטול חסימות גלובליות באופן מקומי',
);

/** Hindi (हिन्दी)
 * @author Kaustubh
 */
$messages['hi'] = array(
	'globalblocking-desc' => 'आइपी एड्रेस को [[Special:GlobalBlockList|एक से ज्यादा विकियोंपर ब्लॉक]] करने की [[Special:GlobalBlock|अनुमति]] देता हैं।',
	'globalblocking-block' => 'एक आइपी एड्रेस को ग्लोबलि ब्लॉक करें',
	'globalblocking-block-intro' => 'आप इस पन्ने का इस्तेमाल करके सभी विकियोंपर एक आईपी एड्रेस ब्लॉक कर सकतें हैं।',
	'globalblocking-block-reason' => 'इस ब्लॉक का कारण:',
	'globalblocking-block-expiry' => 'ब्लॉक समाप्ति:',
	'globalblocking-block-expiry-other' => 'अन्य समाप्ती समय',
	'globalblocking-block-expiry-otherfield' => 'अन्य समय:',
	'globalblocking-block-legend' => 'एक सदस्य को ग्लोबली ब्लॉक करें',
	'globalblocking-block-options' => 'विकल्प',
	'globalblocking-block-errors' => 'ब्लॉक अयशस्वी हुआ, कारण:
$1',
	'globalblocking-block-ipinvalid' => 'आपने दिया हुआ आईपी एड्रेस ($1) अवैध हैं।
कृपया ध्यान दें आप सदस्यनाम नहीं दे सकतें!',
	'globalblocking-block-expiryinvalid' => 'आपने दिया हुआ समाप्ती समय ($1) अवैध हैं।',
	'globalblocking-block-submit' => 'इस आईपी को ग्लोबली ब्लॉक करें',
	'globalblocking-block-success' => '$1 इस आयपी एड्रेसको सभी विकिंयोंपर ब्लॉक कर दिया गया हैं।
आप शायद [[Special:GlobalBlockList|वैश्विक ब्लॉक सूची]] देखना चाहते हैं।',
	'globalblocking-block-successsub' => 'ग्लोबल ब्लॉक यशस्वी हुआ',
	'globalblocking-block-alreadyblocked' => '$1 इस आइपी एड्रेसको पहलेसे ब्लॉक किया हुआ हैं। आप अस्तित्वमें होनेवाले ब्लॉक [[Special:GlobalBlockList|वैश्विक ब्लॉक सूचीमें]] देख सकतें हैं।',
	'globalblocking-list' => 'ग्लोबल ब्लॉक किये हुए आईपी एड्रेसोंकी सूची',
	'globalblocking-search-legend' => 'ग्लोबल ब्लॉक खोजें',
	'globalblocking-search-ip' => 'आइपी एड्रेस:',
	'globalblocking-search-submit' => 'ब्लॉक खोजें',
	'globalblocking-list-ipinvalid' => 'आपने खोजने के लिये दिया हुआ आइपी एड्रेस ($1) अवैध हैं।
कृपया वैध आइपी एड्रेस दें।',
	'globalblocking-search-errors' => 'आपकी खोज़ अयशस्वी हुई हैं, कारण:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') ग्लोबली ब्लॉक किया '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'समाप्ती $1',
	'globalblocking-list-anononly' => 'सिर्फ-अनामक',
	'globalblocking-list-unblock' => 'अनब्लॉक',
	'globalblocking-list-whitelisted' => '$1 ने स्थानिक स्तरपर रद्द किया: $2',
	'globalblocking-list-whitelist' => 'स्थानिक स्थिती',
	'globalblocking-unblock-ipinvalid' => 'आपने दिया हुआ आईपी एड्रेस ($1) अवैध हैं।
कृपया ध्यान दें आप सदस्यनाम नहीं दे सकतें!',
	'globalblocking-unblock-legend' => 'ग्लोबल ब्लॉक हटायें',
	'globalblocking-unblock-submit' => 'ग्लोबल ब्लॉक हटायें',
	'globalblocking-unblock-reason' => 'कारण:',
	'globalblocking-unblock-unblocked' => "आपने '''$1''' इस आइपी एड्रेस पर होने वाला ग्लोबल ब्लॉक #$2 हटा दिया हैं",
	'globalblocking-unblock-errors' => 'आप इस आईपी एड्रेस का ग्लोबल ब्लॉक हटा नहीं सकतें, कारण:
$1',
	'globalblocking-unblock-successsub' => 'ग्लोबल ब्लॉक हटा दिया गया हैं',
	'globalblocking-whitelist-legend' => 'स्थानिक स्थिती बदलें',
	'globalblocking-whitelist-reason' => 'बदलाव के कारण:',
	'globalblocking-whitelist-status' => 'स्थानिक स्थिती:',
	'globalblocking-whitelist-statuslabel' => '{{SITENAME}} पर से यह वैश्विक ब्लॉक हटायें',
	'globalblocking-whitelist-submit' => 'स्थानिक स्थिती बदलें',
	'globalblocking-whitelist-whitelisted' => "आपने '''$1''' इस एड्रेसपर दिया हुआ वैश्विक ब्लॉक #$2, {{SITENAME}} पर रद्द कर दिया हैं।",
	'globalblocking-whitelist-dewhitelisted' => "आपने '''$1''' इस आइपी एड्रेसपर दिया हुआ वैश्विक ब्लॉक #$2, {{SITENAME}} पर फिरसे दिया हैं।",
	'globalblocking-whitelist-successsub' => 'स्थानिक स्थिती बदल दी गई हैं',
	'globalblocking-blocked' => "आपके आइपी एड्रेसको सभी विकिमीडिया विकिंवर '''\$1''' (''\$2'') ने ब्लॉक किया हुआ हैं।
इसके लिये ''\"\$3\"'' यह कारण दिया हुआ हैं। इस ब्लॉक की समाप्ति ''\$4'' हैं।",
	'globalblocking-logpage' => 'ग्लोबल ब्लॉक सूची',
	'globalblocking-block-logentry' => '[[$1]] को ग्लोबली ब्लॉक किया समाप्ति समय $2',
	'globalblocking-unblock-logentry' => '[[$1]] का ग्लोबल ब्लॉक निकाल दिया',
	'globalblocking-whitelist-logentry' => '[[$1]] पर दिया हुआ वैश्विक ब्लॉक स्थानिक स्तरपर रद्द कर दिया',
	'globalblocking-dewhitelist-logentry' => '[[$1]] पर दिया हुआ वैश्विक ब्लॉक स्थानिक स्तरपर फिरसे दिया',
	'globalblocklist' => 'ग्लोबल ब्लॉक होनेवाले आइपी एड्रेसकी सूची',
	'globalblock' => 'एक आइपी एड्रेसको ग्लोबल ब्लॉक करें',
	'right-globalblock' => 'वैश्विक ब्लॉक तैयार करें',
	'right-globalunblock' => 'वैश्विक ब्लॉक हटा दें',
	'right-globalblock-whitelist' => 'वैश्विक ब्लॉक स्थानिक स्तरपर रद्द करें',
);

/** Hiligaynon (Ilonggo)
 * @author Jose77
 */
$messages['hil'] = array(
	'globalblocking-unblock-reason' => 'Rason:',
);

/** Croatian (Hrvatski)
 * @author Dalibor Bosits
 * @author Suradnik13
 */
$messages['hr'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Omogućuje]] blokiranje IP adresa [[Special:GlobalBlockList|na svim wikijima]]',
	'globalblocking-block' => 'Globalno blokiraj IP adresu',
	'globalblocking-block-intro' => 'Možete koristiti ovu stranicu kako biste blokirali IP adresu na svim wikijima.',
	'globalblocking-block-reason' => 'Razlog za ovo blokiranje:',
	'globalblocking-block-expiry' => 'Blokiranje istječe:',
	'globalblocking-block-expiry-other' => 'Drugo vrijeme isteka',
	'globalblocking-block-expiry-otherfield' => 'Drugo vrijeme:',
	'globalblocking-block-legend' => 'Blokiraj suradnika globalno',
	'globalblocking-block-options' => 'Mogućnosti:',
	'globalblocking-block-errors' => 'Vaše blokiranje je neuspješno, iz {{PLURAL:$1|sljedećeg razloga|sljedećih razloga}}:',
	'globalblocking-block-ipinvalid' => 'IP adresa ($1) koju ste upisali je neispravna.
Uzmite u obzir da ne možete upisati suradničko ime!',
	'globalblocking-block-expiryinvalid' => 'Vremenski rok koji ste upisali ($1) je neispravan.',
	'globalblocking-block-submit' => 'Blokiraj ovu IP adresu globalno',
	'globalblocking-block-success' => 'IP adresa $1 je uspješno blokirana na svim projektima.',
	'globalblocking-block-successsub' => 'Globalno blokiranje je uspješno',
	'globalblocking-block-alreadyblocked' => 'IP adresa $1 je već globalno blokirana.
Možete vidjeti postojeća blokiranja na [[Special:GlobalBlockList|popisu globalnih blokiranja]].',
	'globalblocking-block-bigrange' => 'Opseg koji ste odredili ($1) je prevelik za blokiranje.
Možete blokirati najviše 65,536 adresa (/16 opseg)',
	'globalblocking-list-intro' => 'Ovo je popis globalno blokiranih adresu trenutačno aktivnih.
Neka blokiranja su označena kao mjesno onemogućena: to znači da je blokiranje aktivno na drugim projektima, ali ne na ovom wikiju.',
	'globalblocking-list' => 'Popis globalno blokiranih IP adresa',
	'globalblocking-search-legend' => 'Traži globalno blokiranje',
	'globalblocking-search-ip' => 'IP Adresa:',
	'globalblocking-search-submit' => 'Traži blokiranje',
	'globalblocking-list-ipinvalid' => 'IP adresa koju ste tražili ($1) je neispravna.
Molimo vas upišite ispravnu IP adresu.',
	'globalblocking-search-errors' => 'Važe traženje je neuspješno, iz {{PLURAL:$1|sljedećeg razloga|sljedećih razloga}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globalno blokirao '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'istječe $1',
	'globalblocking-list-anononly' => 'samo neprijavljeni',
	'globalblocking-list-unblock' => 'ukloni',
	'globalblocking-list-whitelisted' => '$1 mjesno onemogućio: $2',
	'globalblocking-list-whitelist' => 'mjesni status',
	'globalblocking-goto-block' => 'Globalno blokiraj IP adresu',
	'globalblocking-goto-unblock' => 'Ukloni globalno blokiranje',
	'globalblocking-goto-status' => 'Promijeni mjesni status za globalno blokiranje',
	'globalblocking-return' => 'Vrati se na popis globalnih blokiranja',
	'globalblocking-notblocked' => 'IP adresa ($1) koju ste upisali nije globalno blokirana.',
	'globalblocking-unblock' => 'Ukloni globalno blokiranje',
	'globalblocking-unblock-ipinvalid' => 'IP adresa ($1) koju ste upisali je neispravna.
Molimo vas uzmite u obzir da ne možete upisati suradničko ime!',
	'globalblocking-unblock-legend' => 'Ukloni globalno blokiranje',
	'globalblocking-unblock-submit' => 'Ukloni globalno blokiranje',
	'globalblocking-unblock-reason' => 'Razlog:',
	'globalblocking-unblock-unblocked' => "Uspješno ste uklonili globalno blokiranje #$2 za IP adresu '''$1'''",
	'globalblocking-unblock-errors' => 'Vaše uklanjanje globalnog blokiranja je neuspješno, iz {{PLURAL:$1|sljedećeg razloga|sljedećih razloga}}:',
	'globalblocking-unblock-successsub' => 'Globalno blokiranje uspješno uklonjeno',
	'globalblocking-unblock-subtitle' => 'Uklanjanje globalnog blokiranja',
	'globalblocking-unblock-intro' => 'Ovu stranicu možete koristiti za uklanjanje globalnog blokiranja.
[[Special:GlobalBlockList|Odaberite ovo]] za povratak na popis globalnih blokiranja.',
	'globalblocking-whitelist' => 'Mjesni status globalnih blokiranja',
	'globalblocking-whitelist-legend' => 'Promijeni mjesni status',
	'globalblocking-whitelist-reason' => 'Razlog za promjenu:',
	'globalblocking-whitelist-status' => 'Mjesni status:',
	'globalblocking-whitelist-statuslabel' => 'Onemogući ovo globalno blokiranje na {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Promijeni mjesni status',
	'globalblocking-whitelist-whitelisted' => "Uspješno ste onemogućili globalno blokiranje #$2 za IP adresu '''$1''' na {{SITENAME}}",
	'globalblocking-whitelist-dewhitelisted' => "Uspješno ste omogućili globalno blokiranje #$2 za IP adresu ''''$1''' na {{SITENAME}}",
	'globalblocking-whitelist-successsub' => 'Mjesni status uspješno promijenjen',
	'globalblocking-whitelist-nochange' => 'Niste napravili promjene za mjesni status ovog blokiranja.
[[Special:GlobalBlockList|Vrati se na popis globalno blokiranih adresa]].',
	'globalblocking-whitelist-errors' => 'Vaša promjena mjesnog statusa za globalno blokiranje je neuspješna, iz {{PLURAL:$1|sljedećeg razloga|sljedećih razloga}}:',
	'globalblocking-whitelist-intro' => 'Možete koristiti ovu stranicu za uređivanje mjesnog statusa globalnog blokiranja.
Ako je globalno blokiranje onemogućeno na ovom wikiju, suradnici s tom IP adresom će moći normalno uređivati.
[[Special:GlobalBlockList|Vrati se na popis globalno blokiranih adresa]].',
	'globalblocking-blocked' => "Vaša IP adresa je blokirana na svim wikijima od '''\$1''' (''\$2'').
Razlog je ''\"\$1\"''.
Blokiranje ''\$4''.",
	'globalblocking-logpage' => 'Evidencija globalnog blokiranja',
	'globalblocking-block-logentry' => 'globalno blokirao [[$1]] s istekom vremena od $2',
	'globalblocking-unblock-logentry' => 'uklonio globalno blokiranje za [[$1]]',
	'globalblocking-whitelist-logentry' => 'onemogućio globalno blokiranje za [[$1]] mjesno',
	'globalblocking-dewhitelist-logentry' => 'omogućio globalno blokiranje za [[$1]] mjesno',
	'globalblocklist' => 'Popis globalno blokiranih IP adresa',
	'globalblock' => 'Globalno blokiraj IP adresu',
	'globalblockstatus' => 'Mjesni status globalnih blokiranja',
	'removeglobalblock' => 'Ukloni globalno blokiranje',
	'right-globalblock' => 'Mogućnost globalnog blokiranja',
	'right-globalunblock' => 'Uklanjanje globalnog blokiranja',
	'right-globalblock-whitelist' => 'Mjesno uklanjanje globalnog blokiranja',
);

/** Haitian (Kreyòl ayisyen)
 * @author Jvm
 */
$messages['ht'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Pemèt]] Pou vin adrès IP yo [[Special:GlobalBlockList|bloke atravè plizyè wiki]]',
	'globalblocking-block' => 'Bloke yon adrès IP globalman',
	'globalblocking-block-intro' => 'Ou kapab itilize paj sa pou bloke yon adrès IP nan tou wiki yo.',
	'globalblocking-block-reason' => 'Rezon pou blokaj sa:',
	'globalblocking-block-expiry' => 'Blokaj expirasyon:',
	'globalblocking-block-expiry-other' => 'Lòt tan tèminasyon',
	'globalblocking-block-expiry-otherfield' => 'Lòt tan:',
	'globalblocking-block-legend' => 'Bloke yon itilizatè globalman',
	'globalblocking-block-options' => 'Opsyon yo',
	'globalblocking-block-errors' => 'Blokaj sa pa reyisi, paske:  
$1',
	'globalblocking-block-ipinvalid' => 'Adrès IP sa ($1) ou te antre a envalid.
Souple note ke ou pa kapab antre yon non itlizatè!',
	'globalblocking-block-expiryinvalid' => 'Expirasyon ($1) ou te antre a envalid.',
	'globalblocking-block-submit' => 'Bloke adrès IP sa globalman',
	'globalblocking-block-success' => 'Adrès IP sa $1 te bloke avèk siksès nan tout projè Wikimedia yo.
Ou ka desire pou konsilte [[Special:LisBlokajGlobal|lis blokaj global yo]].',
	'globalblocking-block-successsub' => 'Blokaj global reyisi',
	'globalblocking-block-alreadyblocked' => 'Adrès IP sa $1 deja bloke globalman. Ou ka wè blokaj ki deja ekziste a nan [[Special:GlobalBlockList|lis blokaj global yo]].',
	'globalblocking-list' => 'Lis adrès IP ki bloke globalman yo',
	'globalblocking-search-legend' => 'Chache pou yon blokaj global',
	'globalblocking-search-ip' => 'Adrès IP:',
	'globalblocking-search-submit' => 'Chache pou blokaj yo',
	'globalblocking-list-ipinvalid' => "Adrès IP ou t'ap chache a ($1) envalid.
Souple antre yon adrès IP ki valid.",
	'globalblocking-search-errors' => 'Bouskay ou a pa t’ reyisi, paske:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') bloke globalman '''[[Espesyal:Kontribisyon yo/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expirasyon $1',
	'globalblocking-list-anononly' => 'Anonim sèlman',
	'globalblocking-list-unblock' => 'Debloke',
	'globalblocking-list-whitelisted' => 'Te lokalman deaktive pa $1: $2',
	'globalblocking-list-whitelist' => 'estati lokal',
	'globalblocking-unblock-ipinvalid' => 'Adrès IP ($1) ou te antre a envalid.
Silvouplè note ke ou pa kapab antre yon non itilizatè!',
	'globalblocking-unblock-legend' => 'Retire yon blokaj global',
	'globalblocking-unblock-submit' => 'Retire blokaj global',
	'globalblocking-unblock-reason' => 'Rezon:',
	'globalblocking-unblock-unblocked' => "Ou reyisi nan retire blokaj global #$2 sa sou adrès IP '''$1'''",
	'globalblocking-unblock-errors' => 'Ou pa kabap retire yon blokaj global pou adrès IP sa, paske:
$1',
	'globalblocking-unblock-successsub' => 'Blokaj global te retire avèk siksès.',
	'globalblocking-whitelist-legend' => 'Chanje estati local',
	'globalblocking-whitelist-reason' => 'Rezon pou chanjman:',
	'globalblocking-whitelist-status' => 'Estati lokal:',
	'globalblocking-whitelist-statuslabel' => 'Dezame blokaj global sa nan {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Chanje estati lokal',
	'globalblocking-whitelist-whitelisted' => "Ou te dezame avèk siksès blokaj global sa #$2 pou adrès IP '''$1''' nan {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Ou te re-pemèt blokaj global la #$2 sou adrès IP '''$1''' nan {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Estati lokal te chanje avèk siksès',
	'globalblocking-blocked' => "Adrès IP w la te bloke nan tout Wikimedia wikis pa '''\$1''' (''\$2'').
Rezon ki te bay la se ''\"\$3\"''. Tan expirasyon blòkaj la se ''\$4''.",
	'globalblocking-logpage' => 'Lòg blokaj global',
	'globalblocking-block-logentry' => 'globalman bloke [[$1]] avèk yon tan expirasyon $2',
	'globalblocking-unblock-logentry' => 'retire blokaj global la sou [[$1]]',
	'globalblocking-whitelist-logentry' => 'dezame blokaj global la sou [[$1]] lokalman',
	'globalblocking-dewhitelist-logentry' => 're-mete blokaj global sou [[$1]] lokalman',
	'globalblocklist' => 'Lis Adrès IP bloke globalman yo',
	'globalblock' => 'Bloke yon adrès IP globalman',
	'right-globalblock' => 'Fè blokaj global',
	'right-globalunblock' => 'Retire blokaj global yo',
	'right-globalblock-whitelist' => 'Dezame blokaj global yo lokalman',
);

/** Hungarian (Magyar)
 * @author Dani
 * @author Dorgan
 */
$messages['hu'] = array(
	'globalblocking-list' => 'Globálisan blokkolt IP-címek listája',
	'globalblocking-list-expiry' => 'lejárat: $1',
	'globalblocking-unblock-reason' => 'Ok:',
	'globalblocking-whitelist' => 'Globális blokkok helyi állapota',
	'globalblocklist' => 'Globálisan blokkolt IP-címek listája',
	'globalblock' => 'IP-cím globális blokkolása',
	'globalblockstatus' => 'Globális blokkok helyi állapota',
);

/** Interlingua (Interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permitte]] que adresses IP sia [[Special:GlobalBlockList|blocate trans plure wikis]]',
	'globalblocking-block' => 'Blocar globalmente un adresse IP',
	'globalblocking-block-intro' => 'Tu pote usar iste pagina pro blocar un adresse IP in tote le wikis.',
	'globalblocking-block-reason' => 'Motivo pro iste blocada:',
	'globalblocking-block-expiry' => 'Expiration del blocada:',
	'globalblocking-block-expiry-other' => 'Altere tempore de expiration',
	'globalblocking-block-expiry-otherfield' => 'Altere duration:',
	'globalblocking-block-legend' => 'Blocar un usator globalmente',
	'globalblocking-block-options' => 'Optiones:',
	'globalblocking-block-errors' => 'Tu blocada non ha succedite, pro le sequente {{PLURAL:$1|motivo|motivos}}:',
	'globalblocking-block-ipinvalid' => 'Le adresse IP ($1) que tu entrava es invalide.
Per favor nota que tu non pote entrar un nomine de usator!',
	'globalblocking-block-expiryinvalid' => 'Le expiraton que tu entrava ($1) es invalide.',
	'globalblocking-block-submit' => 'Blocar globalmente iste adresse IP',
	'globalblocking-block-success' => 'Le adresse IP $1 ha essite blocate con successo in tote le projectos.',
	'globalblocking-block-successsub' => 'Blocada global succedite',
	'globalblocking-block-alreadyblocked' => 'Le adresse IP $1 es ja blocate globalmente. Tu pote vider le blocada existente in le [[Special:GlobalBlockList|lista de blocadas global]].',
	'globalblocking-block-bigrange' => 'Le intervallo que tu specificava ($1) es troppo grande pro esser blocate. Tu pote blocar, al maximo, 65&nbsp;536 adresses (i.e.: intervallos /16).',
	'globalblocking-list-intro' => 'Isto es un lista de tote le blocadas global actualmente in effecto. Alcun blocadas es marcate como localmente disactivate: isto significa que illos es applicabile in altere sitos, sed un administrator local ha decidite a disactivar los in iste wiki.',
	'globalblocking-list' => 'Lista de adresses IP blocate globalmente',
	'globalblocking-search-legend' => 'Cercar un blocada global',
	'globalblocking-search-ip' => 'Adresse IP:',
	'globalblocking-search-submit' => 'Cercar blocadas',
	'globalblocking-list-ipinvalid' => 'le adresse IP que tu cercava ($1) es invalide.
Per favor entra un adresse IP valide.',
	'globalblocking-search-errors' => 'Tu recerca non ha succedite, pro le sequente {{PLURAL:$1|motivo|motivos}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') blocava globalmente '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiration $1',
	'globalblocking-list-anononly' => 'anon-solmente',
	'globalblocking-list-unblock' => 'remover',
	'globalblocking-list-whitelisted' => 'disactivate localmente per $1: $2',
	'globalblocking-list-whitelist' => 'stato local',
	'globalblocking-goto-block' => 'Blocar globalmente un adresse IP',
	'globalblocking-goto-unblock' => 'Remover un blocada global',
	'globalblocking-goto-status' => 'Cambiar le stato local de un blocada global',
	'globalblocking-return' => 'Retornar al lista de blocadas global',
	'globalblocking-notblocked' => 'Le adresse IP ($1) que tu entrava non es globalmente blocate.',
	'globalblocking-unblock' => 'Remover un blocada global',
	'globalblocking-unblock-ipinvalid' => 'Le adresse IP ($1) que tu entrava es invalide.
Per favor nota que tu non pote entrar un nomine de usator!',
	'globalblocking-unblock-legend' => 'Remover un blocada global',
	'globalblocking-unblock-submit' => 'Remover blocada global',
	'globalblocking-unblock-reason' => 'Motivo:',
	'globalblocking-unblock-unblocked' => "Tu ha removite con successo le blocada global #$2 del adresse IP '''$1'''",
	'globalblocking-unblock-errors' => 'Le remotion del blocada global non ha succedite, pro le sequente {{PLURAL:$1|motivo|motivos}}:',
	'globalblocking-unblock-successsub' => 'Blocada global removite con successo',
	'globalblocking-unblock-subtitle' => 'Remotion de blocada global',
	'globalblocking-unblock-intro' => 'Tu pote usar iste formulario pro remover un blocada global.
[[Special:GlobalBlockList|Clicca hic]] pro retornar al lista de blocadas global.',
	'globalblocking-whitelist' => 'Stato local de blocadas global',
	'globalblocking-whitelist-legend' => 'Cambiar stato local',
	'globalblocking-whitelist-reason' => 'Motivo pro cambio:',
	'globalblocking-whitelist-status' => 'Stato local:',
	'globalblocking-whitelist-statuslabel' => 'Disactivar iste blocada global in {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Cambiar stato local',
	'globalblocking-whitelist-whitelisted' => "Tu ha disactivate con successo le blocada global #$2 del adresse IP '''$1''' in {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Tu ha reactivate con successo le blocada global #$2 del adresse IP '''$1''' in {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Stato local cambiate con successo',
	'globalblocking-whitelist-nochange' => 'Tu non ha cambiate le stato local de iste blocada.
[[Special:GlobalBlockList|Retornar al lista de blocadas global]].',
	'globalblocking-whitelist-errors' => 'Le cambio del stato local de un blocada global non ha succedite, pro le sequente {{PLURAL:$1|motivo|motivos}}:',
	'globalblocking-whitelist-intro' => 'Tu pote usar iste formulario pro modificar le stato local de un blocada global. Si un blocada global es disactivate in iste wiki, le usatores que se connecte a partir del adresse IP in question potera facer modificationes normalmente. [[Special:GlobalBlockList|Clicca hic]] pro returnar al lista de blocadas global.',
	'globalblocking-blocked' => "Tu adresse IP ha essite blocate in tote le wikis per '''\$1''' (''\$2'').
Le motivo date esseva ''\"\$3\"''.
Le blocada ''\$4''.",
	'globalblocking-logpage' => 'Registro de blocadas global',
	'globalblocking-logpagetext' => 'Isto es un registro de blocadas global que ha essite facite e removite in iste wiki.
Il debe esser notate que le blocadas global pote esser facite e removite in altere wikis, e que iste blocadas global pote afficer etiam iste wiki.
Pro vider tote le blocadas global active, tu pote vider le [[Special:GlobalBlockList|lista de blocadas global]].',
	'globalblocking-block-logentry' => 'blocava globalmente [[$1]] con un tempore de expiration de $2',
	'globalblocking-unblock-logentry' => 'removeva blocada global de [[$1]]',
	'globalblocking-whitelist-logentry' => 'disactivava localmente le blocada global de [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'reactivava localmente le blocada global de [[$1]]',
	'globalblocklist' => 'Lista de adresses IP blocate globalmente',
	'globalblock' => 'Blocar globalmente un adresse IP',
	'globalblockstatus' => 'Stato local de blocadas global',
	'removeglobalblock' => 'Remover un blocada global',
	'right-globalblock' => 'Facer blocadas global',
	'right-globalunblock' => 'Remover blocadas global',
	'right-globalblock-whitelist' => 'Disactivar blocadas global localmente',
);

/** Indonesian (Bahasa Indonesia)
 * @author Irwangatot
 * @author Rex
 */
$messages['id'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Memungkinkan]] pemblokiran alamat IP [[Special:GlobalBlockList|sekaligus di banyak wiki]]',
	'globalblocking-block' => 'Memblokir sebuah alamat IP secara global',
	'globalblocking-block-intro' => 'Anda dapat menggunakan halaman ini untuk memblokir sebuah alamat IP di seluruh wiki.',
	'globalblocking-block-reason' => 'Alasan pemblokiran:',
	'globalblocking-block-expiry' => 'Kadaluwarsa:',
	'globalblocking-block-expiry-other' => 'Waktu lain',
	'globalblocking-block-expiry-otherfield' => 'Waktu lain:',
	'globalblocking-block-legend' => 'Memblokir sebuah akun secara global',
	'globalblocking-block-options' => 'Pilihan:',
	'globalblocking-block-errors' => 'Pemblokiran tidak berhasil, atas {{PLURAL:$1|alasan|alasan-alasan}} berikut:',
	'globalblocking-block-ipinvalid' => 'Anda memasukkan alamat IP ($1) yang tidak sah.
Ingat, Anda tidak dapat memasukkan nama pengguna!',
	'globalblocking-block-expiryinvalid' => 'Waktu kadaluwarsa tidak sah ($1).',
	'globalblocking-block-submit' => 'Blokir alamat IP ini secara global',
	'globalblocking-block-success' => 'Alamat IP $1 berhasil diblokir di seluruh proyek.',
	'globalblocking-block-successsub' => 'Pemblokiran global berhasil',
	'globalblocking-block-alreadyblocked' => 'Alamat IP $1 telah diblokir secara global.
Anda dapat melihat [[Special:GlobalBlockList|daftar pemblokiran global]].',
	'globalblocking-block-bigrange' => 'Rentang yang Anda masukkan ($1) terlalu besar untuk diblokir.
Anda dapat memblokir maksimum 65.536 alamat (/16 rentang)',
	'globalblocking-list-intro' => 'Ini adalah daftar seluruh pemblokiran global yang efektif pada saat ini.
Beberapa pemblokiran ditandai sebagai non-aktif pada wiki lokal: ini artinya pemblokiran ini aktif pada situs-situs lain, tapi Pengurus di wiki lokal telah memutuskan untuk menon-aktifkannya di wiki ini.',
	'globalblocking-list' => 'Daftar pemblokiran global alamat IP',
	'globalblocking-search-legend' => 'Pencarian pemblokiran global',
	'globalblocking-search-ip' => 'Alamat IP:',
	'globalblocking-search-submit' => 'Pencarian pemblokiran',
	'globalblocking-list-ipinvalid' => 'Alamat IP yang Anda cari ($1) tidak sah.
Harap masukkan alamat IP yang sah.',
	'globalblocking-search-errors' => 'Pencarian Anda tidak berhasil, untuk {{PLURAL:$1|alasan|alasan-alasan}} berikut:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') memblokir secara global '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'kadaluwarsa $1',
	'globalblocking-list-anononly' => 'hanya pengguna anonim',
	'globalblocking-list-unblock' => 'hapuskan',
	'globalblocking-list-whitelisted' => 'dinon-aktifkan di wiki lokal oleh $1: $2',
	'globalblocking-list-whitelist' => 'status di wiki lokal',
	'globalblocking-goto-block' => 'Memblokir alamat IP secara global',
	'globalblocking-goto-unblock' => 'Menghapuskan pemblokiran global',
	'globalblocking-goto-status' => 'Mengubah status lokal untuk sebuah pemblokiran global',
	'globalblocking-return' => 'Kembali ke daftar pemblokiran global',
	'globalblocking-notblocked' => 'Alamat IP ($1) yang Anda masukkan tidak diblokir secara global.',
	'globalblocking-unblock' => 'Membatalkan pemblokiran global',
	'globalblocking-unblock-ipinvalid' => 'Anda memasukkan alamat IP ($1) yang tidak sah.
Ingat, Anda tidak dapat memasukkan nama pengguna!',
	'globalblocking-unblock-legend' => 'Membatalkan pemblokiran global',
	'globalblocking-unblock-submit' => 'Membatalkan pemblokiran global',
	'globalblocking-unblock-reason' => 'Alasan:',
	'globalblocking-unblock-unblocked' => "Anda telah berhasil membatalkan pemblokiran global #$2 atas alamat IP '''$1'''",
	'globalblocking-unblock-errors' => 'Pembatalan pemblokiran global tidak berhasil, karena {{PLURAL:$1|alasan|alasan-alasan}} berikut:',
	'globalblocking-unblock-successsub' => 'Pemblokiran global berhasil dibatalkan',
	'globalblocking-unblock-subtitle' => 'Membatalkan pemblokiran global',
	'globalblocking-unblock-intro' => 'Anda dapat menggunakan formulir ini untuk membatalkan sebuah pemblokiran global.
[[Special:GlobalBlockList|Klik di sini]] untuk kembali ke daftar pemblokiran global.',
	'globalblocking-whitelist' => 'Status wiki lokal atas pemblokiran global',
	'globalblocking-whitelist-legend' => 'Mengubah status di wiki lokal',
	'globalblocking-whitelist-reason' => 'Alasan perubahan:',
	'globalblocking-whitelist-status' => 'Status di wiki lokal:',
	'globalblocking-whitelist-statuslabel' => 'Menon-aktifkan pemblokiran global ini di {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Mengubah status di wiki lokal',
	'globalblocking-whitelist-whitelisted' => "Anda telah berhasil membatalkan pemblokiran global #$2 atas alamat IP '''$1''' di {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Anda telah berhasil mengaktifkan kembali pemblokiran global #$2 atas alamat IP '''$1''' di {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Status wiki lokal berhasil diubah',
	'globalblocking-whitelist-nochange' => 'Anda tidak mengubah status lokal atas pemblokiran ini.
[[Special:GlobalBlockList|Kembali ke daftar pemblokiran global]].',
	'globalblocking-whitelist-errors' => 'Perubahan atas status lokal dari pemblokiran global tidak berhasil; atas {{PLURAL:$1|alasan|alasan-alasan}} berikut:',
	'globalblocking-whitelist-intro' => 'Anda dapat menggunakan formulir ini untuk menyunting status lokal dari suatu pemblokiran global.
Jika sebuah pemblokiran global dinon-aktifkan di wiki ini, pengguna-pengguna dengan alamat IP tersebut akan dapat menyunting secara normal.
[[Special:GlobalBlockList|Kembali ke daftar pemblokiran global]].',
	'globalblocking-blocked' => "Alamat IP Anda telah diblokir di seluruh wiki oleh '''\$1''' (''\$2'').
Alasan pemblokiran adalah ''\"\$3\"''.
Pemblokiran ''\$4''.",
	'globalblocking-logpage' => 'Log pemblokiran global',
	'globalblocking-logpagetext' => 'Ini adalah log pemblokiran global yang dibuat dan dihapuskan di wiki ini.
Sebagai catatan, pemblokiran global dapat dibuat dan dihapuskan di wiki lain yang akan juga mempengaruhi wiki ini.
Untuk menampilkan seluruh pemblokiran global yang aktif saat ini, Anda dapat melihat [[Special:GlobalBlockList|daftar pemblokiran global]].',
	'globalblocking-block-logentry' => 'memblokir secara global [[$1]] dengan kadaluwarsa $2',
	'globalblocking-unblock-logentry' => 'menghapuskan pemblokiran global atas [[$1]]',
	'globalblocking-whitelist-logentry' => 'menonaktifkan pemblokiran global atas [[$1]] di wiki lokal',
	'globalblocking-dewhitelist-logentry' => 'mengaktifkan kembali pemblokiran global pada [[$1]] di wiki lokal',
	'globalblocklist' => 'Daftar alamat IP yang diblokir secara global',
	'globalblock' => 'Memblokir suatu alamat IP secara global',
	'globalblockstatus' => 'Status pemblokiran global di wiki lokal',
	'removeglobalblock' => 'Menghapuskan pemblokiran global',
	'right-globalblock' => 'Melakukan pemblokiran global',
	'right-globalunblock' => 'Menghapuskan pemblokiran global',
	'right-globalblock-whitelist' => 'Menonaktifkan suatu pemblokiran global di wiki lokal',
);

/** Icelandic (Íslenska)
 * @author S.Örvarr.S
 */
$messages['is'] = array(
	'globalblocking-unblock-reason' => 'Ástæða:',
);

/** Italian (Italiano)
 * @author Darth Kule
 */
$messages['it'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permette]] di [[Special:GlobalBlockList|bloccare su più wiki]] indirizzi IP',
	'globalblocking-block' => 'Blocca globalmente un indirizzo IP',
	'globalblocking-block-intro' => 'È possibile usare questa pagina per bloccare un indirizzo IP su tutte le wiki.',
	'globalblocking-block-reason' => 'Motivo per il blocco:',
	'globalblocking-block-expiry' => 'Scadenza del blocco:',
	'globalblocking-block-expiry-other' => 'Altri tempi di scadenza',
	'globalblocking-block-expiry-otherfield' => 'Durata non in elenco:',
	'globalblocking-block-legend' => 'Blocca un utente globalmente',
	'globalblocking-block-options' => 'Opzioni:',
	'globalblocking-block-errors' => 'Il blocco non è stato eseguito per {{PLURAL:$1|il seguente motivo|i seguenti motivi}}:',
	'globalblocking-block-ipinvalid' => "L'indirizzo IP ($1) che hai inserito non è valido. Fai attenzione al fatto che non puoi inserire un nome utente!",
	'globalblocking-block-expiryinvalid' => 'La scadenza che hai inserito ($1) non è valida.',
	'globalblocking-block-submit' => 'Blocca questo indirizzo IP globalmente',
	'globalblocking-block-success' => "L'indirizzo IP $1 è stato bloccato con successo su tutti i progetti.",
	'globalblocking-block-successsub' => 'Blocco globale eseguito con successo',
	'globalblocking-block-alreadyblocked' => "L'indirizzo IP $1 è già bloccato globalmente. È possibile consultare il blocco attivo nell'[[Special:GlobalBlockList|elenco dei blocchi globali]].",
	'globalblocking-block-bigrange' => 'La classe che hai indicato ($1) è troppo ampia per essere bloccata. È possibile bloccare, al massimo, 65.536 indirizzi (classe /16)',
	'globalblocking-list-intro' => 'Di seguito sono elencati tutti i blocchi globali che sono attualmente attivi. Alcuni blocchi sono segnati come disattivati localmente: ciò significa che questi sono attivi su altri siti, ma un amministratore locale ha deciso di disattivarli su quella wiki.',
	'globalblocking-list' => 'Elenco degli indirizzi IP bloccati globalmente',
	'globalblocking-search-legend' => 'Ricerca un blocco globale',
	'globalblocking-search-ip' => 'Indirizzo IP:',
	'globalblocking-search-submit' => 'Ricerca blocchi',
	'globalblocking-list-ipinvalid' => "L'indirizzo IP che hai cercato ($1) non è valido. Inserisci un indirizzo IP valido.",
	'globalblocking-search-errors' => 'La tua ricerca non ha prodotto risultati per {{PLURAL:$1|il seguente motivo|i seguenti motivi}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') ha bloccato globalmente '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'scadenza del blocco $1',
	'globalblocking-list-anononly' => 'solo anonimi',
	'globalblocking-list-unblock' => 'rimuovi',
	'globalblocking-list-whitelisted' => 'disattivato localmente da $1: $2',
	'globalblocking-list-whitelist' => 'stato locale',
	'globalblocking-goto-block' => 'Blocca globalmente un indirizzo IP',
	'globalblocking-goto-unblock' => 'Rimuovi un blocco globale',
	'globalblocking-goto-status' => 'Cambia stato locale di un blocco globale',
	'globalblocking-return' => "Torna all'elenco dei blocchi globali",
	'globalblocking-notblocked' => "L'indirizzo IP ($1) che hai inserito non è bloccato globalmente.",
	'globalblocking-unblock' => 'Rimuovi un blocco globale',
	'globalblocking-unblock-ipinvalid' => "L'indirizzo IP ($1) che hai inserito non è valido. Fai attenzione al fatto che non puoi inserire un nome utente!",
	'globalblocking-unblock-legend' => 'Rimuovi un blocco globale',
	'globalblocking-unblock-submit' => 'Rimuovi blocco globale',
	'globalblocking-unblock-reason' => 'Motivo del blocco:',
	'globalblocking-unblock-unblocked' => "È stato rimosso con successo il blocco globale #$2 sull'indirizzo IP '''$1'''",
	'globalblocking-unblock-errors' => 'La rimozione del blocco globale che hai richiesto non è stata eseguita per {{PLURAL:$1|il seguente motivo|i seguenti motivi}}:',
	'globalblocking-unblock-successsub' => 'Blocco globale rimosso con successo',
	'globalblocking-unblock-subtitle' => 'Rimozione blocco globale',
	'globalblocking-unblock-intro' => "È possibile usare questo modulo per rimuovere un blocco globale. [[Special:GlobalBlockList|Fai clic qui]] per tornare all'elenco dei blocchi globali.",
	'globalblocking-whitelist' => 'Stato locale dei blocchi globali',
	'globalblocking-whitelist-legend' => 'Cambia stato locale',
	'globalblocking-whitelist-reason' => 'Motivo del cambiamento:',
	'globalblocking-whitelist-status' => 'Stato locale:',
	'globalblocking-whitelist-statuslabel' => 'Disattiva il blocco globale su {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Cambia stato locale',
	'globalblocking-whitelist-whitelisted' => "Hai disattivato con successo il blocco globale #$2 sull'indirizzo IP '''$1''' su {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Hai riabilitato con successo il blocco globale #$2 sull'indirizzo IP '''$1''' su {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Stato locale cambiato con successo',
	'globalblocking-whitelist-nochange' => "Non hai effettuato cambiamenti allo stato locale di questo blocco. [[Special:GlobalBlockList|Torna all'elenco dei blocchi globali]].",
	'globalblocking-whitelist-errors' => 'Il tuo cambiamento allo stato locale di un blocco globale non è stato effettuato per {{PLURAL:$1|il seguente motivo|i seguenti motivi}}:',
	'globalblocking-whitelist-intro' => "È possibile usare questo modulo per modificare lo stato locale di un blocco globale. Se un blocco globale è disattivato su questa wiki, gli utenti che utilizzano l'indirizzo IP colpito saranno in grado di editare normalmente.
[[Special:GlobalBlockList|Fai clic qui]] per tornare all'elenco dei blocchi globali.",
	'globalblocking-blocked' => "Il tuo indirizzo IP è stato bloccato su tutte le wiki da '''\$1''' (''\$2'').
Il motivo fornito è ''\"\$3\"''. Il blocco ''\$4''.",
	'globalblocking-logpage' => 'Log dei blocchi globali',
	'globalblocking-logpagetext' => "Di seguito sono elencati i blocchi globali che sono stati effettuati e rimossi su questa wiki. I blocchi globali possono essere effettuati su altre wiki e questi blocchi globali possono essere validi anche su questa wiki.
Per visualizzare tutti i blocchi globali attivi si veda l'[[Special:GlobalBlockList|elenco dei blocchi globali]].",
	'globalblocking-block-logentry' => 'ha bloccato globalmente [[$1]] con una scadenza di $2',
	'globalblocking-unblock-logentry' => 'ha rimosso il blocco globale su [[$1]]',
	'globalblocking-whitelist-logentry' => 'ha disattivato il blocco globale su [[$1]] localmente',
	'globalblocking-dewhitelist-logentry' => 'ha riabilitato il blocco globale su [[$1]] localmente',
	'globalblocklist' => 'Elenco degli indirizzi IP bloccati globalmente',
	'globalblock' => 'Blocca globalmente un indirizzo IP',
	'globalblockstatus' => 'Stato locale di blocchi globali',
	'removeglobalblock' => 'Rimuovi un blocco globale',
	'right-globalblock' => 'Effettua blocchi globali',
	'right-globalunblock' => 'Rimuove blocchi globali',
	'right-globalblock-whitelist' => 'Disattiva blocchi globali localmente',
);

/** Japanese (日本語)
 * @author Aotake
 * @author Muttley
 */
$messages['ja'] = array(
	'globalblocking-desc' => 'IPアドレスを[[Special:GlobalBlockList|複数のウィキで横断的に]][[Special:GlobalBlock|ブロックする]]',
	'globalblocking-block' => 'IPアドレスをグローバルブロックする',
	'globalblocking-block-intro' => 'このページで全ウィキにおいてIPアドレスをブロックできます。',
	'globalblocking-block-reason' => 'ブロックの理由:',
	'globalblocking-block-expiry' => 'ブロック期限:',
	'globalblocking-block-expiry-other' => 'その他の有効期限',
	'globalblocking-block-expiry-otherfield' => '期間 (その他のとき)',
	'globalblocking-block-legend' => '利用者をグローバルブロックする',
	'globalblocking-block-options' => 'オプション:',
	'globalblocking-block-errors' => '実施しようとしたブロックは以下の理由のために実行できませんでした:',
	'globalblocking-block-ipinvalid' => 'あなたが入力したIPアドレス($1)には誤りがあります。
アカウント名では入力できない点に注意してください！',
	'globalblocking-block-expiryinvalid' => '入力した期限 ($1) に誤りがあります。',
	'globalblocking-block-submit' => 'このIPアドレスをグローバルブロックする',
	'globalblocking-block-success' => 'IPアドレス $1 の全プロジェクトでのブロックに成功しました。',
	'globalblocking-block-successsub' => 'グローバルブロックに成功',
	'globalblocking-block-alreadyblocked' => 'IPアドレス $1 はすでにグローバルブロックされています。現在のブロックの状態については[[Special:GlobalBlockList|グローバルブロック一覧]]で確認できます。',
	'globalblocking-block-bigrange' => '指定したレンジ ($1) が広すぎるためブロックできません。ブロックできるアドレスの最大数は 65,536 (/16 レンジ) です。',
	'globalblocking-list-intro' => 'これは現在有効なグローバルブロックの全リストです。
いくつかは「ローカルで無効」とマークされています。このマークのあるグローバルブロックは他のサイトでは有効ですが、このウィキではローカル管理者が無効とすることにしたことを意味します。',
	'globalblocking-list' => 'グローバルブロックを受けているIPアドレス一覧',
	'globalblocking-search-legend' => 'グローバルブロックの検索',
	'globalblocking-search-ip' => 'IPアドレス:',
	'globalblocking-search-submit' => 'ブロックを検索',
	'globalblocking-list-ipinvalid' => 'あなたが検索したIPアドレス($1)には誤りがあります。
再度有効なIPアドレスを入力してください。',
	'globalblocking-search-errors' => '以下の理由により検索に失敗しました:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') が '''[[Special:Contributions/$4|$4]]''' を全プロジェクトでブロック ''($5)''",
	'globalblocking-list-expiry' => '満了 $1',
	'globalblocking-list-anononly' => '匿名利用者のみ',
	'globalblocking-list-unblock' => '解除',
	'globalblocking-list-whitelisted' => '$1 によりローカルで無効化: $2',
	'globalblocking-list-whitelist' => 'ローカルの状態',
	'globalblocking-goto-block' => 'IPアドレスをグローバルブロックする',
	'globalblocking-goto-unblock' => 'グローバルブロックを解除',
	'globalblocking-goto-status' => 'グローバルブロックのローカルステータスを変更',
	'globalblocking-return' => 'グローバルブロック一覧へ戻る',
	'globalblocking-notblocked' => '入力したIPアドレス ($1) はグローバルブロックを受けていません。',
	'globalblocking-unblock' => 'グローバルブロックを解除する',
	'globalblocking-unblock-ipinvalid' => 'あなたが入力したIPアドレス($1)には誤りがあります。
アカウント名では入力できない点に注意してください！',
	'globalblocking-unblock-legend' => 'グローバルブロックを解除する',
	'globalblocking-unblock-submit' => 'グローバルブロックを解除',
	'globalblocking-unblock-reason' => '理由:',
	'globalblocking-unblock-unblocked' => "IPアドレス '''$1''' に対するグローバルブロック #$2 を解除しました",
	'globalblocking-unblock-errors' => '実施しようとしたグローバルブロックの解除は以下の理由により実行できませんでした:',
	'globalblocking-unblock-successsub' => 'グローバルブロックの解除に成功',
	'globalblocking-unblock-subtitle' => 'グローバルブロックを解除中',
	'globalblocking-unblock-intro' => 'このフォームを使用してグローバルブロックを解除できます。
[[Special:GlobalBlockList|グローバルブロックリストに戻る]]。',
	'globalblocking-whitelist' => 'グローバルブロックのこのウィキでの状況',
	'globalblocking-whitelist-legend' => 'ローカルステータスを変更',
	'globalblocking-whitelist-reason' => '変更理由:',
	'globalblocking-whitelist-status' => 'ローカルステータス:',
	'globalblocking-whitelist-statuslabel' => '{{SITENAME}}でのグローバルブロックを無効にする',
	'globalblocking-whitelist-submit' => 'ローカルステータスを変更する',
	'globalblocking-whitelist-whitelisted' => "{{SITENAME}}におけるIPアドレス '''$1''' のアカウント#$2のグローバルブロックを解除しました。",
	'globalblocking-whitelist-dewhitelisted' => "{{SITENAME}}におけるIPアドレス '''$1''' のアカウント #$2 のグローバルブロックの再有効化に成功しました。",
	'globalblocking-whitelist-successsub' => 'ローカルステータスは正しく変更されました',
	'globalblocking-whitelist-nochange' => 'このブロックのローカルステータスは変更されませんでした。[[Special:GlobalBlockList|グローバルブロックリストに戻る]]。',
	'globalblocking-whitelist-errors' => 'グローバルブロックのローカルステータスの変更に失敗しました。理由は以下の通りです:',
	'globalblocking-whitelist-intro' => 'このフォームを使用してグローバルブロックのローカルステータスを変更できます。
もしグローバルブロックがこのウィキで無効になっている場合は、該当IPアドレスは通常の編集ができるようになります。
[[Special:GlobalBlockList|グローバルブロックリストに戻る]]。',
	'globalblocking-blocked' => "このIPアドレスは、'''$1'''('''$2''')によって全ての関連ウィキプロジェクトからからブロックされています。
理由は'''$3'''です。
ブロック解除予定:'''$4'''",
	'globalblocking-logpage' => 'グローバルブロックのログ',
	'globalblocking-logpagetext' => '以下はこのウィキで実施および解除されたグローバルブロックのログです。グローバルブロックは他のウィキでも実施したり解除したりすることができ、その結果がこのウィキにも及びます。現在有効なグローバルブロックの一覧は[[Special:GlobalBlockList]]を参照してください。',
	'globalblocking-block-logentry' => '[[$1]]はグローバルブロックされました。$2に解除されます。',
	'globalblocking-unblock-logentry' => '[[$1]]へのグローバルブロックを解除しました',
	'globalblocking-whitelist-logentry' => '[[$1]]へのグローバルブロックをローカルで無効にしました',
	'globalblocking-dewhitelist-logentry' => 'ローカルにおける[[$1]]へのグローバルブロックを再有効化する',
	'globalblocklist' => 'グローバルブロックされたIPアドレスのリスト',
	'globalblock' => 'このIPアドレスをグローバルブロックする',
	'globalblockstatus' => 'グローバルブロックのローカルステータス',
	'removeglobalblock' => 'グローバルブロックを解除する',
	'right-globalblock' => '他利用者のグローバルブロック',
	'right-globalunblock' => 'グローバルブロックを解除する',
	'right-globalblock-whitelist' => 'グローバルブロックをローカルで無効にする',
);

/** Javanese (Basa Jawa)
 * @author Meursault2004
 */
$messages['jv'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Marengaké]] alamat-alamat IP [[Special:GlobalBlockList|diblokir sacara lintas wiki]]',
	'globalblocking-block' => 'Blokir alamat IP sacara global',
	'globalblocking-block-intro' => 'Panjenengan bisa nganggo kaca iki kanggo mblokir sawijining alamat IP ing kabèh wiki.',
	'globalblocking-block-reason' => 'Alesan pamblokiran iki:',
	'globalblocking-block-expiry' => 'Kadaluwarsa pamblokiran:',
	'globalblocking-block-expiry-other' => 'Wektu kadaluwarsa liya',
	'globalblocking-block-expiry-otherfield' => 'Wektu liya:',
	'globalblocking-block-legend' => 'Blokir sawijining panganggo sacara global',
	'globalblocking-block-options' => 'Opsi-opsi',
	'globalblocking-block-errors' => 'Blokadené ora suksès, amerga:
$1',
	'globalblocking-block-ipinvalid' => 'AlamatIP sing dilebokaké ($1) iku ora absah.
Tulung digatèkaké yèn panjenengan ora bisa nglebokaké jeneng panganggo!',
	'globalblocking-block-expiryinvalid' => 'Wektu kadaluwarsa sing dilebokaké ($1) ora absah.',
	'globalblocking-block-submit' => 'Blokir alamat IP iki sacara global',
	'globalblocking-block-success' => 'Alamat IP $1 bisa diblokir sacara suksès ing kabèh proyèk Wikimedia.
Panjenengan mbok-menawa kersa mirsani [[Special:GlobalBlockList|daftar blokade global]].',
	'globalblocking-block-successsub' => 'Pamblokiran global bisa kasil suksès',
	'globalblocking-block-alreadyblocked' => 'Alamat IP $1 wis diblokir sacara global. Panjenengan bisa ndeleng blokade sing ana ing kaca [[Special:GlobalBlockList|daftar blokade global]].',
	'globalblocking-list' => 'Daftar alamat-alamat IP sing diblokir sacara global',
	'globalblocking-search-legend' => 'Nggolèki blokade global',
	'globalblocking-search-ip' => 'Alamat IP:',
	'globalblocking-search-submit' => 'Nggolèki blokade',
	'globalblocking-list-ipinvalid' => 'Alamat IP sing digolèki ($1) iku ora absah.
Tulung lebokna alamat IP sing absah.',
	'globalblocking-search-errors' => 'Panggolèkan panjenengan ora ana kasilé, amarga:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') sacara global mblokir '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'kadaluwarsa $1',
	'globalblocking-list-anononly' => 'anon-waé',
	'globalblocking-list-unblock' => 'batal blokir',
	'globalblocking-list-whitelisted' => 'dijabel sacara lokal déning $1: $2',
	'globalblocking-list-whitelist' => 'status lokal',
	'globalblocking-unblock-ipinvalid' => 'AlamatIP sing dilebokaké ($1) iku ora absah.
Tulung digatèkaké yèn panjenengan ora bisa nglebokaké jeneng panganggo!',
	'globalblocking-unblock-legend' => 'Ilangana sawijining pamblokiran global',
	'globalblocking-unblock-submit' => 'Ilangana pamblokiran global',
	'globalblocking-unblock-reason' => 'Alesan:',
	'globalblocking-unblock-unblocked' => "Panjenengan sacara suksès ngilangi blokade global #$2 ing alamat IP '''$1'''",
	'globalblocking-unblock-errors' => 'Panjenengan ora bisa ngilangi blokade global kanggo alamat IP iku, amerga:
$1',
	'globalblocking-unblock-successsub' => 'Blokade global bisa dibatalaké',
	'globalblocking-whitelist-legend' => 'Ganti status lokal',
	'globalblocking-whitelist-reason' => 'Alesané diganti:',
	'globalblocking-whitelist-status' => 'Status lokal:',
	'globalblocking-whitelist-statuslabel' => 'Batalna pamblokiran global iki ing {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Ngganti status lokal',
	'globalblocking-whitelist-whitelisted' => "Panjenengan sacara suksès njabel blokade global #$2 ing alamat IP '''$1''' ing {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Panjenengan sacara suksès blokade global #$2 ing alamat IP '''$1''' ing {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Status lokal kasil diganti',
	'globalblocking-blocked' => "Alamat IP panjenengan diblokir ing kabèh wiki Wikimedia déning '''\$1''' (''\$2'').
Alesan sing diwènèhaké yaiku ''\"\$3\"''. Blokade iki bakal kadaluwarsa ing ''\$4''.",
	'globalblocking-logpage' => 'Log pamblokiran global',
	'globalblocking-block-logentry' => 'diblokir sacara global [[$1]] mawa wektu kadaluwarsa $2',
	'globalblocking-unblock-logentry' => 'jabelen blokade global ing [[$1]]',
	'globalblocking-whitelist-logentry' => 'njabel blokade global ing [[$1]] sacara lokal',
	'globalblocking-dewhitelist-logentry' => 'trapna ulang blokade global ing [[$1]] sacara lokal',
	'globalblocklist' => 'Tuduhna daftar alamat-alamat IP sing diblokir sacara global',
	'globalblock' => 'Mblokir alamat IP sacara global',
	'right-globalblock' => 'Nggawé pamblokiran global',
	'right-globalunblock' => 'Ilangana pamblokiran global',
	'right-globalblock-whitelist' => 'Jabel blokade global sacara lokal',
);

/** Georgian (ქართული)
 * @author Malafaya
 */
$messages['ka'] = array(
	'globalblocking-unblock-reason' => 'მიზეზი:',
);

/** Khmer (ភាសាខ្មែរ)
 * @author Lovekhmer
 * @author គីមស៊្រុន
 */
$messages['km'] = array(
	'globalblocking-block-intro' => 'អ្នកអាចប្រើប្រាស់ទំព័រនេះដើម្បីហាមឃាត់អាសយដ្ឋាន IP នៅគ្រប់វិគីទាំងអស់។',
	'globalblocking-block-reason' => 'មូលហេតុនៃការហាមឃាត់នេះ:',
	'globalblocking-block-expiry' => 'ពេលផុតកំនត់នៃការហាមឃាត់:',
	'globalblocking-block-options' => 'ជំរើសនានា៖',
	'globalblocking-search-ip' => 'អាសយដ្ឋានIP:',
	'globalblocking-search-submit' => 'ស្វែងរកចំពោះការហាមឃាត់',
	'globalblocking-list-expiry' => 'ផុតកំនត់ $1',
	'globalblocking-list-anononly' => 'អនាមិកជនប៉ុណ្ណោះ',
	'globalblocking-list-unblock' => 'ដកហូត',
	'globalblocking-unblock-reason' => 'មូលហេតុ៖',
	'globalblocking-whitelist-reason' => 'មូលហេតុផ្លាស់ប្តូរ:',
	'globalblocking-logpage' => 'កំនត់ហេតុនៃការហាមឃាត់ជាសាកល',
);

/** Korean (한국어)
 * @author Albamhandae
 * @author Ficell
 */
$messages['ko'] = array(
	'globalblocking-block' => 'IP 주소를 모든 위키에서 차단',
	'globalblocking-list-intro' => '현재 유효한 전체 차단의 목록입니다. 전체 차단은 로컬의 관리자의 권한으로 무효화 할 수 있습니다. 단 로컬에서 무효화하더라도 다른 위키에서는 차단 상태가 지속됩니다.',
	'globalblocking-list' => '모든 위키에서 차단된 IP 목록',
	'globalblocking-search-legend' => '전체 차단 찾기',
	'globalblocking-search-ip' => 'IP 주소:',
	'globalblocking-search-submit' => '차단 찾기',
	'globalblocking-list-blockitem' => "$1: '''$2''' ($3) 이(가) '''[[Special:Contributions/$4|$4]]''' 을(를) 전체 위키에서 차단하였습니다. ($5)",
	'globalblocking-goto-status' => '전체 차단의 로컬 상태 바꾸기',
	'globalblocking-unblock' => '전체 차단 제거',
	'globalblocking-whitelist' => '전체 차단의 로컬 상태',
	'globalblocking-logpage' => '전체 위키 차단 기록',
	'globalblocklist' => '모든 위키에서 차단된 IP 목록',
	'globalblock' => '전체 위키에서 IP 주소를 차단',
	'globalblockstatus' => '전체 차단의 로컬 상태',
);

/** Ripoarisch (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Älaup]] IP Addresses ze [[Special:GlobalBlockList|sperre övver ettlijje Wikis]].',
	'globalblocking-block' => 'En IP-Address en alle Wikis sperre',
	'globalblocking-block-intro' => 'He op dä Sigg kans De IP-Address en alle Wikis sperre.',
	'globalblocking-block-reason' => 'Dä Jrond för et Sperre:',
	'globalblocking-block-expiry' => 'De Door:',
	'globalblocking-block-expiry-other' => 'En ander Dooer',
	'globalblocking-block-expiry-otherfield' => 'Ander Dooer (op änglesch):',
	'globalblocking-block-legend' => 'Don ene Metmaacher en alle Wikis sperre',
	'globalblocking-block-errors' => 'Dat Sperre hät nit jeklapp.
{{PLURAL:$1|Der Jrond:|De Jrönd:|Woröm, wesse mer nit.}}',
	'globalblocking-block-ipinvalid' => 'Do häs en kapodde IP-Address ($1) aanjejovve.
Denk draan, dat De kein Name fun Metmaacher he aanjevve kanns.',
	'globalblocking-block-expiryinvalid' => 'De Door ($1) es Kappes.',
	'globalblocking-block-success' => 'Di IP adress „$1“ eß jetz en alle Wikis jesperrt. <!--
Loor Der de [[Special:GlobalBlockList|Leß med jlobale Sperre]] aan, wann de mieh esu en Sperre fenge wells. -->',
	'globalblocking-search-ip' => 'IP Address:',
	'globalblocking-list-anononly' => 'nor namelose',
	'globalblocking-unblock-reason' => 'Aanlass:',
	'globalblocking-blocked' => "Ding IP_Address es in alle Wikis jespert woode.
Dä '''$1''' (''$2'') hädd_et jedonn.
Sing Jrund wohr: „''$3''“.
De Sperr bliet bestonn bes: ''$4''.",
	'globalblocking-logpage' => 'Logboch fum IP-Adresse en alle Wikis sperre',
	'globalblocklist' => 'Less met dä en alle Wikis jesperrte IP-Addresse',
	'globalblock' => 'Don en IP-Address en alle Wikis sperre',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Erlaabt et]] IP-Adressen op [[Special:GlobalBlockList|méi Wikien mateneen ze spären]]',
	'globalblocking-block' => 'Eng IP-Adress global spären',
	'globalblocking-block-intro' => 'Dir kënnt dës Säit benotzen fir eng IP-Adress op alle Wikien ze spären.',
	'globalblocking-block-reason' => 'Grond fir dës Spär:',
	'globalblocking-block-expiry' => 'Dauer vun der Spär:',
	'globalblocking-block-expiry-other' => 'Aner Dauer vun der Spär',
	'globalblocking-block-expiry-otherfield' => 'Aner Dauer:',
	'globalblocking-block-legend' => 'E Benotzer global spären',
	'globalblocking-block-options' => 'Optiounen:',
	'globalblocking-block-errors' => "D'Spär huet net fonctionnéiert, aus {{PLURAL:$1|dësem Grond|dëse Grënn}}:",
	'globalblocking-block-ipinvalid' => 'Dir hutt eng ongëlteg IP-Adress ($1) aginn.
Denkt w.e.g. drun datt Dir och e Benotzernumm agi kënnt!',
	'globalblocking-block-expiryinvalid' => "D'Dauer déi dir aginn hutt ($1) ass ongëlteg.",
	'globalblocking-block-submit' => 'Dës IP-Adress global spären',
	'globalblocking-block-success' => "D'IP-Adress $1 gouf op alle Wikimedia-Projete gespaart.",
	'globalblocking-block-successsub' => 'Global gespaart',
	'globalblocking-block-alreadyblocked' => "D'IP-Adress $1 ass scho global gespaart. Dir kënnt d'Spären op der [[Special:GlobalBlockList|Lëscht vun de globale Späre]] kucken.",
	'globalblocking-block-bigrange' => 'De Beräich den dir uginn hutt ($1) ass ze grouss fir ze spären. Dir kënnt maximal 65.536 Adressen (/16 Beräicher) spären',
	'globalblocking-list-intro' => 'Dëst ass eng Lëscht vun alle globale Spärendéi elo aktiv sinn.
E puer Spären sinn lokal ausgeschalt: dat heescht si si just op anere Site gëlteg, well e lokalen Administrateur entscheed huet se op dëser Wiki ze desaktivéieren.',
	'globalblocking-list' => 'Lëscht vun de global gespaarten IP-Adressen',
	'globalblocking-search-legend' => 'Sich no enger globaler Spär',
	'globalblocking-search-ip' => 'IP-Adress:',
	'globalblocking-search-submit' => 'Späre sichen',
	'globalblocking-list-ipinvalid' => "D'IP-adress no däer Dir Gesicht hutt ($1) ass net korrekt.
Gitt w.e.g eng korrekt IP-Adress an.",
	'globalblocking-search-errors' => 'Bäi ärer Sich gouf, aus {{PLURAL:$1|dësem Grond|dëse Grënn}} näischt fonnt:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (vu(n) ''$3'') huet'''[[Special:Contributions/$4|$4]]''' global gespaart ''($5)''",
	'globalblocking-list-expiry' => 'Dauer vun der Spär $1',
	'globalblocking-list-anononly' => 'nëmmen anonym Benotzer',
	'globalblocking-list-unblock' => 'Spär ophiewen',
	'globalblocking-list-whitelisted' => 'lokal ausgeschalt vum $1: $2',
	'globalblocking-list-whitelist' => 'lokale Status',
	'globalblocking-goto-block' => 'Eng IP-Adress global spären',
	'globalblocking-goto-unblock' => 'Eng global Spär ophiewen',
	'globalblocking-goto-status' => 'Lokale Status vun enger globaler Spär änneren',
	'globalblocking-return' => "Zréck op d'Lëscht vun de globale Spären",
	'globalblocking-notblocked' => 'Déi IP-Adress ($1) déi Dir aginn hutt ass net global gespaart.',
	'globalblocking-unblock' => 'Eng global Spär ophiewen',
	'globalblocking-unblock-ipinvalid' => 'Dir hutt eng ongëlteg IP-Adress ($1) aginn.
Denkt w.e.g. drun datt Dir och e Benotzernumm agi kënnt!',
	'globalblocking-unblock-legend' => 'Eng global Spär ophiewen',
	'globalblocking-unblock-submit' => 'Global Spär ophiewen',
	'globalblocking-unblock-reason' => 'Grond:',
	'globalblocking-unblock-unblocked' => "Dir hutt d'global Spär #$2 vun der IP-Adress '''$1''' opgehuewen",
	'globalblocking-unblock-errors' => "Dir kënnt d'global Spär fir déi IP-Adress net ophiewen, aus {{PLURAL:$1|dësem Grond|dëse Grënn}}:",
	'globalblocking-unblock-successsub' => 'Global Spär ass opgehuewen',
	'globalblocking-unblock-subtitle' => 'Global Spär gëtt opgehuewen',
	'globalblocking-unblock-intro' => "Dir kënnt dëse Formulaire benotze fir eng global Spär opzehiewen.
[[Special:GlobalBlockList|Klickt hei]] fir zréck op d'Lëscht vun de globale Spären.",
	'globalblocking-whitelist' => 'Lokale Statut vun e globale Spären',
	'globalblocking-whitelist-legend' => 'De lokale Status änneren',
	'globalblocking-whitelist-reason' => 'Grond vun der Ännerung:',
	'globalblocking-whitelist-status' => 'Lokale Status:',
	'globalblocking-whitelist-statuslabel' => 'Dës global Spär op {{SITENAME}} ophiewen',
	'globalblocking-whitelist-submit' => 'De globale Status änneren',
	'globalblocking-whitelist-whitelisted' => "Dir hutt d'global Spär #$2 vun der IP-Adress '''$1''' op {{SITENAME}} opgehiuewen.",
	'globalblocking-whitelist-dewhitelisted' => "Dir hutt d'global Spär #$2 vun der IP-Adress '''$1''' op {{SITENAME}} nees aktivéiert.",
	'globalblocking-whitelist-successsub' => 'De lokale Status gouf geännert',
	'globalblocking-whitelist-nochange' => "Dir hutt de lokale Status vun dëser Spär net geännert.
[[Special:GlobalBlockList|Zréck op d'Lëscht vun de globale Spären]].",
	'globalblocking-blocked' => "Är IP-Adress gouf op alle Wikimedia Wikie vum '''\$1''' (''\$2'') gespaart.
De Grond den ugi gouf war ''\"\$3\"''.
De Beräich ''\$4''.",
	'globalblocking-logpage' => 'Lëscht vun de globale Spären',
	'globalblocking-block-logentry' => '[[$1]] gouf global gespaart fir $2',
	'globalblocking-unblock-logentry' => 'global Spär vum [[$1]] opgehuewen',
	'globalblocking-whitelist-logentry' => 'huet déi global Spär vum [[$1]] lokal ausgeschalt',
	'globalblocking-dewhitelist-logentry' => 'huet déi global Spär vun [[$1]] lokal nees aktivéiert',
	'globalblocklist' => 'Lëscht vun de global gespaarten IP-Adressen',
	'globalblock' => 'Eng IP-Adress global spären',
	'globalblockstatus' => 'Lokale Statut vu globale Spären',
	'removeglobalblock' => 'Eng global Spär ophiewen',
	'right-globalblock' => 'Benotzer global spären',
	'right-globalunblock' => 'Global Spären ophiewen',
	'right-globalblock-whitelist' => 'Global Späre lokal ausschalten',
);

/** Malayalam (മലയാളം)
 * @author Shijualex
 */
$messages['ml'] = array(
	'globalblocking-block' => 'ഒരു ഐപി വിലാസത്തെ ആഗോളമായി തടയുക',
	'globalblocking-block-intro' => 'ഒരു ഐപി വിലാസത്തെ എല്ലാ വിക്കികളിലും നിരോധിക്കുവാന്‍ താങ്കള്‍ക്കു ഈ താള്‍ ഉപയോഗിക്കാം.',
	'globalblocking-block-reason' => 'ഐപി വിലാസം തടയുവാനുള്ള കാരണം:',
	'globalblocking-block-expiry' => 'തടയലിന്റെ കാലാവധി:',
	'globalblocking-block-expiry-other' => 'മറ്റ് കാലാവധി',
	'globalblocking-block-expiry-otherfield' => 'മറ്റ് കാലാവധി:',
	'globalblocking-block-legend' => 'ഒരു ഉപയോക്താവിനെ ആഗോളമായി തടയുക',
	'globalblocking-block-errors' => 'തടയല്‍ പരാജയപ്പെട്ടു, കാരണം: 
$1',
	'globalblocking-block-ipinvalid' => 'താങ്കള്‍ കൊടുത്ത ഐപി വിലാസം ($1) അസാധുവാണ്‌. 
താങ്കള്‍ക്കു ഇവിടെ ഒരു ഉപയോക്തൃനാമം കൊടുക്കുവാന്‍ പറ്റില്ല എന്നതു പ്രത്യേകം ശ്രദ്ധിക്കുക.',
	'globalblocking-block-expiryinvalid' => 'താങ്കള്‍ കൊടുത്ത കാലാവധി ($1) അസാധുവാണ്‌.',
	'globalblocking-block-submit' => 'ഈ ഐപിവിലാസത്തെ ആഗോളമായി തടയുക',
	'globalblocking-block-successsub' => 'ആഗോള തടയല്‍ വിജയകരം',
	'globalblocking-list' => 'ആഗോളമായി തടയപ്പെട്ട ഐപി വിലാസങ്ങള്‍',
	'globalblocking-search-legend' => 'ആഗോള തടയലിന്റെ വിവരത്തിനായി തിരയുക',
	'globalblocking-search-ip' => 'ഐപി വിലാസം:',
	'globalblocking-search-submit' => 'തടയലിന്റെ വിവരങ്ങള്‍ തിരയുക',
	'globalblocking-list-expiry' => 'കാലാവധി $1',
	'globalblocking-list-anononly' => 'അജ്ഞാത ഉപയോക്താക്കളെ മാത്രം',
	'globalblocking-list-unblock' => 'സ്വതന്ത്രമാക്കുക',
	'globalblocking-list-whitelisted' => '$1 ഇതിനെ പ്രാദേശികമായി നിര്‍‌വീര്യമാക്കിയിക്കുന്നു: $2',
	'globalblocking-list-whitelist' => 'പ്രാദേശിക സ്ഥിതി',
	'globalblocking-unblock-ipinvalid' => 'താങ്കള്‍ കൊടുത്ത ഐപി വിലാസം ($1) അസാധുവാണ്‌. 
താങ്കള്‍ക്കു ഇവിടെ ഒരു ഉപയോക്തൃനാമം കൊടുക്കുവാന്‍ പറ്റില്ല എന്നതു പ്രത്യേകം ശ്രദ്ധിക്കുക.',
	'globalblocking-unblock-legend' => 'ആഗോള ബ്ലോക്ക് മാറ്റുക',
	'globalblocking-unblock-submit' => 'ആഗോള ബ്ലോക്ക് മാറ്റുക',
	'globalblocking-unblock-reason' => 'കാരണം:',
	'globalblocking-unblock-unblocked' => "'''$1''' എന്ന ഐപി വിലാസത്തിന്മേലുള്ള #$2 എന്ന ആഗോള ബ്ലോക്ക് താങ്കള്‍ വിജയകരമായി ഒഴിവാക്കിയിരിക്കുന്നു",
	'globalblocking-unblock-errors' => 'ഈ ഐപി വിലാസത്തിന്മേലുള്ള ആഗോള ബ്ലോക്ക് ഒഴിവാക്കാന്‍ താങ്കള്‍ക്ക് പറ്റില്ല, അതിന്റെ കാരണം: $1',
	'globalblocking-unblock-successsub' => 'ആഗോള ബ്ലോക്ക് വിജയകരമായി നീക്കിയിരിക്കുന്നു',
	'globalblocking-whitelist-legend' => 'പ്രാദേശിക സ്ഥിതി മാറ്റുക',
	'globalblocking-whitelist-reason' => 'മാറ്റം വരുത്താനുള്ള കാരണം:',
	'globalblocking-whitelist-status' => 'പ്രാദേശിക സ്ഥിതി:',
	'globalblocking-whitelist-statuslabel' => '{{SITENAME}} സം‌രംഭത്തില്‍ ആഗോളബ്ലോക്ക് ഡിസേബിള്‍ ചെയ്യുക',
	'globalblocking-whitelist-submit' => 'പ്രാദേശിക സ്ഥിതി മാറ്റുക',
	'globalblocking-whitelist-whitelisted' => "'''$1''' എന്ന ഐപി വിലാസത്തിന്റെ #$2 എന്ന ആഗോളബ്ലോക്ക് {{SITENAME}} സം‌രംഭത്തില്‍ വിജയകരമായി പ്രവര്‍ത്തനരഹിതമാക്കിയിരിക്കുന്നു",
	'globalblocking-whitelist-dewhitelisted' => "'''$1''' എന്ന ഐപി വിലാസത്തിന്റെ #$2 എന്ന ആഗോളബ്ലോക്ക് {{SITENAME}} സം‌രംഭത്തില്‍ വിജയകരമായി പ്രവര്‍ത്തനയോഗ്യമാക്കിയിരിക്കുന്നു.",
	'globalblocking-whitelist-successsub' => 'പ്രാദേശിക സ്ഥിതി വിജയകരമായി മാറ്റിയിരിക്കുന്നു',
	'globalblocking-blocked' => "താങ്കളുടെ ഐപി വിലാസം എല്ലാ വിക്കിമീഡിയ സം‌രംഭങ്ങളിലും '''\$1''' (''\$2'') തടഞ്ഞിരിക്കുന്നു. അതിനു സൂചിപ്പിച്ച കാരണം ''\"\$3\"'' ആണ്‌. ബ്ലോക്കിന്റെ കാലാവധി തീരുന്നത് ''\$4''.",
	'globalblocking-logpage' => 'ആഗോള തടയലിന്റെ പ്രവര്‍ത്തനരേഖ',
	'globalblocking-block-logentry' => '[[$1]]നെ $2 കാലവധിയോടെ ആഗോള ബ്ലോക്ക് ചെയ്തിരിക്കുന്നു.',
	'globalblocking-unblock-logentry' => '[[$1]]നു മേലുള്ള ആഗോള ബ്ലോക്ക് ഒഴിവാക്കിയിരിക്കുന്നു',
	'globalblocking-whitelist-logentry' => '[[$1]] നു മേലുള്ള ആഗോള ബ്ലോക്ക് പ്രാദേശികമായി ഒഴിവാക്കിയിരിക്കുന്നു',
	'globalblocklist' => 'ആഗോളമായി തടയപ്പെട്ട ഐപിവിലാസങ്ങള്‍ പ്രദര്‍ശിപ്പിക്കുക',
	'globalblock' => 'ഒരു ഐപി വിലാസത്തെ ആഗോളമായി തടയുക',
	'right-globalblock' => 'ആഗോള തടയല്‍ നടത്തുക',
	'right-globalunblock' => 'ആഗോള ബ്ലോക്ക് മാറ്റുക',
	'right-globalblock-whitelist' => 'ആഗോള തടയലിനെ പ്രാദേശികമായി നിര്‍‌വീര്യമാക്കുക',
);

/** Marathi (मराठी)
 * @author Kaustubh
 */
$messages['mr'] = array(
	'globalblocking-desc' => 'आइपी अंकपत्त्याला [[Special:GlobalBlockList|अनेक विकिंवर ब्लॉक]] करण्याची [[Special:GlobalBlock|परवानगी]] देतो.',
	'globalblocking-block' => 'आयपी अंकपत्ता वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-intro' => 'तुम्ही हे पान वापरून एखाद्या आयपी अंकपत्त्याला सर्व विकिंवर ब्लॉक करू शकता.',
	'globalblocking-block-reason' => 'या ब्लॉक करीता कारण:',
	'globalblocking-block-expiry' => 'ब्लॉक समाप्ती:',
	'globalblocking-block-expiry-other' => 'इतर समाप्ती वेळ',
	'globalblocking-block-expiry-otherfield' => 'इतर वेळ:',
	'globalblocking-block-legend' => 'एक सदस्य वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-options' => 'विकल्प',
	'globalblocking-block-errors' => 'ब्लॉक अयशस्वी झालेला आहे, कारण:
$1',
	'globalblocking-block-ipinvalid' => 'तुम्ही दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया नोंद घ्या की तुम्ही सदस्य नाव देऊ शकत नाही!',
	'globalblocking-block-expiryinvalid' => 'तुम्ही दिलेली समाप्तीची वेळ ($1) अयोग्य आहे.',
	'globalblocking-block-submit' => 'ह्या आयपी अंकपत्त्याला वैश्विक पातळीवर ब्लॉक करा',
	'globalblocking-block-success' => '$1 या आयपी अंकपत्त्याला सर्व विकिंवर यशस्वीरित्या ब्लॉक करण्यात आलेले आहे.
तुम्ही कदाचित [[Special:GlobalBlockList|वैश्विक ब्लॉक्सची यादी]] पाहू इच्छिता.',
	'globalblocking-block-successsub' => 'वैश्विक ब्लॉक यशस्वी',
	'globalblocking-block-alreadyblocked' => '$1 हा आयपी अंकपत्ता अगोदरच ब्लॉक केलेला आहे. तुम्ही अस्तित्वात असलेले ब्लॉक [[Special:GlobalBlockList|वैश्विक ब्लॉकच्या यादीत]] पाहू शकता.',
	'globalblocking-block-bigrange' => 'तुम्ही दिलेली रेंज ($1) ही ब्लॉक करण्यासाठी खूप मोठी आहे. तुम्ही एकावेळी जास्तीत जास्त ६५,५३६ पत्ते ब्लॉक करू शकता (/१६ रेंज)',
	'globalblocking-list' => 'वैश्विक पातळीवर ब्लॉक केलेले आयपी अंकपत्ते',
	'globalblocking-search-legend' => 'एखाद्या वैश्विक ब्लॉक ला शोधा',
	'globalblocking-search-ip' => 'आयपी अंकपत्ता:',
	'globalblocking-search-submit' => 'ब्लॉक साठी शोध',
	'globalblocking-list-ipinvalid' => 'तुम्ही शोधायला दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया योग्य आयपी अंकपत्ता द्या.',
	'globalblocking-search-errors' => 'तुमचा शोध अयशस्वी झालेला आहे, कारण:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') वैश्विक पातळीवर ब्लॉक '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'समाप्ती $1',
	'globalblocking-list-anononly' => 'फक्त-अनामिक',
	'globalblocking-list-unblock' => 'अनब्लॉक',
	'globalblocking-list-whitelisted' => '$1 ने स्थानिक पातळीवर रद्द केले: $2',
	'globalblocking-list-whitelist' => 'स्थानिक स्थिती',
	'globalblocking-unblock-ipinvalid' => 'तुम्ही दिलेला आयपी अंकपत्ता ($1) अयोग्य आहे.
कृपया नोंद घ्या की तुम्ही सदस्य नाव वापरू शकत नाही!',
	'globalblocking-unblock-legend' => 'एक वैश्विक ब्लॉक काढा',
	'globalblocking-unblock-submit' => 'वैश्विक ब्लॉक काढा',
	'globalblocking-unblock-reason' => 'कारण:',
	'globalblocking-unblock-unblocked' => "तुम्ही आयपी अंकपत्ता '''$1''' वर असणारा वैश्विक ब्लॉक #$2 यशस्वीरित्या काढलेला आहे",
	'globalblocking-unblock-errors' => 'तुम्ही या आयपी अंकपत्त्यावरील वैश्विक ब्लॉक काढू शकत नाही, कारण:
$1',
	'globalblocking-unblock-successsub' => 'वैश्विक ब्लॉक काढलेला आहे',
	'globalblocking-unblock-subtitle' => 'वैश्विक ब्लॉक काढत आहे',
	'globalblocking-whitelist-legend' => 'स्थानिक स्थिती बदला',
	'globalblocking-whitelist-reason' => 'बदलांसाठीचे कारण:',
	'globalblocking-whitelist-status' => 'स्थानिक स्थिती:',
	'globalblocking-whitelist-statuslabel' => '{{SITENAME}} वर हा वैश्विक ब्लॉक रद्द करा',
	'globalblocking-whitelist-submit' => 'स्थानिक स्थिती बदला',
	'globalblocking-whitelist-whitelisted' => "तुम्ही '''$1''' या अंकपत्त्याचा वैश्विक ब्लॉक #$2 {{SITENAME}} वर रद्द केलेला आहे.",
	'globalblocking-whitelist-dewhitelisted' => "तुम्ही '''$1''' या अंकपत्त्याचा वैश्विक ब्लॉक #$2 {{SITENAME}} वर पुन्हा दिलेला आहे.",
	'globalblocking-whitelist-successsub' => 'स्थानिक स्थिती बदलली',
	'globalblocking-blocked' => "तुमचा आयपी अंकपत्ता सर्व विकिमीडिया विकिंवर '''\$1''' (''\$2'') ने ब्लॉक केलेला आहे.
यासाठी ''\"\$3\"'' हे कारण दिलेले आहे. या ब्लॉक ची समाप्ती ''\$4'' आहे.",
	'globalblocking-logpage' => 'वैश्विक ब्लॉक सूची',
	'globalblocking-block-logentry' => '$2 हा समाप्ती कालावधी देऊन [[$1]] ला वैश्विक पातळीवर ब्लॉक केले',
	'globalblocking-unblock-logentry' => '[[$1]] वरील वैश्विक ब्लॉक काढला',
	'globalblocking-whitelist-logentry' => '[[$1]] वरचा वैश्विक ब्लॉक स्थानिक पातळीवर रद्द केला',
	'globalblocking-dewhitelist-logentry' => '[[$1]] वरचा वैश्विक ब्लॉक स्थानिक पातळीवर पुन्हा दिला',
	'globalblocklist' => 'वैश्विक पातळीवर ब्लॉक केलेल्या आयपी अंकपत्त्यांची यादी',
	'globalblock' => 'आयपी अंकपत्त्याला वैश्विक पातळीवर ब्लॉक करा',
	'right-globalblock' => 'वैश्विक ब्लॉक तयार करा',
	'right-globalunblock' => 'वैश्विक ब्लॉक काढून टाका',
	'right-globalblock-whitelist' => 'वैश्विक ब्लॉक स्थानिक पातळीवर रद्द करा',
);

/** Malay (Bahasa Melayu)
 * @author Aviator
 */
$messages['ms'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Membolehkan]] sekatan alamat IP di [[Special:GlobalBlockList|pelbagai wiki]] sekaligus',
	'globalblocking-block' => 'Sekat alamat IP di semua wiki',
	'globalblocking-block-intro' => 'Anda boleh menggunakan laman khas ini untuk menyekat alamat IP di semua wiki.',
	'globalblocking-block-reason' => 'Sebab sekatan ini:',
	'globalblocking-block-expiry' => 'Tamat:',
	'globalblocking-block-expiry-other' => 'Waktu tamat lain',
	'globalblocking-block-expiry-otherfield' => 'Waktu lain:',
	'globalblocking-block-legend' => 'Sekat pengguna di semua wiki',
	'globalblocking-block-options' => 'Pilihan:',
	'globalblocking-block-errors' => 'Sekatan anda tidak dapat dilakukan kerana {{PLURAL:$1|sebab|sebab-sebab}} berikut:',
	'globalblocking-block-ipinvalid' => 'Alamat IP tersebut ($1) tidak sah.
Sila ambil perhatian bahawa anda tidak boleh menyatakan nama pengguna!',
	'globalblocking-block-expiryinvalid' => 'Tarikh tamat yang anda nyatakan ($1) tidak sah.',
	'globalblocking-block-submit' => 'Sekat alamat IP ini di semua wiki',
	'globalblocking-block-success' => 'Alamat IP $1 telah disekat di semua projek wiki.',
	'globalblocking-block-successsub' => 'Sekatan sejagat berjaya',
	'globalblocking-block-alreadyblocked' => 'Alamat IP $1 telah pun disekat di semua wiki.
Anda boleh melihat sekatan ini di [[Special:GlobalBlockList|senarai sekatan sejagat]].',
	'globalblocking-block-bigrange' => 'Julat yang anda nyatakan ($1) terlalu besar.
Anda hanya boleh menyekat sehingga 65,536 alamat (julat /16)',
	'globalblocking-list-intro' => 'Berikut ialah senarai sekatan sejagat yang sedang berkuat kuasa.
Sesetengah sekatan telah dimatikan di wiki tempatan. Dalam kata lain, sekatan itu berkuat kuasa di wiki-wiki lain tetapi pentadbir tempatan telah memutuskan untuk membatalkan sekatan itu di wiki ini.',
	'globalblocking-list' => 'Senarai sekatan sejagat',
	'globalblocking-search-legend' => 'Cari sekatan sejagat',
	'globalblocking-search-ip' => 'Alamat IP:',
	'globalblocking-search-submit' => 'Cari sekatan',
	'globalblocking-list-ipinvalid' => 'Alamat IP yang anda ingin cari ($1) tidak sah.
Sila nyatakan alamat IP yang sah.',
	'globalblocking-search-errors' => 'Carian anda tidak dapat dilakukan kerana {{PLURAL:$1|sebab|sebab-sebab}} berikut:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') menyekat '''[[Special:Contributions/$4|$4]]''' di semua wiki ''($5)''",
	'globalblocking-list-expiry' => 'tamat $1',
	'globalblocking-list-anononly' => 'pengguna tanpa nama sahaja',
	'globalblocking-list-unblock' => 'nyahsekat',
	'globalblocking-list-whitelisted' => 'dimatikan di wiki tempatan oleh $1: $2',
	'globalblocking-list-whitelist' => 'status tempatan',
	'globalblocking-goto-block' => 'Sekat alamat IP di semua wiki',
	'globalblocking-goto-unblock' => 'Batalkan sekatan sejagat',
	'globalblocking-goto-status' => 'Tukar status tempatan bagi sekatan sejagat',
	'globalblocking-return' => 'Kembali ke senarai sekatan sejagat',
	'globalblocking-notblocked' => 'Alamat IP yang anda nyatakan ($1) tidak disekat di semua wiki.',
	'globalblocking-unblock' => 'Batalkan sekatan sejagat',
	'globalblocking-unblock-ipinvalid' => 'Alamat IP yang anda nyatakan ($1) tidak sah.
Sila ambil perhatian bahawa anda tidak boleh menyatakan nama pengguna!',
	'globalblocking-unblock-legend' => 'Batalkan sekatan sejagat',
	'globalblocking-unblock-submit' => 'Batalkan sekatan sejagat',
	'globalblocking-unblock-reason' => 'Sebab:',
	'globalblocking-unblock-unblocked' => "Anda telah membatalkan sekatan sejagat #$2 terhadap alamat IP '''$1'''",
	'globalblocking-unblock-errors' => 'Sekatan sejagat itu tidak dapat dibatalkan kerana {{PLURAL:$1|sebab|sebab-sebab}} berikut:',
	'globalblocking-unblock-successsub' => 'Sekatan sejagat telah dibatalkan',
	'globalblocking-unblock-subtitle' => 'Membatalkan sekatan sejagat',
	'globalblocking-unblock-intro' => 'Anda boleh menggunakan borang ini untuk membatalkan sekatan sejagat.
[[Special:GlobalBlockList|Klik di sini]] untuk kembali ke senarai sekatan sejagat.',
	'globalblocking-whitelist' => 'Status tempatan bagi sekatan sejagat',
	'globalblocking-whitelist-legend' => 'Tukar status tempatan',
	'globalblocking-whitelist-reason' => 'Sebab:',
	'globalblocking-whitelist-status' => 'Status tempatan:',
	'globalblocking-whitelist-statuslabel' => 'Matikan sekatan sejagat ini di {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Tukar status tempatan',
	'globalblocking-whitelist-whitelisted' => "Anda telah mematikan sekatan sejagat #$2 terhadap alamat IP '''$1''' di {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Anda telah menghidupkan semula sekatan sejagat #$2 terhadap alamat IP '''$1''' di {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Status tempatan telah ditukar',
	'globalblocking-whitelist-nochange' => 'Anda tidak melakukan apa-apa perubahan pada status tempatan bagi sekatan ini.
[[Special:GlobalBlockList|Kembali ke senarai sekatan sejagat]].',
	'globalblocking-whitelist-errors' => 'Status tempatan bagi sekatan sejagat itu tidak dapat ditukar kerana {{PLURAL:$1|sebab|sebab-sebab}} berikut:',
	'globalblocking-whitelist-intro' => 'Gunakan borang ini untuk mengubah status tempatan bagi suatu sekatan sejagat.
Jika suatu sekatan sejagat dimatikan di wiki ini, pengguna alamat IP yang berkenaan boleh menyunting seperti biasa.
[[Special:GlobalBlockList|Kembali ke senarai sekatan sejagat]].',
	'globalblocking-blocked' => "Alamat IP anda telah disekat di semua wiki oleh '''\$1''' (''\$2'').
Sebab yang diberikan ialah ''\"\$3\"''.
Sekatan ini ''\$4''.",
	'globalblocking-logpage' => 'Log sekatan sejagat',
	'globalblocking-logpagetext' => 'Berikut ialah log sekatan sejagat yang telah dikenakan dan dibatalkan di wiki ini. Sila ambil perhatian bahawa sekatan sejagat boleh dikenakan dan dibatalkan di wiki-wiki lain, justeru berkuatkuasa di wiki ini juga. Anda juga boleh melihat [[Special:GlobalBlockList|senarai semakan sejagat yang sedang berkuatkuasa]].',
	'globalblocking-block-logentry' => 'menyekat [[$1]] di semua wiki sehingga $2',
	'globalblocking-unblock-logentry' => 'membatalkan sekatan sejagat terhadap [[$1]]',
	'globalblocking-whitelist-logentry' => 'mematikan sekatan sejagat terhadap [[$1]] di wiki tempatan',
	'globalblocking-dewhitelist-logentry' => 'menghidupkan semula sekatan sejagat terhadap [[$1]] di wiki tempatan',
	'globalblocklist' => 'Senarai sekatan sejagat',
	'globalblock' => 'Sekat alamat IP di semua wiki',
	'globalblockstatus' => 'Status tempatan bagi sekatan sejagat',
	'removeglobalblock' => 'Batalkan sekatan sejagat',
	'right-globalblock' => 'Mengenakan sekatan sejagat',
	'right-globalunblock' => 'Membatalkan sekatan sejagat',
	'right-globalblock-whitelist' => 'Mematikan sekatan sejagat di wiki tempatan',
);

/** Erzya (Эрзянь)
 * @author Botuzhaleny-sodamo
 */
$messages['myv'] = array(
	'globalblocking-unblock-reason' => 'Тувталось:',
);

/** Nahuatl (Nāhuatl)
 * @author Fluence
 */
$messages['nah'] = array(
	'globalblocking-search-ip' => 'IP:',
	'globalblocking-list-anononly' => 'zan ahtōcā',
	'globalblocking-unblock-reason' => 'Īxtlamatiliztli:',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'globalblocking-desc' => "[[Special:GlobalBlock|Maakt het mogelijk]] IP-addressen [[Special:GlobalBlockList|in meerdere wiki's tegelijk]] te blokkeren",
	'globalblocking-block' => 'Een IP-adres globaal blokkeren',
	'globalblocking-block-intro' => "U kunt deze pagina gebruiken om een IP-adres op alle wiki's te blokkeren.",
	'globalblocking-block-reason' => 'Reden voor deze blokkade:',
	'globalblocking-block-expiry' => 'Verloopdatum blokkade:',
	'globalblocking-block-expiry-other' => 'Andere verlooptermijn',
	'globalblocking-block-expiry-otherfield' => 'Andere tijd:',
	'globalblocking-block-legend' => 'Een gebruiker globaal blokkeren',
	'globalblocking-block-options' => 'Opties:',
	'globalblocking-block-errors' => 'De blokkade is niet geslaagd om de volgende {{PLURAL:$1|reden|redenen}}:',
	'globalblocking-block-ipinvalid' => 'Het IP-adres ($1) dat u hebt opgegeven is onjuist.
Let op: u kunt geen gebruikersnaam opgeven!',
	'globalblocking-block-expiryinvalid' => 'De verloopdatum/tijd die u hebt opgegeven is ongeldig ($1).',
	'globalblocking-block-submit' => 'Dit IP-adres globaal blokkeren',
	'globalblocking-block-success' => 'Het IP-adres $1 is op alle projecten geblokkeerd.',
	'globalblocking-block-successsub' => 'Globale blokkade geslaagd',
	'globalblocking-block-alreadyblocked' => 'Het IP-adres $1 is al globaal geblokkeerd. U kunt de bestaande blokkade bekijken in de [[Special:GlobalBlockList|lijst met globale blokkades]].',
	'globalblocking-block-bigrange' => 'De reeks die u hebt opgegeven ($1) is te groot om te blokkeren. U mag ten hoogste 65.536 adressen blokkeren (/16-reeksen)',
	'globalblocking-list-intro' => 'Dit is een lijst met alle globale blokkades die op het moment actief zijn.
Sommige blokkades zijn gemarkeerd als lokaal opgeheven.
Dit betekent dat ze op andere sites van toepassing zijn, maar dat een lokale beheerder heeft besloten dat de blokkade op deze wiki niet van toepassing is.',
	'globalblocking-list' => 'Lijst met globaal geblokeerde IP-adressen',
	'globalblocking-search-legend' => 'Naar een globale blokkade zoeken',
	'globalblocking-search-ip' => 'IP-adres:',
	'globalblocking-search-submit' => 'Naar blokkades zoeken',
	'globalblocking-list-ipinvalid' => 'Het IP-adres waar u naar zocht is onjuist ($1).
Voer een correct IP-adres in.',
	'globalblocking-search-errors' => 'Uw zoekopdracht is niet geslaagd om de volgende {{PLURAL:$1|reden|redenen}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') heeft '''[[Special:Contributions/$4|$4]]''' globaal geblokkeerd ''($5)''",
	'globalblocking-list-expiry' => 'verloopt $1',
	'globalblocking-list-anononly' => 'alleen anoniemen',
	'globalblocking-list-unblock' => 'blokkade opheffen',
	'globalblocking-list-whitelisted' => 'lokaal genegeerd door $1: $2',
	'globalblocking-list-whitelist' => 'lokale status',
	'globalblocking-goto-block' => 'IP-adres globaal blokkeren',
	'globalblocking-goto-unblock' => 'Globale blokkades verwijderen',
	'globalblocking-goto-status' => 'Lokale status van een globale blokkade wijzigen',
	'globalblocking-return' => 'Terug naar de lijst met globale blokkades',
	'globalblocking-notblocked' => 'Het ingegeven IP-adres ($1) is niet globaal geblokkeerd.',
	'globalblocking-unblock' => 'Globale blokkades verwijderen',
	'globalblocking-unblock-ipinvalid' => 'Het IP-adres ($1) dat u hebt ingegeven is onjuist.
Let op: u kunt geen gebruikersnaam ingeven!',
	'globalblocking-unblock-legend' => 'Een globale blokkade verwijderen',
	'globalblocking-unblock-submit' => 'Globale blokkade verwijderen',
	'globalblocking-unblock-reason' => 'Reden:',
	'globalblocking-unblock-unblocked' => "U hebt de globale blokkade met nummer $2 voor het IP-adres '''$1''' verwijderd",
	'globalblocking-unblock-errors' => 'De globale blokkade is niet verwijderd om de volgende {{PLURAL:$1|reden|redenen}}:',
	'globalblocking-unblock-successsub' => 'De globale blokkade is verwijderd',
	'globalblocking-unblock-subtitle' => 'Globale blokkade aan het verwijderen',
	'globalblocking-unblock-intro' => 'U kunt dit formulier gebruik om een globale blokkade op te heffen.
[[Special:GlobalBlockList|Terugkeren naar de lijst met globale blokkades]].',
	'globalblocking-whitelist' => 'Lokale status van globale blokkades',
	'globalblocking-whitelist-legend' => 'Lokale status wijzigen',
	'globalblocking-whitelist-reason' => 'Reden:',
	'globalblocking-whitelist-status' => 'Lokale status:',
	'globalblocking-whitelist-statuslabel' => 'Deze globale blokkade op {{SITENAME}} uitschakelen',
	'globalblocking-whitelist-submit' => 'Lokale status wijzigen',
	'globalblocking-whitelist-whitelisted' => "U hebt de globale blokkade #$2 met het IP-adres '''$1''' op {{SITENAME}} opgeheven.",
	'globalblocking-whitelist-dewhitelisted' => "U hebt de globale blokkade #$2 met het IP-adres '''$1''' op {{SITENAME}} opnieuw actief gemaakt.",
	'globalblocking-whitelist-successsub' => 'De lokale status is gewijzigd',
	'globalblocking-whitelist-nochange' => 'U hebt de lokale status van deze blokkade niet gewijzigd.
[[Special:GlobalBlockList|Terugkeren naar de lijst met globale blokkades]].',
	'globalblocking-whitelist-errors' => 'U kon de lokale status van de globale blokkade niet wijzigen om de volgende {{PLURAL:$1|reden|redenen}}:',
	'globalblocking-whitelist-intro' => 'U kunt dit formulier gebruiken om de lokale status van een globale blokkade te wijzigen.
Als een globale blokkade op deze wiki is opgeheven, kunnen gebruikers vanaf het IP-adres gewoon bewerkingen uitvoeren.
[[Special:GlobalBlockList|Terugkeren naar de lijst met globale blokkades]].',
	'globalblocking-blocked' => "Uw IP-adres is door '''\$1''' (''\$2'') geblokkeerd op alle wiki's.
De reden is ''\"\$3\"''.
De blokkade ''\$4''.",
	'globalblocking-logpage' => 'Globaal blokkeerlogboek',
	'globalblocking-logpagetext' => "Dit logboek bevat aangemaakte en verwijderde globale blokkades op deze wiki.
Globale blokkades kunnen ook op andere wiki's aangemaakt en verwijderd worden, en invloed hebben op deze wiki.
Alle globale blokkades staan in de [[Special:GlobalBlockList|lijst met globale blokkades]].",
	'globalblocking-block-logentry' => 'heeft [[$1]] globaal geblokkeerd met een verlooptijd van $2',
	'globalblocking-unblock-logentry' => 'heeft de globale blokkade voor [[$1]] verwijderd',
	'globalblocking-whitelist-logentry' => 'heeft de globale blokkade van [[$1]] lokaal opgeheven',
	'globalblocking-dewhitelist-logentry' => 'heeft de globale blokkade van [[$1]] lokaal opnieuw ingesteld',
	'globalblocklist' => 'Lijst van globaal geblokkeerde IP-adressen',
	'globalblock' => 'Een IP-adres globaal blokkeren',
	'globalblockstatus' => 'Lokale status van globale blokkades',
	'removeglobalblock' => 'Globale blokkade verwijderen',
	'right-globalblock' => 'Globale blokkades instellen',
	'right-globalunblock' => 'Globale blokkades verwijderen',
	'right-globalblock-whitelist' => 'Globale blokkades lokaal negeren',
);

/** Norwegian Nynorsk (‪Norsk (nynorsk)‬)
 * @author Eirik
 * @author Jon Harald Søby
 * @author Jorunn
 */
$messages['nn'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Gjer det råd]] å blokkera IP-adresser [[Special:GlobalBlockList|krosswiki]]',
	'globalblocking-block' => 'Blokker ei IP-adresse krosswiki',
	'globalblocking-block-intro' => 'Du kan nytte denne sida til å blokkere ei IP-adresse krosswiki.',
	'globalblocking-block-reason' => 'Grunngjeving for blokkeringa:',
	'globalblocking-block-expiry' => 'Blokkeringa varer til:',
	'globalblocking-block-expiry-other' => 'Anna varigheit',
	'globalblocking-block-expiry-otherfield' => 'Anna tid:',
	'globalblocking-block-legend' => 'Blokker ein brukar krosswiki',
	'globalblocking-block-options' => 'Alternativ:',
	'globalblocking-block-errors' => 'Blokkeringa tok ikkje, grunna:',
	'globalblocking-block-ipinvalid' => 'IP-adressa du skreiv inn ($1) er ugyldig.
Merk at du ikkje kan skrive inn brukarnamn.',
	'globalblocking-block-expiryinvalid' => 'Varigheita du skreiv inn ($1) er ikkje gyldig.',
	'globalblocking-block-submit' => 'Blokker denne IP-adressa krosswiki',
	'globalblocking-block-success' => 'IP-adressa $1 har vorte blokkert på alle Wikimedia-prosjekta.
Sjå òg [[Special:GlobalBlockList|lista over krosswikiblokkeringar]].',
	'globalblocking-block-successsub' => 'Krosswikiblokkeringa vart utførd',
	'globalblocking-block-alreadyblocked' => 'IP-adressa $1 er allereide krosswikiblokkert.
Du kan sjå blokkeringa på [[Special:GlobalBlockList|lista over krosswikiblokkeringar]].',
	'globalblocking-list' => 'Liste over krosswikiblokkertet IP-adresser',
	'globalblocking-search-legend' => 'Søk etter ei krosswikiblokkering',
	'globalblocking-search-ip' => 'IP-adresse:',
	'globalblocking-search-submit' => 'Søk etter blokkeringar',
	'globalblocking-list-ipinvalid' => 'IP-adressa du skreiv inn ($1) er ikkje gyldig.
Skriv inn ei gyldig IP-adresse.',
	'globalblocking-search-errors' => 'Søket ditt lukkast ikkje fordi:
$1',
	'globalblocking-list-blockitem' => "$1 '''$2''' ('''$3''') blokkerte '''[[Special:Contributions/$4|$4]]''' krosswiki ''($5)''",
	'globalblocking-list-expiry' => 'varigheit $1',
	'globalblocking-list-anononly' => 'berre uregistrerte',
	'globalblocking-list-unblock' => 'fjern blokkeringa',
	'globalblocking-unblock-ipinvalid' => 'IP-adressa du skreiv inn ($1) er ugyldig.
Merk at du ikkje kan skrive inn brukarnamn.',
	'globalblocking-unblock-legend' => 'Fjern ei krosswikiblokkering',
	'globalblocking-unblock-submit' => 'Fjern krosswikiblokkering',
	'globalblocking-unblock-reason' => 'Grunngjeving:',
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Gjør det mulig]] å blokkere IP-adresser på [[Special:GlobalBlockList|alle wikier]]',
	'globalblocking-block' => 'Blokker en IP-adresse globalt',
	'globalblocking-block-intro' => 'Du kan bruke denne siden for å blokkere en IP-adresse på alle wikier.',
	'globalblocking-block-reason' => 'Blokkeringsårsak:',
	'globalblocking-block-expiry' => 'Varighet:',
	'globalblocking-block-expiry-other' => 'Annen varighet',
	'globalblocking-block-expiry-otherfield' => 'Annen tid:',
	'globalblocking-block-legend' => 'Blokker en bruker globalt',
	'globalblocking-block-options' => 'Alternativer:',
	'globalblocking-block-errors' => 'Blokkeringen mislyktes fordi:<!--{{PLURAL:$1}}-->',
	'globalblocking-block-ipinvalid' => 'IP-adressen du skrev inn ($1) er ugyldig.
Merk at du ikke kan skrive inn brukernavn.',
	'globalblocking-block-expiryinvalid' => 'Varigheten du skrev inn ($1) er ugyldig.',
	'globalblocking-block-submit' => 'Blokker denne IP-adressen globalt',
	'globalblocking-block-success' => 'IP-adressen $1 har blitt blokkert på alle prosjekter.',
	'globalblocking-block-successsub' => 'Global blokkering lyktes',
	'globalblocking-block-alreadyblocked' => 'IP-adressen $1 er blokkkert globalt fra før. Du kan se eksisterende blokkeringer på [[Special:GlobalBlockList|listen over globale blokkeringer]].',
	'globalblocking-block-bigrange' => 'IP-området du oppga ($1) er for stort til å blokkeres. Du kan blokkere maks 65&nbsp;536 adresser (/16-områder)',
	'globalblocking-list-intro' => 'Dette er en liste over nåværende globale blokkeringer. Noen blokkeringer er slått av lokalt; dette betyr at den gjelder andre steder, men at en lokal administrator har bestemt seg for å slå av blokkeringen på sin wiki.',
	'globalblocking-list' => 'Liste over globalt blokkerte IP-adresser',
	'globalblocking-search-legend' => 'Søk etter en global blokkering',
	'globalblocking-search-ip' => 'IP-adresse:',
	'globalblocking-search-submit' => 'Søk etter blokkeringer',
	'globalblocking-list-ipinvalid' => 'IP-adressen du skrev inn ($1) er ugyldig.
Skriv inn en gyldig IP-adresse.',
	'globalblocking-search-errors' => 'Søket ditt mislyktes fordi:<!--{{PLURAL:$1}}-->',
	'globalblocking-list-blockitem' => "$1 '''$2''' ('''$3''') blokkerte '''[[Special:Contributions/$4|$4]]''' globalt ''($5)''",
	'globalblocking-list-expiry' => 'varighet $1',
	'globalblocking-list-anononly' => 'kun uregistrerte',
	'globalblocking-list-unblock' => 'avblokker',
	'globalblocking-list-whitelisted' => 'slått av lokalt av $1: $2',
	'globalblocking-list-whitelist' => 'lokal status',
	'globalblocking-goto-block' => 'Blokker in IP-adresse globalt',
	'globalblocking-goto-unblock' => 'Fjern en global blokkering',
	'globalblocking-goto-status' => 'Endre lokal status for en global blokkering',
	'globalblocking-return' => 'Tilbake til listen over globale blokkeringer',
	'globalblocking-notblocked' => 'IP-adressen du oppga ($1) er ikke blokkert globalt.',
	'globalblocking-unblock' => 'Fjern global blokkering',
	'globalblocking-unblock-ipinvalid' => 'IP-adressen du skrev inn ($1) er ugyldig.
Merk at du ikke kan skrive inn brukernavn.',
	'globalblocking-unblock-legend' => 'Fjern en global blokkering',
	'globalblocking-unblock-submit' => 'Fjern global blokkering',
	'globalblocking-unblock-reason' => 'Årsak:',
	'globalblocking-unblock-unblocked' => "Du har fjernet den globale blokkeringen (#$2) på IP-adressen '''$1'''",
	'globalblocking-unblock-errors' => 'Du kan ikke fjerne en global blokkering på den IP-adressen fordi:<!--{{PLURAL:$1}}-->',
	'globalblocking-unblock-successsub' => 'Global blokkering fjernet',
	'globalblocking-unblock-subtitle' => 'Fjerner global blokkering',
	'globalblocking-unblock-intro' => 'Du kan bruke dette skjemaet for å fjerne en global blokkering. [[Special:GlobalBlockList|Tilbake til den globale blokkeringslista.]]',
	'globalblocking-whitelist' => 'Lokal status for globale blokkeringer',
	'globalblocking-whitelist-legend' => 'Endre lokal status',
	'globalblocking-whitelist-reason' => 'Endringsårsak:',
	'globalblocking-whitelist-status' => 'Lokal status:',
	'globalblocking-whitelist-statuslabel' => 'Slå av denne globale blokkeringen på {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Endre lokal status',
	'globalblocking-whitelist-whitelisted' => "Du har slått av global blokkering nr. $2 på IP-adressen '''$1''' på {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Du har slått på igjen global blokkering nr. $2 på IP-adressen '''$1''' på {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Lokal status endret',
	'globalblocking-whitelist-nochange' => 'Du endret ikke denne blokkeringens lokale status. [[Special:GlobalBlockList|Tilbake til den globale blokkeringslista.]]',
	'globalblocking-whitelist-errors' => 'Endringen i lokal status lyktes ikke fordi:<!--{{PLURAL:$1}}-->',
	'globalblocking-whitelist-intro' => 'Du kan bruke dette skjemaet til å redigere en global blokkerings lokale status. Om en global blokkering er slått av på denne wikien, vil brukerne av de påvirkede IP-adressene kunne redigere normalt. [[Special:GlobalBlockList|Tilbake til den globale blokkeringslista.]]',
	'globalblocking-blocked' => "IP-adressen din har blitt blokkert på alle wikier av '''$1''' (''$2'').
Årsaken som ble oppgitt var '''$3'''. Blokkeringen utgår ''$4''.",
	'globalblocking-logpage' => 'Global blokkeringslogg',
	'globalblocking-block-logentry' => 'blokkerte [[$1]] globalt med en varighet på $2',
	'globalblocking-unblock-logentry' => 'fjernet global blokkering på [[$1]]',
	'globalblocking-whitelist-logentry' => 'slo av global blokkering av [[$1]] lokalt',
	'globalblocking-dewhitelist-logentry' => 'slo på igjen global blokkering av [[$1]] lokalt',
	'globalblocklist' => 'Liste over globalt blokkerte IP-adresser',
	'globalblock' => 'Blokker en IP-adresse globalt',
	'globalblockstatus' => 'Lokal status for globale blokkeringer',
	'removeglobalblock' => 'Fjern en global blokkering',
	'right-globalblock' => 'Blokkere IP-er globalt',
	'right-globalunblock' => 'Fjerne globale blokkeringer',
	'right-globalblock-whitelist' => 'Slå av globale blokkeringer lokalt',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permet]] lo blocatge de las adreças IP [[Special:GlobalBlockList|a travèrs maites wikis]]',
	'globalblocking-block' => 'Blocar globalament una adreça IP',
	'globalblocking-block-intro' => 'Podètz utilizar aquesta pagina per blocar una adreça IP sus l’ensemble dels wikis.',
	'globalblocking-block-reason' => "Motius d'aqueste blocatge :",
	'globalblocking-block-expiry' => 'Plaja d’expiracion :',
	'globalblocking-block-expiry-other' => 'Autra durada d’expiracion',
	'globalblocking-block-expiry-otherfield' => 'Autra durada :',
	'globalblocking-block-legend' => 'Blocar globalament un utilizaire',
	'globalblocking-block-options' => 'Opcions :',
	'globalblocking-block-errors' => 'Lo blocatge a fracassat {{PLURAL:$1|pel motiu seguent|pels motius seguents}} :',
	'globalblocking-block-ipinvalid' => "L’adreça IP ($1) qu'avètz picada es incorrècta.
Notatz que podètz pas inscriure un nom d’utilizaire !",
	'globalblocking-block-expiryinvalid' => "L’expiracion qu'avètz picada ($1) es incorrècta.",
	'globalblocking-block-submit' => 'Blocar globalament aquesta adreça IP',
	'globalblocking-block-success' => 'L’adreça IP $1 es estada blocada amb succès sus l’ensemble dels projèctes.',
	'globalblocking-block-successsub' => 'Blocatge global capitat',
	'globalblocking-block-alreadyblocked' => "L’adreça IP ja es blocada globalament. Podètz afichar los blocatges qu'existisson sus la tièra [[Special:GlobalBlockList|dels blocatges globals]].",
	'globalblocking-block-bigrange' => "La plaja qu'avètz especificada ($1) es tròp granda per èsser blocada. Podètz pas blocar mai de 65'536 adreças (plajas en /16).",
	'globalblocking-list-intro' => 'Vaquí la lista de totes los blocatges globals actius. Qualques plajas son marcadas coma localament desactivadas : aquò significa que son aplicadas sus d’autres sits, mas qu’un administrator local a decidit de las desactivar sus aqueste wiki.',
	'globalblocking-list' => 'Tièra de las adreças IP blocadas globalament',
	'globalblocking-search-legend' => 'Recèrca d’un blocatge global',
	'globalblocking-search-ip' => 'Adreça IP :',
	'globalblocking-search-submit' => 'Recèrca dels blocatges',
	'globalblocking-list-ipinvalid' => 'L’adreça IP que recercatz per ($1) es incorrècta.
Picatz una adreça IP corrècta.',
	'globalblocking-search-errors' => 'Vòstra recèrca es estada infructuosa, {{PLURAL:$1|pel motiu seguent|pels motius seguents}} :',
	'globalblocking-list-blockitem' => "$1 : '''$2''' (''$3'') blocat globalament '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expiracion $1',
	'globalblocking-list-anononly' => 'utilizaire non enregistrat unicament',
	'globalblocking-list-unblock' => 'desblocar',
	'globalblocking-list-whitelisted' => 'desactivat localament per $1 : $2',
	'globalblocking-list-whitelist' => 'estatut local',
	'globalblocking-goto-block' => 'Blocar globalament una adreça IP',
	'globalblocking-goto-unblock' => 'Levar un blocatge global',
	'globalblocking-goto-status' => "Modifica l'estatut local d’un blocatge global",
	'globalblocking-return' => 'Tornar a la lista dels blocatges globals',
	'globalblocking-notblocked' => "L’adreça IP ($1) qu'avètz inscricha es pas blocada globalament.",
	'globalblocking-unblock' => 'Levar un blocatge global',
	'globalblocking-unblock-ipinvalid' => "L’adreça IP ($1) qu'avètz picada es incorrècta.
Notatz que podètz pas inscriure un nom d’utilizaire !",
	'globalblocking-unblock-legend' => 'Levar un blocatge global',
	'globalblocking-unblock-submit' => 'Levar lo blocatge global',
	'globalblocking-unblock-reason' => 'Motiu :',
	'globalblocking-unblock-unblocked' => "Avètz capitat de levar lo blocatge global n° $2 correspondent a l’adreça IP '''$1'''",
	'globalblocking-unblock-errors' => 'Podètz pas levar un blocatge global per aquesta adreça IP {{PLURAL:$1|pel motiu seguent|pels motius seguents}} :
$1',
	'globalblocking-unblock-successsub' => 'Blocatge global levat amb succès',
	'globalblocking-unblock-subtitle' => 'Supression del blocatge global',
	'globalblocking-unblock-intro' => 'Podètz utilizar aqueste formulari per levar un blocatge global.
[[Special:GlobalBlockList|Clicatz aicí]] per tornar a la tièra globala dels blocatges.',
	'globalblocking-whitelist' => 'Estatut local dels blocatges globals',
	'globalblocking-whitelist-legend' => "Cambiar l'estatut local",
	'globalblocking-whitelist-reason' => 'Rason del cambiament :',
	'globalblocking-whitelist-status' => 'Estatut local :',
	'globalblocking-whitelist-statuslabel' => 'Desactivar aqueste blocatge global sus {{SITENAME}}',
	'globalblocking-whitelist-submit' => "Cambiar l'estatut local",
	'globalblocking-whitelist-whitelisted' => "Avètz desactivat amb succès lo blocatge global n° $2 sus l'adreça IP '''$1''' sus {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Avètz reactivat amb succès lo blocatge global n° $2 sus l'adreça IP '''$1''' sus {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Estatut local cambiat amb succès',
	'globalblocking-whitelist-nochange' => "Avètz pas modificat l'estatut local d'aqueste blocatge.
[[Special:GlobalBlockList|Tornar a la lista globala dels blocatges]].",
	'globalblocking-whitelist-errors' => "Vòstra modificacion de l'estatut local d’un blocage global a pas capitat {{PLURAL:$1|pel motiu seguent|pels motius seguents}} :",
	'globalblocking-whitelist-intro' => "Podètz utilizar aqueste formulari per modificar l'estatut local d’un blocatge global. Se un blocatge global es desactivat sus aqueste wiki, los utilizaires concernits per l’adreça IP poiràn editar normalament. [[Special:GlobalBlockList|Clicatz aicí]] per tornar a la lista globala.",
	'globalblocking-blocked' => "Vòstra adreça IP es estada blocada sus l’ensemble dels wiki per '''$1''' (''$2'').
Lo motiu indicat èra « $3 ». La plaja ''$4''.",
	'globalblocking-logpage' => 'Jornal dels blocatges globals',
	'globalblocking-logpagetext' => 'Vaquí un jornal dels blocatges globals que son estats faches e revocats sus aqueste wiki.
Deuriá èsser relevat que los blocatges globals pòdon èsser faches o anullats sus d’autres wikis, e que losdiches blocatges globals son de natura a interferir sus aqueste wiki.
Per visionar totes los blocatges globals actius, podètz visitar la [[Special:GlobalBlockList|lista dels blocatges globals]].',
	'globalblocking-block-logentry' => '[[$1]] blocat globalament amb una durada d’expiracion de $2',
	'globalblocking-unblock-logentry' => 'blocatge global levat sus [[$1]]',
	'globalblocking-whitelist-logentry' => 'a desactivat localament lo blocatge global de [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'a tornat activar localament lo blocatge global de [[$1]]',
	'globalblocklist' => 'Tièra de las adreças IP blocadas globalament',
	'globalblock' => 'Blocar globalament una adreça IP',
	'globalblockstatus' => 'Estatuts locals dels blocatges globals',
	'removeglobalblock' => 'Suprimir un blocatge global',
	'right-globalblock' => "Blocar d'utilizaires globalament",
	'right-globalunblock' => "Desblocar d'utilizaires blocats globalament",
	'right-globalblock-whitelist' => 'Desactivar localament los blocatges globals',
);

/** Polish (Polski)
 * @author Derbeth
 * @author Sp5uhe
 */
$messages['pl'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Umożliwia]] równoczesne [[Special:GlobalBlockList|blokowanie]] adresów IP na wielu wiki',
	'globalblocking-block' => 'Zablokuj globalnie adres IP',
	'globalblocking-block-intro' => 'Na tej stronie możesz blokować adresy IP na wszystkich wiki.',
	'globalblocking-block-reason' => 'Powód zablokowania',
	'globalblocking-block-expiry' => 'Czas blokady',
	'globalblocking-block-expiry-other' => 'Inny czas blokady',
	'globalblocking-block-expiry-otherfield' => 'Inny czas blokady',
	'globalblocking-block-legend' => 'Zablokuj użytkownika globalnie',
	'globalblocking-block-options' => 'Opcje:',
	'globalblocking-block-errors' => 'Zablokowanie nie powiodło się z {{PLURAL:$1|następującego powodu|następujących powodów}}:',
	'globalblocking-block-ipinvalid' => 'Wprowadzony przez Ciebie adres IP ($1) jest nieprawidłowy.
Zwróć uwagę na to, że nie możesz wprowadzić nazwy użytkownika!',
	'globalblocking-block-expiryinvalid' => 'Czas obowiązywania blokady ($1) jest nieprawidłowy.',
	'globalblocking-block-submit' => 'Zablokuj ten adres IP globalnie',
	'globalblocking-block-success' => 'Adres IP $1 został zablokowany na wszystkich projektach.',
	'globalblocking-block-successsub' => 'Globalna blokada założona',
	'globalblocking-block-alreadyblocked' => 'Adres IP $1 jest obecnie globalnie zablokowany. Możesz zobaczyć aktualnie obowiązujące blokady w [[Special:GlobalBlockList|spisie globalnych blokad]].',
	'globalblocking-list' => 'Spis globalnie zablokowanych adresów IP',
	'globalblocking-search-legend' => 'Szukaj globalnej blokady',
	'globalblocking-search-ip' => 'Adres IP',
	'globalblocking-search-submit' => 'Szukaj blokad',
	'globalblocking-list-ipinvalid' => 'Adres IP którego szukasz ($1) jest nieprawidłowy.
Wprowadź poprawny adres.',
	'globalblocking-search-errors' => 'Wyszukiwanie nie powiodło się z {{PLURAL:$1|następującego powodu|następujących powodów}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globalnie zablokował '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'wygaśnie $1',
	'globalblocking-list-anononly' => 'tylko niezalogowani',
	'globalblocking-list-unblock' => 'odblokowanie',
	'globalblocking-list-whitelisted' => 'lokalnie zniesiona przez $1: $2',
	'globalblocking-list-whitelist' => 'status lokalny',
	'globalblocking-unblock-ipinvalid' => 'Wprowadzony przez Ciebie adres IP ($1) jest nieprawidłowy.
Zwróć uwagę na to, że nie możesz wprowadzić nazwy użytkownika!',
	'globalblocking-unblock-legend' => 'Zdejmowanie globalnej blokady',
	'globalblocking-unblock-submit' => 'Zdejmij globalną blokadę',
	'globalblocking-unblock-reason' => 'Powód',
	'globalblocking-unblock-unblocked' => "Zdjąłeś globalną blokadę $2 dla adresu IP '''$1'''",
	'globalblocking-unblock-errors' => 'Nie możesz zdjąć globalnej blokady dla tego adresu IP, ponieważ:
$1',
	'globalblocking-unblock-successsub' => 'Globalna blokada została zdjęta',
	'globalblocking-whitelist-legend' => 'Zmień lokalny status',
	'globalblocking-whitelist-reason' => 'Powód zmiany',
	'globalblocking-whitelist-status' => 'Lokalny status:',
	'globalblocking-whitelist-statuslabel' => 'Znieś na {{GRAMMAR:MS.lp|{{SITENAME}}}} tą globalną blokadę',
	'globalblocking-whitelist-submit' => 'Zmień lokalny status',
	'globalblocking-whitelist-whitelisted' => "Wyłączyłeś na {{GRAMMAR:MS.lp|{{SITENAME}}}} stosowanie globalnej blokady $2 dla adresu IP '''$1'''.",
	'globalblocking-whitelist-dewhitelisted' => "Uruchomiłeś ponownie na {{GRAMMAR:MS.lp|{{SITENAME}}}} globalną blokadę $2 dla adresu IP '''$1'''.",
	'globalblocking-whitelist-successsub' => 'Status lokalny blokady został zmieniony',
	'globalblocking-blocked' => "Twój adres IP został zablokowany na wszystkich wiki przez '''$1''' (''$2'').
Przyczyna blokady: ''„$3”''.
Blokada ''$4''.",
	'globalblocking-logpage' => 'Rejestr globalnych blokad',
	'globalblocking-block-logentry' => 'zablokował globalnie [[$1]], czas blokady $2',
	'globalblocking-unblock-logentry' => 'zdjął globalną blokadę z [[$1]]',
	'globalblocking-whitelist-logentry' => 'wyłączył lokalne stosowanie globalnej blokady dla [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'ponownie uaktywnił lokalnie globalną blokadę dla [[$1]]',
	'globalblocklist' => 'Spis globalnie zablokowanych adresów IP',
	'globalblock' => 'Zablokuj globalnie adres IP',
	'right-globalblock' => 'Twórz globalne blokady',
	'right-globalunblock' => 'Zdejmij globalne blokady',
	'right-globalblock-whitelist' => 'Lokalnie nie stosuj globalnych blokad',
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'globalblocking-block-expiry-otherfield' => 'بل وخت:',
	'globalblocking-search-ip' => 'IP پته:',
	'globalblocking-list-whitelist' => 'سيمه ايز دريځ',
	'globalblocking-unblock-reason' => 'سبب:',
	'globalblocking-whitelist-reason' => 'د بدلون سبب:',
	'globalblocking-whitelist-status' => 'سيمه ايز دريځ:',
);

/** Portuguese (Português)
 * @author 555
 * @author Lijealso
 * @author Malafaya
 */
$messages['pt'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Permite]] que endereços IP sejam [[Special:GlobalBlockList|bloqueados através de múltiplos wikis]]',
	'globalblocking-block' => 'Bloquear globalmente um endereço IP',
	'globalblocking-block-intro' => 'Você pode usar esta página para bloquear um endereço IP em todos os wikis.',
	'globalblocking-block-reason' => 'Motivo para este bloqueio:',
	'globalblocking-block-expiry' => 'Validade do bloqueio:',
	'globalblocking-block-expiry-other' => 'Outro tempo de validade',
	'globalblocking-block-expiry-otherfield' => 'Outra duração:',
	'globalblocking-block-legend' => 'Bloquear um utilizador globalmente',
	'globalblocking-block-options' => 'Opções:',
	'globalblocking-block-errors' => 'O bloqueio não teve sucesso {{PLURAL:$1|pelo seguinte motivo|pelos seguintes motivos}}:',
	'globalblocking-block-ipinvalid' => 'O endereço IP ($1) que introduziu é inválido.
Por favor, note que não pode introduzir um nome de utilizador!',
	'globalblocking-block-expiryinvalid' => 'A expiração que introduziu ($1) é inválida.',
	'globalblocking-block-submit' => 'Bloquear globalmente este endereço IP',
	'globalblocking-block-success' => 'O endereço IP $1 foi bloqueado com sucesso em todos os projectos.',
	'globalblocking-block-successsub' => 'Bloqueio global com sucesso',
	'globalblocking-block-alreadyblocked' => 'O endereço IP $1 já está bloqueado globalmente.
Você pode ver o bloqueio existente na [[Special:GlobalBlockList|lista de bloqueios globais]].',
	'globalblocking-block-bigrange' => 'O intervalo especificado ($1) é demasiado grande para ser bloqueado.
Pode bloquear, no máximo, 65.536 endereços (intervalos /16)',
	'globalblocking-list' => 'Lista de endereços IP bloqueados globalmente',
	'globalblocking-search-legend' => 'Pesquisar bloqueio global',
	'globalblocking-search-ip' => 'Endereço IP:',
	'globalblocking-search-submit' => 'Pesquisar bloqueios',
	'globalblocking-list-ipinvalid' => 'O endereço IP que procurou ($1) é inválido.
Por favor, introduza um endereço IP válido.',
	'globalblocking-search-errors' => 'A sua busca não teve sucesso {{PLURAL:$1|pelo seguinte motivo|pelos seguintes motivos}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') bloqueou globalmente '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'expira $1',
	'globalblocking-list-anononly' => 'só anónimos',
	'globalblocking-list-unblock' => 'desbloquear',
	'globalblocking-list-whitelisted' => 'localmente desactivado por $1: $2',
	'globalblocking-list-whitelist' => 'estado local',
	'globalblocking-goto-block' => 'Bloquear globalmente um endereço IP',
	'globalblocking-goto-unblock' => 'Remover um bloqueio global',
	'globalblocking-goto-status' => 'Alterar estado local de um bloqueio global',
	'globalblocking-return' => 'Voltar à lista de bloqueios globais',
	'globalblocking-notblocked' => 'O endereço IP ($1) introduzido não está bloqueado globalmente.',
	'globalblocking-unblock' => 'Eliminar um bloqueio global',
	'globalblocking-unblock-ipinvalid' => 'O endereço IP ($1) que introduziu é inválido.
Por favor, note que não pode introduzir um nome de utilizador!',
	'globalblocking-unblock-legend' => 'Remover um bloqueio global',
	'globalblocking-unblock-submit' => 'Remover bloqueio global',
	'globalblocking-unblock-reason' => 'Motivo:',
	'globalblocking-unblock-unblocked' => "Você removeu o bloqueio global #$2 sobre o endereço IP '''$1''' com sucesso",
	'globalblocking-unblock-errors' => 'Você não pôde remover este bloqueio global, {{PLURAL:$1|pelo seguinte motivo|pelos seguintes motivos}}:',
	'globalblocking-unblock-successsub' => 'Bloqueio global removido com sucesso',
	'globalblocking-unblock-subtitle' => 'Removendo bloqueio global',
	'globalblocking-whitelist' => 'Estado local de bloqueios globais',
	'globalblocking-whitelist-legend' => 'Alterar estado local',
	'globalblocking-whitelist-reason' => 'Motivo da alteração:',
	'globalblocking-whitelist-status' => 'Estado local:',
	'globalblocking-whitelist-statuslabel' => 'Desactivar este bloqueio global em {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Alterar estado local',
	'globalblocking-whitelist-whitelisted' => "Você desactivou com sucesso o bloqueio global #$2 sobre o endereço IP '''$1''' em {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Você reactivou com sucesso o bloqueio global #$2 sobre o endereço IP '''$1''' em {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Estado local alterado com sucesso',
	'globalblocking-whitelist-errors' => 'A sua alteração ao estado local de um bloqueio global não teve sucesso {{PLURAL:$1|pela seguinte razão|pelas seguintes razões}}:',
	'globalblocking-blocked' => "O seu endereço IP foi bloqueado em todos os wikis por '''\$1''' (''\$2'').
O motivo dado foi ''\"\$3\"''.
O bloqueio ''\$4''.",
	'globalblocking-logpage' => 'Registo de bloqueios globais',
	'globalblocking-block-logentry' => 'bloqueou globalmente [[$1]] com um tempo de expiração de $2',
	'globalblocking-unblock-logentry' => 'Removido bloqueio global de [[$1]]',
	'globalblocking-whitelist-logentry' => 'desactivou o bloqueio global sobre [[$1]] localmente',
	'globalblocking-dewhitelist-logentry' => 'reactivou o bloqueio global sobre [[$1]] localmente',
	'globalblocklist' => 'Lista de endereços IP bloqueados globalmente',
	'globalblock' => 'Bloquear um endereço IP globalmente',
	'globalblockstatus' => 'Estado local de bloqueios globais',
	'removeglobalblock' => 'Remover um bloqueio global',
	'right-globalblock' => 'Fazer bloqueios globais',
	'right-globalunblock' => 'Remover bloqueios globais',
	'right-globalblock-whitelist' => 'Desactivar bloqueios globais localmente',
);

/** Brazilian Portuguese (Português do Brasil)
 * @author Brunoy Anastasiya Seryozhenko
 */
$messages['pt-br'] = array(
	'globalblocking-desc' => '[[{{ns:Special}}:GlobalBlock|Permite]] que endereços IP sejam [[{{ns:Special}}:GlobalBlockList|bloqueados através de múltiplos wikis]]',
	'globalblocking-block' => 'Bloquear globalmente um endereço IP',
);

/** Romanian (Română)
 * @author KlaudiuMihaila
 */
$messages['ro'] = array(
	'globalblocking-block' => 'Blochează global o adresă IP',
	'globalblocking-block-intro' => 'Această pagină permite blocarea unei adrese IP pe toate proiectele wiki.',
	'globalblocking-block-reason' => 'Motiv pentru această blocare:',
	'globalblocking-block-legend' => 'Blochează global un utilizator',
	'globalblocking-block-options' => 'Opţiuni',
	'globalblocking-block-errors' => 'Blocarea nu a avut succes, din cauză că:
$1',
	'globalblocking-block-submit' => 'Blochează global această adresă IP',
	'globalblocking-block-successsub' => 'Blocare globală cu succes',
	'globalblocking-list' => 'Listă de adrese IP blocate global',
	'globalblocking-search-legend' => 'Caută blocare globală',
	'globalblocking-search-ip' => 'Adresă IP:',
	'globalblocking-search-submit' => 'Caută blocări',
	'globalblocking-search-errors' => 'Căutarea dumneavoastră nu a avut succes, din cauză că:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') a blocat global '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-whitelisted' => 'dezactivat local de $1: $2',
	'globalblocking-list-whitelist' => 'statut local',
	'globalblocking-unblock-legend' => 'Elimină o blocare globală',
	'globalblocking-unblock-submit' => 'Elimină blocare globală',
	'globalblocking-unblock-reason' => 'Motiv:',
	'globalblocking-unblock-errors' => 'Nu puteţi elimina blocarea globală pentru acea adresă IP, din cauză că:
$1',
	'globalblocking-unblock-successsub' => 'Blocare globală eliminată cu succes',
	'globalblocking-unblock-subtitle' => 'Eliminare blocare globală',
	'globalblocking-whitelist-legend' => 'Schimbă statut local',
	'globalblocking-whitelist-reason' => 'Motiv pentru schimbare:',
	'globalblocking-whitelist-status' => 'Statut local:',
	'globalblocking-whitelist-statuslabel' => 'Dezactivează această blocare gloablă pe {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Schimbă statut local',
	'globalblocking-whitelist-successsub' => 'Statut global schimbat cu succes',
	'globalblocking-logpage' => 'Jurnal blocări globale',
	'globalblocking-unblock-logentry' => 'eliminat blocare globală pentru [[$1]]',
	'globalblocklist' => 'Listă de adrese IP blocate global',
	'globalblock' => 'Blochează global o adresă IP',
	'right-globalblock' => 'Efectuează blocări globale',
	'right-globalunblock' => 'Elimină blocări globale',
	'right-globalblock-whitelist' => 'Dezactivează local blocările globale',
);

/** Russian (Русский)
 * @author Александр Сигачёв
 */
$messages['ru'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Разрешает]] блокировку IP-адресов [[Special:GlobalBlockList|на нескольких вики]]',
	'globalblocking-block' => 'Глобальная блокировка IP-адреса',
	'globalblocking-block-intro' => 'Вы можете использовать эту страницу чтобы заблокировать IP-адрес на всех вики.',
	'globalblocking-block-reason' => 'Причина блокировки:',
	'globalblocking-block-expiry' => 'Закончится через:',
	'globalblocking-block-expiry-other' => 'другое время окончания',
	'globalblocking-block-expiry-otherfield' => 'Другое время:',
	'globalblocking-block-legend' => 'Глобальное блокирование участника',
	'globalblocking-block-options' => 'Настройки:',
	'globalblocking-block-errors' => 'Блокировка неудачна. {{PLURAL:$1|Причина|Причины}}:
$1',
	'globalblocking-block-ipinvalid' => 'Введённый вами IP-адрес ($1) ошибочен.
Пожалуйста, обратите внимание, вы не можете вводить имя участника!',
	'globalblocking-block-expiryinvalid' => 'Введённый срок окончания ($1) ошибочен.',
	'globalblocking-block-submit' => 'Заблокировать этот IP-адрес глобально',
	'globalblocking-block-success' => 'IP-адрес $1 был успешно заблокирован во всех проектах.',
	'globalblocking-block-successsub' => 'Глобальная блокировка выполнена успешно',
	'globalblocking-block-alreadyblocked' => 'IP-адрес $1 уже был заблокирован глобально. Вы можете просмотреть существующие блокировки в [[Special:GlobalBlockList|списке глобальных блокировок]].',
	'globalblocking-block-bigrange' => 'Указанный вами диапазон ($1) слишком велик для блокировки.
Вы можете заблокировать максимум 65 536 адресов (/16 область)',
	'globalblocking-list-intro' => 'Это список всех действующих глобальных блокировок.
Некоторые блокировки отмечены как выключенные локально, это означает, что они действуют на других сайтах, но локальный администратор решил отключить её в этой вики.',
	'globalblocking-list' => 'Список глобально заблокированных IP-адресов',
	'globalblocking-search-legend' => 'Поиск глобальной блокировки',
	'globalblocking-search-ip' => 'IP-адрес:',
	'globalblocking-search-submit' => 'Найти блокировки',
	'globalblocking-list-ipinvalid' => 'Вы ищете ошибочный IP-адрес ($1).
Пожалуйста введите корректный IP-адрес.',
	'globalblocking-search-errors' => 'Ваш поиск не был успешен. {{PLURAL:$1|Причина|Причины}}:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') глобально заблокировал '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'истекает $1',
	'globalblocking-list-anononly' => 'только анонимов',
	'globalblocking-list-unblock' => 'разблокировать',
	'globalblocking-list-whitelisted' => 'локально отключил $1: $2',
	'globalblocking-list-whitelist' => 'локальное состояние',
	'globalblocking-goto-block' => 'Заблокировать IP-адрес глобально',
	'globalblocking-goto-unblock' => 'Убрать глобальную блокировку',
	'globalblocking-goto-status' => 'Изменить локальное состояние глобальной блокировки',
	'globalblocking-return' => 'Вернуться к списку глобальных блокировок',
	'globalblocking-notblocked' => 'Введённый вами IP-адрес ($1) не заблокирован глобально.',
	'globalblocking-unblock' => 'Снять глобальную блокировку',
	'globalblocking-unblock-ipinvalid' => 'Введённый вами IP-адрес ($1) ошибочен.
Пожалуйста, обратите внимание, вы не можете вводить имя участника!',
	'globalblocking-unblock-legend' => 'Снятие глобальной блокировки',
	'globalblocking-unblock-submit' => 'Снять глобальную блокировку',
	'globalblocking-unblock-reason' => 'Причина:',
	'globalblocking-unblock-unblocked' => "Вы успешно сняли глобальную блокировку #$2 с IP-адреса '''$1'''",
	'globalblocking-unblock-errors' => 'Попытка снять глобальную блокировку не удалась. {{PLURAL:$1|Причина|Причины}}:',
	'globalblocking-unblock-successsub' => 'Глобальная блокировка успешно снята',
	'globalblocking-unblock-subtitle' => 'Снятие глобальной блокировки',
	'globalblocking-unblock-intro' => 'Вы можете использовать эту форму для снятия глобальной блокировки.
[[Special:GlobalBlockList|Нажмите здесь]], чтобы вернуться к списку глобальных блокировок.',
	'globalblocking-whitelist' => 'Локальное состояние глобальных блокировок',
	'globalblocking-whitelist-legend' => 'Изменение локального состояния',
	'globalblocking-whitelist-reason' => 'Причина изменения:',
	'globalblocking-whitelist-status' => 'Локальное состояние:',
	'globalblocking-whitelist-statuslabel' => 'Отключить эту глобальную блокировку в {{grammar:genitive|{{SITENAME}}}}',
	'globalblocking-whitelist-submit' => 'Изменить локальное состояние',
	'globalblocking-whitelist-whitelisted' => "Вы успешно отключили глобальную блокировку #$2 IP-адреса '''$1''' в {{grammar:genitive|{{SITENAME}}}}",
	'globalblocking-whitelist-dewhitelisted' => "Вы успешно восстановили глобальную блокировку #$2 IP-адреса '''$1''' в {{grammar:genitive|{{SITENAME}}}}",
	'globalblocking-whitelist-successsub' => 'Локальное состояние успешно измененно',
	'globalblocking-whitelist-nochange' => 'Вы не произвели изменений локального состояния этой блокировки.
[[Special:GlobalBlockList|Вернуться к списку глобальных блокировок]].',
	'globalblocking-whitelist-errors' => 'Попытка изменить локальное состояние глобальной блокировки не удалась. {{PLURAL:$1|Причина|Причины}}:',
	'globalblocking-whitelist-intro' => 'Вы можете использовать эту форму для изменения локального состояния глобальной блокировки.
Если глобальная блокировка будет выключена в этой вики, участники с соответствующими IP-адресами смогут нормально редактировать страницы.
[[Special:GlobalBlockList|Вернуться к списку глобальных блокировок]].',
	'globalblocking-blocked' => "Ваш IP-адрес был заблокирован во всех вики участником '''$1''' (''$2'').
Была указана причина: ''«$3»''.
Блокировка ''$4''.",
	'globalblocking-logpage' => 'Журнал глобальных блокировок',
	'globalblocking-logpagetext' => 'Это журнал глобальных блокировок, установленных и снятых в этой вики.
Следует отметить, что глобальные блокировки могут быть установлены в других вики, но действовать также и в данной вики.
Чтобы просмотреть список всех глобальных блокировок, обратитесь к [[Special:GlobalBlockList|соответствующему списку]].',
	'globalblocking-block-logentry' => 'заблокировал глобально [[$1]] со сроком блокировки $2',
	'globalblocking-unblock-logentry' => 'снял глобальную блокировку с [[$1]]',
	'globalblocking-whitelist-logentry' => 'локально отключена глобальная блокировка [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'локально восстановлена глобальная блокировка [[$1]]',
	'globalblocklist' => 'Список заблокированных глобально IP-адресов',
	'globalblock' => 'Глобальная блокировка IP-адреса',
	'globalblockstatus' => 'Локальные состояния глобальных блокировок',
	'removeglobalblock' => 'Снять глобальную блокировку',
	'right-globalblock' => 'наложение глобальных блокировок',
	'right-globalunblock' => 'снятие глобальных блокировок',
	'right-globalblock-whitelist' => 'Локальное отключение глобальных блокировок',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Umožňuje]] zablokovať IP adresy [[Special:GlobalBlockList|na viacerých wiki]]',
	'globalblocking-block' => 'Globálne zablokovať IP adresu',
	'globalblocking-block-intro' => 'Táto stránka slúži na zablokovanie IP adresy na všetkých wiki.',
	'globalblocking-block-reason' => 'Dôvod blokovania:',
	'globalblocking-block-expiry' => 'Vypršanie blokovania:',
	'globalblocking-block-expiry-other' => 'Iný čas vypršania',
	'globalblocking-block-expiry-otherfield' => 'Iný čas:',
	'globalblocking-block-legend' => 'Globálne zablokovať používateľa',
	'globalblocking-block-options' => 'Voľby:',
	'globalblocking-block-errors' => 'Blokovanie bolo neúspešné z {{PLURAL:$1|nasledovného dôvodu|nasledovných dôvodov}}:',
	'globalblocking-block-ipinvalid' => 'IP adresa ($1), ktorú ste zadali nie je platná.
Majte na pamäti, že nemôžete zadať meno používateľa!',
	'globalblocking-block-expiryinvalid' => 'Čas vypršania, ktorý ste zadali ($1) je neplatný.',
	'globalblocking-block-submit' => 'Globálne zablokovať túto IP adresu',
	'globalblocking-block-success' => 'IP adresa $1 bola úspešne zablokovaná na všetkých projektoch.',
	'globalblocking-block-successsub' => 'Globálne blokovanie úspešné',
	'globalblocking-block-alreadyblocked' => 'IP adresa $1 je už globálne zablokovaná. Existujúce blokovanie si môžete pozrieť v [[Special:GlobalBlockList|Zozname globálnych blokovaní]].',
	'globalblocking-block-bigrange' => 'Rozsah, ktorý ste uviedli ($1) nemožno zablokovať, pretože je príliš veľký. Najviac môžete zablokovať 65&nbsp;536 adries (CIDR zápis: /16).',
	'globalblocking-list-intro' => 'Toto je zoznam všetkých globálnych blokovaní, ktoré sú momentálne účinné. Niektoré blokovania sú označené ako lokálne vypnuté: To znamená, že sú účinné na ostatných stránkach, ale lokálny správca sa rozhodol ich vypnúť na tejto wiki.',
	'globalblocking-list' => 'Zoznam globálne zablokovaných IP adries',
	'globalblocking-search-legend' => 'Hľadať globálne blokovanie',
	'globalblocking-search-ip' => 'IP adresa:',
	'globalblocking-search-submit' => 'Hľadať blokovania',
	'globalblocking-list-ipinvalid' => 'IP adresa, ktorú ste hľadali ($1) je neplatná.
Prosím, zadajte platnú IP adresu.',
	'globalblocking-search-errors' => 'Vaše hľadanie bolo neúspešné z {{PLURAL:$1|nasledovného dôvodu|nasledovných dôvodov}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') globálne zablokoval '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'vyprší $1',
	'globalblocking-list-anononly' => 'iba anonym',
	'globalblocking-list-unblock' => 'odblokovať',
	'globalblocking-list-whitelisted' => 'lokálne vypol $1: $2',
	'globalblocking-list-whitelist' => 'lokálny stav',
	'globalblocking-goto-block' => 'Globálne zablokovať IP adresu',
	'globalblocking-goto-unblock' => 'Odstrániť globálne blokovanie',
	'globalblocking-goto-status' => 'Zmeniť lokálny stav globálneho blokovania',
	'globalblocking-return' => 'Vrátiť sa na zoznam globálnych blokovaní',
	'globalblocking-notblocked' => 'IP adresa ($1), ktorú ste zadali, nie je globálne zablokovaná.',
	'globalblocking-unblock' => 'Odstrániť globálne blokovanie',
	'globalblocking-unblock-ipinvalid' => 'IP adresa ($1), ktorú ste zadali, je neplatná.
Majte na pamäti, že nemôžete zadať používateľské meno!',
	'globalblocking-unblock-legend' => 'Odstrániť globálne blokovanie',
	'globalblocking-unblock-submit' => 'Odstrániť globálne blokovanie',
	'globalblocking-unblock-reason' => 'Dôvod:',
	'globalblocking-unblock-unblocked' => "Úspešne ste odstránili globálne blokovanie #$2 IP adresy '''$1'''",
	'globalblocking-unblock-errors' => 'Nemôžete odstrániť globálne blokovanie tejto IP adresy z {{PLURAL:$1|nasledovného dôvodu|nasledovných dôvodov}}:',
	'globalblocking-unblock-successsub' => 'Globálne blokovanie bolo úspešne odstránené',
	'globalblocking-unblock-subtitle' => 'Odstraňuje sa globálne blokovanie',
	'globalblocking-unblock-intro' => 'Tento formulár slúži na odstránenie globálneho blokovania.
Môžete sa vrátiť na [[Special:GlobalBlockList|Zoznam globálnych blokovaní]].',
	'globalblocking-whitelist' => 'Lokálny stav globálneho blokovania',
	'globalblocking-whitelist-legend' => 'Zmeniť lokálny stav',
	'globalblocking-whitelist-reason' => 'Dôvod zmeny:',
	'globalblocking-whitelist-status' => 'Lokálny stav:',
	'globalblocking-whitelist-statuslabel' => 'Vypnúť toto globálne blokovanie na {{GRAMMAR:lokál|{{SITENAME}}}}',
	'globalblocking-whitelist-submit' => 'Zmeniť lokálny stav',
	'globalblocking-whitelist-whitelisted' => "Úspešne ste vypli globálne blokovanie #$2 IP adresy '''$1''' na {{GRAMMAR:lokál|{{SITENAME}}}}.",
	'globalblocking-whitelist-dewhitelisted' => "Úspešne ste znova zapli globálne blokovanie #$2 IP adresy '''$1''' na {{GRAMMAR:lokál|{{SITENAME}}}}.",
	'globalblocking-whitelist-successsub' => 'Lokálny stav bol úspešne zmenený',
	'globalblocking-whitelist-nochange' => 'Nevykonali ste zmeny lokálneho stavu tohto blokovania.
Môžete sa vrátiť na [[Special:GlobalBlockList|Zoznam globálnych blokovaní]].',
	'globalblocking-whitelist-errors' => 'Vaša zmena lokálneho stavu globálneho blokovania bola neúspešná z {{PLURAL:$1|nasledovného dôvodu|nasledovných dôvodov}}:',
	'globalblocking-whitelist-intro' => 'Tento formulár slúži na úpravu lokálneho stavu globálneho blokovania. Ak vypnete globálne blokovanie pre túto wiki, používatelia z danej IP adresy budú môcť normálne vykonávať úpravy.
Môžete sa vrátiť na [[Special:GlobalBlockList|Zoznam globálnych blokovaní]].',
	'globalblocking-blocked' => "Vašu IP adresu zablokoval na všetkých wiki '''$1''' (''$2'').
Ako dôvod udáva ''„$3“''. Blokovanie vyprší ''$4''.",
	'globalblocking-logpage' => 'Záznam globálnych blokovaní',
	'globalblocking-logpagetext' => 'Toto je záznam globálnych blokovaní, ktoré boli vytvorené a zrušené na tejto wiki.
Mali by ste pamätať na to, že globálne blokovania je možné vytvoriť a odstrániť na iných wiki a tieto globálne blokovania môžu ovplyvniť túto wiki.
Všetky aktívne blokovania si môžete pozrieť na [[Special:GlobalBlockList|zozname globálnych blokovaní]].',
	'globalblocking-block-logentry' => 'globálne zablokoval [[$1]] s časom vypršania $2',
	'globalblocking-unblock-logentry' => 'odstránil globálne blokovanie [[$1]]',
	'globalblocking-whitelist-logentry' => 'lokálne vypol globálne blokovanie [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'lokálne znovu zapol globálne blokovanie [[$1]]',
	'globalblocklist' => 'Zoznam globálne zablokovaných IP adries',
	'globalblock' => 'Globálne zablokovať IP adresu',
	'globalblockstatus' => 'Lokálny stav globálnych blokovaní',
	'removeglobalblock' => 'Odstrániť globálne blokovanie',
	'right-globalblock' => 'Robiť globálne blokovania',
	'right-globalunblock' => 'Odstraňovať globálne blokovania',
	'right-globalblock-whitelist' => 'Lokálne vypnúť globálne blokovania',
);

/** Serbian Cyrillic ekavian (ћирилица)
 * @author Sasa Stefanovic
 */
$messages['sr-ec'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Омогућује]] [[Special:GlobalBlockList|глобално блокирање]] ИП адреса на више викија',
	'globalblocking-block' => 'Глобално блокирајте ИП адресу',
	'globalblocking-block-intro' => 'Можете користити ову страницу да блокирате ИП адресу на свим викијима.',
	'globalblocking-block-reason' => 'Разлог блока:',
	'globalblocking-block-expiry' => 'Блок истиче:',
	'globalblocking-block-expiry-other' => 'Друго време истека',
	'globalblocking-block-expiry-otherfield' => 'Друго време:',
	'globalblocking-block-legend' => 'Блокирајте корисника глобално',
	'globalblocking-block-options' => 'Опције',
	'globalblocking-block-errors' => 'Блок није успешан због:
$1',
	'globalblocking-block-ipinvalid' => 'ИП адреса ($1) коју сте унели није добра.
Запамтите да не можете унети корисничко име!',
	'globalblocking-block-expiryinvalid' => 'Време истека блока које сте унели ($1) није исправно.',
	'globalblocking-block-submit' => 'Блокирајте ову ИП адресу глобално',
	'globalblocking-block-success' => 'Ип адреса $1 је успешно блокирана на свим Викимедијиним пројектима.
Погледајте [[Special:GlobalBlockList|списак глобалних блокова]].',
	'globalblocking-block-successsub' => 'Успешан глобални блок',
	'globalblocking-block-alreadyblocked' => 'ИП адреса $1 је већ блокирана глобално. Можете погледати списак постојећих [[Special:GlobalBlockList|глобалних блокова]].',
	'globalblocking-list' => 'Списак глобално блокираних ИП адреса',
	'globalblocking-search-legend' => 'Претражите глобалне блокове',
	'globalblocking-search-ip' => 'ИП адреса:',
	'globalblocking-search-submit' => 'Претражите блокове',
	'globalblocking-list-ipinvalid' => 'ИП адреса коју тражите ($1) није исправна.
Молимо Вас унесите исправну ИП адресу.',
	'globalblocking-search-errors' => 'Ваша претрага није успешна због:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') глобално блокирао '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'истиче $1',
	'globalblocking-list-anononly' => 'само анонимне',
	'globalblocking-list-unblock' => 'одблокирај',
	'globalblocking-unblock-ipinvalid' => 'ИП адреса ($1) коју сте унели није исправна.
Запамтите да не можете уносити корисничка имена!',
	'globalblocking-unblock-legend' => 'Уклоните глобални блок',
	'globalblocking-unblock-submit' => 'Уклоните глобални блок',
	'globalblocking-unblock-reason' => 'Разлог:',
	'globalblocking-unblock-unblocked' => "Успешно сте уклонили глобални блок #$2 за ИП адресу '''$1'''.",
	'globalblocking-unblock-errors' => 'Не можете уклонити глобални блок за ту ИП адресу због:
$1',
	'globalblocking-unblock-successsub' => 'Глобални блок успешно уклоњен',
	'globalblocking-blocked' => "Ваша ИП адреса је блокирана на свим Викимедијиним викијима. Корисник који је блокирао '''$1''' (''$2'').
Разлог за блокаду је „''$3''”. Блок истиче ''$4''.",
	'globalblocking-logpage' => 'Историја глобалних блокова',
	'globalblocking-block-logentry' => 'глобално блокирао [[$1]] са временом истицања од $2',
	'globalblocking-unblock-logentry' => 'уклонио глобални блок за [[$1]]',
	'globalblocklist' => 'Списак глобално блокираних ИП адреса',
	'globalblock' => 'Глобално блокирајте ИП адресу',
);

/** Sundanese (Basa Sunda)
 * @author Irwangatot
 */
$messages['su'] = array(
	'globalblocking-unblock-reason' => 'Alesan:',
	'globalblocking-whitelist-reason' => 'Alesan parobahan:',
);

/** Swedish (Svenska)
 * @author Boivie
 * @author Jon Harald Søby
 * @author M.M.S.
 */
$messages['sv'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Tillåter]] IP-adresser att bli [[Special:GlobalBlockList|blockerade tvärs över mångfaldiga wikier]]',
	'globalblocking-block' => 'Blockerar en IP-adress globalt',
	'globalblocking-block-intro' => 'Du kan använda denna sida för att blockera en IP-adress på alla wikier.',
	'globalblocking-block-reason' => 'Blockeringsorsak:',
	'globalblocking-block-expiry' => 'Varighet:',
	'globalblocking-block-expiry-other' => 'Annan varighet',
	'globalblocking-block-expiry-otherfield' => 'Annan tid:',
	'globalblocking-block-legend' => 'Blockerar en användare globalt',
	'globalblocking-block-options' => 'Alternativ:',
	'globalblocking-block-errors' => 'Blockeringen misslyckades på grund av följande {{PLURAL:$1|anledning|anledningar}}:',
	'globalblocking-block-ipinvalid' => 'IP-adressen du skrev in ($1) är ogiltig.
Notera att du inte kan skriva in användarnamn.',
	'globalblocking-block-expiryinvalid' => 'Varigheten du skrev in ($1) är ogiltig.',
	'globalblocking-block-submit' => 'Blockera denna IP-adress globalt',
	'globalblocking-block-success' => 'IP-adressen $1 har blivit blockerad på alla projekt.',
	'globalblocking-block-successsub' => 'Global blockering lyckades',
	'globalblocking-block-alreadyblocked' => 'IP-adressen $1 är redan blockerad globalt. Du kan visa den existerande blockeringen på [[Special:GlobalBlockList|listan över globala blockeringar]].',
	'globalblocking-block-bigrange' => 'IP-området du angav ($1) är för stort att blockeras. Du kan blockera högst 65&nbsp;536 adresser (/16-områden)',
	'globalblocking-list-intro' => 'Det här är en lista över nuvarande globala blockeringar. Vissa blockeringar är lokalt avslagna: det här betyder att den gäller på andra sajter, men att en lokal administratör har bestämt sig för att stänga av blockeringen på sin wiki.',
	'globalblocking-list' => 'Lista över globalt blockerade IP-adresser',
	'globalblocking-search-legend' => 'Sök efter en global blockering',
	'globalblocking-search-ip' => 'IP-adress:',
	'globalblocking-search-submit' => 'Sök efter blockeringar',
	'globalblocking-list-ipinvalid' => 'IP-adressen du skrev in ($1) är ogiltig.
Skriv in en giltig IP-adress.',
	'globalblocking-search-errors' => 'Din sökning misslyckades på grund av följande {{PLURAL:$1|anledning|anledningar}}:',
	'globalblocking-list-blockitem' => "$1 '''$2''' ('''$3''') blockerade '''[[Special:Contributions/$4|$4]]''' globalt ''($5)''",
	'globalblocking-list-expiry' => 'varighet $1',
	'globalblocking-list-anononly' => 'endast oregistrerade',
	'globalblocking-list-unblock' => 'avblockera',
	'globalblocking-list-whitelisted' => 'lokalt avslagen av $1: $2',
	'globalblocking-list-whitelist' => 'lokal status',
	'globalblocking-goto-block' => 'Blockera en IP-adress globalt',
	'globalblocking-goto-unblock' => 'Ta bort en global blockering',
	'globalblocking-goto-status' => 'Ändra lokal status för en global blockering',
	'globalblocking-return' => 'Tillbaka till listan över globala blockeringar',
	'globalblocking-notblocked' => 'IP-adressen du angav ($1) är inte globalt blockerad.',
	'globalblocking-unblock' => 'Ta bort en global blockering',
	'globalblocking-unblock-ipinvalid' => 'IP-adressen du skrev in ($1) är ogiltig.
Notera att du inte kan skriva in användarnamn!',
	'globalblocking-unblock-legend' => 'Ta bort en global blockering',
	'globalblocking-unblock-submit' => 'Ta bort global blockering',
	'globalblocking-unblock-reason' => 'Anledning:',
	'globalblocking-unblock-unblocked' => "Du har tagit bort den globala blockeringen (#$2) på IP-adressen '''$1'''",
	'globalblocking-unblock-errors' => 'Du kan inte ta bort en global blockering på den IP-adressen på grund av följande {{PLURAL:$1|anledning|anledningar}}:',
	'globalblocking-unblock-successsub' => 'Global blockering borttagen',
	'globalblocking-unblock-subtitle' => 'Tar bort global blockering',
	'globalblocking-unblock-intro' => 'Du kan använda detta formulär för att ta bort en global blockering. [[Special:GlobalBlockList|Klicka här]] för att återvända till den globala blockeringslistan.',
	'globalblocking-whitelist' => 'Lokal status för globala blockeringar',
	'globalblocking-whitelist-legend' => 'Ändra lokal status',
	'globalblocking-whitelist-reason' => 'Anledning för ändring:',
	'globalblocking-whitelist-status' => 'Lokal status:',
	'globalblocking-whitelist-statuslabel' => 'Slå av den här globala blockeringen på {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Ändra lokal status',
	'globalblocking-whitelist-whitelisted' => "Du har slagit av global blockering nr. $2 på IP-adressen '''$1''' på {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Du har slagit på global blockering nr. $2 igen på IP-adressen '''$1''' på {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Lokal status ändrad',
	'globalblocking-whitelist-nochange' => 'Du gjorde ingen ändring av den här blockeringens lokala status.
[[Special:GlobalBlockList|Återvänd till den globala blockeringslistan]].',
	'globalblocking-whitelist-errors' => 'Din ändring i den lokala statusen av en global blockering lyckades inte på grund av följande {{PLURAL:$1|anledning|anledningar}}:',
	'globalblocking-whitelist-intro' => 'Du kan använda det här formuläret till att redigera den lokala statusen för en global blockering. Om en global blockering är avslagen på den här wikin, kommer användarna av de påverkade IP-adresserna kunna redigera normalt. [[Special:GlobalBlockList|Klicka här]] för att gå tillbaka till den globala blockeringslistan.',
	'globalblocking-blocked' => "Din IP-adress har blivit blockerad på alla wikier av '''$1''' (''$2'').
Anledningen var '''$3'''. Blockeringen ''$4''.",
	'globalblocking-logpage' => 'Logg för globala blockeringar',
	'globalblocking-logpagetext' => 'Detta är en logg över globala blockeringar som har lagts och tagits bort på den här wikin.
Det bör noteras att globala blockeringar kan läggas och tas bort på andra wikier, och att dessa globala blockeringar kan påverka den här wikin.
För att se alla aktiva globala blockeringar, kan du se den [[Special:GlobalBlockList|globala blockeringslistan]].',
	'globalblocking-block-logentry' => 'blockerade [[$1]] globalt med en varighet på $2',
	'globalblocking-unblock-logentry' => 'tog bort global blockering på [[$1]]',
	'globalblocking-whitelist-logentry' => 'slog av global blockering av [[$1]] lokalt',
	'globalblocking-dewhitelist-logentry' => 'slog på global blockering igen av [[$1]] lokalt',
	'globalblocklist' => 'Lista över globalt blockerade IP-adresser',
	'globalblock' => 'Blockera en IP-adress globalt',
	'globalblockstatus' => 'Lokal status för globala blockeringar',
	'removeglobalblock' => 'Ta bort en global blockering',
	'right-globalblock' => 'Göra globala blockeringar',
	'right-globalunblock' => 'Ta bort globala blockeringar',
	'right-globalblock-whitelist' => 'Slå av globala blockeringar lokalt',
);

/** Telugu (తెలుగు)
 * @author Veeven
 * @author వైజాసత్య
 */
$messages['te'] = array(
	'globalblocking-block-reason' => 'ఈ నిరోధానికి కారణం:',
	'globalblocking-block-expiry-otherfield' => 'ఇతర సమయం:',
	'globalblocking-block-options' => 'ఎంపికలు',
	'globalblocking-search-ip' => 'IP చిరునామా:',
	'globalblocking-list-whitelist' => 'స్థానిక స్థితి',
	'globalblocking-unblock-reason' => 'కారణం:',
	'globalblocking-whitelist-legend' => 'స్థానిక స్థితి మార్పు',
	'globalblocking-whitelist-reason' => 'మార్చడానికి కారణం:',
	'globalblocking-whitelist-status' => 'స్థానిక స్థితి:',
	'globalblocking-whitelist-submit' => 'స్థానిక స్థితిని మార్చండి',
	'globalblock' => 'సర్వత్రా ఈ ఐపీ చిరునామాను నిరోధించు',
);

/** Tajik (Cyrillic) (Тоҷикӣ (Cyrillic))
 * @author Ibrahim
 */
$messages['tg-cyrl'] = array(
	'globalblocking-whitelist-reason' => 'Сабаби тағйир:',
);

/** Thai (ไทย)
 * @author Passawuth
 */
$messages['th'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|อนุญาต]]ให้คุณสามารถบล็อกผู้ใช้ที่เป็น ไอพี [[Special:GlobalBlockList|ในหลาย ๆ วิกิ]]ในครั้งเดียวได้',
	'globalblocking-block-reason' => 'เหตุผลสำหรับการบล็อก:',
	'globalblocking-block-expiry' => 'หมดอายุ:',
	'globalblocking-block-errors' => 'การบล็อกครั้งนี้ไม่สำเร็จ เนื่องจาก :
$1',
	'globalblocking-search-ip' => 'หมายเลขไอพี:',
);

/** Turkish (Türkçe)
 * @author Suelnur
 */
$messages['tr'] = array(
	'globalblocking-unblock-reason' => 'Neden:',
);

/** Vèneto (Vèneto)
 * @author Candalua
 */
$messages['vec'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Consentir]] el bloco de un indirisso IP su [[Special:GlobalBlockList|tante wiki]]',
	'globalblocking-block' => 'Bloca globalmente un indirisso IP',
	'globalblocking-block-intro' => 'Ti pol doparar sta pagina par blocar un indirisso IP su tute le wiki.',
	'globalblocking-block-reason' => 'Motivassion del bloco:',
	'globalblocking-block-expiry' => 'Scadensa del bloco:',
	'globalblocking-block-expiry-other' => 'Altra scadensa',
	'globalblocking-block-expiry-otherfield' => 'Altro tenpo:',
	'globalblocking-block-legend' => 'Bloca un utente globalmente',
	'globalblocking-block-options' => 'Opzioni:',
	'globalblocking-block-errors' => "El bloco no'l ga vu sucesso, par {{PLURAL:$1|el seguente motivo|i seguenti motivi}}:",
	'globalblocking-block-ipinvalid' => "L'indirisso IP ($1) che te gh'è scrito no'l xe valido.
Par piaser tien conto che no ti pol inserir un nome utente!",
	'globalblocking-block-expiryinvalid' => 'La scadensa che ti ga inserìo ($1) no la xe valida.',
	'globalblocking-block-submit' => 'Bloca sto indirisso IP globalmente',
	'globalblocking-block-success' => "L'indirisso IP $1 el xe stà blocà con sucesso su tuti i progeti.",
	'globalblocking-block-successsub' => 'Bloco global efetuà',
	'globalblocking-block-alreadyblocked' => "L'indirisso IP $1 el xe de zà blocà globalmente. Ti pol védar el bloco esistente su la [[Special:GlobalBlockList|lista dei blochi globali]].",
	'globalblocking-block-bigrange' => 'La classe che ti gà indicà ($1) le xe massa granda par èssar blocà. Se pol blocar, al massimo, 65.536 indirissi (classe /16)',
	'globalblocking-list' => 'Lista de indirissi IP blocà globalmente',
	'globalblocking-search-legend' => 'Serca un bloco global',
	'globalblocking-search-ip' => 'Indirisso IP:',
	'globalblocking-search-submit' => 'Serca un bloco',
	'globalblocking-list-ipinvalid' => "L'indirisso IP che ti gà sercà ($1) no'l xe mìa valido.
Par piaser, inserissi un indirisso IP valido.",
	'globalblocking-search-errors' => 'La to riserca no la gà catà gnente, par {{PLURAL:$1|el seguente motivo|i seguenti motivi}}:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') gà blocà globalmente '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => 'scadensa $1',
	'globalblocking-list-anononly' => 'solo anonimi',
	'globalblocking-list-unblock' => 'desbloca',
	'globalblocking-list-whitelisted' => 'localmente disabilità da $1: $2',
	'globalblocking-list-whitelist' => 'stato local',
	'globalblocking-goto-unblock' => 'Cava un bloco global',
	'globalblocking-unblock-ipinvalid' => "L'indirisso IP che ti gà inserìo ($1) no'l xe mìa valido.
Par piaser tien presente che no ti pol inserir un nome utente!",
	'globalblocking-unblock-legend' => 'Cava un bloco global',
	'globalblocking-unblock-submit' => 'Cava el bloco global',
	'globalblocking-unblock-reason' => 'Motivassion:',
	'globalblocking-unblock-unblocked' => "Ti gà cavà con sucesso el bloco global #$2 su l'indirisso IP '''$1'''",
	'globalblocking-unblock-errors' => 'La rimozion del bloco global che te ghè domandà no la xe riussìa, par {{PLURAL:$1|el seguente motivo|i seguenti motivi}}:',
	'globalblocking-unblock-successsub' => 'El bloco global el xe stà cava',
	'globalblocking-whitelist-legend' => 'Canbia el stato local',
	'globalblocking-whitelist-reason' => 'Motivassion del canbiamento:',
	'globalblocking-whitelist-status' => 'Stato local:',
	'globalblocking-whitelist-statuslabel' => 'Disabilita sto bloco global su {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Canbia stato local',
	'globalblocking-whitelist-whitelisted' => "Ti ga disabilità el bloco global #$2 su l'indirisso IP '''$1''' su {{SITENAME}}",
	'globalblocking-whitelist-dewhitelisted' => "Ti gà ri-ativà el bloco global #$2 su l'indirisso IP '''$1''' su {{SITENAME}}",
	'globalblocking-whitelist-successsub' => 'Stato local canbià',
	'globalblocking-blocked' => "El to indirisso IP el xe stà blocà su tute le wiki da '''\$1''' (''\$2'').
La motivassion fornìa la xe ''\"\$3\"''. 
El bloco ''\$4''.",
	'globalblocking-logpage' => 'Registro dei blochi globali',
	'globalblocking-block-logentry' => '[[$1]] xe stà blocà globalmente con scadensa: $2',
	'globalblocking-unblock-logentry' => 'cavà el bloco global su [[$1]]',
	'globalblocking-whitelist-logentry' => 'disabilità localmente el bloco global su [[$1]]',
	'globalblocking-dewhitelist-logentry' => 'ri-abilità localmente el bloco global su [[$1]]',
	'globalblocklist' => 'Lista dei indirissi IP blocà globalmente',
	'globalblock' => 'Bloca globalmente un indirisso IP',
	'right-globalblock' => 'Bloca dei utenti globalmente',
	'right-globalunblock' => 'Cava blochi globali',
	'right-globalblock-whitelist' => 'Disabilita localmente blochi globali',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 * @author Vinhtantran
 */
$messages['vi'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|Cho phép]] [[Special:GlobalBlockList|cấm địa chỉ IP trên nhiều wiki]]',
	'globalblocking-block' => 'Cấm một địa chỉ IP trên toàn hệ thống',
	'globalblocking-block-intro' => 'Bạn có thể sử dụng trang này để cấm một địa chỉ IP trên tất cả các wiki.',
	'globalblocking-block-reason' => 'Lý do cấm:',
	'globalblocking-block-expiry' => 'Hết hạn cấm:',
	'globalblocking-block-expiry-other' => 'Thời gian hết hạn khác',
	'globalblocking-block-expiry-otherfield' => 'Thời hạn khác:',
	'globalblocking-block-legend' => 'Cấm một thành viên trên toàn hệ thống',
	'globalblocking-block-options' => 'Tùy chọn:',
	'globalblocking-block-errors' => 'Cấm không thành công vì {{PLURAL:$1||các}} lý do sau:',
	'globalblocking-block-ipinvalid' => 'Bạn đã nhập địa chỉ IP ($1) không hợp lệ.
Xin chú ý rằng không thể nhập một tên người dùng!',
	'globalblocking-block-expiryinvalid' => 'Thời hạn bạn nhập ($1) không hợp lệ.',
	'globalblocking-block-submit' => 'Cấm địa chỉ IP này trên toàn hệ thống',
	'globalblocking-block-success' => 'Đã cấm thành công địa chỉ IP $1 trên tất cả các dự án.',
	'globalblocking-block-successsub' => 'Cấm thành công trên toàn hệ thống',
	'globalblocking-block-alreadyblocked' => 'Địa chỉ IP $1 đã bị cấm trên toàn hệ thống rồi. Bạn có thể xem những IP đang bị cấm tại [[Special:GlobalBlockList|danh sách các lần cấm toàn hệ thống]].',
	'globalblocking-block-bigrange' => 'Tầm địa chỉ mà bạn chỉ định ($1) quá lớn nên không thể cấm. Bạn chỉ có thể cấm nhiều nhất là 65.536 địa chỉ (tầm vực /16)',
	'globalblocking-list-intro' => 'Đây là danh sách tác vụ cấm toàn cục hiện hành.
Một số tác vụ cấm bị tắt cục bộ, tức là người dùng vẫn bị cấm tại các website kia, nhưng một quản lý viên tại đây quyết định bỏ cấm tại wiki này.',
	'globalblocking-list' => 'Danh sách các địa chỉ IP bị cấm trên toàn hệ thống',
	'globalblocking-search-legend' => 'Tìm một lần cấm toàn hệ thống',
	'globalblocking-search-ip' => 'Địa chỉ IP:',
	'globalblocking-search-submit' => 'Tìm lần cấm',
	'globalblocking-list-ipinvalid' => 'Địa chỉ IP bạn muốn tìm ($1) không hợp lệ.
Xin hãy nhập một địa IP hợp lệ.',
	'globalblocking-search-errors' => 'Tìm kiếm không thành công vì {{PLURAL:$1||các}} lý do sau:',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') đã cấm '''[[Special:Contributions/$4|$4]]''' trên toàn hệ thống ''($5)''",
	'globalblocking-list-expiry' => 'hết hạn $1',
	'globalblocking-list-anononly' => 'chỉ cấm vô danh',
	'globalblocking-list-unblock' => 'bỏ cấm',
	'globalblocking-list-whitelisted' => 'bị tắt cục bộ bởi $1: $2',
	'globalblocking-list-whitelist' => 'trạng thái cục bộ',
	'globalblocking-goto-block' => 'Cấm địa chỉ IP toàn cục',
	'globalblocking-goto-unblock' => 'Bỏ cấm toàn cục',
	'globalblocking-goto-status' => 'Thay đổi trạng thái cục bộ của tác vụ cấm toàn cục',
	'globalblocking-return' => 'Trở lại danh sách cấm toàn cục',
	'globalblocking-notblocked' => 'Địa chỉ IP ($1) mà bạn cho vào chưa bị cấm toàn cục.',
	'globalblocking-unblock' => 'Bỏ cấm toàn cục',
	'globalblocking-unblock-ipinvalid' => 'Bạn đã nhập địa chỉ IP ($1) không hợp lệ.
Xin chú ý rằng không thể nhập một tên người dùng!',
	'globalblocking-unblock-legend' => 'Xóa bỏ một lần cấm toàn hệ thống',
	'globalblocking-unblock-submit' => 'Bỏ cấm hệ thống',
	'globalblocking-unblock-reason' => 'Lý do:',
	'globalblocking-unblock-unblocked' => "Bạn đã bỏ thành công lần cấm #$2 đối với địa chỉ IP '''$1'''",
	'globalblocking-unblock-errors' => 'Bạn không thể bỏ cấm cho địa chỉ IP này vì {{PLURAL:$1||các}} lý do sau:',
	'globalblocking-unblock-successsub' => 'Đã bỏ cấm trên toàn hệ thống thành công',
	'globalblocking-unblock-subtitle' => 'Bỏ cấm toàn bộ',
	'globalblocking-unblock-intro' => 'Biểu mẫu này để bỏ cấm toàn cục.
[[Special:GlobalBlockList|Trở lại danh sách cấm toàn cục]].',
	'globalblocking-whitelist' => 'Trạng thái cục bộ của các tác vụ cấm toàn cục',
	'globalblocking-whitelist-legend' => 'Thay đổi trạng thái cục bộ',
	'globalblocking-whitelist-reason' => 'Lý do thay đổi:',
	'globalblocking-whitelist-status' => 'Trạng thái cục bộ:',
	'globalblocking-whitelist-statuslabel' => 'Tắt tác vụ cấm toàn cục này tại {{SITENAME}}',
	'globalblocking-whitelist-submit' => 'Thay đổi trạng thái cục bộ',
	'globalblocking-whitelist-whitelisted' => "Bạn đã tắt tác vụ cấm địa chỉ IP '''$1''' toàn cục (#$2) tại {{SITENAME}}.",
	'globalblocking-whitelist-dewhitelisted' => "Bạn đã bật lên tác vụ cấm địa chỉ IP '''$1''' toàn cục (#$2) tại {{SITENAME}}.",
	'globalblocking-whitelist-successsub' => 'Thay đổi trạng thái cục bộ thành công',
	'globalblocking-whitelist-nochange' => 'Bạn không thay đổi trạng thái cục bộ của tác vụ cấm này.
[[Special:GlobalBlockList|Trở lại danh sách cấm toàn cục]].',
	'globalblocking-whitelist-errors' => 'Không thể thay đổi trạng thái cục bộ của tác vụ cấm toàn cục vì {{PLURAL:$1||các}} lý do sau:',
	'globalblocking-whitelist-intro' => 'Biểu mẫu này để thay đổi trạng thái cục bộ của tác vụ cấm toàn cục.
Nếu tác vụ cấm bị tắt tại wiki này, những người dùng những địa chỉ IP đó sẽ được phép sửa đổi bình thường.
[[Special:GlobalBlockList|Trở lại danh sách cấm toàn cục]].',
	'globalblocking-blocked' => "Địa chỉ IP của bạn đã bị '''$1''' (''$2'') cấm trên tất cả các wiki.
Lý do được đưa ra là “''$3''”. Tác vụ cấm này ''$4''.",
	'globalblocking-logpage' => 'Nhật trình cấm trên toàn hệ thống',
	'globalblocking-logpagetext' => 'Đây là danh sách các tác vụ cấm toàn cục được thực hiện hoặc lùi lại tại wiki này. Lưu ý rằng có thể thực hiện và lùi các tác vụ cấm tại wiki khác, nhưng các tác vụ cấm đó cũng có hiệu lực tại đây.

Xem [[Special:GlobalBlockList|tất cả các tác vụ cấm toàn cục]].',
	'globalblocking-block-logentry' => 'đã cấm [[$1]] trên toàn hệ thống với thời gian hết hạn của $2',
	'globalblocking-unblock-logentry' => 'đã bỏ cấm trên toàn hệ thống vào [[$1]]',
	'globalblocking-whitelist-logentry' => 'đã tắt tác vụ cấm [[$1]] cục bộ',
	'globalblocking-dewhitelist-logentry' => 'đã bật lên tác vụ cấm [[$1]] cục bộ',
	'globalblocklist' => 'Danh sách các địa chỉ IP bị cấm trên toàn hệ thống',
	'globalblock' => 'Cấm một địa chỉ IP trên toàn hệ thống',
	'globalblockstatus' => 'Trạng thái cục bộ của các tác vụ cấm toàn cục',
	'removeglobalblock' => 'Bỏ cấm toàn cục',
	'right-globalblock' => 'Cấm toàn cục',
	'right-globalunblock' => 'Bỏ cấm toàn cục',
	'right-globalblock-whitelist' => 'Tắt tác vụ cấm toàn cục',
);

/** Volapük (Volapük)
 * @author Malafaya
 */
$messages['vo'] = array(
	'globalblocking-unblock-reason' => 'Kod:',
);

/** Yue (粵語)
 * @author Shinjiman
 */
$messages['yue'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|容許]]IP地址可以[[Special:GlobalBlockList|響多個wiki度封鎖]]',
	'globalblocking-block' => '全域封鎖一個IP地址',
	'globalblocking-block-intro' => '你可以用呢版去封鎖全部wiki嘅一個IP地址。',
	'globalblocking-block-reason' => '封鎖嘅原因:',
	'globalblocking-block-expiry' => '封鎖到期:',
	'globalblocking-block-expiry-other' => '其它嘅到期時間',
	'globalblocking-block-expiry-otherfield' => '其它時間:',
	'globalblocking-block-legend' => '全域封鎖一位用戶',
	'globalblocking-block-options' => '選項',
	'globalblocking-block-errors' => '個封鎖唔成功，因為:
$1',
	'globalblocking-block-ipinvalid' => '你所輸入嘅IP地址 ($1) 係無效嘅。
請留意嘅係你唔可以輸入一個用戶名！',
	'globalblocking-block-expiryinvalid' => '你所輸入嘅到期 ($1) 係無效嘅。',
	'globalblocking-block-submit' => '全域封鎖呢個IP地址',
	'globalblocking-block-success' => '個IP地址 $1 已經響所有Wikimedia計劃度成功噉封鎖咗。
你亦都可以睇吓個[[Special:GlobalBlockList|全域封鎖一覽]]。',
	'globalblocking-block-successsub' => '全域封鎖成功',
	'globalblocking-block-alreadyblocked' => '個IP地址 $1 已經全域封鎖緊。你可以響[[Special:GlobalBlockList|全域封鎖一覽]]度睇吓現時嘅封鎖。',
	'globalblocking-block-bigrange' => '你所指定嘅範圍 ($1) 太大去封鎖。
你可以封鎖，最多65,536個地址 (/16 範圍)',
	
	'globalblocking-list-intro' => '呢個係全部現時生效緊嘅全域封鎖。
一啲嘅封鎖標明咗響本地停用：即係呢個封鎖響其它wiki度應用咗，但係本地管理員決定咗響呢個wiki度停用佢哋。',
	'globalblocking-list' => '全域封鎖IP地址一覽',
	'globalblocking-search-legend' => '搵一個全域封鎖',
	'globalblocking-search-ip' => 'IP地址:',
	'globalblocking-search-submit' => '搵封鎖',
	'globalblocking-list-ipinvalid' => '你所搵嘅IP地址 ($1) 係無效嘅。
請輸入一個有效嘅IP地址。',
	'globalblocking-search-errors' => '你之前搵過嘅嘢唔成功，因為:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') 全域封鎖咗 '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => '於$1到期',
	'globalblocking-list-anononly' => '限匿名',
	'globalblocking-list-unblock' => '解封',
	'globalblocking-list-whitelisted' => '由$1於本地封鎖: $2',
	'globalblocking-list-whitelist' => '本地狀態',
	'globalblocking-goto-block' => '全域封鎖一個 IP 地址',
	'globalblocking-goto-unblock' => '拎走一個全域封鎖',
	'globalblocking-goto-status' => '改一個全域封鎖嘅本地狀態',
		
	'globalblocking-return' => '返去全域封鎖一覽',
	'globalblocking-notblocked' => '你所輸入嘅 IP 地址 ($1) 並無全域封鎖到。',

	'globalblocking-unblock' => '拎走一個全域封鎖',
	'globalblocking-unblock-ipinvalid' => '你輸入嘅IP地址 ($1) 係無效嘅。
請留意嘅係你唔可以輸入一個用戶名！',
	'globalblocking-unblock-legend' => '拎走一個全域封鎖',
	'globalblocking-unblock-submit' => '拎走全域封鎖',
	'globalblocking-unblock-reason' => '原因:',
	'globalblocking-unblock-unblocked' => "你己經成功噉拎走咗響IP地址 '''$1''' 嘅全域封鎖 #$2",
	'globalblocking-unblock-errors' => '你唔可以拎走嗰個IP地址嘅全域封鎖，因為:
$1',
	'globalblocking-unblock-successsub' => '全域封鎖已經成功噉拎走咗',
	'globalblocking-unblock-subtitle' => '拎走全域封鎖',
	'globalblocking-unblock-intro' => '你可以用呢個表去拎走一個全域封鎖。
[[Special:GlobalBlockList|撳呢度]]返去個全域封鎖一覽。',
	
	'globalblocking-whitelist' => '全域封鎖嘅本地狀態',
	'globalblocking-whitelist-legend' => '改本地狀態',
	'globalblocking-whitelist-reason' => '改嘅原因:',
	'globalblocking-whitelist-status' => '本地狀態:',
	'globalblocking-whitelist-statuslabel' => '停用響{{SITENAME}}嘅全域封鎖',
	'globalblocking-whitelist-submit' => '改本地狀態',
	'globalblocking-whitelist-whitelisted' => "你已經成功噉響{{SITENAME}}嘅IP地址 '''$1''' 度停用咗全域封鎖 #$2。",
	'globalblocking-whitelist-dewhitelisted' => "你已經成功噉響{{SITENAME}}嘅IP地址 '''$1''' 度再次啟用咗全域封鎖 #$2。",
	'globalblocking-whitelist-successsub' => '本地狀態已經成功噉改咗',
	'globalblocking-whitelist-nochange' => '你未對呢個封鎖嘅本地狀態改過嘢。
[[Special:GlobalBlockList|返去全域封鎖一覽]]。',
	'globalblocking-whitelist-errors' => '基於下面嘅{{PLURAL:$1|原因|原因}}，你改過嘅全域封鎖本地狀態唔成功：',
	'globalblocking-whitelist-intro' => "你可以用呢個表去改全域封鎖嘅本地狀態。
如果一個全域封鎖響呢個wiki度停用咗，受影響嘅 IP 地址可以正常噉編輯。
[[Special:GlobalBlockList|返去全域封鎖一覽]]。",

	'globalblocking-blocked' => "你嘅IP地址已經由'''\$1''' (''\$2'') 響所有嘅Wikimedia wiki 度全部封鎖晒。
個原因係 ''\"\$3\"''。個封鎖會響''\$4''到期。",

	'globalblocking-logpage' => '全域封鎖日誌',
	'globalblocking-logpagetext' => '呢個係響呢個wiki度，整過同拎走過嘅全域封鎖日誌。
要留意嘅係全域封鎖可以響其它嘅wiki度整同拎走。
要睇活躍嘅全域封鎖，你可以去睇個[[Special:GlobalBlockList|全域封鎖一覽]]。',
	'globalblocking-block-logentry' => '全域封鎖咗[[$1]]於 $2 到期',
	'globalblocking-unblock-logentry' => '拎走咗[[$1]]嘅全域封鎖',
	'globalblocking-whitelist-logentry' => '停用咗[[$1]]響本地嘅全域封鎖',
	'globalblocking-dewhitelist-logentry' => '再開[[$1]]響本地嘅全域封鎖',
	'globalblocklist' => '全域封鎖IP地址一覽',
	'globalblock' => '全域封鎖一個IP地址',
	'globalblockstatus' => '全域封鎖嘅本地狀態',
	'removeglobalblock' => '拎走一個全域封鎖',
	
	// User rights
	'right-globalblock' => '整一個全域封鎖',
	'right-globalunblock' => '拎走全域封鎖',
	'right-globalblock-whitelist' => '響本地停用全域封鎖',
);

/** Simplified Chinese (‪中文(简体)‬)
 * @author Shinjiman
 */
$messages['zh-hans'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|容许]]IP地址可以[[Special:GlobalBlockList|在多个wiki中封锁]]',
	'globalblocking-block' => '全域封锁一个IP地址',
	'globalblocking-block-intro' => '您可以用这个页面去封锁全部wiki中的一个IP地址。',
	'globalblocking-block-reason' => '封锁的理由:',
	'globalblocking-block-expiry' => '封锁到期:',
	'globalblocking-block-expiry-other' => '其它的到期时间',
	'globalblocking-block-expiry-otherfield' => '其它时间:',
	'globalblocking-block-legend' => '全域封锁一位用户',
	'globalblocking-block-options' => '选项',
	'globalblocking-block-errors' => '该封锁不唔成功，因为:
$1',
	'globalblocking-block-ipinvalid' => '您所输入的IP地址 ($1) 是无效的。
请留意的是您不可以输入一个用户名！',
	'globalblocking-block-expiryinvalid' => '您所输入的到期 ($1) 是无效的。',
	'globalblocking-block-submit' => '全域封锁这个IP地址',
	'globalblocking-block-success' => '该IP地址 $1 已经在所有Wikimedia计划中成功地封锁。
您亦都可以参看[[Special:GlobalBlockList|全域封锁名单]]。',
	'globalblocking-block-successsub' => '全域封锁成功',
	'globalblocking-block-alreadyblocked' => '该IP地址 $1 已经全域封锁中。您可以在[[Special:GlobalBlockList|全域封锁名单]]中参看现时的封锁。',
	'globalblocking-block-bigrange' => '您所指定的范围 ($1) 太大去封锁。
您可以封锁，最多65,536个地址 (/16 范围)',
	
	'globalblocking-list-intro' => '这是全部现时生效中的全域封锁。
一些的封锁已标明在本地停用：即是这个封锁在其它wiki上应用，但是本地管理员已决定在这个wiki上停用它们。',
	'globalblocking-list' => '全域封锁IP地址名单',
	'globalblocking-search-legend' => '搜寻一个全域封锁',
	'globalblocking-search-ip' => 'IP地址:',
	'globalblocking-search-submit' => '搜寻封锁',
	'globalblocking-list-ipinvalid' => '您所搜自导引IP地址 ($1) 是无效的。
请输入一个有效的IP地址。',
	'globalblocking-search-errors' => '您先前搜寻过的项目不成功，因为:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') 全域封锁了 '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => '于$1到期',
	'globalblocking-list-anononly' => '只限匿名',
	'globalblocking-list-unblock' => '解除封锁',
	'globalblocking-list-whitelisted' => '由$1于本地封锁: $2',
	'globalblocking-list-whitelist' => '本地状态',
	'globalblocking-goto-block' => '全域封锁一个 IP 地址',
	'globalblocking-goto-unblock' => '移除一个全域封锁',
	'globalblocking-goto-status' => '改一个全域封锁?本地状态',
		
	'globalblocking-return' => '回到全域封锁名单',
	'globalblocking-notblocked' => '您所输入的 IP 地址 ($1) 并无全域封锁。',

	'globalblocking-unblock' => '移除一个全域封锁',
	'globalblocking-unblock-ipinvalid' => '您所输入的IP地址 ($1) 是无效的。
请留意的是您不可以输入一个用户名！',
	'globalblocking-unblock-legend' => '移除一个全域封锁',
	'globalblocking-unblock-submit' => '移除全域封锁',
	'globalblocking-unblock-reason' => '原因:',
	'globalblocking-unblock-unblocked' => "您己经成功地移除了在IP地址 '''$1''' 上的全域封锁 #$2",
	'globalblocking-unblock-errors' => '您不可以移除该IP地址的全域封锁，因为:
$1',
	'globalblocking-unblock-successsub' => '全域封锁已经成功地移除',
	'globalblocking-unblock-subtitle' => '移除全域封锁',
	'globalblocking-unblock-intro' => '您可以用这个表格去移除一个全域封锁。
[[Special:GlobalBlockList|点击这里]]回到全域封锁名单。',
	
	'globalblocking-whitelist' => '全域封锁的本地状态',
	'globalblocking-whitelist-legend' => '更改本地状态',
	'globalblocking-whitelist-reason' => '改?原因:',
	'globalblocking-whitelist-status' => '本地状态:',
	'globalblocking-whitelist-statuslabel' => '停用在{{SITENAME}}上的全域封锁',
	'globalblocking-whitelist-submit' => '更改本地状态',
	'globalblocking-whitelist-whitelisted' => "您已经成功地在{{SITENAME}}上的IP地址 '''$1''' 中停用了全域封锁 #$2。",
	'globalblocking-whitelist-dewhitelisted' => "您已经成功地在{{SITENAME}}上的IP地址 '''$1''' 中再次启用了全域封锁 #$2。",
	'globalblocking-whitelist-successsub' => '本地状态已经成功地更改',
	'globalblocking-whitelist-nochange' => '您未对这个封锁的本地状态更改过。
[[Special:GlobalBlockList|回到全域封锁名单]]。',
	'globalblocking-whitelist-errors' => '基于以下的{{PLURAL:$1|原因|原因}}，您更改过的全域封锁本地状态不成功：',
	'globalblocking-whitelist-intro' => "您可以利用这个表格去更改全域封锁的本地状态。
如果一个全域封锁在这个wiki度停用，受影响的 IP 地址可以正常地编辑。
[[Special:GlobalBlockList|回到全域封锁名单]]。",

	'globalblocking-blocked' => "您的IP地址已经由'''\$1''' (''\$2'') 在所有的Wikimedia wiki 中全部封锁。
而理由是 ''\"\$3\"''。该封锁将会在''\$4''到期。",
	'globalblocking-logpage' => '全域封锁日志',
	'globalblocking-logpagetext' => '这个是在这个wiki中，弄过和移除整过的全域封锁日志。
要留意的是全域封锁可以在其它的wiki中度弄和移除。
要查看活跃的全域封锁，您可以去参阅[[Special:GlobalBlockList|全域封锁名单]]。',
	'globalblocking-block-logentry' => '全域封锁了[[$1]]于 $2 到期',
	'globalblocking-unblock-logentry' => '移除了[[$1]]的全域封锁',
	'globalblocking-whitelist-logentry' => '停用了[[$1]]于本地的全域封锁',
	'globalblocking-dewhitelist-logentry' => '再次启用[[$1]]于本地的全域封锁',
	'globalblocklist' => '全域封锁IP地址名单',
	'globalblock' => '全域封锁一个IP地址',
	'globalblockstatus' => '全域封锁的本地状态',
	'removeglobalblock' => '移除一个全域封锁',
	
	// User rights
	'right-globalblock' => '弄一个全域封锁',
	'right-globalunblock' => '移除全域封锁',
	'right-globalblock-whitelist' => '在本地停用全域封锁',
);

/** Traditional Chinese (‪中文(繁體)‬)
 * @author Alexsh
 * @author Shinjiman
 */
$messages['zh-hant'] = array(
	'globalblocking-desc' => '[[Special:GlobalBlock|容許]]IP地址可以[[Special:GlobalBlockList|在多個wiki中封鎖]]',
	'globalblocking-block' => '全域封鎖一個IP地址',
	'globalblocking-block-intro' => '您可以用這個頁面去封鎖全部wiki中的一個IP地址。',
	'globalblocking-block-reason' => '封鎖的理由:',
	'globalblocking-block-expiry' => '封鎖到期:',
	'globalblocking-block-expiry-other' => '其它的到期時間',
	'globalblocking-block-expiry-otherfield' => '其它時間:',
	'globalblocking-block-legend' => '全域封鎖一位用戶',
	'globalblocking-block-options' => '選項',
	'globalblocking-block-errors' => '該封鎖不唔成功，因為:
$1',
	'globalblocking-block-ipinvalid' => '您所輸入的IP地址 ($1) 是無效的。
請留意的是您不可以輸入一個用戶名！',
	'globalblocking-block-expiryinvalid' => '您所輸入的到期 ($1) 是無效的。',
	'globalblocking-block-submit' => '全域封鎖這個IP地址',
	'globalblocking-block-success' => '該IP地址 $1 已經在所有Wikimedia計劃中成功地封鎖。
您亦都可以參看[[Special:GlobalBlockList|全域封鎖名單]]。',
	'globalblocking-block-successsub' => '全域封鎖成功',
	'globalblocking-block-alreadyblocked' => '該IP地址 $1 已經全域封鎖中。您可以在[[Special:GlobalBlockList|全域封鎖名單]]中參看現時的封鎖。',
	'globalblocking-block-bigrange' => '您所指定的範圍 ($1) 太大去封鎖。
您可以封鎖，最多65,536個地址 (/16 範圍)',
	
	'globalblocking-list-intro' => '這是全部現時生效中的全域封鎖。
一些的封鎖已標明在本地停用：即是這個封鎖在其它wiki上應用，但是本地管理員已決定在這個wiki上停用它們。',
	'globalblocking-block-bigrange' => '指定封鎖的區段($1)過於龐大。
您最多只能封鎖65536個IP位址( /16區段)',
	'globalblocking-list' => '全域封鎖IP地址名單',
	'globalblocking-search-legend' => '搜尋一個全域封鎖',
	'globalblocking-search-ip' => 'IP地址:',
	'globalblocking-search-submit' => '搜尋封鎖',
	'globalblocking-list-ipinvalid' => '您所搜尋的IP地址 ($1) 是無效的。
請輸入一個有效的IP地址。',
	'globalblocking-search-errors' => '您先前搜尋過的項目不成功，因為:
$1',
	'globalblocking-list-blockitem' => "$1: '''$2''' (''$3'') 全域封鎖了 '''[[Special:Contributions/$4|$4]]''' ''($5)''",
	'globalblocking-list-expiry' => '於$1到期',
	'globalblocking-list-anononly' => '只限匿名',
	'globalblocking-list-unblock' => '解除封鎖',
	'globalblocking-list-whitelisted' => '由$1於本地封鎖: $2',
	'globalblocking-list-whitelist' => '本地狀態',
	'globalblocking-goto-block' => '全域封鎖一個 IP 地址',
	'globalblocking-goto-unblock' => '移除一個全域封鎖',
	'globalblocking-goto-status' => '改一個全域封鎖嘅本地狀態',
		
	'globalblocking-return' => '回到全域封鎖清單',
	'globalblocking-notblocked' => '您輸入的IP位址($1)尚未被全域封鎖。',

	'globalblocking-unblock' => '移除一個全域封鎖',
	'globalblocking-goto-unblock' => '移除全域封鎖',
	'globalblocking-unblock-ipinvalid' => '您所輸入的IP地址 ($1) 是無效的。
請留意的是您不可以輸入一個用戶名！',
	'globalblocking-unblock-legend' => '移除一個全域封鎖',
	'globalblocking-unblock-submit' => '移除全域封鎖',
	'globalblocking-unblock-reason' => '原因:',
	'globalblocking-unblock-unblocked' => "您己經成功地移除了在IP地址 '''$1''' 上的全域封鎖 #$2",
	'globalblocking-unblock-errors' => '您不可以移除該IP地址的全域封鎖，因為:
$1',
	'globalblocking-unblock-successsub' => '全域封鎖已經成功地移除',
	'globalblocking-unblock-subtitle' => '移除全域封鎖',
	'globalblocking-unblock-intro' => '您可以用這個表格去移除一個全域封鎖。
[[Special:GlobalBlockList|點擊這裏]]回到全域封鎖名單。',
	
	'globalblocking-whitelist' => '全域封鎖的本地狀態',
	'globalblocking-whitelist-legend' => '更改本地狀態',
	'globalblocking-whitelist-reason' => '改嘅原因:',
	'globalblocking-whitelist-status' => '本地狀態:',
	'globalblocking-whitelist-statuslabel' => '停用在{{SITENAME}}上的全域封鎖',
	'globalblocking-whitelist-submit' => '更改本地狀態',
	'globalblocking-whitelist-whitelisted' => "您已經成功地在{{SITENAME}}上的IP地址 '''$1''' 中停用了全域封鎖 #$2。",
	'globalblocking-whitelist-dewhitelisted' => "您已經成功地在{{SITENAME}}上的IP地址 '''$1''' 中再次啟用了全域封鎖 #$2。",
	'globalblocking-whitelist-successsub' => '本地狀態已經成功地更改',
	'globalblocking-whitelist-nochange' => '您未對這個封鎖的本地狀態更改過。
[[Special:GlobalBlockList|回到全域封鎖名單]]。',
	'globalblocking-whitelist-errors' => '基於以下的{{PLURAL:$1|原因|原因}}，您更改過的全域封鎖本地狀態不成功：',
	'globalblocking-whitelist-intro' => "您可以利用這個表格去更改全域封鎖的本地狀態。
如果一個全域封鎖在這個wiki度停用，受影響的 IP 地址可以正常地編輯。
[[Special:GlobalBlockList|回到全域封鎖名單]]。",

	'globalblocking-blocked' => "您的IP地址已經由'''\$1''' (''\$2'') 在所有的Wikimedia wiki 中全部封鎖。
而理由是 ''\"\$3\"''。該封鎖將會在''\$4''到期。",
	'globalblocking-logpage' => '全域封鎖日誌',
	'globalblocking-logpagetext' => '這個是在這個wiki中，弄過和移除整過的全域封鎖日誌。
要留意的是全域封鎖可以在其它的wiki中度弄和移除。
要查看活躍的全域封鎖，您可以去參閱[[Special:GlobalBlockList|全域封鎖名單]]。',
	'globalblocking-block-logentry' => '全域封鎖了[[$1]]於 $2 到期',
	'globalblocking-unblock-logentry' => '移除了[[$1]]的全域封鎖',
	'globalblocking-whitelist-logentry' => '停用了[[$1]]於本地的全域封鎖',
	'globalblocking-dewhitelist-logentry' => '再次啟用[[$1]]於本地的全域封鎖',
	'globalblocklist' => '全域封鎖IP地址名單',
	'globalblock' => '全域封鎖一個IP地址',
	'globalblockstatus' => '全域封鎖的本地狀態',
	'removeglobalblock' => '移除一個全域封鎖',
	
	// User rights
	'right-globalblock' => '弄一個全域封鎖',
	'right-globalunblock' => '移除全域封鎖',
	'right-globalblock-whitelist' => '在本地停用全域封鎖',
);

