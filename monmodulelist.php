<?php

/* Copyright (C) 2019      Mathis Guillet	 <mathis.guillet@imie.fr>
*/

require_once ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/menus/standard/mymenu.php';


$sortorder = GETPOST(sortorder,alpha);
$sortfield = GETPOST("sortfield",'alpha');


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



$sql = "SELECT p.ref, p.ref_client, p.fk_soc, p.datec, p.fin_validite, p.fk_user_author, p.fk_statut, p.total_ht, u.login as userLogin, u.lastname as userLastName, s.nom as societeName
		FROM ".MAIN_DB_PREFIX."propal as p
		LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_author = u.rowid
		LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid
		ORDER BY ".$sortfield." ".$sortorder."
		";



$resql = $db->query($sql);
$obj = $db->fetch_object($resql);




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




// Creating the row with column titles
print '<table class="noborder nohover" width="100%">';

if (! empty($arrayfields['p.ref_client']['checked'])){



    print '<tr>';

    // Status
    if (! empty($arrayfields['p.fk_statut']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone" align="right">';
        $formpropal->selectProposalStatus($viewstatut, 1, 0, 1, 'customer', 'search_statut');
        print '</td>';
    }

    // Action column
    print '<td class="liste_titre" align="middle">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print '</tr>';





    // Fields title
    print '<tr class="liste_titre">';
    if (! empty($arrayfields['p.ref']['checked']))            print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['p.ref_client']['checked']))     print_liste_field_titre($arrayfields['p.ref_client']['label'],$_SERVER["PHP_SELF"],'p.ref_client','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
    if (! empty($arrayfields['p.date']['checked']))           print_liste_field_titre($arrayfields['p.date']['label'],$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['p.fin_validite']['checked']))   print_liste_field_titre($arrayfields['p.fin_validite']['label'],$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['p.total_ht']['checked']))       print_liste_field_titre($arrayfields['p.total_ht']['label'],$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['u.login']['checked']))       	  print_liste_field_titre($arrayfields['u.login']['label'],$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

    // Hook fields
    $parameters=array('arrayfields'=>$arrayfields);
    if (! empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
    print '</tr>'."\n";




// Add where from hooks
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
    $sql.=$hookmanager->resPrint;

    $sql.= $db->order($sortfield,$sortorder);
    $sql.=', p.ref DESC';

// Count total nb of records
    $nbtotalofrecords = '';
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
    {
        $result = $db->query($sql);
        $nbtotalofrecords = $db->num_rows($result);
    }




// List the proposals according to the titles
		if ($resql){
			$num = $db->num_rows($resql);       // Number of rows in the table
			$i = 0;
			if ($num){
				while ($i < $num){
					$obj = $db->fetch_object($resql);
					if ($obj){

					    //Retrieves the data to put it in an object
                        $propal->fetch('',$obj->ref);
                        $societe->fetch($obj->fk_soc);

						print '<tr class="oddeven">';

						print '<td class="tdoverflowmax200">';
						print  $propal->getNomUrl(1);
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

						print '<td class="tdoverflowmax200">';
						print $obj->datec;
						print '</td>';

						print '<td class="tdoverflowmax200">';
						print $obj->fin_validite;
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

						print '</tr>';
					}
					$i++;
				}
			}
		}
	}

print '</tr></table>';