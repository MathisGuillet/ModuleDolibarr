<?php

/* Copyright (C) 2019      Mathis Guillet	 <mathis.guillet@imie.fr>
*/




// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");


require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->loadLangs(array("monmodule@monmodule"));

$action=GETPOST('action', 'alpha');


// Securite acces client
if (! $user->rights->monmodule->read) accessforbidden();
$socid=GETPOST('socid','int');
if (isset($user->societe_id) && $user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$max=5;
$now=dol_now();


$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("",$langs->trans("Liste des propositions"));

print load_fiche_titre($langs->trans("Liste des propositions"),'','monmodule.png@monmodule');

print '<div class="fichecenter"><div class="fichethirdleft">';




//require_once ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/mymenu.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';



$massaction=GETPOST('massaction','alpha');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');
$search_ref = GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer = GETPOST('search_refcustomer','alpha');
$search_societe = GETPOST('search_societe','alpha');
$search_town = GETPOST('search_town','alpha');
$search_zip = GETPOST('search_zip','alpha');
$search_month = GETPOST('search_month','int');
$search_year = GETPOST('search_year','int');
$search_month_ht = GETPOST("search_montant_ht",'int');
$search_login = GETPOST("search_login", 'alpha');
//$search_statut = GETPOST("search_statut", 'int');
$remove = GETPOST('button_removefilter','alpha');
$viewstatut = GETPOST('viewstatut','alpha');
$object_statut=GETPOST('search_statut','alpha');

// Initialize technical object to manage context to save list fields
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'proposallist';


if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

// Delete button
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) {
    $search_ref = '';
    $search_refcustomer = '';
    $search_societe = '';
    $search_town = '';
    $search_zip = '';
    $search_month = '';
    $search_year = '';
    $search_month_ht = '';
    $search_login = '';
    $viewstatut='';
    $object_statut='';

}
if ($object_statut != '') $viewstatut=$object_statut;


// Default value of $sortfield
if (empty($sortfield)){
    $sortfield = "p.ref";
}

// Default value of $sortorder
if (empty($sortorder)){
    $sortorder = "ASC";
}


//Define empty object
$propal = new Propal($db);
$user = new User($db);
$societe = new Societe($db);
$formpropal = new FormPropal($db);
$form = new Form($db);
$formother = new FormOther($db);



$sql = 'SELECT p.rowid, p.ref, p.ref_client, p.fk_soc, s.nom, s.town, s.zip, p.datep, p.fin_validite, p.fk_user_author, p.fk_statut, p.total_ht, u.login as userLogin, u.lastname as userLastName, s.nom as societeName
		FROM '.MAIN_DB_PREFIX.'propal as p
		LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid
		LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON p.fk_soc = s.rowid
		WHERE p.ref LIKE "%'.$search_ref.'%"
		AND p.ref_client LIKE "%'.$search_refcustomer.'%"
		AND s.nom LIKE "%'.$search_societe.'%"
		AND s.town LIKE "%'.$search_town.'%"
		AND s.zip LIKE "%'.$search_zip.'%"
		AND p.total_ht LIKE "%'.$search_month_ht.'%"
		AND u.login LIKE "%'.$search_login.'%"';


// Filtre état
if ($viewstatut != '' && $viewstatut != '-1')
{
    $sql.= ' AND p.fk_statut IN ('.$db->escape($viewstatut).')';
}


// Format date pour les filtres
if ($search_month > 0)
{
    if ($search_year > 0 && empty($search_day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
    else if ($search_year > 0 && ! empty($search_day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
    else
        $sql.= " AND date_format(p.datep, '%m') = '".$db->escape($search_month)."'";
}
else if ($search_year > 0)
{
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}

$sql.=	' ORDER BY '.$sortfield.' '.$sortorder.'
		';

$resql = $db->query($sql);





// List of mass actions available
$arrayofmassactions =  array(
    'presend'=>$langs->trans("SendByMail"),
    'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->propal->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);


// Column title fields
$checkedtypetiers=0;
$arrayfields=array(
	'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'p.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'p.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
	'p.fin_validite'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
	'p.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
	'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);



print '<table class="tagtable liste">';
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

print '<tr class="liste_titre_filter">';
    // Ref
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';

    // Ref client
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
    print '</td>';

    // Ref societe
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" size="10" name="search_societe" value="'.$search_societe.'">';
    print '</td>';

    // Search town
    print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';

    // Search zip
    print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';

    // Date
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
    $formother->select_year($search_year,'search_year',1, 20, 5);
    print '</td>';

    // Date end
    print '<td class="liste_titre" align="center">';
    print '</td>';

    // Amount
    print '<td class="liste_titre" align="right">';
    print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.$search_montant_ht.'">';
    print '</td>';

    // Author
    print '<td class="liste_titre" align="center">';
    print '<input class="flat" size="4" type="text" name="search_login" value="'.$search_login.'">';
    print '</td>';

    // Status
    print '<td class="liste_titre" align="right">';
    $formpropal->selectProposalStatus($viewstatut, 1, 0, 1, 'customer', 'search_statut');
    print '</td>';

    // Action column
    print '<td class="liste_titre" align="middle">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';

print '</tr>';



    // Order fields title
    print '<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['p.ref_client']['label'],$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['p.date']['label'],$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['p.fin_validite']['label'],$_SERVER["PHP_SELF"],'p.fin_validite','',$param, 'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['p.total_ht']['label'],$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($arrayfields['u.login']['label'],$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields);
    print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
    print '</tr>'."\n";



        // List the proposals according to the titles
		if ($resql){
			$num = $db->num_rows($resql);       // Number of rows in the table
			$i = 0;
			if ($num){
				while ($i < $num+1){
					$obj = $db->fetch_object($resql);
					if ($obj){
					    //Retrieves the data to put it in an object

                        $societe->fetch($obj->fk_soc);
                        $propal->fetch('',$obj->ref);


						print '<tr class="oddeven">';

                            print '<td class="tdoverflowmax200">';
                            print  $propal->getNomUrl(1, '', '', 0, 1);
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            print $obj->ref_client;
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            $societe->fetch($obj->fk_soc);
                            print $societe->getNomUrl(1);
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            $societe->fetch($obj->fk_soc);
                            print $societe->town;
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            $societe->fetch($obj->fk_soc);
                            print $societe->zip;
                            print '</td>';

                            print '<td align="center">'.dol_print_date($db->jdate($obj->datep),'day');
                            print '</td>';

                            print '<td align="center">'.dol_print_date($db->jdate($obj->fin_validite),'day');
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            print number_format($obj->total_ht, 2, ',', ',');
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            $user->fetch($obj->fk_user_author);
                            print $user->getLoginUrl(1);
                            print '</td>';

                            print '<td class="tdoverflowmax200">';
                            print $propal->LibStatut($obj->fk_statut, 5);
                            print '</td>';

                            print '<td class="nowrap" align="center">';
//                            if ($massactionbutton || $massaction)
//                            {
                                $selected=0;
//                                if (in_array($obj->rowid, $arrayofselected)) $selected=1;
                                print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
//                            }
                            print '</td>';

					print '</tr>';
				}
				$i++;
			}
		}
	}


print '</tr></table>';