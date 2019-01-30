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

llxHeader("",$langs->trans("Liste des Factures"));


print '<div class="fichecenter"><div class="fichethirdleft">';

require_once ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/mymenu.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';


$langs->load('bills');
$langs->load('companies');
$langs->load('products');


$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');
$facnumber = GETPOST('facumber','alpha');
$search_ref = GETPOST('search_ref','alpha');
$search_refcustomer = GETPOST('search_refcustomer','alpha');
$search_societe = GETPOST('search_societe','alpha');
$search_town = GETPOST('search_town','alpha');
$search_zip = GETPOST('search_zip','alpha');
$search_month = GETPOST('search_month','int');
$search_year = GETPOST('search_year','int');
$year_lim = GETPOST('year_lim','int');
$month_lim = GETPOST('month_lim','int');
$search_month_ht = GETPOST("search_montant_ht",'int');
//$search_statut = GETPOST("search_statut", 'int');
$remove = GETPOST('button_removefilter','alpha');
$viewstatut = GETPOST('viewstatut','alpha');
$object_statut = GETPOST('search_statut','alpha');
$viewpaymentmode = GETPOST('viewpaymentmode','alpha');
$objet_paymentmode = GETPOST('search_paymentmode','alpha');


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
    $viewstatut = '';
    $object_statut = '';
    $viewpaymentmode = '';
    $objet_paymentmode = '';
    $year_lim = '';
    $month_lim = '';

}
if ($object_statut != '') $viewstatut=$object_statut;
if ($objet_paymentmode != '') $viewpaymentmode=$objet_paymentmode;


// Default value of $sortfield
if (empty($sortfield)){
    $sortfield = "f.facnumber";
}

// Default value of $sortorder
if (empty($sortorder)){
    $sortorder = "ASC";
}


//Define empty object
$user = new User($db);
$societe = new Societe($db);
$formfile = new FormFile($db);
$form = new Form($db);
$formother = new FormOther($db);
$facture = new Facture($db);



$sql = 'SELECT f.rowid as id, f.facnumber as ref, f.ref_client, f.fk_soc, f.datef, f.date_lim_reglement, f.fk_mode_reglement, f.total as total_ht, s.nom, s.town, f.datef as df, s.zip, f.fk_user_author, f.fk_statut, u.lastname as userLastName, s.nom as societeName
		FROM '.MAIN_DB_PREFIX.'facture as f
		LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON f.fk_user_author = u.rowid
		LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON f.fk_soc = s.rowid
		WHERE f.ref_client LIKE "%'.$search_refcustomer.'%"
		AND f.facnumber LIKE "%'.$search_ref.'%"
		AND s.nom LIKE "%'.$search_societe.'%"
		AND s.town LIKE "%'.$search_town.'%"
		AND s.zip LIKE "%'.$search_zip.'%"
		AND f.total LIKE "%'.$search_month_ht.'%"';



// Filtre état
if ($viewstatut != '' && $viewstatut != '-1')
{
    $sql.= ' AND f.fk_statut IN ('.$db->escape($viewstatut).')';
}

if ($viewpaymentmode != '' && $viewpaymentmode > 0)
{
    $sql.= ' AND f.fk_mode_reglement IN ('.$db->escape($viewpaymentmode).')';
}


// Format date facturation pour les filtres
if ($search_month > 0)
{
    if ($search_year > 0 && empty($search_day))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
    else if ($search_year > 0 && ! empty($search_day))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
    else
        $sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$db->escape($search_month)."'";
}
else if ($search_year > 0)
{
    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}

// Format date échéance pour les filtres
if ($month_lim > 0)
{
    if ($year_lim > 0 && empty($search_day))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
    else if ($year_lim > 0 && ! empty($search_day))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $search_day, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $search_day, $year_lim))."'";
    else
        $sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$db->escape($month_lim)."'";
}
else if ($year_lim > 0)
{
    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}

$sql.=	' ORDER BY '.$sortfield.' '.$sortorder.'
		';



$resql = $db->query($sql);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'f.facnumber'=>'Ref',
    'f.ref_client'=>'RefCustomer',
    'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'f.note_public'=>'NotePublic',
);


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
    'f.facnumber'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'f.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
    'f.datef'=>array('label'=>$langs->trans("DateInvoice"), 'checked'=>1),
    'f.date_lim_reglement'=>array('label'=>$langs->trans("DateDue"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'f.fk_mode_reglement'=>array('label'=>$langs->trans("PaymentMode"), 'checked'=>1),
    'f.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'f.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

//llxHeader("",$langs->trans("Liste des factures"));
//print load_fiche_titre($langs->trans("Liste des factures"),'','monmodule.png@monmodule');


//print "<pre>
//            <h3>MySQL Dump</h3>
//           $sql
//        </pre>";

//print '<div class="fichecenter"><div class="fichethirdleft">';

$numreq = $db->num_rows($resql);
$titre_tableau = load_fiche_titre($langs->trans("Liste des Factures (".$numreq.")"),'','monmodule.png@monmodule');
print $titre_tableau;



print '<table class="tagtable liste">';
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

print '<tr class="liste_titre_filter">';

// facnumber
print '<td class="liste_titre" align="left">';
print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';

// Ref client
print '<td class="liste_titre">';
print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
print '</td>';

// Billing date
print '<td class="liste_titre" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
$formother->select_year($search_year,'search_year',1, 20, 5);
print '</td>';

// Date deadline
print '<td class="liste_titre" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_lim" value="'.dol_escape_htmltag($month_lim).'">';
$formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
print '<br><input type="checkbox" name="option" value="late"'.($option == 'late'?' checked':'').'> '.$langs->trans("Late");
print '</td>';

// Ref societe
print '<td class="liste_titre" align="left">';
print '<input class="flat" type="text" size="10" name="search_societe" value="'.$search_societe.'">';
print '</td>';

// Search town
print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';

// Search zip
print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';

// Search payment choice
print '<td class="liste_titre" align="left">';
$form->select_types_paiements($search_paymentmode, 'search_paymentmode', '', 0, 0, 1, 10);
print '</td>';

// Amount
print '<td class="liste_titre" align="right">';
print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.$search_montant_ht.'">';
print '</td>';

// Status
print '<td class="liste_titre maxwidthonsmartphone" align="right">';
$liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
print $form->selectarray('search_status', $liststatus, $search_status, 1);
print '</td>';

// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>';



// Order fields title
print '<tr class="liste_titre">';
print_liste_field_titre($arrayfields['f.facnumber']['label'],$_SERVER["PHP_SELF"],'f.facnumber','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['f.ref_client']['label'],$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['f.datef']['label'],$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['f.date_lim_reglement']['label'],$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($arrayfields['f.fk_mode_reglement']['label'],$_SERVER["PHP_SELF"],"f.fk_mode_reglement","",$param,"",$sortfield,$sortorder);
print_liste_field_titre($arrayfields['f.total_ht']['label'],$_SERVER['PHP_SELF'],'f.multicurrency_total_ht','',$param,'align="right"',$sortfield,$sortorder);


// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
print_liste_field_titre($arrayfields['f.fk_statut']['label'],$_SERVER["PHP_SELF"],"f.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
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
                $facture->fetch($obj->facnumber);
                $facture->id=$obj->id;
                $facture->ref=$obj->ref;
                $facture->type=$obj->type;
                $facture->statut=$obj->fk_statut;
                $facture->date_lim_reglement=$db->jdate($obj->datelimite);
                $facture->note_public=$obj->note_public;
                $facture->note_private=$obj->note_private;

                $societe->id=$obj->socid;
                $societe->name=$obj->name;
                $societe->client=$obj->client;
                $societe->fournisseur=$obj->fournisseur;
                $societe->code_client=$obj->code_client;
                $societe->code_compta_client=$obj->code_compta_client;
                $societe->code_fournisseur=$obj->code_fournisseur;
                $societe->code_compta_fournisseur=$obj->code_compta_fournisseur;
                $societe->email=$obj->email;
                $societe->country_code=$obj->country_code;

//                $paiement = $facturestatic->getSommePaiement();
//                $totalcreditnotes = $facturestatic->getSumCreditNotesUsed();
//                $totaldeposits = $facturestatic->getSumDepositsUsed();
//                $totalpay = $paiement + $totalcreditnotes + $totaldeposits;
//                $remaintopay = $obj->total_ttc - $totalpay;


                print '<tr class="oddeven">';

                // Facnumber
                print '<td class="nowrap">';

                print '<table class="nobordernopadding"><tr class="nocellnopadd">';

                print '<td class="nobordernopadding nowrap">';
                print $facture->getNomUrl(1,'',200,0,'',0,1);
                print empty($obj->increment)?'':' ('.$obj->increment.')';
                print '</td>';

                print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
                $filename=dol_sanitizeFileName($obj->ref);
                $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->ref);
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->id;
                print $formfile->getDocumentsLink($facture->element, $filename, $filedir);
                print '</td>';
                print '</tr>';
                print '</table>';

                print "</td>\n";

                // RefCustomers
                print '<td class="tdoverflowmax200">';
                print $obj->ref_client;
                print '</td>';

                // Billing date
                print '<td class="tdoverflowmax200">';
                print dol_print_date($db->jdate($obj->df),'day');
                print '</td>';

                // Date
                print '<td align="center">'.dol_print_date($db->jdate($obj->date_lim_reglement),'day');
                print '</td>';

                // Societe Name
                print '<td class="tdoverflowmax200">';
                print $societe->getNomUrl(1);
                print '</td>';

                // Town
                print '<td class="tdoverflowmax200">';
                print $societe->town;
                print '</td>';

                // Zip
                print '<td class="tdoverflowmax200">';
                $societe->fetch($obj->fk_soc);
                print $societe->zip;
                print '</td>';

                // Payment choice
                print '<td class="tdoverflowmax200">';
                $form->form_modes_reglement($_SERVER['PHP_SELF'], $obj->fk_mode_reglement, 'none', '', -1);
                print '</td>';

                // Amount
                print '<td align="right">'.price($obj->total_ht)."</td>\n";

                // Statut
                print '<td align="right" class="nowrap">';
                print $facture->LibStatut($obj->paye,$obj->fk_statut,5,$paiement,$obj->type);
                print "</td>";

                // Action column
                print '<td class="nowrap" align="center">';
//                if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
//                {
                    $selected=0;
//                    if (in_array($obj->id, $arrayofselected)) $selected=1;
                    print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected?' checked="checked"':'').'>';
//                }
                print '</td>' ;

                print '</tr>';
            }
            $i++;
        }
    }
}


print '</tr></table>';

//
//print '</div></div>';
//
//llxFooter();
//
//$db->close();
