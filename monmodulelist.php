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
$search_ref = GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_refcustomer = GETPOST('search_refcustomer','alpha');
$search_societe = GETPOST('search_societe','alpha');
$search_town = GETPOST('search_town','alpha');
$search_zip = GETPOST('search_zip','alpha');
$search_month = GETPOST(search_month,int);
$search_year = GETPOST("search_year","int");
$search_month_ht = GETPOST("search_montant_ht","int");
$search_login = GETPOST(search_login, alpha);
$search_statut = GETPOST(search_statut, alpha);
$remove = GETPOST('button_removefilter','alpha');



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



$sql = 'SELECT p.rowid, p.ref, p.ref_client, p.fk_soc, s.nom, s.town, s.zip, p.datec, p.fin_validite, p.fk_user_author, p.fk_statut, p.total_ht, u.login as userLogin, u.lastname as userLastName, s.nom as societeName
		FROM '.MAIN_DB_PREFIX.'propal as p
		LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid
		LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON p.fk_soc = s.rowid
		WHERE p.ref LIKE "%'.$search_ref.'%"
		AND p.ref_client LIKE "%'.$search_refcustomer.'%"
		AND s.nom LIKE "%'.$search_societe.'%"
		AND s.town LIKE "%'.$search_town.'%"
		AND s.zip LIKE "%'.$search_zip.'%"
		AND p.total_ht LIKE "%'.$search_month_ht.'%"
		AND u.login LIKE "%'.$search_login.'%"
		ORDER BY '.$sortfield.' '.$sortorder.'
		';

//AND p.fk_statut LIKE "%'.$search_statut.'%"


$resql = $db->query($sql);


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



    // Button add filter
    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';

// lines of filter fields

    print '<tr class="liste_titre_filter>';

    // Ref
    if (! empty($arrayfields['p.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
        print '</td>';
    }

    // Ref client
    if (! empty($arrayfields['p.ref_client']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
        print '</td>';
    }

    // Ref societe
    if (! empty($arrayfields['s.nom']['checked'])) {
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" size="10" name="search_societe" value="'.$search_societe.'">';
        print '</td>';
    }

    // Search town
    if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';

    // Search zip
    if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';

    // Date
    if (! empty($arrayfields['p.date']['checked'])) {
        print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
        $formother->select_year($search_year,'search_year',1, 20, 5);
        print '</td>';
    }

    // Date end
    if (! empty($arrayfields['p.fin_validite']['checked'])) {
        print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
        $formother->select_year($search_year,'search_year',1, 20, 5);
        print '</td>';
    }

    // Amount
    if (! empty($arrayfields['p.total_ht']['checked'])) {
        print '<td class="liste_titre" align="right">';
        print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.$search_montant_ht.'">';
        print '</td>';
    }

    // Author
    if (! empty($arrayfields['u.login']['checked'])) {
        print '<td class="liste_titre" align="center">';
        print '<input class="flat" size="4" type="text" name="search_login" value="'.$search_login.'">';
        print '</td>';
    }

    // Status
    if (! empty($arrayfields['p.fk_statut']['checked'])) {
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
















    if (GETPOST('button_removefilter','alpha')) {
        $search_ref='';
    }















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