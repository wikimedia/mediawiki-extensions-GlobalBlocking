<?php
/**
 * Aliases for Special:GlobalBlock
 *
 * @addtogroup Extensions
 */

$aliases = array();

/** English
 * @author Jon Harald Søby
 */
$aliases['en'] = array(
	'GlobalBlock'     => array( 'GlobalBlock' ),
	'GlobalBlockList' => array( 'GlobalBlockList' ),
	'RemoveGlobalBlock' => array( 'GlobalUnblock', 'RemoveGlobalBlock' ),
	'GlobalBlockStatus' => array( 'GlobalBlockWhitelist', 'GlobalBlockStatus', 'DisableGlobalBlock' ),
);

$aliases['ar'] = array(
	'GlobalBlock'         => array( 'منع_عام' ),
	'GlobalBlockList'     => array( 'قائمة_منع_عامة' ),
	'RemoveGlobalBlock'   => array( 'رفع_منع_عام', 'إزالة_منع_عام' ),
	'GlobalBlockStatus'   => array( 'قائمة_المنع_العام_البيضاء', 'حالة_المنع_العام', 'تعطيل_المنع_العام' ),
);

$aliases['arz'] = array(
	'GlobalBlock'         => array( 'بلوك_عام' ),
	'GlobalBlockList'     => array( 'ليستة_بلوك_عامه' ),
	'RemoveGlobalBlock'   => array( 'شيل_بلوك_عام' ),
	'GlobalBlockStatus'   => array( 'ليستة_البلوك_العام_البيضا', 'حالة_البلوك_العام', 'تعطيل_البلوك_العام' ),
);

$aliases['be-tarask'] = array(
	'GlobalBlock'         => array( 'Глябальнае_блякаваньне' ),
	'GlobalBlockList'     => array( 'Сьпіс_глябальных_блякаваньняў' ),
);

$aliases['bs'] = array(
	'GlobalBlock'         => array( 'GlobalnoBlokiranje' ),
	'GlobalBlockList'     => array( 'ListaGlobalnogBlokiranja' ),
	'RemoveGlobalBlock'   => array( 'GlobalnoDeblokiranje', 'UklanjanjeGlobalnogBlokiranja' ),
	'GlobalBlockStatus'   => array( 'GlobalniDopusteniSpisak' ),
);

$aliases['de'] = array(
	'GlobalBlock'         => array( 'Globale Sperre' ),
	'GlobalBlockList'     => array( 'Liste globaler Sperren' ),
	'RemoveGlobalBlock'   => array( 'Globale Sperre aufheben' ),
	'GlobalBlockStatus'   => array( 'Ausnahme von globaler Sperre' ),
);

$aliases['dsb'] = array(
	'GlobalBlock'         => array( 'Globalne blokěrowanje' ),
	'GlobalBlockList'     => array( 'Lisćina globalnych blokěrowanjow' ),
	'RemoveGlobalBlock'   => array( 'Globalne blokěrowanje wótpóraś' ),
	'GlobalBlockStatus'   => array( 'Wuwześa z globalnego blokěrowanja' ),
);

$aliases['es'] = array(
	'GlobalBlock'         => array( 'Bloquear_global', 'Bloqueo_global' ),
	'GlobalBlockList'     => array( 'Lista_de_bloqueos_globales', 'Lista_bloqueos_globales' ),
	'RemoveGlobalBlock'   => array( 'Desbloquear_global' ),
	'GlobalBlockStatus'   => array( 'Lista_blanca_de_bloqueos_globales', 'Lista_blanca_bloqueos_globales' ),
);

$aliases['et'] = array(
	'GlobalBlock'         => array( 'Globaalselt_blokeerimine' ),
	'GlobalBlockList'     => array( 'Globaalne_blokeerimisloend' ),
	'RemoveGlobalBlock'   => array( 'Globaalse_blokeeringu_eemaldamine' ),
	'GlobalBlockStatus'   => array( 'Globaalsete_blokeeringute_valge_nimekiri' ),
);

$aliases['fa'] = array(
	'GlobalBlock'         => array( 'بستن_سراسری' ),
	'GlobalBlockList'     => array( 'فهرست_بستن_سراسری' ),
	'RemoveGlobalBlock'   => array( 'بازکردن_سراسری' ),
	'GlobalBlockStatus'   => array( 'فهرست_سفید_بستن_سراسری' ),
);

$aliases['fi'] = array(
	'GlobalBlock'         => array( 'Globaaliesto' ),
	'GlobalBlockList'     => array( 'Globaaliestojen lista' ),
	'RemoveGlobalBlock'   => array( 'Poista globaaliesto' ),
);

$aliases['fr'] = array(
	'GlobalBlock'         => array( 'Blocage global', 'BlocageGlobal' ),
	'RemoveGlobalBlock'   => array( 'Déblocage global', 'DéblocageGlobal' ),
);

$aliases['frp'] = array(
	'GlobalBlock'         => array( 'Blocâjo globâl', 'BlocâjoGlobâl' ),
	'GlobalBlockList'     => array( 'Lista des blocâjos globâls', 'ListaDesBlocâjosGlobâls' ),
	'RemoveGlobalBlock'   => array( 'Dèblocâjo globâl', 'DèblocâjoGlobâl' ),
	'GlobalBlockStatus'   => array( 'Lista blanche des blocâjos globâls', 'ListaBlancheDesBlocâjosGlobâls' ),
);

$aliases['gl'] = array(
	'GlobalBlock'         => array( 'Bloqueo global' ),
	'GlobalBlockList'     => array( 'Lista de bloqueos globais' ),
	'RemoveGlobalBlock'   => array( 'Desbloqueo global' ),
	'GlobalBlockStatus'   => array( 'Lista branca de bloqueos globais' ),
);

$aliases['gsw'] = array(
	'GlobalBlock'         => array( 'Wältwyti Sperri' ),
	'GlobalBlockList'     => array( 'Lischt vu wältwyte Sperrine' ),
	'RemoveGlobalBlock'   => array( 'Wältwyti Sperri uffhebe' ),
	'GlobalBlockStatus'   => array( 'Uusnahme vun ere wältwyte Sperri' ),
);

$aliases['he'] = array(
	'GlobalBlock'         => array( 'חסימה_גלובלית' ),
	'GlobalBlockList'     => array( 'רשימת_חסומים_גלובליים' ),
	'RemoveGlobalBlock'   => array( 'שחרור_חסימה_גלובלית', 'הסרת_חסימה_גלובלית' ),
	'GlobalBlockStatus'   => array( 'רשימה_לבנה_לחסימה_גלובלית', 'מצב_חסימה_גלובלית', 'ביטול_חסימה_גלובלית' ),
);

$aliases['hr'] = array(
	'GlobalBlock'         => array( 'Globalno_blokiraj' ),
	'GlobalBlockList'     => array( 'Globalno_blokirane_adrese' ),
	'RemoveGlobalBlock'   => array( 'Ukloni_globalno_blokiranje', 'Globalno_odblokiraj' ),
	'GlobalBlockStatus'   => array( 'Status_globalnog_blokiranja' ),
);

$aliases['hsb'] = array(
	'GlobalBlock'         => array( 'Globalne blokowanje' ),
	'GlobalBlockList'     => array( 'Lisćina globalnych blokowanjow' ),
	'RemoveGlobalBlock'   => array( 'Globalne blokowanje wotstronić' ),
	'GlobalBlockStatus'   => array( 'Wuwzaća z globalneho blokowanja' ),
);

$aliases['ht'] = array(
	'GlobalBlock'         => array( 'BlokajGlobal' ),
);

$aliases['hu'] = array(
	'GlobalBlock'         => array( 'Globális blokkolás' ),
	'GlobalBlockList'     => array( 'Globális blokkok listája' ),
	'RemoveGlobalBlock'   => array( 'Globális feloldás' ),
);

$aliases['ia'] = array(
	'GlobalBlock'         => array( 'Blocada global' ),
	'GlobalBlockList'     => array( 'Lista de blocadas global' ),
	'RemoveGlobalBlock'   => array( 'Disblocada global', 'Remover blocada global' ),
	'GlobalBlockStatus'   => array( 'Lista blanc de blocadas global', 'Stato de blocadas global', 'Disactivar blocada global' ),
);

$aliases['id'] = array(
	'GlobalBlock'         => array( 'Pemblokiran global', 'PemblokiranGlobal' ),
	'GlobalBlockList'     => array( 'Daftar pemblokiran global', 'DaftarPemblokiranGlobal' ),
	'RemoveGlobalBlock'   => array( 'Batalkan pemblokiran global', 'BatalkanPemblokiranGlobal' ),
	'GlobalBlockStatus'   => array( 'Daftar putih pemblokiran global', 'DaftarPutihPemblokiranGlobal' ),
);

$aliases['it'] = array(
	'GlobalBlock'         => array( 'BloccoGlobale' ),
	'GlobalBlockList'     => array( 'ElencoBlocchiGlobali', 'ListaBlocchiGlobali' ),
	'RemoveGlobalBlock'   => array( 'SbloccoGlobale' ),
	'GlobalBlockStatus'   => array( 'StatoLocaleBloccoGlobale', 'DisabilitaBloccoGlobale', 'ListaBiancaBloccoGlobale' ),
);

$aliases['ja'] = array(
	'GlobalBlock'         => array( 'グローバルブロック', 'グローバル・ブロック' ),
	'GlobalBlockList'     => array( 'グローバルブロック一覧', 'グローバル・ブロック一覧' ),
	'RemoveGlobalBlock'   => array( 'グローバルブロック解除', 'グローバル・ブロック解除' ),
	'GlobalBlockStatus'   => array( 'グローバルブロックホワイトリスト', 'グローバルブロック状態', 'グローバルブロック無効' ),
);

$aliases['ko'] = array(
	'GlobalBlock'         => array( '전체차단' ),
	'GlobalBlockList'     => array( '전체차단목록' ),
	'RemoveGlobalBlock'   => array( '전체차단취소', '전체차단해제' ),
);

$aliases['ksh'] = array(
	'GlobalBlock'         => array( 'Jemeinsam Sperre' ),
	'GlobalBlockList'     => array( 'Leß met jemeinsam Sperre' ),
	'RemoveGlobalBlock'   => array( 'Jemeinsam Sperre ophävve', 'Jemeinsam Sperre ophevve' ),
	'GlobalBlockStatus'   => array( 'Ußnahme vun de jemeinsam Sperre' ),
);

$aliases['lb'] = array(
	'GlobalBlock'         => array( 'Global Spären' ),
	'GlobalBlockList'     => array( 'Lëscht vun de globale Spären' ),
	'RemoveGlobalBlock'   => array( 'Global Spär ophiewen' ),
	'GlobalBlockStatus'   => array( 'Ausnahm vun der globaler Spär' ),
);

$aliases['mk'] = array(
	'GlobalBlock'         => array( 'ГлобалноБлокирање' ),
	'GlobalBlockList'     => array( 'ЛистаНаГлобалниБлокирања' ),
	'RemoveGlobalBlock'   => array( 'ГлобалниДеблокирања', 'БришиГлобаленБлок' ),
	'GlobalBlockStatus'   => array( 'СтатусНаГлобаленБлок', 'ОневозможиГлобаленБлок' ),
);

$aliases['ml'] = array(
	'GlobalBlock'         => array( 'ആഗോളതടയൽ' ),
	'GlobalBlockList'     => array( 'ആഗോളതടയൽപട്ടിക' ),
	'RemoveGlobalBlock'   => array( 'ആഗോളതടയൽനീക്കുക' ),
	'GlobalBlockStatus'   => array( 'ആഗോളതടയൽശുദ്ധപട്ടിക', 'ആഗോളതടയൽസ്ഥിതി', 'ആഗോളതടയൽപട്ടികനിർജീവമാക്കുക' ),
);

$aliases['mr'] = array(
	'GlobalBlock'         => array( 'वैश्विकब्लॉक' ),
	'GlobalBlockList'     => array( 'वैश्विकब्लॉकयादी' ),
	'RemoveGlobalBlock'   => array( 'वैश्विकअनब्लॉक' ),
	'GlobalBlockStatus'   => array( 'वैश्विकब्लॉकश्वेतपत्र' ),
);

$aliases['ms'] = array(
	'GlobalBlock'         => array( 'Sekatan sejagat' ),
	'GlobalBlockList'     => array( 'Senarai sekatan sejagat' ),
	'RemoveGlobalBlock'   => array( 'Batal sekatan sejagat' ),
	'GlobalBlockStatus'   => array( 'Senarai putih sekatan sejagat' ),
);

$aliases['mt'] = array(
	'GlobalBlock'         => array( 'BlokkGlobali' ),
	'GlobalBlockList'     => array( 'ListaBlokkGlobali' ),
);

$aliases['nds-nl'] = array(
	'GlobalBlock'         => array( 'Globaal_blokkeren' ),
	'GlobalBlockList'     => array( 'Globale_blokkeerlieste' ),
	'RemoveGlobalBlock'   => array( 'Globaal_deblokkeren' ),
	'GlobalBlockStatus'   => array( 'Witte_lieste_blokkeringen' ),
);

$aliases['nl'] = array(
	'GlobalBlock'         => array( 'GlobaalBlokkeren' ),
	'GlobalBlockList'     => array( 'GlobaleBlokkadelijst', 'GlobaleBlokkeerlijst' ),
	'RemoveGlobalBlock'   => array( 'GlobaalDeblokkeren', 'GlobaleBlokkadeVerwijderen' ),
	'GlobalBlockStatus'   => array( 'WitteLijstGlobaleBlokkades', 'GlobaleBlokkadestatus' ),
);

$aliases['nn'] = array(
	'GlobalBlockList'     => array( 'Global blokkeringsliste' ),
);

$aliases['no'] = array(
	'GlobalBlock'         => array( 'Blokker globalt', 'Global blokkering' ),
	'GlobalBlockList'     => array( 'Global blokkeringsliste' ),
	'RemoveGlobalBlock'   => array( 'Avblokker globalt', 'Global avblokkering' ),
	'GlobalBlockStatus'   => array( 'Hviteliste for global blokkering' ),
);

$aliases['oc'] = array(
	'GlobalBlock'         => array( 'Blocatge global', 'BlocatgeGlobal' ),
	'RemoveGlobalBlock'   => array( 'Desblocatge global', 'DesblocatgeGlobal' ),
);

$aliases['pl'] = array(
	'GlobalBlock'         => array( 'Zablokuj globalnie' ),
	'GlobalBlockList'     => array( 'Spis globalnie zablokowanych adresów IP' ),
	'RemoveGlobalBlock'   => array( 'Odblokuj globalnie' ),
	'GlobalBlockStatus'   => array( 'Lokalny status globalnych blokad' ),
);

$aliases['ps'] = array(
	'GlobalBlock'         => array( 'نړېوال بنديزونه' ),
);

$aliases['pt'] = array(
	'GlobalBlock'         => array( 'Bloqueio global' ),
	'GlobalBlockList'     => array( 'Lista de bloqueios globais' ),
	'RemoveGlobalBlock'   => array( 'Desbloqueio global', 'Remover bloqueio global' ),
	'GlobalBlockStatus'   => array( 'Lista branca de bloqueios globais' ),
);

$aliases['pt-br'] = array(
	'GlobalBlock'         => array( 'Bloquear globalmente' ),
);

$aliases['ro'] = array(
	'GlobalBlock'         => array( 'Blocare globală' ),
	'GlobalBlockList'     => array( 'Lista de blocări globale' ),
	'RemoveGlobalBlock'   => array( 'Deblocare globală', 'Elimină blocarea globală' ),
	'GlobalBlockStatus'   => array( 'Lista albă de blocări globale', 'Stare blocare globală', 'Dezactivare blocare globală' ),
);

$aliases['sa'] = array(
	'GlobalBlock'         => array( 'वैश्विकप्रतिबन्ध' ),
	'GlobalBlockList'     => array( 'वैश्विकप्रतिबन्धसूची' ),
	'RemoveGlobalBlock'   => array( 'वैश्विकअप्रतिबन्ध' ),
	'GlobalBlockStatus'   => array( 'वैश्विकअप्रतिबन्धसूची' ),
);

$aliases['sk'] = array(
	'GlobalBlock'         => array( 'GlobálneBlokovanie' ),
	'GlobalBlockList'     => array( 'ZoznamGlobálnehoBlokovania' ),
	'RemoveGlobalBlock'   => array( 'GlobálneOdblokovanie' ),
	'GlobalBlockStatus'   => array( 'BielaListinaGlobálnehoBlokovania' ),
);

$aliases['sv'] = array(
	'GlobalBlock'         => array( 'Global blockering' ),
	'GlobalBlockList'     => array( 'Global blockeringslista' ),
	'RemoveGlobalBlock'   => array( 'Global avblockering' ),
);

$aliases['tl'] = array(
	'GlobalBlock'         => array( 'Pandaigdigang paghadlang' ),
	'GlobalBlockList'     => array( 'Talaan ng pandaigdigang paghadlang' ),
	'RemoveGlobalBlock'   => array( 'Pandaigdigang hindi paghadlang', 'Tanggalin ang pandaigdigang paghadlang' ),
	'GlobalBlockStatus'   => array( 'Puting talaan ng pandaigdigang paghadlang', 'Kalagayan ng pandaigdigang paghadlang', 'Huwag paganahin ang pandaigdigang paghadlang' ),
);

$aliases['tr'] = array(
	'GlobalBlock'         => array( 'KüreselEngel' ),
	'GlobalBlockList'     => array( 'KüreselEngelListesi' ),
	'RemoveGlobalBlock'   => array( 'KüreselEngelKaldırma' ),
	'GlobalBlockStatus'   => array( 'KüreselEngelBeyazListesi' ),
);

$aliases['vec'] = array(
	'GlobalBlock'         => array( 'BlocoGlobal' ),
);

$aliases['zh-hans'] = array(
	'GlobalBlock'         => array( '全域封禁' ),
	'GlobalBlockList'     => array( '全域封禁列表' ),
	'RemoveGlobalBlock'   => array( '解除全域封禁' ),
	'GlobalBlockStatus'   => array( '全域封禁白名单' ),
);

$aliases['zh-hant'] = array(
	'GlobalBlock'         => array( '全域封禁' ),
	'GlobalBlockList'     => array( '全域封禁列表' ),
	'RemoveGlobalBlock'   => array( '解除全域封禁' ),
	'GlobalBlockStatus'   => array( '全域封禁白名單' ),
);
