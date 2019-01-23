<?php

require_once ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';


$propal = new Propal($db);
$user = new User($db);
$societe = new Societe($db);
// die(var_dump($societe));
//$propal->fetch(3);

//

//$resql = $db->query("SELECT * FROM `llx_propal`, `llx_societe`, `llx_propaldet`");  //llx_propal
$sql = "SELECT p.ref, p.ref_client, p.fk_soc, p.datec, p.fin_validite, p.fk_user_author, p.fk_statut, p.total_ht, u.login as userLogin, u.lastname as userLastName, s.nom as societeName
		FROM ".MAIN_DB_PREFIX."propal as p
		LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_author = u.rowid
		LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid
		";
//		die($sql);

$resql = $db->query($sql);  //llx_propal
//die(var_dump($resql));

$obj = $db->fetch_object($resql);
//die(print_r($obj));

$arrayfields=array(
	'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'p.ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	// 'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	// 'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	// 'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'p.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
	'p.fin_validite'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
	'p.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
	// 'p.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
	// 'p.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
	'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
	// 'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	// 'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);



print '<table class="noborder nohover" width="100%">';

	if (! empty($arrayfields['p.ref_client']['checked'])){

		print '<tr class="liste_titre">';
		foreach ($arrayfields as $key => $label) {
			print '<td class="tdoverflowmax200">'.$label['label'].'</td>';
		}
		print '</tr>';
				



		if ($resql){
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num){
				while ($i < $num){
					$obj = $db->fetch_object($resql);
					if ($obj){

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
						$prix_ht = $obj->total_ht;
						print number_format($prix_ht, 2, ',', ',');
						print '</td>';


						print '<td class="tdoverflowmax200">';
						$user->fetch($obj->fk_user_author);
						print $user->getLoginUrl(1);
						//print $obj->userLogin . " (" .$obj->userLastName . ")";
						print '</td>';

						print '<td class="tdoverflowmax200">';
						print $propal->LibStatut($obj->fk_statut, 5);
						// if ($obj->fk_statut == 1) {
						// 	print("");
						// }
						// elseif ($obj->fk_statut == 2) {
						// 	print("");
						// }
						// elseif ($obj->fk_statut == 3) {
						// 	print("Non signé");
						// }
						// elseif ($obj->fk_statut == 4) {
						// 	print("Facturée");
						// }
						print '</td>';

						print '</tr>';

					}
					$i++;
				}
			}
		}
	}



print '</tr></table>';

?>